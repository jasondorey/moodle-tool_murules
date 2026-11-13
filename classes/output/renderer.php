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

namespace tool_murules\output;

use html_writer;
use plugin_renderer_base;
use tool_murules\local\registry;
use tool_murules\local\rule;

/**
 * Plugin renderer.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Overview cards.
     *
     * @return string
     */
    public function rule_cards(): string {
        $rules = [];
        foreach (rule::get_all() as $rec) {
            $condition = registry::get_condition($rec->conditiontype);
            $when = $condition
                ? $condition->get_description(rule::condition_config($rec))
                : $rec->conditiontype;

            $actions = rule::get_actions((int)$rec->id);
            $thenparts = [];
            foreach ($actions as $actionrec) {
                $action = registry::get_action($actionrec->actiontype);
                if ($action) {
                    $thenparts[] = $action->get_description(
                        \tool_murules\local\action\base::decode_config($actionrec->actionconfig)
                    );
                } else {
                    $thenparts[] = $actionrec->actiontype;
                }
            }
            $stats = rule::get_match_stats((int)$rec->id);
            $rules[] = [
                'name' => format_string($rec->name),
                'enabled' => (int)$rec->enabled === 1,
                'when' => $when,
                'then' => implode('; ', $thenparts),
                'stats' => get_string('matchstats', 'tool_murules', (object)$stats),
                'editurl' => (new \moodle_url('/admin/tool/murules/edit.php', ['id' => $rec->id]))->out(false),
                'toggleurl' => (new \moodle_url('/admin/tool/murules/index.php', [
                    'action' => 'toggle',
                    'id' => $rec->id,
                    'sesskey' => sesskey(),
                ]))->out(false),
                'logurl' => (new \moodle_url('/admin/tool/murules/log.php', ['id' => $rec->id]))->out(false),
                'deleteurl' => (new \moodle_url('/admin/tool/murules/index.php', [
                    'action' => 'delete',
                    'id' => $rec->id,
                    'sesskey' => sesskey(),
                ]))->out(false),
            ];
        }

        return $this->render_from_template('tool_murules/rule_cards', [
            'hasrules' => !empty($rules),
            'rules' => $rules,
            'createurl' => (new \moodle_url('/admin/tool/murules/edit.php'))->out(false),
        ]);
    }

    /**
     * Wizard step indicator.
     *
     * @param string $current event|actions|review
     * @return string
     */
    public function wizard_steps(string $current): string {
        $steps = ['event', 'actions', 'review'];
        $html = html_writer::start_div('tool_murules-wizard-steps');
        foreach ($steps as $step) {
            $class = $step === $current ? 'is-current' : '';
            $html .= html_writer::tag('span', get_string('wizard_' . $step, 'tool_murules'), ['class' => $class]);
        }
        $html .= html_writer::end_div();
        return $html;
    }
}
