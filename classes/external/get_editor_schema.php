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
use tool_murules\local\wizard\field_schemas;

/**
 * AJAX: editor field schema for a condition or action type.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_editor_schema extends external_api {
    use wizard_helper;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'kind' => new external_value(PARAM_ALPHA, 'condition or action'),
            'type' => new external_value(PARAM_ALPHANUMEXT, 'Registry type key'),
        ]);
    }

    public static function execute(string $kind, string $type): array {
        [
            'kind' => $kind,
            'type' => $type,
        ] = self::validate_parameters(self::execute_parameters(), [
            'kind' => $kind,
            'type' => $type,
        ]);
        self::require_manage();
        if (!in_array($kind, ['condition', 'action'], true)) {
            throw new \core\exception\invalid_parameter_exception('Invalid kind');
        }
        $schema = field_schemas::for_type($kind, $type);
        return [
            'payload' => json_encode($schema, JSON_UNESCAPED_UNICODE),
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'payload' => new external_value(PARAM_RAW, 'JSON schema'),
        ]);
    }
}
