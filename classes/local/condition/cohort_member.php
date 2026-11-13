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
 * User is member of cohort (G1).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_member extends base {

    public function get_type(): string {
        return 'cohort_member';
    }

    public function get_name(): string {
        return get_string('condition:cohort_member', 'tool_murules');
    }

    public function get_description(array $config): string {
        $a = (object)[
            'cohort' => helper::cohort_name((int)($config['cohortid'] ?? 0)),
            'added' => '',
        ];
        if (!empty($config['added_on_after'])) {
            $a->added = ' ' . get_string('condition:cohort_member_added_after', 'tool_murules',
                userdate((int)$config['added_on_after']));
        }
        return get_string('condition:cohort_member_desc', 'tool_murules', $a);
    }

    public function get_events(): array {
        return [
            \core\event\cohort_member_added::class,
        ];
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + helper::cohort_options();
        $mform->addElement('select', 'cm_cohortid', get_string('cohort', 'tool_murules'), $options);
        $mform->addRule('cm_cohortid', get_string('error_nocohort', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['cohortid'])) {
            $mform->setDefault('cm_cohortid', (int)$config['cohortid']);
        }

        $mform->addElement('date_time_selector', 'cm_added_on_after',
            get_string('cohort_added_on_after', 'tool_murules'), ['optional' => true]);
        if (!empty($config['added_on_after'])) {
            $mform->setDefault('cm_added_on_after', (int)$config['added_on_after']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $cohortid = (int)($data['cm_cohortid'] ?? 0);
        if (!$cohortid || !$this->cohort_exists($cohortid)) {
            $errors['cm_cohortid'] = get_string('error_nocohort', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'cohortid' => (int)($data['cm_cohortid'] ?? 0),
            'added_on_after' => (int)($data['cm_added_on_after'] ?? 0),
        ];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $cohortid = (int)($config['cohortid'] ?? 0);
        if (!$cohortid) {
            return false;
        }
        $member = $DB->get_record('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid]);
        if (!$member) {
            return false;
        }
        $after = (int)($config['added_on_after'] ?? 0);
        if ($after > 0 && (int)$member->timeadded < $after) {
            return false;
        }
        return true;
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $cohortid = (int)($config['cohortid'] ?? 0);
        if (!$cohortid) {
            return [];
        }
        $params = [
            'cohortid' => $cohortid,
            'fromuserid' => $fromuserid,
        ];
        $aftersql = '';
        if (!empty($config['added_on_after'])) {
            $aftersql = ' AND cm.timeadded >= :after';
            $params['after'] = (int)$config['added_on_after'];
        }
        $sql = "SELECT cm.userid
                  FROM {cohort_members} cm
                  JOIN {user} u ON u.id = cm.userid AND u.deleted = 0
                 WHERE cm.cohortid = :cohortid
                   AND cm.userid > :fromuserid
                   $aftersql
              ORDER BY cm.userid ASC";
        $records = $DB->get_records_sql($sql, $params, 0, $limit);
        return array_map(static fn($r) => (int)$r->userid, $records);
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
