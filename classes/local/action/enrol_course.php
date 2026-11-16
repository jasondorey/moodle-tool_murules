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

use context_course;
use MoodleQuickForm;
use stdClass;
use tool_murules\local\condition\helper;

/**
 * Enrol user into a course via the manual enrolment method.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_course extends base {

    public function get_type(): string {
        return 'enrol_course';
    }

    public function get_name(): string {
        return get_string('action:enrol_course', 'tool_murules');
    }

    public function get_description(array $config): string {
        $course = helper::course_name((int)($config['courseid'] ?? 0));
        $role = $this->resolve_role_name((int)($config['roleid'] ?? 0));
        return get_string('action:enrol_course_desc', 'tool_murules', (object)[
            'course' => $course,
            'role' => $role,
        ]);
    }

    public function is_available(): bool {
        return enrol_is_enabled('manual') && enrol_get_plugin('manual') !== null;
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('course', 'ec_courseid', get_string('course', 'tool_murules'), [
            'multiple' => false,
            'includefrontpage' => false,
        ]);
        $mform->addRule('ec_courseid', get_string('error_nocourse', 'tool_murules'), 'required', null, 'client');
        $mform->addHelpButton('ec_courseid', 'action:enrol_course_course', 'tool_murules');
        if (!empty($config['courseid'])) {
            $mform->setDefault('ec_courseid', [(int)$config['courseid']]);
        }

        $roles = $this->role_options();
        $mform->addElement('select', 'ec_roleid', get_string('enrol_role', 'tool_murules'), $roles);
        $mform->addHelpButton('ec_roleid', 'enrol_role', 'tool_murules');
        $defaultrole = (int)($config['roleid'] ?? 0);
        if (!$defaultrole) {
            $defaultrole = $this->default_student_roleid();
        }
        $mform->setDefault('ec_roleid', $defaultrole);
    }

    public function validate_config(array $data): array {
        global $DB;
        $errors = [];
        $courseid = helper::normalize_id($data['ec_courseid'] ?? 0);
        if (!$courseid || $courseid === (int)SITEID || !$DB->record_exists('course', ['id' => $courseid])) {
            $errors['ec_courseid'] = get_string('error_nocourse', 'tool_murules');
        }
        $roleid = (int)($data['ec_roleid'] ?? 0);
        if ($roleid && !isset($this->role_options()[$roleid])) {
            $errors['ec_roleid'] = get_string('error_norole', 'tool_murules');
        }
        if (!enrol_is_enabled('manual') || !enrol_get_plugin('manual')) {
            $errors['ec_courseid'] = get_string('error_nomanualenrol', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        $roleid = (int)($data['ec_roleid'] ?? 0);
        if (!$roleid) {
            $roleid = $this->default_student_roleid();
        }
        return [
            'courseid' => helper::normalize_id($data['ec_courseid'] ?? 0),
            'enrolmethod' => 'manual',
            'roleid' => $roleid,
        ];
    }

    public function is_broken(array $config): bool {
        global $DB;
        $courseid = (int)($config['courseid'] ?? 0);
        if (!$courseid || $courseid === (int)SITEID) {
            return true;
        }
        if (!$DB->record_exists('course', ['id' => $courseid])) {
            return true;
        }
        if (!enrol_is_enabled('manual') || !enrol_get_plugin('manual')) {
            return true;
        }
        $roleid = (int)($config['roleid'] ?? 0);
        if ($roleid && !$DB->record_exists('role', ['id' => $roleid])) {
            return true;
        }
        return false;
    }

    public function execute(int $userid, array $config, stdClass $rule, stdClass $actionrec): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/enrollib.php');

        $courseid = (int)($config['courseid'] ?? 0);
        $roleid = (int)($config['roleid'] ?? 0);
        if (!$roleid) {
            $roleid = $this->default_student_roleid();
        }

        if ($this->is_broken($config)) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_broken', 'tool_murules'),
            ];
        }

        $context = context_course::instance($courseid, IGNORE_MISSING);
        if (!$context) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_broken', 'tool_murules'),
            ];
        }

        if (is_enrolled($context, $userid, '', false)) {
            return [
                'status' => 'skipped',
                'reason' => get_string('reason_already_enrolled', 'tool_murules'),
            ];
        }

        $plugin = enrol_get_plugin('manual');
        if (!$plugin) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_enrol_failed', 'tool_murules',
                    get_string('error_nomanualenrol', 'tool_murules')),
            ];
        }

        try {
            $instance = $this->ensure_manual_instance($courseid, $roleid);
            $plugin->enrol_user($instance, $userid, $roleid);
        } catch (\Throwable $e) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_enrol_failed', 'tool_murules', $e->getMessage()),
            ];
        }

        if (!is_enrolled($context, $userid, '', false)) {
            return [
                'status' => 'failed',
                'reason' => get_string('reason_enrol_failed', 'tool_murules',
                    get_string('reason_enrol_not_active', 'tool_murules')),
            ];
        }

        return [
            'status' => 'applied',
            'reason' => get_string('reason_enrol_ok', 'tool_murules'),
        ];
    }

    /**
     * Find or create an enabled manual enrolment instance for the course.
     *
     * @param int $courseid
     * @param int $roleid
     * @return stdClass
     */
    protected function ensure_manual_instance(int $courseid, int $roleid): stdClass {
        global $DB;

        $instances = enrol_get_instances($courseid, true);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                return $instance;
            }
        }

        // Prefer enabling an existing disabled manual instance over creating another.
        $disabled = $DB->get_record('enrol', [
            'courseid' => $courseid,
            'enrol' => 'manual',
        ], '*', IGNORE_MULTIPLE);
        if ($disabled) {
            $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, ['id' => $disabled->id]);
            $disabled->status = ENROL_INSTANCE_ENABLED;
            return $disabled;
        }

        $course = get_course($courseid);
        $plugin = enrol_get_plugin('manual');
        $fields = [
            'status' => ENROL_INSTANCE_ENABLED,
            'roleid' => $roleid ?: $this->default_student_roleid(),
        ];
        $enrolid = $plugin->add_instance($course, $fields);
        return $DB->get_record('enrol', ['id' => $enrolid], '*', MUST_EXIST);
    }

    /**
     * @return array<int, string>
     */
    protected function role_options(): array {
        $context = context_course::instance(SITEID);
        $roles = get_default_enrol_roles($context);
        if (!$roles) {
            $student = $this->default_student_roleid();
            if ($student) {
                $roles = [$student => role_get_name((object)['id' => $student], $context)];
            }
        }
        return $roles;
    }

    /**
     * @return int
     */
    protected function default_student_roleid(): int {
        $configured = (int)get_config('enrol_manual', 'roleid');
        if ($configured) {
            return $configured;
        }
        $student = get_archetype_roles('student');
        if ($student) {
            $first = reset($student);
            return (int)$first->id;
        }
        return 0;
    }

    /**
     * @param int $roleid
     * @return string
     */
    protected function resolve_role_name(int $roleid): string {
        if (!$roleid) {
            $roleid = $this->default_student_roleid();
        }
        if (!$roleid) {
            return get_string('enrol_role_student_default', 'tool_murules');
        }
        $roles = $this->role_options();
        if (isset($roles[$roleid])) {
            return $roles[$roleid];
        }
        $name = role_get_name((object)['id' => $roleid]);
        return $name !== '' ? $name : '#'.$roleid;
    }
}
