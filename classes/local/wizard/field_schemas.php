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

use tool_murules\local\condition\helper;
use tool_murules\local\registry;

/**
 * JSON editor schemas for the React wizard (Formslib replacement).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class field_schemas {

    /**
     * @param string $kind condition|action
     * @param string $type
     * @return array{fields:array, defaults:array}
     */
    public static function for_type(string $kind, string $type): array {
        if ($kind === 'action') {
            return self::action_schema($type);
        }
        return self::condition_schema($type);
    }

    /**
     * @param string $type
     * @return array{fields:array, defaults:array}
     */
    private static function condition_schema(string $type): array {
        $condition = registry::get_condition($type);
        if (!$condition) {
            return ['fields' => [], 'defaults' => []];
        }

        return match ($type) {
            'user_enrolled' => [
                'fields' => [
                    self::course_field('courseid', true),
                    self::select_field('enrolmethod', get_string('enrolmethod', 'tool_murules'), helper::enrol_method_options()),
                    self::datetime_field('start_on_after', get_string('enrol_start_on_after', 'tool_murules'), false),
                ],
                'defaults' => ['courseid' => 0, 'enrolmethod' => 'any', 'start_on_after' => 0],
            ],
            'user_not_enrolled' => [
                'fields' => [
                    self::course_field('courseid', true),
                    self::select_field('enrolmethod', get_string('enrolmethod', 'tool_murules'), helper::enrol_method_options()),
                ],
                'defaults' => ['courseid' => 0, 'enrolmethod' => 'any'],
            ],
            'course_completed' => [
                'fields' => [
                    self::course_field('courseid', true),
                    self::select_field('completion_when', get_string('completion_when', 'tool_murules'), [
                        'any' => get_string('completion_when_any', 'tool_murules'),
                        'after' => get_string('completion_when_after', 'tool_murules'),
                        'before' => get_string('completion_when_before', 'tool_murules'),
                    ]),
                    self::datetime_field('completion_after', get_string('completion_after', 'tool_murules'), false, [
                        'showif' => ['field' => 'completion_when', 'equals' => 'after'],
                    ]),
                    self::datetime_field('completion_before', get_string('completion_before', 'tool_murules'), false, [
                        'showif' => ['field' => 'completion_when', 'equals' => 'before'],
                    ]),
                    self::select_field('grade_op', get_string('grade_op', 'tool_murules'), [
                        'any' => get_string('grade_op_any', 'tool_murules'),
                        'lt' => get_string('grade_op_lt', 'tool_murules'),
                        'gt' => get_string('grade_op_gt', 'tool_murules'),
                        'range' => get_string('grade_op_range', 'tool_murules'),
                    ]),
                    self::text_field('grade_lt', get_string('grade_lt', 'tool_murules'), 'number', [
                        'showif' => ['field' => 'grade_op', 'equals' => 'lt'],
                    ]),
                    self::text_field('grade_gt', get_string('grade_gt', 'tool_murules'), 'number', [
                        'showif' => ['field' => 'grade_op', 'equals' => 'gt'],
                    ]),
                    self::text_field('grade_from', get_string('grade_from', 'tool_murules'), 'number', [
                        'showif' => ['field' => 'grade_op', 'equals' => 'range'],
                    ]),
                    self::text_field('grade_to', get_string('grade_to', 'tool_murules'), 'number', [
                        'showif' => ['field' => 'grade_op', 'equals' => 'range'],
                    ]),
                ],
                'defaults' => [
                    'courseid' => 0,
                    'completion_when' => 'any',
                    'completion_after' => 0,
                    'completion_before' => 0,
                    'grade_op' => 'any',
                    'grade_lt' => '',
                    'grade_gt' => '',
                    'grade_from' => '',
                    'grade_to' => '',
                ],
            ],
            'course_not_completed' => [
                'fields' => [self::course_field('courseid', true)],
                'defaults' => ['courseid' => 0],
            ],
            'course_last_access' => self::with_time_window(
                [self::course_field('courseid', true)],
                ['courseid' => 0],
                ['ever', 'never', 'before', 'after', 'between', 'during_last', 'prior_to_last'],
                'ever'
            ),
            'activity_completed' => [
                'fields' => [
                    self::course_field('courseid', true),
                    self::cm_field('cmid', get_string('activity', 'tool_murules'), 'activity', 'courseid', true),
                ],
                'defaults' => ['courseid' => 0, 'cmid' => 0],
            ],
            'quiz_score' => [
                'fields' => [
                    self::course_field('courseid', true),
                    self::cm_field('cmid', get_string('quiz', 'tool_murules'), 'quiz', 'courseid', true),
                    self::select_field('operator', get_string('operator', 'tool_murules'), [
                        'gte' => get_string('operator_gte', 'tool_murules'),
                        'lte' => get_string('operator_lte', 'tool_murules'),
                        'eq' => get_string('operator_eq', 'tool_murules'),
                    ]),
                    self::text_field('percent', get_string('percent', 'tool_murules'), 'number', ['required' => true]),
                    self::select_field('grademode', get_string('grademode', 'tool_murules'), [
                        'quiz_method' => get_string('grademode_quiz_method', 'tool_murules'),
                    ]),
                ],
                'defaults' => [
                    'courseid' => 0,
                    'cmid' => 0,
                    'operator' => 'gte',
                    'percent' => 80,
                    'grademode' => 'quiz_method',
                ],
            ],
            'user_created_since' => self::with_time_window(
                [],
                [],
                ['ever', 'before', 'after', 'between', 'during_last', 'prior_to_last'],
                'prior_to_last'
            ),
            'user_first_login' => self::with_time_window(
                [],
                [],
                ['ever', 'never', 'before', 'after', 'between', 'during_last', 'prior_to_last'],
                'ever'
            ),
            'user_last_login' => self::with_time_window(
                [],
                [],
                ['ever', 'never', 'before', 'after', 'between', 'during_last', 'prior_to_last'],
                'prior_to_last'
            ),
            'user_profile_field' => [
                'fields' => [
                    self::select_field('fieldid', get_string('profile_field', 'tool_murules'), self::profile_field_options(), true),
                    self::select_field('op', get_string('operator', 'tool_murules'), [
                        'eq' => get_string('profile_op_eq', 'tool_murules'),
                        'neq' => get_string('profile_op_neq', 'tool_murules'),
                        'contains' => get_string('profile_op_contains', 'tool_murules'),
                        'isempty' => get_string('profile_op_isempty', 'tool_murules'),
                        'isnotempty' => get_string('profile_op_isnotempty', 'tool_murules'),
                    ]),
                    self::text_field('value', get_string('profile_value', 'tool_murules'), 'text', [
                        'showif' => ['field' => 'op', 'notin' => ['isempty', 'isnotempty']],
                    ]),
                ],
                'defaults' => ['fieldid' => '', 'op' => 'eq', 'value' => ''],
            ],
            'cohort_member' => [
                'fields' => [
                    self::select_field('cohortid', get_string('cohort', 'tool_murules'), self::cohort_options(), true),
                    self::datetime_field('added_on_after', get_string('cohort_added_on_after', 'tool_murules'), false),
                ],
                'defaults' => ['cohortid' => 0, 'added_on_after' => 0],
            ],
            'cohort_not_member' => [
                'fields' => [
                    self::select_field('cohortid', get_string('cohort', 'tool_murules'), self::cohort_options(), true),
                ],
                'defaults' => ['cohortid' => 0],
            ],
            'competency_achieved' => [
                'fields' => [
                    self::select_field('competencyid', get_string('competency', 'tool_murules'), self::competency_options(), true),
                ],
                'defaults' => ['competencyid' => 0],
            ],
            'program_allocated', 'program_not_allocated', 'program_completed', 'program_overdue' => [
                'fields' => [
                    self::select_field('programid', get_string('program', 'tool_murules'), self::program_options(), true),
                ],
                'defaults' => ['programid' => 0],
            ],
            'certification_assigned', 'certification_certified', 'certification_expired' => [
                'fields' => [
                    self::select_field('certificationid', get_string('certification', 'tool_murules'), self::certification_options(), true),
                ],
                'defaults' => ['certificationid' => 0],
            ],
            'relation_subordinate', 'relation_supervisor' => [
                'fields' => [
                    self::select_field('frameworkid', get_string('relation_framework', 'tool_murules'), self::relation_options(), true),
                ],
                'defaults' => ['frameworkid' => 0],
            ],
            'tenant_member', 'tenant_not_member' => [
                'fields' => [
                    self::select_field('tenantid', get_string('tenant', 'tool_murules'), self::tenant_options(), true),
                ],
                'defaults' => ['tenantid' => 0],
            ],
            default => ['fields' => [], 'defaults' => []],
        };
    }

    /**
     * @param string $type
     * @return array{fields:array, defaults:array}
     */
    private static function action_schema(string $type): array {
        return match ($type) {
            'allocate_muprog' => [
                'fields' => [
                    self::select_field('programid', get_string('program', 'tool_murules'), self::manual_program_options(), true),
                ],
                'defaults' => ['programid' => 0],
            ],
            'deallocate_muprog' => [
                'fields' => [
                    self::select_field('programid', get_string('program', 'tool_murules'), self::program_options(), true),
                ],
                'defaults' => ['programid' => 0],
            ],
            'cohort_add', 'cohort_remove' => [
                'fields' => [
                    self::select_field('cohortid', get_string('cohort', 'tool_murules'), self::cohort_options(), true),
                ],
                'defaults' => ['cohortid' => 0],
            ],
            'notify_user' => [
                'fields' => [
                    self::text_field('subject', get_string('notify_subject', 'tool_murules'), 'text', ['required' => true]),
                    self::textarea_field('body', get_string('notify_body', 'tool_murules'), true),
                ],
                'defaults' => ['subject' => '', 'body' => ''],
            ],
            default => ['fields' => [], 'defaults' => []],
        };
    }

    /**
     * @param array $fields
     * @param array $defaults
     * @param string[] $modes
     * @param string $defaultmode
     * @return array{fields:array, defaults:array}
     */
    private static function with_time_window(array $fields, array $defaults, array $modes, string $defaultmode): array {
        $modeoptions = [];
        foreach ($modes as $mode) {
            $modeoptions[$mode] = get_string('timewindow_' . $mode, 'tool_murules');
        }
        $fields[] = self::select_field('mode', get_string('timewindow', 'tool_murules'), $modeoptions);
        $fields[] = self::datetime_field('datetime', get_string('timewindow_datetime', 'tool_murules'), false, [
            'showif' => ['field' => 'mode', 'in' => ['before', 'after']],
        ]);
        $fields[] = self::datetime_field('datetime_to', get_string('timewindow_to', 'tool_murules'), false, [
            'showif' => ['field' => 'mode', 'equals' => 'between'],
        ]);
        $fields[] = self::text_field('amount', get_string('timewindow_amount', 'tool_murules'), 'number', [
            'showif' => ['field' => 'mode', 'in' => ['during_last', 'prior_to_last']],
        ]);
        $fields[] = self::select_field('unit', get_string('timewindow_unit', 'tool_murules'), [
            'hours' => get_string('timewindow_unit_hours', 'tool_murules'),
            'days' => get_string('timewindow_unit_days', 'tool_murules'),
            'weeks' => get_string('timewindow_unit_weeks', 'tool_murules'),
        ], false, [
            'showif' => ['field' => 'mode', 'in' => ['during_last', 'prior_to_last']],
        ]);

        return [
            'fields' => $fields,
            'defaults' => $defaults + [
                'mode' => $defaultmode,
                'datetime' => 0,
                'datetime_to' => 0,
                'amount' => 7,
                'unit' => 'days',
            ],
        ];
    }

    /**
     * @param string $name
     * @param bool $required
     * @return array
     */
    private static function course_field(string $name, bool $required): array {
        return [
            'name' => $name,
            'type' => 'course',
            'label' => get_string('course', 'tool_murules'),
            'required' => $required,
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $mode quiz|activity
     * @param string $coursefield
     * @param bool $required
     * @return array
     */
    private static function cm_field(string $name, string $label, string $mode, string $coursefield, bool $required): array {
        return [
            'name' => $name,
            'type' => 'coursemodule',
            'label' => $label,
            'required' => $required,
            'mode' => $mode,
            'dependsOn' => $coursefield,
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param array $options
     * @param bool $required
     * @param array $extra
     * @return array
     */
    private static function select_field(
        string $name,
        string $label,
        array $options,
        bool $required = false,
        array $extra = []
    ): array {
        $opts = [];
        foreach ($options as $value => $lab) {
            $opts[] = ['value' => (string)$value, 'label' => (string)$lab];
        }
        return $extra + [
            'name' => $name,
            'type' => 'select',
            'label' => $label,
            'required' => $required,
            'options' => $opts,
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $input text|number
     * @param array $extra
     * @return array
     */
    private static function text_field(string $name, string $label, string $input = 'text', array $extra = []): array {
        return $extra + [
            'name' => $name,
            'type' => $input === 'number' ? 'number' : 'text',
            'label' => $label,
            'required' => !empty($extra['required']),
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param bool $required
     * @return array
     */
    private static function textarea_field(string $name, string $label, bool $required = false): array {
        return [
            'name' => $name,
            'type' => 'textarea',
            'label' => $label,
            'required' => $required,
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param bool $required
     * @param array $extra
     * @return array
     */
    private static function datetime_field(string $name, string $label, bool $required = false, array $extra = []): array {
        return $extra + [
            'name' => $name,
            'type' => 'datetime',
            'label' => $label,
            'required' => $required,
        ];
    }

    /**
     * @return array<int|string,string>
     */
    private static function cohort_options(): array {
        global $DB;
        $rows = $DB->get_records_menu('cohort', ['visible' => 1], 'name ASC', 'id, name', 0, 500);
        return [0 => get_string('choosedots')] + $rows;
    }

    /**
     * @return array<int|string,string>
     */
    private static function program_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_muprog_program')) {
            return [0 => get_string('choosedots')];
        }
        $rows = $DB->get_records_menu('tool_muprog_program', ['archived' => 0], 'fullname ASC', 'id, fullname', 0, 500);
        return [0 => get_string('choosedots')] + $rows;
    }

    /**
     * @return array<int|string,string>
     */
    private static function manual_program_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_muprog_source')) {
            return [0 => get_string('choosedots')];
        }
        $sql = "SELECT p.id, p.fullname
                  FROM {tool_muprog_program} p
                  JOIN {tool_muprog_source} s ON s.programid = p.id AND s.type = 'manual'
                 WHERE p.archived = 0
              ORDER BY p.fullname ASC";
        $rows = $DB->get_records_sql_menu($sql, [], 0, 500);
        return [0 => get_string('choosedots')] + $rows;
    }

    /**
     * @return array<int|string,string>
     */
    private static function certification_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_mucertify_certification')) {
            return [0 => get_string('choosedots')];
        }
        $rows = $DB->get_records_menu('tool_mucertify_certification', ['archived' => 0], 'fullname ASC', 'id, fullname', 0, 500);
        return [0 => get_string('choosedots')] + $rows;
    }

    /**
     * @return array<int|string,string>
     */
    private static function relation_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_murelation_framework')) {
            return [0 => get_string('choosedots')];
        }
        $rows = $DB->get_records_menu('tool_murelation_framework', null, 'name ASC', 'id, name', 0, 500);
        return [0 => get_string('choosedots')] + $rows;
    }

    /**
     * @return array<int|string,string>
     */
    private static function tenant_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_mutenancy_tenant')) {
            return [0 => get_string('choosedots')];
        }
        $rows = $DB->get_records_menu('tool_mutenancy_tenant', ['archived' => 0], 'name ASC', 'id, name', 0, 500);
        return [0 => get_string('choosedots')] + $rows;
    }

    /**
     * @return array<string,string>
     */
    private static function competency_options(): array {
        global $DB;
        if (!$DB->get_manager()->table_exists('competency')) {
            return ['' => get_string('choosedots')];
        }
        $rows = $DB->get_records('competency', null, 'shortname ASC', 'id, shortname, idnumber', 0, 500);
        $out = ['' => get_string('choosedots')];
        foreach ($rows as $row) {
            $label = format_string($row->shortname);
            if ($row->idnumber !== '') {
                $label .= ' (' . $row->idnumber . ')';
            }
            $out[(string)$row->id] = $label;
        }
        return $out;
    }

    /**
     * @return array<string,string>
     */
    private static function profile_field_options(): array {
        // Prefer condition helper if it exposes field options via a temporary instance method.
        $condition = registry::get_condition('user_profile_field');
        if ($condition && method_exists($condition, 'field_options')) {
            $ref = new \ReflectionMethod($condition, 'field_options');
            $ref->setAccessible(true);
            $opts = $ref->invoke($condition);
            if (is_array($opts)) {
                return $opts;
            }
        }
        return ['' => get_string('choosedots')];
    }
}
