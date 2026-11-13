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
 * Course last access (G5) — mostly scheduled; course_viewed for "ever".
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_last_access extends base {

    public function get_type(): string {
        return 'course_last_access';
    }

    public function get_name(): string {
        return get_string('condition:course_last_access', 'tool_murules');
    }

    public function get_category(): string {
        return 'course';
    }

    public function get_description(array $config): string {
        return get_string('condition:course_last_access_desc', 'tool_murules', (object)[
            'course' => helper::course_name((int)($config['courseid'] ?? 0)),
            'window' => helper::describe_time_window($config),
        ]);
    }

    public function get_events(): array {
        // Optional realtime for "ever" (and to refresh relative windows soon after access).
        return [
            \core\event\course_viewed::class,
        ];
    }

    public function is_scheduled(): bool {
        return true;
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('course', 'cla_courseid', get_string('course', 'tool_murules'), [
            'multiple' => false,
            'includefrontpage' => false,
        ]);
        $mform->addRule('cla_courseid', get_string('error_nocourse', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['courseid'])) {
            $mform->setDefault('cla_courseid', [(int)$config['courseid']]);
        }
        helper::add_time_window_elements($mform, 'cla_', $config, [
            'ever', 'never', 'before', 'after', 'during_last', 'prior_to_last',
        ], 'during_last');
    }

    public function validate_config(array $data): array {
        $errors = [];
        $courseid = helper::normalize_id($data['cla_courseid'] ?? 0);
        if (!$courseid || !$this->course_exists($courseid)) {
            $errors['cla_courseid'] = get_string('error_nocourse', 'tool_murules');
        }
        return array_merge($errors, helper::validate_time_window($data, 'cla_'));
    }

    public function extract_config(array $data): array {
        return array_merge(
            ['courseid' => helper::normalize_id($data['cla_courseid'] ?? 0)],
            helper::extract_time_window($data, 'cla_')
        );
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return false;
        }
        $timeaccess = $DB->get_field('user_lastaccess', 'timeaccess', [
            'userid' => $userid,
            'courseid' => $courseid,
        ]);
        $ts = $timeaccess ? (int)$timeaccess : null;
        return helper::time_matches($ts, $config);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid) {
            return [];
        }
        $mode = $config['mode'] ?? 'ever';
        $guestid = (int)guest_user()->id;

        if ($mode === 'never') {
            $sql = "SELECT u.id
                      FROM {user} u
                 LEFT JOIN {user_lastaccess} ula ON ula.userid = u.id AND ula.courseid = :courseid
                     WHERE u.deleted = 0
                       AND u.id > :fromuserid
                       AND u.id <> :guestid
                       AND ula.id IS NULL
                  ORDER BY u.id ASC";
            $records = $DB->get_records_sql($sql, [
                'courseid' => $courseid,
                'fromuserid' => $fromuserid,
                'guestid' => $guestid,
            ], 0, $limit);
            return array_map(static fn($r) => (int)$r->id, $records);
        }

        // Candidates with an access row; filter via time_matches for relative/absolute windows.
        $sql = "SELECT ula.userid, ula.timeaccess
                  FROM {user_lastaccess} ula
                  JOIN {user} u ON u.id = ula.userid AND u.deleted = 0
                 WHERE ula.courseid = :courseid
                   AND ula.userid > :fromuserid
              ORDER BY ula.userid ASC";
        $fetch = max($limit * 3, 500);
        $records = $DB->get_records_sql($sql, [
            'courseid' => $courseid,
            'fromuserid' => $fromuserid,
        ], 0, $fetch);
        $matched = [];
        foreach ($records as $rec) {
            if (helper::time_matches((int)$rec->timeaccess, $config)) {
                $matched[] = (int)$rec->userid;
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
     * @param int $courseid
     * @return bool
     */
    protected function course_exists(int $courseid): bool {
        global $DB;
        return $DB->record_exists('course', ['id' => $courseid]);
    }
}
