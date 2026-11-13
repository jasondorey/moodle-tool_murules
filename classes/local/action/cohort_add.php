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

namespace tool_murules\local\action;

use MoodleQuickForm;
use stdClass;
use tool_murules\local\condition\helper;

/**
 * Add user to a cohort.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_add extends base {

    public function get_type(): string {
        return 'cohort_add';
    }

    public function get_name(): string {
        return get_string('action:cohort_add', 'tool_murules');
    }

    public function get_description(array $config): string {
        return get_string('action:cohort_add_desc', 'tool_murules', helper::cohort_name((int)($config['cohortid'] ?? 0)));
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + helper::cohort_options();
        $mform->addElement('select', 'ca_cohortid', get_string('cohort', 'tool_murules'), $options);
        $mform->addRule('ca_cohortid', get_string('error_nocohort', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['cohortid'])) {
            $mform->setDefault('ca_cohortid', (int)$config['cohortid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['ca_cohortid'] ?? 0);
        if (!$id || !isset(helper::cohort_options()[$id])) {
            $errors['ca_cohortid'] = get_string('error_nocohort', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['cohortid' => (int)($data['ca_cohortid'] ?? 0)];
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['cohortid'] ?? 0);
        return !$id || !isset(helper::cohort_options()[$id]);
    }

    public function execute(int $userid, array $config, stdClass $rule, stdClass $actionrec): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $cohortid = (int)($config['cohortid'] ?? 0);
        if ($this->is_broken($config)) {
            return ['status' => 'failed', 'reason' => get_string('reason_broken', 'tool_murules')];
        }
        if ($DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid])) {
            return ['status' => 'skipped', 'reason' => get_string('reason_already_cohort_member', 'tool_murules')];
        }
        try {
            cohort_add_member($cohortid, $userid);
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_cohort_failed', 'tool_murules', $e->getMessage()),
            ];
        }
        return ['status' => 'applied', 'reason' => get_string('reason_cohort_add_ok', 'tool_murules')];
    }
}
