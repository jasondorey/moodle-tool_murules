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

namespace tool_murules\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * AJAX: course search for React course fields.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class search_courses extends external_api {
    use wizard_helper;

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'Search query', VALUE_DEFAULT, ''),
            'courseid' => new external_value(PARAM_INT, 'Ensure this course is included', VALUE_DEFAULT, 0),
            'limit' => new external_value(PARAM_INT, 'Max results', VALUE_DEFAULT, 30),
            'tenantid' => new external_value(PARAM_INT, 'Prefer courses in this MuTMS tenant', VALUE_DEFAULT, 0),
        ]);
    }

    public static function execute(string $query = '', int $courseid = 0, int $limit = 30, int $tenantid = 0): array {
        global $DB;
        [
            'query' => $query,
            'courseid' => $courseid,
            'limit' => $limit,
            'tenantid' => $tenantid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'courseid' => $courseid,
            'limit' => $limit,
            'tenantid' => $tenantid,
        ]);
        self::require_manage();
        $limit = max(1, min(100, $limit));
        $query = trim($query);
        $params = ['site' => SITEID];
        $joins = '';
        $where = 'c.id <> :site AND c.visible = 1';
        if ($query !== '') {
            $where .= ' AND ' . $DB->sql_like('c.fullname', ':q', false);
            $params['q'] = '%' . $DB->sql_like_escape($query) . '%';
        }

        $scoped = false;
        if ($tenantid > 0) {
            $categorypath = self::tenant_category_path($tenantid);
            if ($categorypath !== null) {
                $joins = ' JOIN {course_categories} cc ON cc.id = c.category';
                $where .= ' AND (cc.path = :tpath OR ' . $DB->sql_like('cc.path', ':tpathlike') . ')';
                $params['tpath'] = $categorypath;
                $params['tpathlike'] = $DB->sql_like_escape($categorypath) . '/%';
                $scoped = true;
            }
        }

        $sql = "SELECT c.id, c.fullname, c.shortname
                  FROM {course} c
                       $joins
                 WHERE $where
              ORDER BY c.fullname ASC";
        $rows = $DB->get_records_sql($sql, $params, 0, $limit);
        $courses = [];
        $seen = [];
        foreach ($rows as $row) {
            $courses[] = [
                'id' => (int)$row->id,
                'name' => format_string($row->fullname) . ' (' . format_string($row->shortname) . ')',
            ];
            $seen[(int)$row->id] = true;
        }
        if ($courseid > 0 && empty($seen[$courseid])) {
            // Keep an already-selected course visible even when it sits outside the tenant scope.
            $row = $DB->get_record('course', ['id' => $courseid], 'id, fullname, shortname');
            if ($row) {
                $label = format_string($row->fullname) . ' (' . format_string($row->shortname) . ')';
                if ($scoped) {
                    $label .= ' — ' . get_string('course_outside_tenant', 'tool_murules');
                }
                array_unshift($courses, [
                    'id' => (int)$row->id,
                    'name' => $label,
                ]);
            }
        }
        return [
            'courses' => $courses,
            'tenantscoped' => $scoped,
        ];
    }

    /**
     * @param int $tenantid
     * @return string|null Category path or null when tenant/category missing.
     */
    private static function tenant_category_path(int $tenantid): ?string {
        global $DB;
        if (!$DB->get_manager()->table_exists('tool_mutenancy_tenant')) {
            return null;
        }
        $categoryid = (int)$DB->get_field('tool_mutenancy_tenant', 'categoryid', [
            'id' => $tenantid,
            'archived' => 0,
        ]);
        if (!$categoryid) {
            return null;
        }
        $path = $DB->get_field('course_categories', 'path', ['id' => $categoryid]);
        return $path ? (string)$path : null;
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course id'),
                    'name' => new external_value(PARAM_TEXT, 'Display name'),
                ])
            ),
            'tenantscoped' => new external_value(PARAM_BOOL, 'Whether results were filtered to a tenant category'),
        ]);
    }
}
