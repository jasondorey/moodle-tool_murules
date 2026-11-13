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
 * Deallocate user from a MuTMS program — only if previously allocated by tool_murules.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class deallocate_muprog extends base {

    public function get_type(): string {
        return 'deallocate_muprog';
    }

    public function get_name(): string {
        return get_string('action:deallocate_muprog', 'tool_murules');
    }

    public function get_description(array $config): string {
        return get_string('action:deallocate_muprog_desc', 'tool_murules',
            helper::program_name((int)($config['programid'] ?? 0)));
    }

    public function is_available(): bool {
        return class_exists(\tool_muprog\local\source\manual::class);
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $programs = helper::program_options();
        if (!$programs) {
            $mform->addElement('static', 'dm_noprograms', get_string('program', 'tool_murules'),
                get_string('noprograms_manual', 'tool_murules'));
            $mform->addElement('hidden', 'dm_programid', 0);
            $mform->setType('dm_programid', PARAM_INT);
            return;
        }
        $options = [0 => get_string('choosedots')] + $programs;
        $mform->addElement('select', 'dm_programid', get_string('program', 'tool_murules'), $options);
        $mform->addRule('dm_programid', get_string('error_noprogram', 'tool_murules'), 'required', null, 'client');
        $mform->addHelpButton('dm_programid', 'program_deallocate', 'tool_murules');
        if (!empty($config['programid'])) {
            $mform->setDefault('dm_programid', (int)$config['programid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['dm_programid'] ?? 0);
        if (!$id || !isset(helper::program_options()[$id])) {
            $errors['dm_programid'] = get_string('error_noprogram', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return ['programid' => (int)($data['dm_programid'] ?? 0)];
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['programid'] ?? 0);
        return !$id || !isset(helper::program_options()[$id]);
    }

    public function execute(int $userid, array $config, stdClass $rule, stdClass $actionrec): array {
        global $DB;

        $programid = (int)($config['programid'] ?? 0);
        if ($this->is_broken($config)) {
            return ['status' => 'failed', 'reason' => get_string('reason_broken', 'tool_murules')];
        }

        $allocation = $DB->get_record('tool_muprog_allocation', [
            'programid' => $programid,
            'userid' => $userid,
        ]);
        if (!$allocation) {
            return ['status' => 'skipped', 'reason' => get_string('reason_not_allocated', 'tool_murules')];
        }

        // Only deallocate allocations previously applied by tool_murules.
        if (!$this->is_rule_owned((int)$allocation->id, $userid, $programid)) {
            return [
                'status' => 'skipped',
                'reason' => get_string('reason_not_rule_owned', 'tool_murules'),
                'muprogallocationid' => (int)$allocation->id,
            ];
        }

        $program = $DB->get_record('tool_muprog_program', ['id' => $programid], '*', MUST_EXIST);
        $source = $DB->get_record('tool_muprog_source', ['id' => $allocation->sourceid], '*', MUST_EXIST);

        $classname = \tool_muprog\local\allocation::get_source_classname($source->type);
        if (!$classname || !method_exists($classname, 'allocation_delete')) {
            return ['status' => 'failed', 'reason' => get_string('reason_broken', 'tool_murules')];
        }

        try {
            if (method_exists($classname, 'is_allocation_delete_possible')
                && !$classname::is_allocation_delete_possible($program, $source, $allocation)) {
                // Manual source allows delete even when not archived; others may require archive first.
                if (method_exists($classname, 'allocation_archive')) {
                    $classname::allocation_archive((int)$allocation->id);
                    $allocation = $DB->get_record('tool_muprog_allocation', ['id' => $allocation->id], '*', MUST_EXIST);
                }
            }
            $classname::allocation_delete($program, $source, $allocation, true);
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_deallocate_failed', 'tool_murules', $e->getMessage()),
                'muprogallocationid' => (int)$allocation->id,
            ];
        }

        return [
            'status' => 'applied',
            'reason' => get_string('reason_deallocate_ok', 'tool_murules'),
            'muprogallocationid' => (int)$allocation->id,
        ];
    }

    /**
     * True if murules previously applied an allocate for this allocation/user/program.
     *
     * @param int $allocationid
     * @param int $userid
     * @param int $programid
     * @return bool
     */
    protected function is_rule_owned(int $allocationid, int $userid, int $programid): bool {
        global $DB;
        if ($DB->record_exists('tool_murules_match', [
            'userid' => $userid,
            'muprogallocationid' => $allocationid,
            'status' => 'applied',
        ])) {
            return true;
        }
        // Fallback: any applied allocate_muprog match whose action config targets this program.
        $sql = "SELECT m.id
                  FROM {tool_murules_match} m
                  JOIN {tool_murules_action} a ON a.id = m.actionid
                 WHERE m.userid = :userid
                   AND m.status = 'applied'
                   AND a.actiontype = 'allocate_muprog'
                   AND a.actionconfig LIKE :prog";
        return $DB->record_exists_sql($sql, [
            'userid' => $userid,
            'prog' => '%"programid":' . $programid . '%',
        ]);
    }
}
