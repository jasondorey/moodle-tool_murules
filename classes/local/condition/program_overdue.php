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
 * User program allocation is overdue (due date passed, not completed).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class program_overdue extends base {

    public function get_type(): string {
        return 'program_overdue';
    }

    public function get_name(): string {
        return get_string('condition:program_overdue', 'tool_murules');
    }

    public function get_category(): string {
        return 'program';
    }

    public function get_description(array $config): string {
        return get_string('condition:program_overdue_desc', 'tool_murules', (object)[
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
        $mform->addElement('select', 'po_programid', get_string('program', 'tool_murules'), $options);
        $mform->addRule('po_programid', get_string('error_noprogram', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['programid'])) {
            $mform->setDefault('po_programid', (int)$config['programid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['po_programid'] ?? 0);
        if (!$id || !isset(helper::program_options()[$id])) {
            $errors['po_programid'] = get_string('error_noprogram', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['programid' => (int)($data['po_programid'] ?? 0)];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $programid = (int)($config['programid'] ?? 0);
        if (!$programid) {
            return false;
        }
        $alloc = $DB->get_record('tool_muprog_allocation', [
            'programid' => $programid,
            'userid' => $userid,
            'archived' => 0,
        ]);
        if (!$alloc || !empty($alloc->timecompleted)) {
            return false;
        }
        return !empty($alloc->timedue) && (int)$alloc->timedue < time();
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $programid = (int)($config['programid'] ?? 0);
        if (!$programid) {
            return [];
        }
        $sql = "SELECT a.userid
                  FROM {tool_muprog_allocation} a
                  JOIN {user} u ON u.id = a.userid AND u.deleted = 0
                 WHERE a.programid = :programid
                   AND a.archived = 0
                   AND (a.timecompleted IS NULL OR a.timecompleted = 0)
                   AND a.timedue IS NOT NULL
                   AND a.timedue > 0
                   AND a.timedue < :now
                   AND a.userid > :fromuserid
              ORDER BY a.userid ASC";
        $records = $DB->get_records_sql($sql, [
            'programid' => $programid,
            'now' => time(),
            'fromuserid' => $fromuserid,
        ], 0, $limit);
        return array_map(static fn($r) => (int)$r->userid, $records);
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['programid'] ?? 0);
        return !$id || !isset(helper::program_options()[$id]);
    }
}
