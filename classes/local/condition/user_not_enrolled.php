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
 * User not enrolled in course (G11) — scheduled.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_not_enrolled extends base {

    public function get_type(): string {
        return 'user_not_enrolled';
    }

    public function get_name(): string {
        return get_string('condition:user_not_enrolled', 'tool_murules');
    }

    public function get_category(): string {
        return 'enrollment';
    }

    public function get_description(array $config): string {
        $method = $config['enrolmethod'] ?? 'any';
        $methodlabel = $method === 'any'
            ? get_string('enrolmethod_any', 'tool_murules')
            : get_string('pluginname', 'enrol_' . $method);
        return get_string('condition:user_not_enrolled_desc', 'tool_murules', (object)[
            'course' => helper::course_name((int)($config['courseid'] ?? 0)),
            'method' => $methodlabel,
        ]);
    }

    public function is_scheduled(): bool {
        return true;
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('course', 'une_courseid', get_string('course', 'tool_murules'), [
            'multiple' => false,
            'includefrontpage' => false,
        ]);
        $mform->addRule('une_courseid', get_string('error_nocourse', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['courseid'])) {
            $mform->setDefault('une_courseid', [(int)$config['courseid']]);
        }
        $mform->addElement('select', 'une_enrolmethod', get_string('enrolmethod', 'tool_murules'),
            helper::enrol_method_options());
        $mform->setDefault('une_enrolmethod', $config['enrolmethod'] ?? 'any');
    }

    public function validate_config(array $data): array {
        $errors = [];
        $courseid = helper::normalize_id($data['une_courseid'] ?? 0);
        if (!$courseid || !$this->course_exists($courseid)) {
            $errors['une_courseid'] = get_string('error_nocourse', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'courseid' => helper::normalize_id($data['une_courseid'] ?? 0),
            'enrolmethod' => (string)($data['une_enrolmethod'] ?? 'any'),
        ];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return false;
        }
        $method = (string)($config['enrolmethod'] ?? 'any');
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
        $sql = "SELECT ue.id
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid = :courseid
                   AND ue.userid = :userid
                   AND ue.status = :active
                   $methodsql";
        return !$DB->record_exists_sql($sql, $params);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return [];
        }
        $method = (string)($config['enrolmethod'] ?? 'any');
        $params = [
            'courseid' => $courseid,
            'fromuserid' => $fromuserid,
            'guestid' => (int)guest_user()->id,
            'active' => ENROL_USER_ACTIVE,
        ];
        $methodsql = '';
        if ($method !== 'any') {
            $methodsql = ' AND e.enrol = :enrolmethod';
            $params['enrolmethod'] = $method;
        }
        $sql = "SELECT u.id
                  FROM {user} u
             LEFT JOIN (
                    SELECT ue.userid
                      FROM {user_enrolments} ue
                      JOIN {enrol} e ON e.id = ue.enrolid
                     WHERE e.courseid = :courseid
                       AND ue.status = :active
                       $methodsql
                 ) enrolled ON enrolled.userid = u.id
                 WHERE u.deleted = 0
                   AND u.id > :fromuserid
                   AND u.id <> :guestid
                   AND enrolled.userid IS NULL
              ORDER BY u.id ASC";
        $records = $DB->get_records_sql($sql, $params, 0, $limit);
        return array_map(static fn($r) => (int)$r->id, $records);
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
