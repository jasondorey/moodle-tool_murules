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
 * User has achieved competency (G3).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class competency_achieved extends base {

    public function get_type(): string {
        return 'competency_achieved';
    }

    public function get_name(): string {
        return get_string('condition:competency_achieved', 'tool_murules');
    }

    public function get_category(): string {
        return 'competency';
    }

    public function get_description(array $config): string {
        return get_string('condition:competency_achieved_desc', 'tool_murules', (object)[
            'competency' => $this->competency_name((int)($config['competencyid'] ?? 0)),
        ]);
    }

    public function get_events(): array {
        return [
            \core\event\competency_evidence_created::class,
            \core\event\competency_user_competency_rated::class,
            \core\event\competency_user_competency_rated_in_course::class,
            \core\event\competency_user_competency_rated_in_plan::class,
        ];
    }

    public function is_available(): bool {
        if (!class_exists('\\core_competency\\api') || !\core_competency\api::is_enabled()) {
            return false;
        }
        global $DB;
        return $DB->record_exists('competency_framework', []);
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $options = [0 => get_string('choosedots')] + $this->competency_options();
        $mform->addElement('select', 'ca_competencyid', get_string('competency', 'tool_murules'), $options);
        $mform->addRule('ca_competencyid', get_string('error_nocompetency', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['competencyid'])) {
            $mform->setDefault('ca_competencyid', (int)$config['competencyid']);
        }
    }

    public function validate_config(array $data): array {
        $errors = [];
        $id = (int)($data['ca_competencyid'] ?? 0);
        if (!$id || !$this->competency_exists($id)) {
            $errors['ca_competencyid'] = get_string('error_nocompetency', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'competencyid' => (int)($data['ca_competencyid'] ?? 0),
        ];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $competencyid = (int)($config['competencyid'] ?? 0);
        if (!$competencyid) {
            return false;
        }
        // Proficiency true = achieved.
        return $DB->record_exists('competency_usercomp', [
            'userid' => $userid,
            'competencyid' => $competencyid,
            'proficiency' => 1,
        ]);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $competencyid = (int)($config['competencyid'] ?? 0);
        if (!$competencyid) {
            return [];
        }
        $sql = "SELECT uc.userid
                  FROM {competency_usercomp} uc
                  JOIN {user} u ON u.id = uc.userid AND u.deleted = 0
                 WHERE uc.competencyid = :competencyid
                   AND uc.proficiency = 1
                   AND uc.userid > :fromuserid
              ORDER BY uc.userid ASC";
        $records = $DB->get_records_sql($sql, [
            'competencyid' => $competencyid,
            'fromuserid' => $fromuserid,
        ], 0, $limit);
        return array_map(static fn($r) => (int)$r->userid, $records);
    }

    public function is_broken(array $config): bool {
        $id = (int)($config['competencyid'] ?? 0);
        return !$id || !$this->competency_exists($id);
    }

    /**
     * @return array<int, string>
     */
    protected function competency_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('competency')) {
            return [];
        }
        $sql = "SELECT c.id, c.shortname, c.idnumber, f.shortname AS framework
                  FROM {competency} c
                  JOIN {competency_framework} f ON f.id = c.competencyframeworkid
              ORDER BY f.shortname ASC, c.path ASC, c.shortname ASC";
        $records = $DB->get_records_sql($sql);
        $options = [];
        foreach ($records as $rec) {
            $label = format_string($rec->framework) . ': ' . format_string($rec->shortname);
            if ($rec->idnumber !== '') {
                $label .= ' (' . $rec->idnumber . ')';
            }
            $options[(int)$rec->id] = $label;
        }
        return $options;
    }

    /**
     * @param int $competencyid
     * @return string
     */
    protected function competency_name(int $competencyid): string {
        global $DB;
        if (!$competencyid) {
            return '?';
        }
        $name = $DB->get_field('competency', 'shortname', ['id' => $competencyid]);
        return $name ? format_string($name) : '#'.$competencyid;
    }

    /**
     * @param int $competencyid
     * @return bool
     */
    protected function competency_exists(int $competencyid): bool {
        global $DB;
        return $DB->record_exists('competency', ['id' => $competencyid]);
    }
}
