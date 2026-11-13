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
 * Shared helpers for general condition classes.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class helper {

    /**
     * Normalize course autocomplete / scalar into an int id.
     *
     * @param mixed $value
     * @return int
     */
    public static function normalize_id($value): int {
        if (is_array($value)) {
            $value = reset($value);
        }
        return (int)$value;
    }

    /**
     * Seconds for a relative amount + unit.
     *
     * @param int $amount
     * @param string $unit days|hours|weeks
     * @return int
     */
    public static function relative_seconds(int $amount, string $unit): int {
        $amount = max(0, $amount);
        return match ($unit) {
            'hours' => $amount * HOURSECS,
            'weeks' => $amount * WEEKSECS,
            default => $amount * DAYSECS,
        };
    }

    /**
     * Evaluate a timestamp against a time-window config.
     *
     * Supported modes: ever, never, before, after, during_last, prior_to_last, between.
     *
     * @param int|null $timestamp null = never happened
     * @param array $window keys: mode, datetime, datetime_to, amount, unit
     * @param int|null $now
     * @return bool
     */
    public static function time_matches(?int $timestamp, array $window, ?int $now = null): bool {
        $now = $now ?? time();
        $mode = (string)($window['mode'] ?? 'ever');
        $has = $timestamp !== null && $timestamp > 0;

        return match ($mode) {
            'never' => !$has,
            'ever' => $has,
            'before' => $has && $timestamp < (int)($window['datetime'] ?? 0),
            'after' => $has && $timestamp >= (int)($window['datetime'] ?? 0),
            'between' => $has
                && $timestamp >= (int)($window['datetime'] ?? 0)
                && $timestamp <= (int)($window['datetime_to'] ?? 0),
            'during_last' => $has && $timestamp >= ($now - self::relative_seconds(
                (int)($window['amount'] ?? 0),
                (string)($window['unit'] ?? 'days')
            )),
            'prior_to_last' => $has && $timestamp < ($now - self::relative_seconds(
                (int)($window['amount'] ?? 0),
                (string)($window['unit'] ?? 'days')
            )),
            default => false,
        };
    }

    /**
     * Add common time-window form elements with a field prefix.
     *
     * @param MoodleQuickForm $mform
     * @param string $prefix e.g. cla_
     * @param array $config
     * @param string[] $modes subset of modes to offer
     * @param string $defaultmode
     */
    public static function add_time_window_elements(
        MoodleQuickForm $mform,
        string $prefix,
        array $config,
        array $modes,
        string $defaultmode = 'ever'
    ): void {
        $options = [];
        foreach ($modes as $mode) {
            $options[$mode] = get_string('timewindow_' . $mode, 'tool_murules');
        }
        $mform->addElement('select', $prefix . 'mode', get_string('timewindow', 'tool_murules'), $options);
        $mform->setDefault($prefix . 'mode', $config['mode'] ?? $defaultmode);

        $mform->addElement('date_time_selector', $prefix . 'datetime', get_string('timewindow_datetime', 'tool_murules'), [
            'optional' => true,
        ]);
        if (!empty($config['datetime'])) {
            $mform->setDefault($prefix . 'datetime', (int)$config['datetime']);
        }
        $mform->hideIf($prefix . 'datetime', $prefix . 'mode', 'neq', 'before');
        // Also show for after — Moodle hideIf is single; use disabledIf pairs via second element clone.
        $mform->addElement('date_time_selector', $prefix . 'datetime_after', get_string('timewindow_datetime_after', 'tool_murules'), [
            'optional' => true,
        ]);
        if (!empty($config['datetime']) && ($config['mode'] ?? '') === 'after') {
            $mform->setDefault($prefix . 'datetime_after', (int)$config['datetime']);
        }
        $mform->hideIf($prefix . 'datetime_after', $prefix . 'mode', 'neq', 'after');

        $mform->addElement('date_time_selector', $prefix . 'datetime_from', get_string('timewindow_from', 'tool_murules'), [
            'optional' => true,
        ]);
        $mform->addElement('date_time_selector', $prefix . 'datetime_to', get_string('timewindow_to', 'tool_murules'), [
            'optional' => true,
        ]);
        if (!empty($config['datetime'])) {
            $mform->setDefault($prefix . 'datetime_from', (int)$config['datetime']);
        }
        if (!empty($config['datetime_to'])) {
            $mform->setDefault($prefix . 'datetime_to', (int)$config['datetime_to']);
        }
        $mform->hideIf($prefix . 'datetime_from', $prefix . 'mode', 'neq', 'between');
        $mform->hideIf($prefix . 'datetime_to', $prefix . 'mode', 'neq', 'between');

        $mform->addElement('text', $prefix . 'amount', get_string('timewindow_amount', 'tool_murules'));
        $mform->setType($prefix . 'amount', PARAM_INT);
        $mform->setDefault($prefix . 'amount', $config['amount'] ?? 7);
        $mform->addElement('select', $prefix . 'unit', get_string('timewindow_unit', 'tool_murules'), [
            'hours' => get_string('timewindow_unit_hours', 'tool_murules'),
            'days' => get_string('timewindow_unit_days', 'tool_murules'),
            'weeks' => get_string('timewindow_unit_weeks', 'tool_murules'),
        ]);
        $mform->setDefault($prefix . 'unit', $config['unit'] ?? 'days');
        foreach (['during_last', 'prior_to_last'] as $rel) {
            // hideIf only supports one condition; leave relative fields always visible when either relative mode.
        }
        $mform->hideIf($prefix . 'amount', $prefix . 'mode', 'in', ['ever', 'never', 'before', 'after', 'between']);
        $mform->hideIf($prefix . 'unit', $prefix . 'mode', 'in', ['ever', 'never', 'before', 'after', 'between']);
    }

    /**
     * Extract time-window config from form data.
     *
     * @param array $data
     * @param string $prefix
     * @return array
     */
    public static function extract_time_window(array $data, string $prefix): array {
        $mode = (string)($data[$prefix . 'mode'] ?? 'ever');
        $datetime = 0;
        $datetimeTo = 0;
        if ($mode === 'before') {
            $datetime = (int)($data[$prefix . 'datetime'] ?? 0);
        } else if ($mode === 'after') {
            $datetime = (int)($data[$prefix . 'datetime_after'] ?? 0);
        } else if ($mode === 'between') {
            $datetime = (int)($data[$prefix . 'datetime_from'] ?? 0);
            $datetimeTo = (int)($data[$prefix . 'datetime_to'] ?? 0);
        }
        return [
            'mode' => $mode,
            'datetime' => $datetime,
            'datetime_to' => $datetimeTo,
            'amount' => (int)($data[$prefix . 'amount'] ?? 0),
            'unit' => (string)($data[$prefix . 'unit'] ?? 'days'),
        ];
    }

    /**
     * Validate time-window form fields.
     *
     * @param array $data
     * @param string $prefix
     * @return array field => error
     */
    public static function validate_time_window(array $data, string $prefix): array {
        $errors = [];
        $mode = (string)($data[$prefix . 'mode'] ?? '');
        if (in_array($mode, ['during_last', 'prior_to_last'], true)) {
            $amount = (int)($data[$prefix . 'amount'] ?? 0);
            if ($amount <= 0) {
                $errors[$prefix . 'amount'] = get_string('error_timewindow_amount', 'tool_murules');
            }
        }
        if ($mode === 'before' && empty($data[$prefix . 'datetime'])) {
            $errors[$prefix . 'datetime'] = get_string('error_timewindow_datetime', 'tool_murules');
        }
        if ($mode === 'after' && empty($data[$prefix . 'datetime_after'])) {
            $errors[$prefix . 'datetime_after'] = get_string('error_timewindow_datetime', 'tool_murules');
        }
        if ($mode === 'between') {
            $from = (int)($data[$prefix . 'datetime_from'] ?? 0);
            $to = (int)($data[$prefix . 'datetime_to'] ?? 0);
            if (!$from || !$to || $to < $from) {
                $errors[$prefix . 'datetime_from'] = get_string('error_timewindow_range', 'tool_murules');
            }
        }
        return $errors;
    }

    /**
     * Human label for a time window.
     *
     * @param array $window
     * @return string
     */
    public static function describe_time_window(array $window): string {
        $mode = (string)($window['mode'] ?? 'ever');
        $a = (object)[
            'amount' => (int)($window['amount'] ?? 0),
            'unit' => get_string('timewindow_unit_' . ($window['unit'] ?? 'days'), 'tool_murules'),
            'datetime' => !empty($window['datetime']) ? userdate((int)$window['datetime']) : '',
            'datetime_to' => !empty($window['datetime_to']) ? userdate((int)$window['datetime_to']) : '',
        ];
        return get_string('timewindow_desc_' . $mode, 'tool_murules', $a);
    }

    /**
     * Cohort menu id => name.
     *
     * @return array<int, string>
     */
    public static function cohort_options(): array {
        global $DB;
        $records = $DB->get_records('cohort', null, 'name ASC', 'id, name, idnumber');
        $options = [];
        foreach ($records as $rec) {
            $label = format_string($rec->name);
            if ($rec->idnumber !== '') {
                $label .= ' (' . $rec->idnumber . ')';
            }
            $options[(int)$rec->id] = $label;
        }
        return $options;
    }

    /**
     * Enrol method menu for a course (plus "any").
     *
     * @param int $courseid
     * @return array<string, string>
     */
    public static function enrol_method_options(int $courseid = 0): array {
        $options = ['any' => get_string('enrolmethod_any', 'tool_murules')];
        $plugins = enrol_get_plugins(true);
        foreach ($plugins as $name => $plugin) {
            $options[$name] = get_string('pluginname', 'enrol_' . $name);
        }
        return $options;
    }

    /**
     * Course display name.
     *
     * @param int $courseid
     * @return string
     */
    public static function course_name(int $courseid): string {
        global $DB;
        if (!$courseid) {
            return '?';
        }
        $name = $DB->get_field('course', 'fullname', ['id' => $courseid]);
        return $name ? format_string($name) : '#'.$courseid;
    }

    /**
     * Cohort display name.
     *
     * @param int $cohortid
     * @return string
     */
    public static function cohort_name(int $cohortid): string {
        global $DB;
        if (!$cohortid) {
            return '?';
        }
        $name = $DB->get_field('cohort', 'name', ['id' => $cohortid]);
        return $name ? format_string($name) : '#'.$cohortid;
    }

    /**
     * Program menu (non-archived).
     *
     * @return array<int, string>
     */
    public static function program_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_muprog_program')) {
            return [];
        }
        $records = $DB->get_records('tool_muprog_program', ['archived' => 0], 'fullname ASC', 'id, fullname');
        $options = [];
        foreach ($records as $rec) {
            $options[(int)$rec->id] = format_string($rec->fullname);
        }
        return $options;
    }

    /**
     * @param int $programid
     * @return string
     */
    public static function program_name(int $programid): string {
        global $DB;
        if (!$programid) {
            return '?';
        }
        $name = $DB->get_field('tool_muprog_program', 'fullname', ['id' => $programid]);
        return $name ? format_string($name) : '#'.$programid;
    }

    /**
     * Certification menu (non-archived).
     *
     * @return array<int, string>
     */
    public static function certification_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_mucertify_certification')) {
            return [];
        }
        $records = $DB->get_records('tool_mucertify_certification', ['archived' => 0], 'fullname ASC', 'id, fullname');
        $options = [];
        foreach ($records as $rec) {
            $options[(int)$rec->id] = format_string($rec->fullname);
        }
        return $options;
    }

    /**
     * @param int $certificationid
     * @return string
     */
    public static function certification_name(int $certificationid): string {
        global $DB;
        if (!$certificationid) {
            return '?';
        }
        $name = $DB->get_field('tool_mucertify_certification', 'fullname', ['id' => $certificationid]);
        return $name ? format_string($name) : '#'.$certificationid;
    }

    /**
     * Tenant menu (non-archived).
     *
     * @return array<int, string>
     */
    public static function tenant_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_mutenancy_tenant')) {
            return [];
        }
        $records = $DB->get_records('tool_mutenancy_tenant', ['archived' => 0], 'name ASC', 'id, name, idnumber');
        $options = [];
        foreach ($records as $rec) {
            $label = format_string($rec->name);
            if ($rec->idnumber !== '') {
                $label .= ' (' . $rec->idnumber . ')';
            }
            $options[(int)$rec->id] = $label;
        }
        return $options;
    }

    /**
     * @param int $tenantid
     * @return string
     */
    public static function tenant_name(int $tenantid): string {
        global $DB;
        if (!$tenantid) {
            return '?';
        }
        $name = $DB->get_field('tool_mutenancy_tenant', 'name', ['id' => $tenantid]);
        return $name ? format_string($name) : '#'.$tenantid;
    }

    /**
     * Relation framework menu.
     *
     * @return array<int, string>
     */
    public static function framework_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_murelation_framework')) {
            return [];
        }
        $records = $DB->get_records('tool_murelation_framework', null, 'name ASC', 'id, name, idnumber');
        $options = [];
        foreach ($records as $rec) {
            $label = format_string($rec->name);
            if ($rec->idnumber !== '') {
                $label .= ' (' . $rec->idnumber . ')';
            }
            $options[(int)$rec->id] = $label;
        }
        return $options;
    }

    /**
     * @param int $frameworkid
     * @return string
     */
    public static function framework_name(int $frameworkid): string {
        global $DB;
        if (!$frameworkid) {
            return '?';
        }
        $name = $DB->get_field('tool_murelation_framework', 'name', ['id' => $frameworkid]);
        return $name ? format_string($name) : '#'.$frameworkid;
    }
}
