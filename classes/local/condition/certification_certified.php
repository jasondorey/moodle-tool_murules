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
 * User currently holds a valid MuTMS certification period.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certification_certified extends base {

    public function get_type(): string {
        return 'certification_certified';
    }

    public function get_name(): string {
        return get_string('condition:certification_certified', 'tool_murules');
    }

    public function get_category(): string {
        return 'certification';
    }

    public function get_description(array $config): string {
        return get_string('condition:certification_certified_desc', 'tool_murules', (object)[
            'certification' => helper::certification_name((int)($config['certificationid'] ?? 0)),
        ]);
    }

    public function get_events(): array {
        return [
            \tool_mucertify\event\period_certified::class,
            \tool_mucertify\event\period_updated::class,
        ];
    }

    public function is_available(): bool {
        return class_exists(\tool_mucertify\local\certification::class)
            && !empty(helper::certification_options());
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + helper::certification_options();
        $mform->addElement('select', 'ccert_certificationid', get_string('certification', 'tool_murules'), $options);
        $mform->addRule('ccert_certificationid', get_string('error_nocertification', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['certificationid'])) {
            $mform->setDefault('ccert_certificationid', (int)$config['certificationid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['ccert_certificationid'] ?? 0);
        if (!$id || !isset(helper::certification_options()[$id])) {
            $errors['ccert_certificationid'] = get_string('error_nocertification', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['certificationid' => (int)($data['ccert_certificationid'] ?? 0)];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $cid = (int)($config['certificationid'] ?? 0);
        if (!$cid) {
            return false;
        }
        $now = time();
        $sql = "SELECT p.id
                  FROM {tool_mucertify_period} p
                 WHERE p.certificationid = :cid
                   AND p.userid = :userid
                   AND p.timecertified IS NOT NULL
                   AND p.timecertified > 0
                   AND p.timerevoked IS NULL
                   AND (p.timeuntil IS NULL OR p.timeuntil = 0 OR p.timeuntil > :now)";
        return $DB->record_exists_sql($sql, ['cid' => $cid, 'userid' => $userid, 'now' => $now]);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $cid = (int)($config['certificationid'] ?? 0);
        if (!$cid) {
            return [];
        }
        $sql = "SELECT DISTINCT p.userid
                  FROM {tool_mucertify_period} p
                  JOIN {user} u ON u.id = p.userid AND u.deleted = 0
                 WHERE p.certificationid = :cid
                   AND p.timecertified IS NOT NULL
                   AND p.timecertified > 0
                   AND p.timerevoked IS NULL
                   AND (p.timeuntil IS NULL OR p.timeuntil = 0 OR p.timeuntil > :now)
                   AND p.userid > :fromuserid
              ORDER BY p.userid ASC";
        $records = $DB->get_records_sql($sql, [
            'cid' => $cid,
            'now' => time(),
            'fromuserid' => $fromuserid,
        ], 0, $limit);
        return array_map(static fn($r) => (int)$r->userid, $records);
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['certificationid'] ?? 0);
        return !$id || !isset(helper::certification_options()[$id]);
    }
}
