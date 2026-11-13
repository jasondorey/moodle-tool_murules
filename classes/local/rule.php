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
     * Condition rows for a rule (AND), ordered.
     *
     * @param int $ruleid
     * @return stdClass[]
     */
    public static function get_conditions(int $ruleid): array {
        global $DB;
        return array_values($DB->get_records(
            'tool_murules_condition',
            ['ruleid' => $ruleid],
            'sortorder ASC, id ASC'
        ));
    }

    /**
     * Decoded condition list for a rule.
     *
     * Falls back to legacy rule.conditiontype/config when child rows are missing.
     *
     * @param stdClass $rulerec
     * @return array<int,array{conditiontype:string,conditionconfig:array,id?:int}>
     */
    public static function get_conditions_decoded(stdClass $rulerec): array {
        $rows = self::get_conditions((int)$rulerec->id);
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int)$row->id,
                'conditiontype' => (string)$row->conditiontype,
                'conditionconfig' => condition\base::decode_config($row->conditionconfig ?? null),
            ];
        }
        if ($out) {
            return $out;
        }
        // Legacy single-column fallback.
        if (!empty($rulerec->conditiontype)) {
            return [[
                'conditiontype' => (string)$rulerec->conditiontype,
                'conditionconfig' => self::condition_config($rulerec),
            ]];
        }
        return [];
    }

    /**
     * Human WHEN summary (conditions joined with AND).
     *
     * @param stdClass $rulerec
     * @return string
     */
    public static function format_when(stdClass $rulerec): string {
        $parts = [];
        foreach (self::get_conditions_decoded($rulerec) as $cond) {
            $condition = registry::get_condition($cond['conditiontype']);
            if ($condition) {
                $parts[] = $condition->get_description($cond['conditionconfig']);
            } else {
                $parts[] = $cond['conditiontype'];
            }
        }
        return $parts ? implode(' AND ', $parts) : '';
    }

    /**
     * True if any condition is missing or broken.
     *
     * @param stdClass $rulerec
     * @return bool
     */
    public static function is_broken(stdClass $rulerec): bool {
        $conds = self::get_conditions_decoded($rulerec);
        if (!$conds) {
            return true;
        }
        foreach ($conds as $cond) {
            $condition = registry::get_condition($cond['conditiontype']);
            if (!$condition || $condition->is_broken($cond['conditionconfig'])) {
                return true;
            }
        }
        return false;
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
     * Create or update a rule with AND conditions + a single action (Wave A).
     *
     * @param stdClass $data name, enabled, actionlimit, includesuspended,
     *                       conditions[] (conditiontype + conditionconfig) OR legacy conditiontype/config,
     *                       actiontype, actionconfig, timestart?, timeend?, id?
     * @return int rule id
     */
    public static function save(stdClass $data): int {
        global $DB, $USER;

        $now = time();
        $conditions = self::normalize_conditions_input($data);
        if (!$conditions) {
            throw new \moodle_exception('error_condition', 'tool_murules');
        }
        $primary = $conditions[0];
        $conditionconfig = json_encode($primary['conditionconfig']);
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
            // Keep primary columns in sync for indexes / legacy readers.
            $record->conditiontype = (string)$primary['conditiontype'];
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
                $DB->insert_record('tool_murules_action', (object)[
                    'ruleid' => $ruleid,
                    'sortorder' => 0,
                    'actiontype' => (string)$data->actiontype,
                    'actionconfig' => $actionconfig,
                ]);
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
                'conditiontype' => (string)$primary['conditiontype'],
                'conditionconfig' => $conditionconfig,
                'timecreated' => $now,
                'timemodified' => $now,
                'usermodified' => (int)$USER->id,
            ];
            $ruleid = (int)$DB->insert_record('tool_murules_rule', $record);
            $DB->insert_record('tool_murules_action', (object)[
                'ruleid' => $ruleid,
                'sortorder' => 0,
                'actiontype' => (string)$data->actiontype,
                'actionconfig' => $actionconfig,
            ]);
        }

        self::replace_conditions($ruleid, $conditions);

        $trans->allow_commit();

        $enabled = !empty($data->enabled);
        if ($enabled && (!$wasenabled || empty($data->id))) {
            engine::queue_backfill($ruleid);
        }

        return $ruleid;
    }

    /**
     * Replace all child condition rows for a rule.
     *
     * @param int $ruleid
     * @param array $conditions
     */
    public static function replace_conditions(int $ruleid, array $conditions): void {
        global $DB;
        $DB->delete_records('tool_murules_condition', ['ruleid' => $ruleid]);
        $sort = 0;
        foreach ($conditions as $cond) {
            $config = $cond['conditionconfig'] ?? [];
            $DB->insert_record('tool_murules_condition', (object)[
                'ruleid' => $ruleid,
                'sortorder' => $sort++,
                'conditiontype' => (string)$cond['conditiontype'],
                'conditionconfig' => is_array($config) ? json_encode($config) : (string)$config,
            ]);
        }
    }

    /**
     * @param stdClass $data
     * @return array<int,array{conditiontype:string,conditionconfig:array}>
     */
    protected static function normalize_conditions_input(stdClass $data): array {
        $out = [];
        if (!empty($data->conditions) && is_array($data->conditions)) {
            foreach ($data->conditions as $cond) {
                if (is_object($cond)) {
                    $cond = (array)$cond;
                }
                if (empty($cond['conditiontype'])) {
                    continue;
                }
                $config = $cond['conditionconfig'] ?? [];
                if (is_string($config)) {
                    $config = condition\base::decode_config($config);
                }
                $out[] = [
                    'conditiontype' => (string)$cond['conditiontype'],
                    'conditionconfig' => is_array($config) ? $config : [],
                ];
            }
        }
        if ($out) {
            return $out;
        }
        if (!empty($data->conditiontype)) {
            $config = $data->conditionconfig ?? [];
            if (is_string($config)) {
                $config = condition\base::decode_config($config);
            }
            return [[
                'conditiontype' => (string)$data->conditiontype,
                'conditionconfig' => is_array($config) ? $config : [],
            ]];
        }
        return [];
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
        $DB->delete_records('tool_murules_condition', ['ruleid' => $ruleid]);
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
     * Decoded primary (first) condition config — legacy helper.
     *
     * @param stdClass $rulerec
     * @return array
     */
    public static function condition_config(stdClass $rulerec): array {
        $conds = self::get_conditions_decoded($rulerec);
        if ($conds) {
            return $conds[0]['conditionconfig'];
        }
        return condition\base::decode_config($rulerec->conditionconfig ?? null);
    }
}
