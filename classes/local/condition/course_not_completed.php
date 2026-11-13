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
 * Course not completed (G6) — scheduled; regardless of enrolment.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_not_completed extends base {

    public function get_type(): string {
        return 'course_not_completed';
    }

    public function get_name(): string {
        return get_string('condition:course_not_completed', 'tool_murules');
    }

    public function get_category(): string {
        return 'course';
    }

    public function get_description(array $config): string {
        return get_string('condition:course_not_completed_desc', 'tool_murules', (object)[
            'course' => helper::course_name((int)($config['courseid'] ?? 0)),
        ]);
    }

    public function is_scheduled(): bool {
        return true;
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('course', 'cnc_courseid', get_string('course', 'tool_murules'), [
            'multiple' => false,
            'includefrontpage' => false,
        ]);
        $mform->addRule('cnc_courseid', get_string('error_nocourse', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['courseid'])) {
            $mform->setDefault('cnc_courseid', [(int)$config['courseid']]);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $courseid = helper::normalize_id($data['cnc_courseid'] ?? 0);
        if (!$courseid || !$this->course_exists($courseid)) {
            $errors['cnc_courseid'] = get_string('error_nocourse', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'courseid' => helper::normalize_id($data['cnc_courseid'] ?? 0),
        ];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return false;
        }
        $completion = $DB->get_record('course_completions', ['userid' => $userid, 'course' => $courseid]);
        return !$completion || empty($completion->timecompleted);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return [];
        }
        $sql = "SELECT u.id
                  FROM {user} u
             LEFT JOIN {course_completions} cc
                    ON cc.userid = u.id AND cc.course = :courseid
                   AND cc.timecompleted IS NOT NULL AND cc.timecompleted > 0
                 WHERE u.deleted = 0
                   AND u.id > :fromuserid
                   AND u.id <> :guestid
                   AND cc.id IS NULL
              ORDER BY u.id ASC";
        $records = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'fromuserid' => $fromuserid,
            'guestid' => (int)guest_user()->id,
        ], 0, $limit);
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
