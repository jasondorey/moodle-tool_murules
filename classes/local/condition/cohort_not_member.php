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
 * User is not member of cohort (G2) — scheduled/negative.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_not_member extends base {

    public function get_type(): string {
        return 'cohort_not_member';
    }

    public function get_name(): string {
        return get_string('condition:cohort_not_member', 'tool_murules');
    }

    public function get_category(): string {
        return 'cohort';
    }

    public function get_description(array $config): string {
        return get_string('condition:cohort_not_member_desc', 'tool_murules', (object)[
            'cohort' => helper::cohort_name((int)($config['cohortid'] ?? 0)),
        ]);
    }

    public function is_scheduled(): bool {
        return true;
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + helper::cohort_options();
        $mform->addElement('select', 'cnm_cohortid', get_string('cohort', 'tool_murules'), $options);
        $mform->addRule('cnm_cohortid', get_string('error_nocohort', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['cohortid'])) {
            $mform->setDefault('cnm_cohortid', (int)$config['cohortid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $cohortid = (int)($data['cnm_cohortid'] ?? 0);
        if (!$cohortid || !$this->cohort_exists($cohortid)) {
            $errors['cnm_cohortid'] = get_string('error_nocohort', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'cohortid' => (int)($data['cnm_cohortid'] ?? 0),
        ];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $cohortid = (int)($config['cohortid'] ?? 0);
        if (!$cohortid) {
            return false;
        }
        return !$DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid]);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $cohortid = (int)($config['cohortid'] ?? 0);
        if (!$cohortid) {
            return [];
        }
        $sql = "SELECT u.id
                  FROM {user} u
             LEFT JOIN {cohort_members} cm ON cm.userid = u.id AND cm.cohortid = :cohortid
                 WHERE u.deleted = 0
                   AND u.id > :fromuserid
                   AND u.id <> :guestid
                   AND cm.id IS NULL
              ORDER BY u.id ASC";
        $params = [
            'cohortid' => $cohortid,
            'fromuserid' => $fromuserid,
            'guestid' => (int)guest_user()->id,
        ];
        $records = $DB->get_records_sql($sql, $params, 0, $limit);
        return array_map(static fn($r) => (int)$r->id, $records);
    }

    public function is_broken(array $config): bool {
        $cohortid = (int)($config['cohortid'] ?? 0);
        return !$cohortid || !$this->cohort_exists($cohortid);
    }

    /**
     * @param int $cohortid
     * @return bool
     */
    protected function cohort_exists(int $cohortid): bool {
        global $DB;
        return $DB->record_exists('cohort', ['id' => $cohortid]);
    }
}
