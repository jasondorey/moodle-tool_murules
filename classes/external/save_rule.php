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

namespace tool_murules\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use tool_murules\local\rule;
use tool_murules\local\wizard\config_bridge;

/**
 * AJAX: save rule from React wizard (multi-condition AND + multi-action).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class save_rule extends external_api {
    use wizard_helper;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'payloadjson' => new external_value(PARAM_RAW, 'Rule draft JSON'),
        ]);
    }

    public static function execute(string $payloadjson): array {
        ['payloadjson' => $payloadjson] = self::validate_parameters(self::execute_parameters(), [
            'payloadjson' => $payloadjson,
        ]);
        self::require_manage();
        $payload = self::decode_json_object($payloadjson);

        $name = trim((string)($payload['name'] ?? ''));
        if ($name === '') {
            return [
                'ok' => false,
                'ruleid' => 0,
                'message' => get_string('error_rulename', 'tool_murules'),
                'errorsjson' => json_encode(['name' => get_string('error_rulename', 'tool_murules')]),
            ];
        }

        $conditions = [];
        foreach (array_values($payload['conditions'] ?? []) as $cond) {
            if (!is_array($cond)) {
                continue;
            }
            $type = (string)($cond['conditiontype'] ?? '');
            $config = is_array($cond['conditionconfig'] ?? null) ? $cond['conditionconfig'] : [];
            $check = config_bridge::validate_and_extract('condition', $type, $config);
            if (!$check['ok']) {
                return [
                    'ok' => false,
                    'ruleid' => 0,
                    'message' => get_string('error_condition', 'tool_murules'),
                    'errorsjson' => json_encode($check['errors']),
                ];
            }
            $conditions[] = [
                'conditiontype' => $type,
                'conditionconfig' => $check['config'],
            ];
        }
        if (!$conditions) {
            return [
                'ok' => false,
                'ruleid' => 0,
                'message' => get_string('error_condition', 'tool_murules'),
                'errorsjson' => json_encode(['conditions' => get_string('error_condition', 'tool_murules')]),
            ];
        }

        $actions = [];
        foreach (array_values($payload['actions'] ?? []) as $act) {
            if (!is_array($act)) {
                continue;
            }
            $type = (string)($act['actiontype'] ?? '');
            $config = is_array($act['actionconfig'] ?? null) ? $act['actionconfig'] : [];
            $check = config_bridge::validate_and_extract('action', $type, $config);
            if (!$check['ok']) {
                return [
                    'ok' => false,
                    'ruleid' => 0,
                    'message' => get_string('error_action', 'tool_murules'),
                    'errorsjson' => json_encode($check['errors']),
                ];
            }
            $row = [
                'actiontype' => $type,
                'actionconfig' => $check['config'],
            ];
            if (!empty($act['id'])) {
                $row['id'] = (int)$act['id'];
            }
            $actions[] = $row;
        }
        if (!$actions) {
            return [
                'ok' => false,
                'ruleid' => 0,
                'message' => get_string('error_action', 'tool_murules'),
                'errorsjson' => json_encode(['actions' => get_string('error_action', 'tool_murules')]),
            ];
        }

        $data = (object)[
            'name' => $name,
            'enabled' => !empty($payload['enabled']) ? 1 : 0,
            'actionlimit' => (string)($payload['actionlimit'] ?? 'once'),
            'includesuspended' => !empty($payload['includesuspended']) ? 1 : 0,
            'conditions' => $conditions,
            'actions' => $actions,
        ];
        if (!empty($payload['id'])) {
            $data->id = (int)$payload['id'];
        }

        try {
            $ruleid = rule::save($data);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'ruleid' => 0,
                'message' => $e->getMessage(),
                'errorsjson' => json_encode(['_save' => $e->getMessage()]),
            ];
        }

        return [
            'ok' => true,
            'ruleid' => $ruleid,
            'message' => get_string('rulesaved', 'tool_murules', format_string($name)),
            'errorsjson' => '{}',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Saved'),
            'ruleid' => new external_value(PARAM_INT, 'Rule id'),
            'message' => new external_value(PARAM_TEXT, 'Status message'),
            'errorsjson' => new external_value(PARAM_RAW, 'Errors JSON'),
        ]);
    }
}
