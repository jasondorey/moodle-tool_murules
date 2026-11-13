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

/**
 * External functions for tool_murules.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_murules_get_course_cms' => [
        'classname' => \tool_murules\external\get_course_cms::class,
        'description' => 'Return course modules for a course (quiz or completion activities).',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/murules:manage',
    ],
    'tool_murules_get_wizard_bootstrap' => [
        'classname' => \tool_murules\external\get_wizard_bootstrap::class,
        'description' => 'Bootstrap payload for the React create/edit wizard.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/murules:manage',
    ],
    'tool_murules_get_editor_schema' => [
        'classname' => \tool_murules\external\get_editor_schema::class,
        'description' => 'Field schema for a condition or action editor.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/murules:manage',
    ],
    'tool_murules_validate_editor_config' => [
        'classname' => \tool_murules\external\validate_editor_config::class,
        'description' => 'Validate and normalize editor config JSON.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'tool/murules:manage',
    ],
    'tool_murules_estimate_matches' => [
        'classname' => \tool_murules\external\estimate_matches::class,
        'description' => 'Estimate users matching AND conditions.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/murules:manage',
    ],
    'tool_murules_search_courses' => [
        'classname' => \tool_murules\external\search_courses::class,
        'description' => 'Search courses for React wizard course fields.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'tool/murules:manage',
    ],
    'tool_murules_save_rule' => [
        'classname' => \tool_murules\external\save_rule::class,
        'description' => 'Save a rule from the React wizard.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'tool/murules:manage',
    ],
];
