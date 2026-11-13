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
 * Event observers for tool_murules.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Wave A — local extras.
    [
        'eventname' => '\mod_quiz\event\attempt_graded',
        'callback' => '\tool_murules\local\observer::attempt_graded',
    ],
    [
        'eventname' => '\core\event\user_graded',
        'callback' => '\tool_murules\local\observer::user_graded',
    ],
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\tool_murules\local\observer::course_module_completion_updated',
    ],
    // Wave B — Workplace General.
    [
        'eventname' => '\core\event\cohort_member_added',
        'callback' => '\tool_murules\local\observer::cohort_member_added',
    ],
    [
        'eventname' => '\core\event\course_completed',
        'callback' => '\tool_murules\local\observer::course_completed',
    ],
    [
        'eventname' => '\core\event\course_viewed',
        'callback' => '\tool_murules\local\observer::course_viewed',
    ],
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\tool_murules\local\observer::user_enrolment_created',
    ],
    [
        'eventname' => '\core\event\user_enrolment_updated',
        'callback' => '\tool_murules\local\observer::user_enrolment_updated',
    ],
    [
        'eventname' => '\core\event\user_loggedin',
        'callback' => '\tool_murules\local\observer::user_loggedin',
    ],
    [
        'eventname' => '\core\event\user_created',
        'callback' => '\tool_murules\local\observer::user_created',
    ],
    [
        'eventname' => '\core\event\user_updated',
        'callback' => '\tool_murules\local\observer::user_updated',
    ],
    [
        'eventname' => '\core\event\competency_evidence_created',
        'callback' => '\tool_murules\local\observer::competency_evidence_created',
    ],
    [
        'eventname' => '\core\event\competency_user_competency_rated',
        'callback' => '\tool_murules\local\observer::competency_user_competency_rated',
    ],
    [
        'eventname' => '\core\event\competency_user_competency_rated_in_course',
        'callback' => '\tool_murules\local\observer::competency_user_competency_rated_in_course',
    ],
    [
        'eventname' => '\core\event\competency_user_competency_rated_in_plan',
        'callback' => '\tool_murules\local\observer::competency_user_competency_rated_in_plan',
    ],
];
