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
 * Remove user from a cohort.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cohort_remove extends base {

    public function get_type(): string {
        return 'cohort_remove';
    }

    public function get_name(): string {
        return get_string('action:cohort_remove', 'tool_murules');
    }

    public function get_description(array $config): string {
        return get_string('action:cohort_remove_desc', 'tool_murules', helper::cohort_name((int)($config['cohortid'] ?? 0)));
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $cohorts = helper::cohort_options();
        if (!$cohorts) {
            $mform->addElement('static', 'cr_nocohorts', get_string('cohort', 'tool_murules'),
                get_string('nocohorts', 'tool_murules'));
            $mform->addElement('hidden', 'cr_cohortid', 0);
            $mform->setType('cr_cohortid', PARAM_INT);
            return;
        }
        $options = [0 => get_string('choosedots')] + $cohorts;
        $mform->addElement('select', 'cr_cohortid', get_string('cohort', 'tool_murules'), $options);
        $mform->addRule('cr_cohortid', get_string('error_nocohort', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['cohortid'])) {
            $mform->setDefault('cr_cohortid', (int)$config['cohortid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['cr_cohortid'] ?? 0);
        if (!$id || !isset(helper::cohort_options()[$id])) {
            $errors['cr_cohortid'] = get_string('error_nocohort', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['cohortid' => (int)($data['cr_cohortid'] ?? 0)];
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
        if (!$DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid])) {
            return ['status' => 'skipped', 'reason' => get_string('reason_not_cohort_member', 'tool_murules')];
        }
        try {
            cohort_remove_member($cohortid, $userid);
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_cohort_failed', 'tool_murules', $e->getMessage()),
            ];
        }
        return ['status' => 'applied', 'reason' => get_string('reason_cohort_remove_ok', 'tool_murules')];
    }
}
