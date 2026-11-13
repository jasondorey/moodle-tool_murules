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

use tool_murules\local\action\base as action_base;
use tool_murules\local\condition\base as condition_base;

/**
 * Discovers condition and action classes.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class registry {

    /**
     * @return array<string, condition_base>
     */
    public static function get_conditions(): array {
        $instances = [];
        $classes = \core_component::get_component_classes_in_namespace('tool_murules', 'local\\condition');
        foreach (array_keys($classes) as $classname) {
            if ($classname === condition_base::class || !is_subclass_of($classname, condition_base::class)) {
                continue;
            }
            /** @var condition_base $instance */
            $instance = new $classname();
            $instances[$instance->get_type()] = $instance;
        }
        ksort($instances);
        return $instances;
    }

    /**
     * @return array<string, action_base>
     */
    public static function get_actions(): array {
        $instances = [];
        $classes = \core_component::get_component_classes_in_namespace('tool_murules', 'local\\action');
        foreach (array_keys($classes) as $classname) {
            if ($classname === action_base::class || !is_subclass_of($classname, action_base::class)) {
                continue;
            }
            /** @var action_base $instance */
            $instance = new $classname();
            $instances[$instance->get_type()] = $instance;
        }
        ksort($instances);
        return $instances;
    }

    /**
     * @param string $type
     * @return condition_base|null
     */
    public static function get_condition(string $type): ?condition_base {
        $all = self::get_conditions();
        return $all[$type] ?? null;
    }

    /**
     * @param string $type
     * @return action_base|null
     */
    public static function get_action(string $type): ?action_base {
        $all = self::get_actions();
        return $all[$type] ?? null;
    }

    /**
     * Condition options grouped for the wizard.
     *
     * @param bool $availableonly
     * @return array<string, array<string, string>> category => [type => name]
     */
    public static function get_condition_menu(bool $availableonly = true): array {
        $menu = [];
        foreach (self::get_conditions() as $type => $condition) {
            if ($availableonly && !$condition->is_available()) {
                continue;
            }
            $category = $condition->get_category();
            $catlabel = get_string('category_' . $category, 'tool_murules');
            $menu[$catlabel][$type] = $condition->get_name();
        }
        return $menu;
    }

    /**
     * @param bool $availableonly
     * @return array<string, string>
     */
    public static function get_action_menu(bool $availableonly = true): array {
        $menu = [];
        foreach (self::get_actions() as $type => $action) {
            if ($availableonly && !$action->is_available()) {
                continue;
            }
            $menu[$type] = $action->get_name();
        }
        return $menu;
    }
}
