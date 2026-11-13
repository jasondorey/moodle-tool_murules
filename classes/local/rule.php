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

use stdClass;

/**
 * Rule persistence helpers.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class rule {

    /**
     * @param int $id
     * @return stdClass
     */
    public static function get(int $id): stdClass {
        global $DB;
        return $DB->get_record('tool_murules_rule', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * @return stdClass[]
     */
    public static function get_all(): array {
        global $DB;
        return $DB->get_records('tool_murules_rule', null, 'name ASC');
    }

    /**
     * @param bool $enabledonly
     * @return stdClass[]
     */
    public static function get_enabled(bool $enabledonly = true): array {
        global $DB;
        if ($enabledonly) {
            return $DB->get_records('tool_murules_rule', ['enabled' => 1], 'id ASC');
        }
        return self::get_all();
    }

    /**
     * @param int $ruleid
     * @return stdClass[]
     */
    public static function get_actions(int $ruleid): array {
        global $DB;
        return $DB->get_records('tool_murules_action', ['ruleid' => $ruleid], 'sortorder ASC, id ASC');
    }

    /**
     * Create or update a rule with a single action (Wave A).
     *
     * @param stdClass $data name, enabled, actionlimit, includesuspended, conditiontype, conditionconfig (array|string),
     *                       actiontype, actionconfig (array|string), timestart?, timeend?, id?
     * @return int rule id
     */
    public static function save(stdClass $data): int {
        global $DB, $USER;

        $now = time();
        $conditionconfig = is_array($data->conditionconfig ?? null)
            ? json_encode($data->conditionconfig)
            : (string)($data->conditionconfig ?? '{}');
        $actionconfig = is_array($data->actionconfig ?? null)
            ? json_encode($data->actionconfig)
            : (string)($data->actionconfig ?? '{}');

        $wasenabled = false;
        $trans = $DB->start_delegated_transaction();

        if (!empty($data->id)) {
            $record = self::get((int)$data->id);
            $wasenabled = (int)$record->enabled === 1;
            $record->name = (string)$data->name;
            $record->enabled = !empty($data->enabled) ? 1 : 0;
            $record->timestart = !empty($data->timestart) ? (int)$data->timestart : null;
            $record->timeend = !empty($data->timeend) ? (int)$data->timeend : null;
            $record->actionlimit = (string)($data->actionlimit ?? 'once');
            $record->actionlimitcount = $data->actionlimitcount ?? null;
            $record->actionperiod = $data->actionperiod ?? null;
            $record->includesuspended = !empty($data->includesuspended) ? 1 : 0;
            $record->conditiontype = (string)$data->conditiontype;
            $record->conditionconfig = $conditionconfig;
            $record->timemodified = $now;
            $record->usermodified = (int)$USER->id;
            $DB->update_record('tool_murules_rule', $record);
            $ruleid = (int)$record->id;

            $actions = self::get_actions($ruleid);
            $action = reset($actions);
            if ($action) {
                $action->actiontype = (string)$data->actiontype;
                $action->actionconfig = $actionconfig;
                $action->sortorder = 0;
                $DB->update_record('tool_murules_action', $action);
            } else {
                $action = (object)[
                    'ruleid' => $ruleid,
                    'sortorder' => 0,
                    'actiontype' => (string)$data->actiontype,
                    'actionconfig' => $actionconfig,
                ];
                $DB->insert_record('tool_murules_action', $action);
            }
        } else {
            $record = (object)[
                'name' => (string)$data->name,
                'enabled' => !empty($data->enabled) ? 1 : 0,
                'timestart' => !empty($data->timestart) ? (int)$data->timestart : null,
                'timeend' => !empty($data->timeend) ? (int)$data->timeend : null,
                'actionlimit' => (string)($data->actionlimit ?? 'once'),
                'actionlimitcount' => $data->actionlimitcount ?? null,
                'actionperiod' => $data->actionperiod ?? null,
                'includesuspended' => !empty($data->includesuspended) ? 1 : 0,
                'conditiontype' => (string)$data->conditiontype,
                'conditionconfig' => $conditionconfig,
                'timecreated' => $now,
                'timemodified' => $now,
                'usermodified' => (int)$USER->id,
            ];
            $ruleid = (int)$DB->insert_record('tool_murules_rule', $record);
            $action = (object)[
                'ruleid' => $ruleid,
                'sortorder' => 0,
                'actiontype' => (string)$data->actiontype,
                'actionconfig' => $actionconfig,
            ];
            $DB->insert_record('tool_murules_action', $action);
        }

        $trans->allow_commit();

        $enabled = !empty($data->enabled);
        if ($enabled && (!$wasenabled || empty($data->id))) {
            engine::queue_backfill($ruleid);
        }

        return $ruleid;
    }

    /**
     * @param int $ruleid
     * @param bool $enabled
     */
    public static function set_enabled(int $ruleid, bool $enabled): void {
        global $DB, $USER;
        $record = self::get($ruleid);
        $was = (int)$record->enabled === 1;
        $record->enabled = $enabled ? 1 : 0;
        $record->timemodified = time();
        $record->usermodified = (int)$USER->id;
        $DB->update_record('tool_murules_rule', $record);
        if ($enabled && !$was) {
            engine::queue_backfill($ruleid);
        }
    }

    /**
     * @param int $ruleid
     */
    public static function delete(int $ruleid): void {
        global $DB;
        $DB->delete_records('tool_murules_match', ['ruleid' => $ruleid]);
        $DB->delete_records('tool_murules_action', ['ruleid' => $ruleid]);
        $DB->delete_records('tool_murules_rule', ['id' => $ruleid]);
    }

    /**
     * Match counts by status.
     *
     * @param int $ruleid
     * @return array{applied:int,skipped:int,failed:int}
     */
    public static function get_match_stats(int $ruleid): array {
        global $DB;
        $stats = ['applied' => 0, 'skipped' => 0, 'failed' => 0];
        $sql = "SELECT status, COUNT(1) AS cnt
                  FROM {tool_murules_match}
                 WHERE ruleid = :ruleid
              GROUP BY status";
        $rows = $DB->get_records_sql($sql, ['ruleid' => $ruleid]);
        foreach ($rows as $row) {
            if (isset($stats[$row->status])) {
                $stats[$row->status] = (int)$row->cnt;
            }
        }
        return $stats;
    }

    /**
     * Decoded condition config.
     *
     * @param stdClass $rulerec
     * @return array
     */
    public static function condition_config(stdClass $rulerec): array {
        return condition\base::decode_config($rulerec->conditionconfig ?? null);
    }
}
