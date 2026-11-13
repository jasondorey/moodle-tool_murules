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
use tool_murules\local\engine;

/**
 * AJAX: estimate users matching AND conditions.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class estimate_matches extends external_api {
    use wizard_helper;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'conditionsjson' => new external_value(PARAM_RAW, 'JSON array of {conditiontype,conditionconfig}'),
            'limit' => new external_value(PARAM_INT, 'Cap', VALUE_DEFAULT, 500),
        ]);
    }

    public static function execute(string $conditionsjson, int $limit = 500): array {
        [
            'conditionsjson' => $conditionsjson,
            'limit' => $limit,
        ] = self::validate_parameters(self::execute_parameters(), [
            'conditionsjson' => $conditionsjson,
            'limit' => $limit,
        ]);
        self::require_manage();
        $conditions = self::decode_json_object($conditionsjson);
        if (!is_array($conditions)) {
            $conditions = [];
        }
        $conditions = array_values($conditions);
        $limit = max(1, min(2000, $limit));
        $result = engine::estimate_matching_users_and($conditions, $limit);
        $label = engine::format_matching_estimate($result);
        return [
            'count' => (int)($result['count'] ?? 0),
            'capped' => !empty($result['capped']),
            'broken' => !empty($result['broken']),
            'label' => $label,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'count' => new external_value(PARAM_INT, 'Match count'),
            'capped' => new external_value(PARAM_BOOL, 'Estimate capped'),
            'broken' => new external_value(PARAM_BOOL, 'Broken condition'),
            'label' => new external_value(PARAM_TEXT, 'Human label'),
        ]);
    }
}
