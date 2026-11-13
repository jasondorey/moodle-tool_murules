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

/**
 * Event observers for tool_murules conditions.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observer {

    /**
     * @param \mod_quiz\event\attempt_graded $event
     */
    public static function attempt_graded(\mod_quiz\event\attempt_graded $event): void {
        self::queue_for_user(
            (int)$event->relateduserid,
            \mod_quiz\event\attempt_graded::class,
            (int)$event->contextinstanceid
        );
    }

    /**
     * @param \core\event\user_graded $event
     */
    public static function user_graded(\core\event\user_graded $event): void {
        $userid = (int)($event->relateduserid ?: $event->userid);
        $cmid = 0;
        try {
            $grade = $event->get_grade();
            if ($grade && !empty($grade->itemid)) {
                global $CFG;
                require_once($CFG->libdir . '/gradelib.php');
                $gi = \grade_item::fetch(['id' => $grade->itemid]);
                if ($gi && $gi->itemtype === 'mod' && $gi->itemmodule === 'quiz') {
                    $cm = get_coursemodule_from_instance('quiz', $gi->iteminstance, $gi->courseid, false);
                    $cmid = $cm ? (int)$cm->id : 0;
                } else {
                    return;
                }
            }
        } catch (\Throwable $e) {
            // Still queue broadly for quiz_score rules.
        }
        self::queue_for_user($userid, \core\event\user_graded::class, $cmid);
    }

    /**
     * @param \core\event\course_module_completion_updated $event
     */
    public static function course_module_completion_updated(
        \core\event\course_module_completion_updated $event
    ): void {
        self::queue_for_user(
            (int)$event->relateduserid,
            \core\event\course_module_completion_updated::class,
            (int)$event->contextinstanceid
        );
    }

    /**
     * @param \core\event\cohort_member_added $event
     */
    public static function cohort_member_added(\core\event\cohort_member_added $event): void {
        self::queue_for_user((int)$event->relateduserid, \core\event\cohort_member_added::class, 0, [
            'cohortid' => (int)$event->objectid,
        ]);
    }

    /**
     * @param \core\event\course_completed $event
     */
    public static function course_completed(\core\event\course_completed $event): void {
        $userid = (int)($event->relateduserid ?: $event->userid);
        self::queue_for_user($userid, \core\event\course_completed::class, 0, [
            'courseid' => (int)$event->courseid,
        ]);
    }

    /**
     * @param \core\event\course_viewed $event
     */
    public static function course_viewed(\core\event\course_viewed $event): void {
        $userid = (int)$event->userid;
        self::queue_for_user($userid, \core\event\course_viewed::class, 0, [
            'courseid' => (int)$event->courseid,
        ]);
    }

    /**
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolment_created(\core\event\user_enrolment_created $event): void {
        self::queue_for_user((int)$event->relateduserid, \core\event\user_enrolment_created::class, 0, [
            'courseid' => (int)$event->courseid,
        ]);
    }

    /**
     * @param \core\event\user_enrolment_updated $event
     */
    public static function user_enrolment_updated(\core\event\user_enrolment_updated $event): void {
        self::queue_for_user((int)$event->relateduserid, \core\event\user_enrolment_updated::class, 0, [
            'courseid' => (int)$event->courseid,
        ]);
    }

    /**
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        self::queue_for_user((int)$event->userid, \core\event\user_loggedin::class);
    }

    /**
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event): void {
        self::queue_for_user((int)$event->objectid, \core\event\user_created::class);
    }

    /**
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event): void {
        self::queue_for_user((int)$event->objectid, \core\event\user_updated::class);
    }

    /**
     * @param \core\event\competency_evidence_created $event
     */
    public static function competency_evidence_created(\core\event\competency_evidence_created $event): void {
        self::queue_for_user((int)$event->relateduserid, \core\event\competency_evidence_created::class);
    }

    /**
     * @param \core\event\competency_user_competency_rated $event
     */
    public static function competency_user_competency_rated(
        \core\event\competency_user_competency_rated $event
    ): void {
        self::queue_for_user((int)$event->relateduserid, \core\event\competency_user_competency_rated::class);
    }

    /**
     * @param \core\event\competency_user_competency_rated_in_course $event
     */
    public static function competency_user_competency_rated_in_course(
        \core\event\competency_user_competency_rated_in_course $event
    ): void {
        self::queue_for_user(
            (int)$event->relateduserid,
            \core\event\competency_user_competency_rated_in_course::class
        );
    }

    /**
     * @param \core\event\competency_user_competency_rated_in_plan $event
     */
    public static function competency_user_competency_rated_in_plan(
        \core\event\competency_user_competency_rated_in_plan $event
    ): void {
        self::queue_for_user(
            (int)$event->relateduserid,
            \core\event\competency_user_competency_rated_in_plan::class
        );
    }

    /**
     * @param int $userid
     * @param string $eventclass
     * @param int $cmid
     * @param array $filters optional config filters e.g. courseid, cohortid
     */
    protected static function queue_for_user(
        int $userid,
        string $eventclass,
        int $cmid = 0,
        array $filters = []
    ): void {
        if (!$userid || engine::is_killed() || !engine::observers_enabled()) {
            return;
        }
        $ruleids = matcher::ruleids_for_event($eventclass);
        if ($cmid) {
            $ruleids = matcher::filter_by_cmid($ruleids, $cmid);
        }
        foreach ($filters as $key => $value) {
            if ($value) {
                $ruleids = matcher::filter_by_config_key($ruleids, (string)$key, (int)$value);
            }
        }
        if (!$ruleids) {
            return;
        }
        engine::queue_process_event($userid, $ruleids, $eventclass);
    }
}
