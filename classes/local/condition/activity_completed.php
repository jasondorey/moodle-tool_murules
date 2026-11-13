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

namespace tool_murules\local\condition;

use completion_info;
use MoodleQuickForm;

/**
 * Activity successfully completed condition.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_completed extends base {

    public function get_type(): string {
        return 'activity_completed';
    }

    public function get_name(): string {
        return get_string('condition:activity_completed', 'tool_murules');
    }

    public function get_category(): string {
        return 'local';
    }

    public function get_description(array $config): string {
        $name = $this->resolve_activity_name($config);
        return get_string('condition:activity_completed_desc', 'tool_murules', (object)['activity' => $name]);
    }

    public function get_events(): array {
        return [
            \core\event\course_module_completion_updated::class,
        ];
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('course', 'ac_courseid', get_string('course', 'tool_murules'), [
            'multiple' => false,
            'includefrontpage' => false,
        ]);
        $mform->addRule('ac_courseid', get_string('error_nocourse', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['courseid'])) {
            $mform->setDefault('ac_courseid', [(int)$config['courseid']]);
        }

        // Prefer saved config; on redisplay after submit also read posted course so AJAX-filled
        // CM options still exist in the select (Moodle rejects values missing from options).
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            $courseid = self::normalize_courseid($_POST['ac_courseid'] ?? 0);
        }
        $options = [0 => get_string('choosedots')];
        if ($courseid) {
            $options += \tool_murules\local\course_modules::options_map($courseid, 'activity');
        }
        $postedcm = (int)($config['cmid'] ?? ($_POST['ac_cmid'] ?? 0));
        if ($postedcm && !isset($options[$postedcm])) {
            $options[$postedcm] = get_string('activity', 'tool_murules') . ' (#' . $postedcm . ')';
        }
        $mform->addElement('select', 'ac_cmid', get_string('activity', 'tool_murules'), $options);
        $mform->addRule('ac_cmid', get_string('error_noactivity', 'tool_murules'), 'required', null, 'client');
        if ($postedcm) {
            $mform->setDefault('ac_cmid', $postedcm);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $courseid = self::normalize_courseid($data['ac_courseid'] ?? 0);
        $cmid = (int)($data['ac_cmid'] ?? 0);
        if (!$courseid) {
            $errors['ac_courseid'] = get_string('error_nocourse', 'tool_murules');
        }
        if (!$cmid) {
            $errors['ac_cmid'] = get_string('error_noactivity', 'tool_murules');
        } else if ($courseid) {
            $cm = get_coursemodule_from_id('', $cmid, $courseid, false);
            if (!$cm) {
                $errors['ac_cmid'] = get_string('error_noactivity', 'tool_murules');
            }
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'courseid' => self::normalize_courseid($data['ac_courseid'] ?? 0),
            'cmid' => (int)($data['ac_cmid'] ?? 0),
            'success_states' => ['complete', 'complete_pass'],
        ];
    }

    /**
     * @param mixed $value
     * @return int
     */
    protected static function normalize_courseid($value): int {
        if (is_array($value)) {
            $value = reset($value);
        }
        return (int)$value;
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $courseid = (int)($config['courseid'] ?? 0);
        $cmid = (int)($config['cmid'] ?? 0);
        $cm = get_coursemodule_from_id('', $cmid, $courseid, false);
        if (!$cm) {
            return false;
        }
        $course = get_course($courseid);
        $completion = new completion_info($course);
        if (!$completion->is_enabled($cm)) {
            return false;
        }
        $data = $completion->get_data($cm, false, $userid);
        $state = (int)($data->completionstate ?? COMPLETION_INCOMPLETE);
        $allowed = $this->success_state_values($config);
        return in_array($state, $allowed, true);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $courseid = (int)($config['courseid'] ?? 0);
        $cmid = (int)($config['cmid'] ?? 0);
        if (!$courseid || !$cmid) {
            return [];
        }
        $allowed = $this->success_state_values($config);
        if (!$allowed) {
            return [];
        }
        list($insql, $inparams) = $DB->get_in_or_equal($allowed, SQL_PARAMS_NAMED, 'st');
        $params = array_merge($inparams, [
            'cmid' => $cmid,
            'fromuserid' => $fromuserid,
        ]);
        $sql = "SELECT cmc.userid
                  FROM {course_modules_completion} cmc
                  JOIN {user} u ON u.id = cmc.userid AND u.deleted = 0
                 WHERE cmc.coursemoduleid = :cmid
                   AND cmc.completionstate $insql
                   AND cmc.userid > :fromuserid
              ORDER BY cmc.userid ASC";
        $records = $DB->get_records_sql($sql, $params, 0, $limit);
        return array_map(static fn($rec) => (int)$rec->userid, $records);
    }

    public function is_broken(array $config): bool {
        $courseid = (int)($config['courseid'] ?? 0);
        $cmid = (int)($config['cmid'] ?? 0);
        if (!$courseid || !$cmid) {
            return true;
        }
        return !get_coursemodule_from_id('', $cmid, $courseid, false);
    }

    /**
     * @param array $config
     * @return int[]
     */
    protected function success_state_values(array $config): array {
        $map = [
            'complete' => COMPLETION_COMPLETE,
            'complete_pass' => COMPLETION_COMPLETE_PASS,
        ];
        $states = $config['success_states'] ?? ['complete', 'complete_pass'];
        $out = [];
        foreach ($states as $key) {
            if (isset($map[$key])) {
                $out[] = $map[$key];
            }
        }
        return $out;
    }

    /**
     * @param array $config
     * @return string
     */
    protected function resolve_activity_name(array $config): string {
        $cmid = (int)($config['cmid'] ?? 0);
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$cmid) {
            return '?';
        }
        try {
            $modinfo = get_fast_modinfo($courseid);
            if (isset($modinfo->cms[$cmid])) {
                return $modinfo->cms[$cmid]->get_formatted_name();
            }
        } catch (\Throwable $e) {
            // Fall through.
        }
        $cm = get_coursemodule_from_id('', $cmid, $courseid ?: 0, false);
        return $cm ? format_string($cm->name) : '#'.$cmid;
    }
}
