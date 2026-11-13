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
 * User is not allocated to a MuTMS program (scheduled).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_not_allocated extends base {

    public function get_type(): string {
        return 'program_not_allocated';
    }

    public function get_name(): string {
        return get_string('condition:program_not_allocated', 'tool_murules');
    }

    public function get_category(): string {
        return 'program';
    }

    public function get_description(array $config): string {
        return get_string('condition:program_not_allocated_desc', 'tool_murules', (object)[
            'program' => helper::program_name((int)($config['programid'] ?? 0)),
        ]);
    }

    public function is_scheduled(): bool {
        return true;
    }

    public function is_available(): bool {
        return class_exists(\tool_muprog\local\program::class);
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + helper::program_options();
        $mform->addElement('select', 'pna_programid', get_string('program', 'tool_murules'), $options);
        $mform->addRule('pna_programid', get_string('error_noprogram', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['programid'])) {
            $mform->setDefault('pna_programid', (int)$config['programid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['pna_programid'] ?? 0);
        if (!$id || !isset(helper::program_options()[$id])) {
            $errors['pna_programid'] = get_string('error_noprogram', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['programid' => (int)($data['pna_programid'] ?? 0)];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $programid = (int)($config['programid'] ?? 0);
        if (!$programid) {
            return false;
        }
        return !$DB->record_exists('tool_muprog_allocation', [
            'programid' => $programid,
            'userid' => $userid,
            'archived' => 0,
        ]);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $programid = (int)($config['programid'] ?? 0);
        if (!$programid) {
            return [];
        }
        $sql = "SELECT u.id
                  FROM {user} u
             LEFT JOIN {tool_muprog_allocation} a
                    ON a.userid = u.id AND a.programid = :programid AND a.archived = 0
                 WHERE u.deleted = 0
                   AND u.id > :fromuserid
                   AND u.id <> :guestid
                   AND a.id IS NULL
              ORDER BY u.id ASC";
        $records = $DB->get_records_sql($sql, [
            'programid' => $programid,
            'fromuserid' => $fromuserid,
            'guestid' => (int)guest_user()->id,
        ], 0, $limit);
        return array_map(static fn($r) => (int)$r->id, $records);
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['programid'] ?? 0);
        return !$id || !isset(helper::program_options()[$id]);
    }
}
