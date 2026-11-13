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
 * User is a supervisor in a MuTMS relation framework.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class relation_supervisor extends base {

    public function get_type(): string {
        return 'relation_supervisor';
    }

    public function get_name(): string {
        return get_string('condition:relation_supervisor', 'tool_murules');
    }

    public function get_category(): string {
        return 'relation';
    }

    public function get_description(array $config): string {
        return get_string('condition:relation_supervisor_desc', 'tool_murules', (object)[
            'framework' => helper::framework_name((int)($config['frameworkid'] ?? 0)),
        ]);
    }

    public function is_scheduled(): bool {
        return true;
    }

    public function is_available(): bool {
        return class_exists(\tool_murelation\local\util::class)
            && \tool_murelation\local\util::is_murelation_active()
            && !empty(helper::framework_options());
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + helper::framework_options();
        $mform->addElement('select', 'rv_frameworkid', get_string('relation_framework', 'tool_murules'), $options);
        $mform->addRule('rv_frameworkid', get_string('error_noframework', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['frameworkid'])) {
            $mform->setDefault('rv_frameworkid', (int)$config['frameworkid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['rv_frameworkid'] ?? 0);
        if (!$id || !isset(helper::framework_options()[$id])) {
            $errors['rv_frameworkid'] = get_string('error_noframework', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['frameworkid' => (int)($data['rv_frameworkid'] ?? 0)];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $fid = (int)($config['frameworkid'] ?? 0);
        if (!$fid) {
            return false;
        }
        return $DB->record_exists('tool_murelation_supervisor', [
            'frameworkid' => $fid,
            'userid' => $userid,
        ]);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $fid = (int)($config['frameworkid'] ?? 0);
        if (!$fid) {
            return [];
        }
        $sql = "SELECT s.userid
                  FROM {tool_murelation_supervisor} s
                  JOIN {user} u ON u.id = s.userid AND u.deleted = 0
                 WHERE s.frameworkid = :fid
                   AND s.userid IS NOT NULL
                   AND s.userid > :fromuserid
              ORDER BY s.userid ASC";
        $records = $DB->get_records_sql($sql, ['fid' => $fid, 'fromuserid' => $fromuserid], 0, $limit);
        return array_map(static fn($r) => (int)$r->userid, $records);
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['frameworkid'] ?? 0);
        return !$id || !isset(helper::framework_options()[$id]);
    }
}
