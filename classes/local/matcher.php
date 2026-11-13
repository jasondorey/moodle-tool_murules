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
 * Helpers to resolve which rules care about an event.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class matcher {

    /**
     * Enabled rule ids that have at least one condition listening for this event.
     *
     * @param string $eventclass
     * @return int[]
     */
    public static function ruleids_for_event(string $eventclass): array {
        $ids = [];
        foreach (rule::get_enabled(true) as $rulerec) {
            foreach (rule::get_conditions_decoded($rulerec) as $cond) {
                $condition = registry::get_condition($cond['conditiontype']);
                if (!$condition) {
                    continue;
                }
                if (in_array($eventclass, $condition->get_events(), true)) {
                    $ids[] = (int)$rulerec->id;
                    break;
                }
            }
        }
        return $ids;
    }

    /**
     * Narrow rule ids that could match a specific course module.
     *
     * @param int[] $ruleids
     * @param int $cmid
     * @return int[]
     */
    public static function filter_by_cmid(array $ruleids, int $cmid): array {
        return self::filter_by_config_key($ruleids, 'cmid', $cmid);
    }

    /**
     * Narrow rule ids whose condition config key matches a value (when the key is set).
     *
     * For AND multi-condition rules: drop the rule if any condition sets the key
     * to a different value.
     *
     * @param int[] $ruleids
     * @param string $key
     * @param int $value
     * @return int[]
     */
    public static function filter_by_config_key(array $ruleids, string $key, int $value): array {
        if (!$ruleids || !$value) {
            return $ruleids;
        }
        $out = [];
        foreach ($ruleids as $ruleid) {
            try {
                $rulerec = rule::get((int)$ruleid);
            } catch (\Throwable $e) {
                continue;
            }
            $keep = true;
            foreach (rule::get_conditions_decoded($rulerec) as $cond) {
                $config = $cond['conditionconfig'];
                if (isset($config[$key]) && (int)$config[$key] !== $value) {
                    $keep = false;
                    break;
                }
            }
            if ($keep) {
                $out[] = (int)$ruleid;
            }
        }
        return $out;
    }
}
