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
 * User is allocated to a MuTMS tenant.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tenant_member extends base {

    public function get_type(): string {
        return 'tenant_member';
    }

    public function get_name(): string {
        return get_string('condition:tenant_member', 'tool_murules');
    }

    public function get_category(): string {
        return 'tenancy';
    }

    public function get_description(array $config): string {
        return get_string('condition:tenant_member_desc', 'tool_murules', (object)[
            'tenant' => helper::tenant_name((int)($config['tenantid'] ?? 0)),
        ]);
    }

    public function get_events(): array {
        return [
            \tool_mutenancy\event\user_allocated::class,
        ];
    }

    public function is_available(): bool {
        return class_exists(\tool_mutenancy\local\tenancy::class)
            && \tool_mutenancy\local\tenancy::is_active()
            && !empty(helper::tenant_options());
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + helper::tenant_options();
        $mform->addElement('select', 'tm_tenantid', get_string('tenant', 'tool_murules'), $options);
        $mform->addRule('tm_tenantid', get_string('error_notenant', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['tenantid'])) {
            $mform->setDefault('tm_tenantid', (int)$config['tenantid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['tm_tenantid'] ?? 0);
        if (!$id || !isset(helper::tenant_options()[$id])) {
            $errors['tm_tenantid'] = get_string('error_notenant', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['tenantid' => (int)($data['tm_tenantid'] ?? 0)];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        $tenantid = (int)($config['tenantid'] ?? 0);
        if (!$tenantid) {
            return false;
        }
        $userTenant = \tool_mutenancy\local\tenancy::get_user_tenantid($userid);
        return $userTenant !== null && (int)$userTenant === $tenantid;
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $tenantid = (int)($config['tenantid'] ?? 0);
        if (!$tenantid) {
            return [];
        }
        $sql = "SELECT u.id
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.tenantid = :tenantid
                   AND u.id > :fromuserid
                   AND u.id <> :guestid
              ORDER BY u.id ASC";
        $records = $DB->get_records_sql($sql, [
            'tenantid' => $tenantid,
            'fromuserid' => $fromuserid,
            'guestid' => (int)guest_user()->id,
        ], 0, $limit);
        return array_map(static fn($r) => (int)$r->id, $records);
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['tenantid'] ?? 0);
        return !$id || !isset(helper::tenant_options()[$id]);
    }
}
