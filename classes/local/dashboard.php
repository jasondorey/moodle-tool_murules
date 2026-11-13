<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_murules\local;

/**
 * Local dashboard metrics from murules tables (no cloud / destinations).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class dashboard {

    /**
     * Rule counts including broken (config / missing dependency).
     *
     * @return array{all:int,enabled:int,disabled:int,broken:int}
     */
    public static function rule_counts(): array {
        $counts = ['all' => 0, 'enabled' => 0, 'disabled' => 0, 'broken' => 0];
        foreach (rule::get_all() as $rec) {
            $broken = rule::is_broken($rec);
            $enabled = (int)$rec->enabled === 1;
            $counts['all']++;
            if ($enabled) {
                $counts['enabled']++;
            } else {
                $counts['disabled']++;
            }
            if ($broken) {
                $counts['broken']++;
            }
        }
        return $counts;
    }

    /**
     * Match totals by status (all time).
     *
     * @return array{applied:int,skipped:int,failed:int,all:int}
     */
    public static function match_totals(): array {
        global $DB;
        $totals = ['applied' => 0, 'skipped' => 0, 'failed' => 0, 'all' => 0];
        $rows = $DB->get_records_sql(
            "SELECT status, COUNT(1) AS cnt
               FROM {tool_murules_match}
           GROUP BY status"
        );
        foreach ($rows as $row) {
            $status = (string)$row->status;
            $cnt = (int)$row->cnt;
            $totals['all'] += $cnt;
            if (isset($totals[$status])) {
                $totals[$status] = $cnt;
            }
        }
        return $totals;
    }

    /**
     * Matches since a unix timestamp, by status.
     *
     * @param int $since
     * @return array{applied:int,skipped:int,failed:int,all:int}
     */
    public static function match_totals_since(int $since): array {
        global $DB;
        $totals = ['applied' => 0, 'skipped' => 0, 'failed' => 0, 'all' => 0];
        $rows = $DB->get_records_sql(
            "SELECT status, COUNT(1) AS cnt
               FROM {tool_murules_match}
              WHERE timecreated >= :since
           GROUP BY status",
            ['since' => $since]
        );
        foreach ($rows as $row) {
            $status = (string)$row->status;
            $cnt = (int)$row->cnt;
            $totals['all'] += $cnt;
            if (isset($totals[$status])) {
                $totals[$status] = $cnt;
            }
        }
        return $totals;
    }

    /**
     * Last N match rows with rule name for recent activity.
     *
     * @param int $limit
     * @return \stdClass[]
     */
    public static function recent_matches(int $limit = 20): array {
        global $DB;
        $limit = max(1, min(100, $limit));
        $sql = "SELECT m.id, m.ruleid, m.userid, m.status, m.reason, m.timecreated,
                       r.name AS rulename
                  FROM {tool_murules_match} m
                  JOIN {tool_murules_rule} r ON r.id = m.ruleid
              ORDER BY m.timecreated DESC, m.id DESC";
        return array_values($DB->get_records_sql($sql, [], 0, $limit));
    }

    /**
     * Daily applied/skipped/failed counts for the last N local midnights (inclusive of today).
     *
     * @param int $days
     * @return array{labels:string[],applied:int[],skipped:int[],failed:int[]}
     */
    public static function daily_series(int $days = 7): array {
        global $DB;
        $days = max(1, min(31, $days));
        $labels = [];
        $applied = [];
        $skipped = [];
        $failed = [];
        $buckets = [];

        $today = usergetmidnight(time());
        for ($i = $days - 1; $i >= 0; $i--) {
            $daystart = $today - ($i * DAYSECS);
            $key = (string)$daystart;
            $buckets[$key] = ['applied' => 0, 'skipped' => 0, 'failed' => 0];
            $labels[] = userdate($daystart, get_string('strftimedate', 'langconfig'));
            $applied[] = 0;
            $skipped[] = 0;
            $failed[] = 0;
        }

        $since = $today - (($days - 1) * DAYSECS);
        $rows = $DB->get_recordset_sql(
            "SELECT timecreated, status
               FROM {tool_murules_match}
              WHERE timecreated >= :since",
            ['since' => $since]
        );
        foreach ($rows as $row) {
            $daystart = usergetmidnight((int)$row->timecreated);
            $key = (string)$daystart;
            if (!isset($buckets[$key])) {
                continue;
            }
            $status = (string)$row->status;
            if (isset($buckets[$key][$status])) {
                $buckets[$key][$status]++;
            }
        }
        $rows->close();

        $idx = 0;
        foreach ($buckets as $counts) {
            $applied[$idx] = $counts['applied'];
            $skipped[$idx] = $counts['skipped'];
            $failed[$idx] = $counts['failed'];
            $idx++;
        }

        return [
            'labels' => $labels,
            'applied' => $applied,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }
}
