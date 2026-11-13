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

use MoodleQuickForm;

/**
 * Course completed (G4).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_completed extends base {

    public function get_type(): string {
        return 'course_completed';
    }

    public function get_name(): string {
        return get_string('condition:course_completed', 'tool_murules');
    }

    public function get_category(): string {
        return 'course';
    }

    public function get_description(array $config): string {
        $a = (object)[
            'course' => helper::course_name((int)($config['courseid'] ?? 0)),
            'completion' => get_string('completion_when_' . ($config['completion_when'] ?? 'any'), 'tool_murules'),
            'grade' => get_string('grade_op_' . ($config['grade_op'] ?? 'any'), 'tool_murules'),
        ];
        return get_string('condition:course_completed_desc', 'tool_murules', $a);
    }

    public function get_events(): array {
        return [
            \core\event\course_completed::class,
        ];
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('course', 'cc_courseid', get_string('course', 'tool_murules'), [
            'multiple' => false,
            'includefrontpage' => false,
        ]);
        $mform->addRule('cc_courseid', get_string('error_nocourse', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['courseid'])) {
            $mform->setDefault('cc_courseid', [(int)$config['courseid']]);
        }

        $mform->addElement('select', 'cc_completion_when', get_string('completion_when', 'tool_murules'), [
            'any' => get_string('completion_when_any', 'tool_murules'),
            'after' => get_string('completion_when_after', 'tool_murules'),
            'before' => get_string('completion_when_before', 'tool_murules'),
        ]);
        $mform->setDefault('cc_completion_when', $config['completion_when'] ?? 'any');

        $mform->addElement('date_time_selector', 'cc_completion_after', get_string('completion_after', 'tool_murules'), [
            'optional' => true,
        ]);
        if (!empty($config['completion_after'])) {
            $mform->setDefault('cc_completion_after', (int)$config['completion_after']);
        }
        $mform->hideIf('cc_completion_after', 'cc_completion_when', 'neq', 'after');

        $mform->addElement('date_time_selector', 'cc_completion_before', get_string('completion_before', 'tool_murules'), [
            'optional' => true,
        ]);
        if (!empty($config['completion_before'])) {
            $mform->setDefault('cc_completion_before', (int)$config['completion_before']);
        }
        $mform->hideIf('cc_completion_before', 'cc_completion_when', 'neq', 'before');

        $mform->addElement('select', 'cc_grade_op', get_string('grade_op', 'tool_murules'), [
            'any' => get_string('grade_op_any', 'tool_murules'),
            'lt' => get_string('grade_op_lt', 'tool_murules'),
            'gt' => get_string('grade_op_gt', 'tool_murules'),
            'range' => get_string('grade_op_range', 'tool_murules'),
        ]);
        $mform->setDefault('cc_grade_op', $config['grade_op'] ?? 'any');

        $mform->addElement('text', 'cc_grade_lt', get_string('grade_lt', 'tool_murules'));
        $mform->setType('cc_grade_lt', PARAM_FLOAT);
        $mform->setDefault('cc_grade_lt', $config['grade_lt'] ?? '');
        $mform->hideIf('cc_grade_lt', 'cc_grade_op', 'neq', 'lt');

        $mform->addElement('text', 'cc_grade_gt', get_string('grade_gt', 'tool_murules'));
        $mform->setType('cc_grade_gt', PARAM_FLOAT);
        $mform->setDefault('cc_grade_gt', $config['grade_gt'] ?? '');
        $mform->hideIf('cc_grade_gt', 'cc_grade_op', 'neq', 'gt');

        $mform->addElement('text', 'cc_grade_from', get_string('grade_from', 'tool_murules'));
        $mform->setType('cc_grade_from', PARAM_FLOAT);
        $mform->setDefault('cc_grade_from', $config['grade_from'] ?? '');
        $mform->addElement('text', 'cc_grade_to', get_string('grade_to', 'tool_murules'));
        $mform->setType('cc_grade_to', PARAM_FLOAT);
        $mform->setDefault('cc_grade_to', $config['grade_to'] ?? '');
        $mform->hideIf('cc_grade_from', 'cc_grade_op', 'neq', 'range');
        $mform->hideIf('cc_grade_to', 'cc_grade_op', 'neq', 'range');
    }

    public function validate_config(array $data): array {
        $errors = [];
        $courseid = helper::normalize_id($data['cc_courseid'] ?? 0);
        if (!$courseid || !$this->course_exists($courseid)) {
            $errors['cc_courseid'] = get_string('error_nocourse', 'tool_murules');
        }
        $when = $data['cc_completion_when'] ?? 'any';
        if ($when === 'after' && empty($data['cc_completion_after'])) {
            $errors['cc_completion_after'] = get_string('error_completion_date', 'tool_murules');
        }
        if ($when === 'before' && empty($data['cc_completion_before'])) {
            $errors['cc_completion_before'] = get_string('error_completion_date', 'tool_murules');
        }
        $gop = $data['cc_grade_op'] ?? 'any';
        if ($gop === 'lt' && ($data['cc_grade_lt'] === '' || $data['cc_grade_lt'] === null)) {
            $errors['cc_grade_lt'] = get_string('error_grade_value', 'tool_murules');
        }
        if ($gop === 'gt' && ($data['cc_grade_gt'] === '' || $data['cc_grade_gt'] === null)) {
            $errors['cc_grade_gt'] = get_string('error_grade_value', 'tool_murules');
        }
        if ($gop === 'range') {
            if ($data['cc_grade_from'] === '' || $data['cc_grade_to'] === ''
                || (float)$data['cc_grade_to'] < (float)$data['cc_grade_from']) {
                $errors['cc_grade_from'] = get_string('error_grade_range', 'tool_murules');
            }
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'courseid' => helper::normalize_id($data['cc_courseid'] ?? 0),
            'completion_when' => (string)($data['cc_completion_when'] ?? 'any'),
            'completion_after' => (int)($data['cc_completion_after'] ?? 0) ?: null,
            'completion_before' => (int)($data['cc_completion_before'] ?? 0) ?: null,
            'grade_op' => (string)($data['cc_grade_op'] ?? 'any'),
            'grade_lt' => ($data['cc_grade_lt'] === '' || $data['cc_grade_lt'] === null) ? null : (float)$data['cc_grade_lt'],
            'grade_gt' => ($data['cc_grade_gt'] === '' || $data['cc_grade_gt'] === null) ? null : (float)$data['cc_grade_gt'],
            'grade_from' => ($data['cc_grade_from'] === '' || $data['cc_grade_from'] === null) ? null : (float)$data['cc_grade_from'],
            'grade_to' => ($data['cc_grade_to'] === '' || $data['cc_grade_to'] === null) ? null : (float)$data['cc_grade_to'],
        ];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return false;
        }
        $completion = $DB->get_record('course_completions', ['userid' => $userid, 'course' => $courseid]);
        if (!$completion || empty($completion->timecompleted)) {
            return false;
        }
        $when = $config['completion_when'] ?? 'any';
        $t = (int)$completion->timecompleted;
        if ($when === 'after' && $t < (int)($config['completion_after'] ?? 0)) {
            return false;
        }
        if ($when === 'before' && $t >= (int)($config['completion_before'] ?? 0)) {
            return false;
        }
        return $this->grade_matches($userid, $courseid, $config);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return [];
        }
        $params = [
            'courseid' => $courseid,
            'fromuserid' => $fromuserid,
        ];
        $timesql = '';
        $when = $config['completion_when'] ?? 'any';
        if ($when === 'after' && !empty($config['completion_after'])) {
            $timesql = ' AND cc.timecompleted >= :after';
            $params['after'] = (int)$config['completion_after'];
        } else if ($when === 'before' && !empty($config['completion_before'])) {
            $timesql = ' AND cc.timecompleted < :before';
            $params['before'] = (int)$config['completion_before'];
        }
        $sql = "SELECT cc.userid
                  FROM {course_completions} cc
                  JOIN {user} u ON u.id = cc.userid AND u.deleted = 0
                 WHERE cc.course = :courseid
                   AND cc.timecompleted IS NOT NULL
                   AND cc.timecompleted > 0
                   AND cc.userid > :fromuserid
                   $timesql
              ORDER BY cc.userid ASC";
        // Over-fetch a bit when grade filter is active, then filter in PHP.
        $fetch = ($config['grade_op'] ?? 'any') === 'any' ? $limit : max($limit * 3, 500);
        $records = $DB->get_records_sql($sql, $params, 0, $fetch);
        $matched = [];
        foreach ($records as $rec) {
            $userid = (int)$rec->userid;
            if ($this->grade_matches($userid, $courseid, $config)) {
                $matched[] = $userid;
                if (count($matched) >= $limit) {
                    break;
                }
            }
        }
        return $matched;
    }

    public function is_broken(array $config): bool {
        $courseid = (int)($config['courseid'] ?? 0);
        return !$courseid || !$this->course_exists($courseid);
    }

    /**
     * @param int $userid
     * @param int $courseid
     * @param array $config
     * @return bool
     */
    protected function grade_matches(int $userid, int $courseid, array $config): bool {
        global $CFG;
        $op = $config['grade_op'] ?? 'any';
        if ($op === 'any') {
            return true;
        }
        require_once($CFG->libdir . '/gradelib.php');
        $gi = \grade_item::fetch_course_item($courseid);
        if (!$gi) {
            return false;
        }
        $grade = new \grade_grade(['itemid' => $gi->id, 'userid' => $userid]);
        if ($grade->finalgrade === null) {
            return false;
        }
        $pct = $gi->grademax > 0
            ? ((float)$grade->finalgrade / (float)$gi->grademax) * 100.0
            : (float)$grade->finalgrade;
        return match ($op) {
            'lt' => $pct < (float)($config['grade_lt'] ?? 0),
            'gt' => $pct > (float)($config['grade_gt'] ?? 0),
            'range' => $pct >= (float)($config['grade_from'] ?? 0) && $pct <= (float)($config['grade_to'] ?? 0),
            default => true,
        };
    }

    /**
     * @param int $courseid
     * @return bool
     */
    protected function course_exists(int $courseid): bool {
        global $DB;
        return $DB->record_exists('course', ['id' => $courseid]);
    }
}
