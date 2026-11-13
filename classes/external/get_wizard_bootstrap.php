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
use core_external\external_value;
use tool_murules\local\wizard\state;

/**
 * AJAX: bootstrap React create/edit wizard.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_wizard_bootstrap extends external_api {
    use wizard_helper;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'ruleid' => new external_value(PARAM_INT, 'Existing rule id or 0', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(int $ruleid = 0): array {
        ['ruleid' => $ruleid] = self::validate_parameters(self::execute_parameters(), ['ruleid' => $ruleid]);
        self::require_manage();
        if ($ruleid > 0) {
            // Throws if missing.
            \tool_murules\local\rule::get($ruleid);
        }
        return [
            'payload' => json_encode(state::bootstrap($ruleid), JSON_UNESCAPED_UNICODE),
        ];
    }

    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'payload' => new external_value(PARAM_RAW, 'JSON bootstrap payload'),
        ]);
    }
}
