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

namespace tool_murules\local;

/**
 * Course-module option helpers for wizard AJAX + condition forms.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class course_modules {

    /**
     * List selectable course modules for a course.
     *
     * @param int $courseid
     * @param string $mode quiz|activity
     * @return array<int, array{id:int, name:string}>
     */
    public static function list_for_course(int $courseid, string $mode): array {
        if ($courseid <= 0) {
            return [];
        }
        return match ($mode) {
            'quiz' => self::list_quizzes($courseid),
            'activity' => self::list_completion_activities($courseid),
            default => [],
        };
    }

    /**
     * Quiz CMs in a course.
     *
     * @param int $courseid
     * @return array<int, array{id:int, name:string}>
     */
    public static function list_quizzes(int $courseid): array {
        global $DB;

        $modid = $DB->get_field('modules', 'id', ['name' => 'quiz']);
        if (!$modid) {
            return [];
        }
        $sql = "SELECT cm.id, q.name
                  FROM {course_modules} cm
                  JOIN {quiz} q ON q.id = cm.instance
                 WHERE cm.course = :courseid AND cm.module = :modid AND cm.deletioninprogress = 0
              ORDER BY q.name ASC";
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid, 'modid' => $modid]);
        $out = [];
        foreach ($records as $rec) {
            $out[] = [
                'id' => (int)$rec->id,
                'name' => (string)format_string($rec->name),
            ];
        }
        return $out;
    }

    /**
     * Activities with completion tracking enabled.
     *
     * @param int $courseid
     * @return array<int, array{id:int, name:string}>
     */
    public static function list_completion_activities(int $courseid): array {
        try {
            $modinfo = get_fast_modinfo($courseid);
            $options = [];
            foreach ($modinfo->get_cms() as $cm) {
                if ((int)$cm->completion === 0 || $cm->deletioninprogress) {
                    continue;
                }
                $options[(int)$cm->id] = $cm->get_formatted_name() . ' (' . $cm->modname . ')';
            }
            asort($options, SORT_NATURAL | SORT_FLAG_CASE);
            $out = [];
            foreach ($options as $id => $name) {
                $out[] = ['id' => (int)$id, 'name' => (string)$name];
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Select options map id => name (includes no leading "choose").
     *
     * @param int $courseid
     * @param string $mode
     * @return array<int, string>
     */
    public static function options_map(int $courseid, string $mode): array {
        $map = [];
        foreach (self::list_for_course($courseid, $mode) as $item) {
            $map[$item['id']] = $item['name'];
        }
        return $map;
    }
}
