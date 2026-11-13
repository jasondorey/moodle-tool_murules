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
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use tool_murules\local\course_modules;

/**
 * AJAX: course modules for wizard dependent selects.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_course_cms extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'mode' => new external_value(PARAM_ALPHA, 'quiz or activity'),
        ]);
    }

    /**
     * @param int $courseid
     * @param string $mode
     * @return array
     */
    public static function execute(int $courseid, string $mode): array {
        [
            'courseid' => $courseid,
            'mode' => $mode,
        ] = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'mode' => $mode,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/murules:manage', $context);

        if (!in_array($mode, ['quiz', 'activity'], true)) {
            throw new \core\exception\invalid_parameter_exception('Invalid mode');
        }

        if ($courseid > 0) {
            $coursecontext = \context_course::instance($courseid, IGNORE_MISSING);
            if (!$coursecontext) {
                return ['cms' => []];
            }
        }

        return [
            'cms' => course_modules::list_for_course($courseid, $mode),
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'cms' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course module id'),
                    'name' => new external_value(PARAM_TEXT, 'Display name'),
                ])
            ),
        ]);
    }
}
