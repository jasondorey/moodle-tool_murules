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
use tool_murules\local\rule;

/**
 * Bootstrap payload for the React create/edit wizard.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class state {

    /**
     * @param int $ruleid
     * @return array
     */
    public static function bootstrap(int $ruleid = 0): array {
        $draft = [
            'id' => $ruleid,
            'name' => '',
            'enabled' => 0,
            'actionlimit' => 'once',
            'includesuspended' => 0,
            'conditions' => [],
            'actions' => [],
        ];

        if ($ruleid > 0) {
            $rec = rule::get($ruleid);
            $draft['name'] = (string)$rec->name;
            $draft['enabled'] = (int)$rec->enabled;
            $draft['actionlimit'] = (string)$rec->actionlimit;
            $draft['includesuspended'] = (int)$rec->includesuspended;
            foreach (rule::get_conditions_decoded($rec) as $cond) {
                $draft['conditions'][] = [
                    'conditiontype' => $cond['conditiontype'],
                    'conditionconfig' => $cond['conditionconfig'],
                    'description' => self::describe_condition($cond['conditiontype'], $cond['conditionconfig']),
                ];
            }
            foreach (rule::get_actions_decoded($ruleid) as $act) {
                $draft['actions'][] = [
                    'id' => $act['id'] ?? 0,
                    'actiontype' => $act['actiontype'],
                    'actionconfig' => $act['actionconfig'],
                    'description' => self::describe_action($act['actiontype'], $act['actionconfig']),
                ];
            }
        }

        return [
            'draft' => $draft,
            'conditionCatalog' => self::condition_catalog(),
            'actionCatalog' => self::action_catalog(),
            'strings' => self::ui_strings(),
            'actionLimits' => [
                ['value' => 'once', 'label' => get_string('actionlimit_once', 'tool_murules')],
                ['value' => 'none', 'label' => get_string('actionlimit_none', 'tool_murules')],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function condition_catalog(): array {
        $categories = [];
        foreach (registry::get_conditions_by_category(true) as $catkey => $conditions) {
            $items = [];
            foreach ($conditions as $type => $condition) {
                $items[] = [
                    'type' => $type,
                    'name' => $condition->get_name(),
                    'category' => $catkey,
                ];
            }
            $categories[] = [
                'key' => $catkey,
                'label' => get_string('category_' . $catkey, 'tool_murules'),
                'count' => count($items),
                'items' => $items,
            ];
        }
        return $categories;
    }

    /**
     * @return array
     */
    public static function action_catalog(): array {
        $items = [];
        foreach (registry::get_actions() as $type => $action) {
            if (!$action->is_available()) {
                continue;
            }
            $items[] = [
                'type' => $type,
                'name' => $action->get_name(),
            ];
        }
        return $items;
    }

    /**
     * @param string $type
     * @param array $config
     * @return string
     */
    public static function describe_condition(string $type, array $config): string {
        $c = registry::get_condition($type);
        return $c ? $c->get_description($config) : $type;
    }

    /**
     * @param string $type
     * @param array $config
     * @return string
     */
    public static function describe_action(string $type, array $config): string {
        $a = registry::get_action($type);
        return $a ? $a->get_description($config) : $type;
    }

    /**
     * @return array<string,string>
     */
    private static function ui_strings(): array {
        $keys = [
            'wizard_event', 'wizard_actions', 'wizard_review', 'wizard_next_actions', 'wizard_next_review',
            'wizard_event_help', 'wizard_actions_help', 'wizard_steps_label', 'wizard_progress',
            'pickcondition_heading', 'pickcondition_help', 'pickcondition', 'pickcondition_hint',
            'configure_condition', 'configure_next_condition',
            'changecondition', 'addanothercondition', 'continueactions', 'conditions_and_heading',
            'conditions_and_help', 'noconditionsyet', 'removecondition', 'actions_heading', 'actions_help',
            'noactionsyet', 'addanotheraction', 'continuereview', 'removeaction', 'configure_next_action',
            'rulename', 'enabled', 'actionlimit', 'includesuspended', 'save', 'cancelwizard', 'back',
            'when', 'then', 'matchingusers', 'saveandcontinue', 'wizard_review_help', 'wizard_review_recap',
            'createrule', 'editrule', 'loadingcms', 'nocms', 'error_rulename',
            'react_wizard_loading', 'react_wizard_error', 'wizard_intro', 'wizard_current_step',
            'category_card_hint', 'pickaction', 'pickaction_hint', 'pickaction_help',
            'configure_action', 'changeaction', 'retry', 'form_has_errors', 'loading_editor',
            'search_courses_placeholder', 'searching_courses', 'no_courses_found',
            'course_search_error', 'selected_course', 'clear_course', 'choose_course_first',
            'cms_load_error', 'estimate_error', 'estimating', 'save_error', 'saving',
        ];
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = get_string($key, 'tool_murules');
        }
        $out['select_action'] = get_string('actiontype', 'tool_murules');
        $out['search_courses'] = get_string('course', 'tool_murules');
        $out['choosedots'] = get_string('choosedots');
        return $out;
    }
}
