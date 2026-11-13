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
 * User is assigned to a MuTMS certification.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certification_assigned extends base {

    public function get_type(): string {
        return 'certification_assigned';
    }

    public function get_name(): string {
        return get_string('condition:certification_assigned', 'tool_murules');
    }

    public function get_category(): string {
        return 'certification';
    }

    public function get_description(array $config): string {
        return get_string('condition:certification_assigned_desc', 'tool_murules', (object)[
            'certification' => helper::certification_name((int)($config['certificationid'] ?? 0)),
        ]);
    }

    public function get_events(): array {
        return [
            \tool_mucertify\event\assignment_created::class,
            \tool_mucertify\event\assignment_restored::class,
        ];
    }

    public function is_available(): bool {
        return class_exists(\tool_mucertify\local\certification::class)
            && !empty(helper::certification_options());
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + helper::certification_options();
        $mform->addElement('select', 'cas_certificationid', get_string('certification', 'tool_murules'), $options);
        $mform->addRule('cas_certificationid', get_string('error_nocertification', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['certificationid'])) {
            $mform->setDefault('cas_certificationid', (int)$config['certificationid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['cas_certificationid'] ?? 0);
        if (!$id || !isset(helper::certification_options()[$id])) {
            $errors['cas_certificationid'] = get_string('error_nocertification', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['certificationid' => (int)($data['cas_certificationid'] ?? 0)];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $cid = (int)($config['certificationid'] ?? 0);
        if (!$cid) {
            return false;
        }
        return $DB->record_exists('tool_mucertify_assignment', [
            'certificationid' => $cid,
            'userid' => $userid,
            'archived' => 0,
        ]);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $cid = (int)($config['certificationid'] ?? 0);
        if (!$cid) {
            return [];
        }
        $sql = "SELECT a.userid
                  FROM {tool_mucertify_assignment} a
                  JOIN {user} u ON u.id = a.userid AND u.deleted = 0
                 WHERE a.certificationid = :cid
                   AND a.archived = 0
                   AND a.userid > :fromuserid
              ORDER BY a.userid ASC";
        $records = $DB->get_records_sql($sql, ['cid' => $cid, 'fromuserid' => $fromuserid], 0, $limit);
        return array_map(static fn($r) => (int)$r->userid, $records);
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['certificationid'] ?? 0);
        return !$id || !isset(helper::certification_options()[$id]);
    }
}
