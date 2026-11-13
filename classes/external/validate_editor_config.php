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
use tool_murules\local\wizard\config_bridge;
use tool_murules\local\wizard\state;

/**
 * AJAX: validate + normalize condition/action config from the React editor.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class validate_editor_config extends external_api {
    use wizard_helper;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'kind' => new external_value(PARAM_ALPHA, 'condition or action'),
            'type' => new external_value(PARAM_ALPHANUMEXT, 'Registry type'),
            'configjson' => new external_value(PARAM_RAW, 'Canonical config JSON'),
        ]);
    }

    public static function execute(string $kind, string $type, string $configjson): array {
        [
            'kind' => $kind,
            'type' => $type,
            'configjson' => $configjson,
        ] = self::validate_parameters(self::execute_parameters(), [
            'kind' => $kind,
            'type' => $type,
            'configjson' => $configjson,
        ]);
        self::require_manage();
        if (!in_array($kind, ['condition', 'action'], true)) {
            throw new \core\exception\invalid_parameter_exception('Invalid kind');
        }
        $config = self::decode_json_object($configjson);
        $result = config_bridge::validate_and_extract($kind, $type, $config);
        $description = '';
        if ($result['ok']) {
            $description = $kind === 'condition'
                ? state::describe_condition($type, $result['config'])
                : state::describe_action($type, $result['config']);
        }
        return [
            'ok' => $result['ok'],
            'errorsjson' => json_encode($result['errors'], JSON_UNESCAPED_UNICODE),
            'configjson' => json_encode($result['config'], JSON_UNESCAPED_UNICODE),
            'description' => $description,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'ok' => new external_value(PARAM_BOOL, 'Valid'),
            'errorsjson' => new external_value(PARAM_RAW, 'Field errors JSON'),
            'configjson' => new external_value(PARAM_RAW, 'Normalized config JSON'),
            'description' => new external_value(PARAM_TEXT, 'Human summary'),
        ]);
    }
}
