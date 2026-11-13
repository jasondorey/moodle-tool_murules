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
 * User enrolled in course (G8).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_enrolled extends base {

    public function get_type(): string {
        return 'user_enrolled';
    }

    public function get_name(): string {
        return get_string('condition:user_enrolled', 'tool_murules');
    }

    public function get_description(array $config): string {
        $method = $config['enrolmethod'] ?? 'any';
        $methodlabel = $method === 'any'
            ? get_string('enrolmethod_any', 'tool_murules')
            : get_string('pluginname', 'enrol_' . $method);
        $a = (object)[
            'course' => helper::course_name((int)($config['courseid'] ?? 0)),
            'method' => $methodlabel,
            'start' => '',
        ];
        if (!empty($config['start_on_after'])) {
            $a->start = ' ' . get_string('enrol_start_after_desc', 'tool_murules',
                userdate((int)$config['start_on_after']));
        }
        return get_string('condition:user_enrolled_desc', 'tool_murules', $a);
    }

    public function get_events(): array {
        return [
            \core\event\user_enrolment_created::class,
            \core\event\user_enrolment_updated::class,
        ];
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('course', 'ue_courseid', get_string('course', 'tool_murules'), [
            'multiple' => false,
            'includefrontpage' => false,
        ]);
        $mform->addRule('ue_courseid', get_string('error_nocourse', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['courseid'])) {
            $mform->setDefault('ue_courseid', [(int)$config['courseid']]);
        }

        $mform->addElement('select', 'ue_enrolmethod', get_string('enrolmethod', 'tool_murules'),
            helper::enrol_method_options());
        $mform->setDefault('ue_enrolmethod', $config['enrolmethod'] ?? 'any');

        $mform->addElement('date_time_selector', 'ue_start_on_after',
            get_string('enrol_start_on_after', 'tool_murules'), ['optional' => true]);
        if (!empty($config['start_on_after'])) {
            $mform->setDefault('ue_start_on_after', (int)$config['start_on_after']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $courseid = helper::normalize_id($data['ue_courseid'] ?? 0);
        if (!$courseid || !$this->course_exists($courseid)) {
            $errors['ue_courseid'] = get_string('error_nocourse', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'courseid' => helper::normalize_id($data['ue_courseid'] ?? 0),
            'enrolmethod' => (string)($data['ue_enrolmethod'] ?? 'any'),
            'start_on_after' => (int)($data['ue_start_on_after'] ?? 0),
        ];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return false;
        }
        $method = (string)($config['enrolmethod'] ?? 'any');
        $after = (int)($config['start_on_after'] ?? 0);

        $params = [
            'courseid' => $courseid,
            'userid' => $userid,
            'active' => ENROL_USER_ACTIVE,
        ];
        $methodsql = '';
        if ($method !== 'any') {
            $methodsql = ' AND e.enrol = :enrolmethod';
            $params['enrolmethod'] = $method;
        }
        $aftersql = '';
        if ($after > 0) {
            // Prefer enrolment start; fall back to timecreated.
            $aftersql = ' AND (CASE WHEN ue.timestart > 0 THEN ue.timestart ELSE ue.timecreated END) >= :after';
            $params['after'] = $after;
        }
        $sql = "SELECT ue.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid
                   AND ue.userid = :userid
                   AND ue.status = :active
                   $methodsql
                   $aftersql";
        return $DB->record_exists_sql($sql, $params);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return [];
        }
        $method = (string)($config['enrolmethod'] ?? 'any');
        $after = (int)($config['start_on_after'] ?? 0);
        $params = [
            'courseid' => $courseid,
            'fromuserid' => $fromuserid,
            'active' => ENROL_USER_ACTIVE,
        ];
        $methodsql = '';
        if ($method !== 'any') {
            $methodsql = ' AND e.enrol = :enrolmethod';
            $params['enrolmethod'] = $method;
        }
        $aftersql = '';
        if ($after > 0) {
            $aftersql = ' AND (CASE WHEN ue.timestart > 0 THEN ue.timestart ELSE ue.timecreated END) >= :after';
            $params['after'] = $after;
        }
        $sql = "SELECT DISTINCT ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {user} u ON u.id = ue.userid AND u.deleted = 0
                 WHERE e.courseid = :courseid
                   AND ue.status = :active
                   AND ue.userid > :fromuserid
                   $methodsql
                   $aftersql
              ORDER BY ue.userid ASC";
        $records = $DB->get_records_sql($sql, $params, 0, $limit);
        return array_map(static fn($r) => (int)$r->userid, $records);
    }

    public function is_broken(array $config): bool {
        $courseid = (int)($config['courseid'] ?? 0);
        return !$courseid || !$this->course_exists($courseid);
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
