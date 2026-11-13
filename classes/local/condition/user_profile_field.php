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
 * User profile field (G12) — standard + custom fields.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_profile_field extends base {

    /** @var string[] Standard user table fields offered in the wizard. */
    protected const STANDARD_FIELDS = [
        'firstname', 'lastname', 'email', 'city', 'country', 'department',
        'institution', 'idnumber', 'phone1', 'phone2', 'address', 'auth', 'lang',
    ];

    public function get_type(): string {
        return 'user_profile_field';
    }

    public function get_name(): string {
        return get_string('condition:user_profile_field', 'tool_murules');
    }

    public function get_description(array $config): string {
        $field = (string)($config['fieldid'] ?? '');
        $op = (string)($config['op'] ?? 'eq');
        return get_string('condition:user_profile_field_desc', 'tool_murules', (object)[
            'field' => $this->field_label($field),
            'op' => get_string('profile_op_' . $op, 'tool_murules'),
            'value' => (string)($config['value'] ?? ''),
        ]);
    }

    public function get_events(): array {
        return [
            \core\event\user_updated::class,
            \core\event\user_created::class,
        ];
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        $mform->addElement('select', 'upf_fieldid', get_string('profile_field', 'tool_murules'), $this->field_options());
        $mform->addRule('upf_fieldid', get_string('error_noprofilefield', 'tool_murules'), 'required', null, 'client');
        if (!empty($config['fieldid'])) {
            $mform->setDefault('upf_fieldid', $config['fieldid']);
        }

        $ops = [
            'eq' => get_string('profile_op_eq', 'tool_murules'),
            'neq' => get_string('profile_op_neq', 'tool_murules'),
            'contains' => get_string('profile_op_contains', 'tool_murules'),
            'notcontains' => get_string('profile_op_notcontains', 'tool_murules'),
            'isempty' => get_string('profile_op_isempty', 'tool_murules'),
            'isnotempty' => get_string('profile_op_isnotempty', 'tool_murules'),
            'startswith' => get_string('profile_op_startswith', 'tool_murules'),
            'endswith' => get_string('profile_op_endswith', 'tool_murules'),
        ];
        $mform->addElement('select', 'upf_op', get_string('operator', 'tool_murules'), $ops);
        $mform->setDefault('upf_op', $config['op'] ?? 'eq');

        $mform->addElement('text', 'upf_value', get_string('profile_value', 'tool_murules'), ['size' => 40]);
        $mform->setType('upf_value', PARAM_RAW_TRIMMED);
        $mform->setDefault('upf_value', $config['value'] ?? '');
        $mform->disabledIf('upf_value', 'upf_op', 'eq', 'isempty');
        $mform->disabledIf('upf_value', 'upf_op', 'eq', 'isnotempty');
    }

    public function validate_config(array $data): array {
        $errors = [];
        $field = (string)($data['upf_fieldid'] ?? '');
        if ($field === '' || !isset($this->field_options()[$field])) {
            $errors['upf_fieldid'] = get_string('error_noprofilefield', 'tool_murules');
        }
        $op = (string)($data['upf_op'] ?? 'eq');
        if (!in_array($op, ['isempty', 'isnotempty'], true)
            && (!isset($data['upf_value']) || $data['upf_value'] === '')) {
            $errors['upf_value'] = get_string('error_profile_value', 'tool_murules');
        }
        return $errors;
    }

    public function extract_config(array $data): array {
        return [
            'fieldid' => (string)($data['upf_fieldid'] ?? ''),
            'op' => (string)($data['upf_op'] ?? 'eq'),
            'value' => (string)($data['upf_value'] ?? ''),
        ];
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        $field = (string)($config['fieldid'] ?? '');
        $op = (string)($config['op'] ?? 'eq');
        $expected = (string)($config['value'] ?? '');
        $actual = $this->get_field_value($userid, $field);
        return $this->compare_values($actual, $op, $expected);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $field = (string)($config['fieldid'] ?? '');
        $op = (string)($config['op'] ?? 'eq');
        $expected = (string)($config['value'] ?? '');
        $guestid = (int)guest_user()->id;

        if (str_starts_with($field, 'profile_field_')) {
            $shortname = substr($field, strlen('profile_field_'));
            $fieldid = $DB->get_field('user_info_field', 'id', ['shortname' => $shortname]);
            if (!$fieldid) {
                return [];
            }
            // Candidate set from custom field data (or all users for empty checks).
            if (in_array($op, ['isempty', 'neq', 'notcontains'], true)) {
                $sql = "SELECT u.id
                          FROM {user} u
                         WHERE u.deleted = 0
                           AND u.id > :fromuserid
                           AND u.id <> :guestid
                      ORDER BY u.id ASC";
                $records = $DB->get_records_sql($sql, [
                    'fromuserid' => $fromuserid,
                    'guestid' => $guestid,
                ], 0, max($limit * 5, 500));
            } else {
                $sql = "SELECT u.id
                          FROM {user} u
                          JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = :fieldid
                         WHERE u.deleted = 0
                           AND u.id > :fromuserid
                           AND u.id <> :guestid
                      ORDER BY u.id ASC";
                $records = $DB->get_records_sql($sql, [
                    'fieldid' => $fieldid,
                    'fromuserid' => $fromuserid,
                    'guestid' => $guestid,
                ], 0, max($limit * 5, 500));
            }
        } else {
            if (!in_array($field, self::STANDARD_FIELDS, true)) {
                return [];
            }
            $sql = "SELECT u.id
                      FROM {user} u
                     WHERE u.deleted = 0
                       AND u.id > :fromuserid
                       AND u.id <> :guestid
                  ORDER BY u.id ASC";
            $records = $DB->get_records_sql($sql, [
                'fromuserid' => $fromuserid,
                'guestid' => $guestid,
            ], 0, max($limit * 5, 500));
        }

        $matched = [];
        foreach ($records as $rec) {
            $userid = (int)$rec->id;
            $actual = $this->get_field_value($userid, $field);
            if ($this->compare_values($actual, $op, $expected)) {
                $matched[] = $userid;
                if (count($matched) >= $limit) {
                    break;
                }
            }
        }
        return $matched;
    }

    public function is_broken(array $config): bool {
        $field = (string)($config['fieldid'] ?? '');
        if ($field === '') {
            return true;
        }
        if (str_starts_with($field, 'profile_field_')) {
            global $DB;
            $shortname = substr($field, strlen('profile_field_'));
            return !$DB->record_exists('user_info_field', ['shortname' => $shortname]);
        }
        return !in_array($field, self::STANDARD_FIELDS, true);
    }

    /**
     * @return array<string, string>
     */
    protected function field_options(): array {
        global $DB;
        $options = [];
        foreach (self::STANDARD_FIELDS as $field) {
            $options[$field] = get_string($field) . ' (' . get_string('profile_standard', 'tool_murules') . ')';
        }
        $customs = $DB->get_records('user_info_field', null, 'name ASC', 'id, shortname, name, visible');
        foreach ($customs as $field) {
            // Workplace note: custom fields with visibility "All" for matching — include all for site-admin rules.
            $key = 'profile_field_' . $field->shortname;
            $options[$key] = format_string($field->name) . ' (' . get_string('profile_custom', 'tool_murules') . ')';
        }
        return $options;
    }

    /**
     * @param string $field
     * @return string
     */
    protected function field_label(string $field): string {
        $options = $this->field_options();
        return $options[$field] ?? $field;
    }

    /**
     * @param int $userid
     * @param string $field
     * @return string
     */
    protected function get_field_value(int $userid, string $field): string {
        global $DB;
        if (str_starts_with($field, 'profile_field_')) {
            $shortname = substr($field, strlen('profile_field_'));
            $sql = "SELECT d.data
                      FROM {user_info_data} d
                      JOIN {user_info_field} f ON f.id = d.fieldid
                     WHERE d.userid = :userid AND f.shortname = :shortname";
            $val = $DB->get_field_sql($sql, ['userid' => $userid, 'shortname' => $shortname]);
            return $val === false ? '' : (string)$val;
        }
        if (!in_array($field, self::STANDARD_FIELDS, true)) {
            return '';
        }
        $val = $DB->get_field('user', $field, ['id' => $userid]);
        return $val === false || $val === null ? '' : (string)$val;
    }

    /**
     * @param string $actual
     * @param string $op
     * @param string $expected
     * @return bool
     */
    protected function compare_values(string $actual, string $op, string $expected): bool {
        $a = trim($actual);
        $e = trim($expected);
        $al = \core_text::strtolower($a);
        $el = \core_text::strtolower($e);
        return match ($op) {
            'neq' => $al !== $el,
            'contains' => $e !== '' && \core_text::strpos($al, $el) !== false,
            'notcontains' => $e === '' || \core_text::strpos($al, $el) === false,
            'isempty' => $a === '',
            'isnotempty' => $a !== '',
            'startswith' => $e !== '' && \core_text::strpos($al, $el) === 0,
            'endswith' => $e !== '' && \core_text::strlen($al) >= \core_text::strlen($el)
                && \core_text::substr($al, -\core_text::strlen($el)) === $el,
            default => $al === $el,
        };
    }
}
