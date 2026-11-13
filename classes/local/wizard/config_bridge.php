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

namespace tool_murules\local\wizard;

use tool_murules\local\registry;

/**
 * Bridge React canonical config ↔ Moodle form field names for validate/extract.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class config_bridge {

    /**
     * Map canonical editor config into form-shaped data for existing validate/extract.
     *
     * @param string $kind condition|action
     * @param string $type
     * @param array $config
     * @return array
     */
    public static function to_form_data(string $kind, string $type, array $config): array {
        $map = self::field_map($kind, $type);
        $data = [];
        foreach ($map as $canonical => $formname) {
            if (array_key_exists($canonical, $config)) {
                $data[$formname] = $config[$canonical];
            }
        }
        // Time-window specials: form uses mode-specific datetime field names.
        if (isset($config['mode'], $config['datetime'])) {
            foreach ($map as $canonical => $formname) {
                if ($canonical !== 'datetime' || !str_ends_with($formname, 'datetime')) {
                    continue;
                }
                $prefix = substr($formname, 0, -strlen('datetime'));
                if ($config['mode'] === 'after') {
                    $data[$prefix . 'datetime_after'] = $config['datetime'];
                } else if ($config['mode'] === 'between') {
                    $data[$prefix . 'datetime_from'] = $config['datetime'];
                }
            }
        }
        return $data;
    }

    /**
     * Validate + normalize config via existing condition/action classes.
     *
     * @param string $kind
     * @param string $type
     * @param array $config
     * @return array{ok:bool, errors:array<string,string>, config:array}
     */
    public static function validate_and_extract(string $kind, string $type, array $config): array {
        if ($kind === 'condition') {
            $instance = registry::get_condition($type);
        } else {
            $instance = registry::get_action($type);
        }
        if (!$instance || !$instance->is_available()) {
            return [
                'ok' => false,
                'errors' => ['_type' => get_string('error_condition', 'tool_murules')],
                'config' => [],
            ];
        }

        $formdata = self::to_form_data($kind, $type, $config);
        $rawerrors = $instance->validate_config($formdata);
        $errors = [];
        $inversemap = array_flip(self::field_map($kind, $type));
        foreach ($rawerrors as $formfield => $message) {
            $errors[$inversemap[$formfield] ?? $formfield] = $message;
        }
        if ($errors) {
            return ['ok' => false, 'errors' => $errors, 'config' => []];
        }
        $extracted = $instance->extract_config($formdata);
        return ['ok' => true, 'errors' => [], 'config' => $extracted];
    }

    /**
     * Canonical key => form field name.
     *
     * @param string $kind
     * @param string $type
     * @return array<string,string>
     */
    public static function field_map(string $kind, string $type): array {
        if ($kind === 'action') {
            return match ($type) {
                'allocate_muprog' => ['programid' => 'am_programid'],
                'deallocate_muprog' => ['programid' => 'dm_programid'],
                'cohort_add' => ['cohortid' => 'ca_cohortid'],
                'cohort_remove' => ['cohortid' => 'cr_cohortid'],
                'notify_user' => ['subject' => 'nu_subject', 'body' => 'nu_body'],
                default => [],
            };
        }

        return match ($type) {
            'user_enrolled' => [
                'courseid' => 'ue_courseid',
                'enrolmethod' => 'ue_enrolmethod',
                'start_on_after' => 'ue_start_on_after',
            ],
            'user_not_enrolled' => [
                'courseid' => 'une_courseid',
                'enrolmethod' => 'une_enrolmethod',
            ],
            'course_completed' => [
                'courseid' => 'cc_courseid',
                'completion_when' => 'cc_completion_when',
                'completion_after' => 'cc_completion_after',
                'completion_before' => 'cc_completion_before',
                'grade_op' => 'cc_grade_op',
                'grade_lt' => 'cc_grade_lt',
                'grade_gt' => 'cc_grade_gt',
                'grade_from' => 'cc_grade_from',
                'grade_to' => 'cc_grade_to',
            ],
            'course_not_completed' => ['courseid' => 'cnc_courseid'],
            'course_last_access' => self::time_window_map('cla_', ['courseid' => 'cla_courseid']),
            'activity_completed' => [
                'courseid' => 'ac_courseid',
                'cmid' => 'ac_cmid',
            ],
            'quiz_score' => [
                'courseid' => 'qs_courseid',
                'cmid' => 'qs_cmid',
                'operator' => 'qs_operator',
                'percent' => 'qs_percent',
                'grademode' => 'qs_grademode',
            ],
            'user_created_since' => self::time_window_map('ucs_'),
            'user_first_login' => self::time_window_map('ufl_'),
            'user_last_login' => self::time_window_map('ull_'),
            'user_profile_field' => [
                'fieldid' => 'upf_fieldid',
                'op' => 'upf_op',
                'value' => 'upf_value',
            ],
            'cohort_member' => [
                'cohortid' => 'cm_cohortid',
                'added_on_after' => 'cm_added_on_after',
            ],
            'cohort_not_member' => ['cohortid' => 'cnm_cohortid'],
            'competency_achieved' => ['competencyid' => 'ca_competencyid'],
            'program_allocated' => ['programid' => 'pa_programid'],
            'program_not_allocated' => ['programid' => 'pna_programid'],
            'program_completed' => ['programid' => 'pc_programid'],
            'program_overdue' => ['programid' => 'po_programid'],
            'certification_assigned' => ['certificationid' => 'cas_certificationid'],
            'certification_certified' => ['certificationid' => 'ccert_certificationid'],
            'certification_expired' => ['certificationid' => 'cexp_certificationid'],
            'relation_subordinate' => ['frameworkid' => 'rs_frameworkid'],
            'relation_supervisor' => ['frameworkid' => 'rv_frameworkid'],
            'tenant_member' => ['tenantid' => 'tm_tenantid'],
            'tenant_not_member' => ['tenantid' => 'tnm_tenantid'],
            default => [],
        };
    }

    /**
     * @param string $prefix
     * @param array $extra
     * @return array<string,string>
     */
    private static function time_window_map(string $prefix, array $extra = []): array {
        return $extra + [
            'mode' => $prefix . 'mode',
            'datetime' => $prefix . 'datetime',
            'datetime_to' => $prefix . 'datetime_to',
            'amount' => $prefix . 'amount',
            'unit' => $prefix . 'unit',
        ];
    }
}
