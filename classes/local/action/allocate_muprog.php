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

/**
 * Allocate user into a MuTMS program via manual source.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class allocate_muprog extends base {

    public function get_type(): string {
        return 'allocate_muprog';
    }

    public function get_name(): string {
        return get_string('action:allocate_muprog', 'tool_murules');
    }

    public function get_description(array $config): string {
        $name = $this->resolve_program_name($config);
        return get_string('action:allocate_muprog_desc', 'tool_murules', $name);
    }

    public function is_available(): bool {
        return class_exists(\tool_muprog\local\source\manual::class);
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $programs = $this->get_program_options();
        if (!$programs) {
            $mform->addElement('static', 'am_noprograms', get_string('program', 'tool_murules'),
                get_string('noprograms_manual', 'tool_murules'));
            $mform->addElement('hidden', 'am_programid', 0);
            $mform->setType('am_programid', PARAM_INT);
            return;
        }
        $options = [0 => get_string('choosedots')] + $programs;
        $mform->addElement('select', 'am_programid', get_string('program', 'tool_murules'), $options);
        $mform->addRule('am_programid', get_string('error_noprogram', 'tool_murules'), 'required', null, 'client');
        $mform->addHelpButton('am_programid', 'program_allocate', 'tool_murules');
        if (!empty($config['programid'])) {
            $mform->setDefault('am_programid', $config['programid']);
        }
    }

    public function validate_config(array $data): array {
        global $DB;
        $errors = [];
        $programid = (int)($data['am_programid'] ?? 0);
        if (!$programid) {
            $errors['am_programid'] = get_string('error_noprogram', 'tool_murules');
            return $errors;
        }
        $program = $DB->get_record('tool_muprog_program', ['id' => $programid, 'archived' => 0]);
        if (!$program) {
            $errors['am_programid'] = get_string('error_noprogram', 'tool_murules');
            return $errors;
        }
        $source = $DB->get_record('tool_muprog_source', [
            'programid' => $programid,
            'type' => 'manual',
        ]);
        if (!$source) {
            $errors['am_programid'] = get_string('error_nosource', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        global $DB;
        $programid = (int)($data['am_programid'] ?? 0);
        $sourceid = 0;
        if ($programid) {
            $sourceid = (int)$DB->get_field('tool_muprog_source', 'id', [
                'programid' => $programid,
                'type' => 'manual',
            ]);
        }
        return [
            'programid' => $programid,
            'sourceid' => $sourceid,
            'dateoverrides' => [],
        ];
    }

    public function is_broken(array $config): bool {
        global $DB;
        $programid = (int)($config['programid'] ?? 0);
        $sourceid = (int)($config['sourceid'] ?? 0);
        if (!$programid || !$sourceid) {
            return true;
        }
        if (!$DB->record_exists('tool_muprog_program', ['id' => $programid, 'archived' => 0])) {
            return true;
        }
        return !$DB->record_exists('tool_muprog_source', [
            'id' => $sourceid,
            'programid' => $programid,
            'type' => 'manual',
        ]);
    }

    public function execute(int $userid, array $config, stdClass $rule, stdClass $actionrec): array {
        global $DB;

        $programid = (int)($config['programid'] ?? 0);
        $sourceid = (int)($config['sourceid'] ?? 0);
        $dateoverrides = is_array($config['dateoverrides'] ?? null) ? $config['dateoverrides'] : [];

        if ($this->is_broken($config)) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_broken', 'tool_murules'),
            ];
        }

        if ($DB->record_exists('tool_muprog_allocation', ['programid' => $programid, 'userid' => $userid])) {
            $existing = $DB->get_field('tool_muprog_allocation', 'id', [
                'programid' => $programid,
                'userid' => $userid,
            ]);
            return [
                'status' => 'skipped',
                'reason' => get_string('reason_already_allocated', 'tool_murules'),
                'muprogallocationid' => $existing ? (int)$existing : null,
            ];
        }

        try {
            $ids = \tool_muprog\local\source\manual::allocate_users(
                $programid,
                $sourceid,
                [$userid],
                $dateoverrides
            );
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_allocate_failed', 'tool_murules', $e->getMessage()),
            ];
        }

        if (!$ids) {
            // Race: already allocated between check and call.
            $existing = $DB->get_field('tool_muprog_allocation', 'id', [
                'programid' => $programid,
                'userid' => $userid,
            ]);
            return [
                'status' => 'skipped',
                'reason' => get_string('reason_no_allocation_returned', 'tool_murules'),
                'muprogallocationid' => $existing ? (int)$existing : null,
            ];
        }

        return [
            'status' => 'applied',
            'reason' => get_string('reason_allocate_ok', 'tool_murules'),
            'muprogallocationid' => (int)reset($ids),
        ];
    }

    /**
     * Programs that have a manual source.
     *
     * @return array<int, string>
     */
    protected function get_program_options(): array {
        global $DB;
        $sql = "SELECT p.id, p.fullname
                  FROM {tool_muprog_program} p
                  JOIN {tool_muprog_source} s ON s.programid = p.id AND s.type = 'manual'
                 WHERE p.archived = 0
              ORDER BY p.fullname ASC";
        $records = $DB->get_records_sql($sql);
        $options = [];
        foreach ($records as $rec) {
            $options[(int)$rec->id] = format_string($rec->fullname);
        }
        return $options;
    }

    /**
     * @param array $config
     * @return string
     */
    protected function resolve_program_name(array $config): string {
        global $DB;
        $programid = (int)($config['programid'] ?? 0);
        if (!$programid) {
            return '?';
        }
        $name = $DB->get_field('tool_muprog_program', 'fullname', ['id' => $programid]);
        return $name ? format_string($name) : '#'.$programid;
    }
}
