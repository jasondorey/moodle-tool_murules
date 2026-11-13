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
            $config = rule::condition_config($rec);
            $broken = $condition ? $condition->is_broken($config) : true;
            $rules[] = [
                'name' => format_string($rec->name),
                'enabled' => (int)$rec->enabled === 1,
                'broken' => $broken,
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
        $currentidx = array_search($current, $steps, true);
        $html = html_writer::start_tag('nav', [
            'class' => 'tool_murules-wizard-steps',
            'aria-label' => get_string('wizard_steps_label', 'tool_murules'),
        ]);
        $html .= html_writer::start_tag('ol', ['class' => 'tool_murules-wizard-steps-list']);
        foreach ($steps as $idx => $step) {
            $classes = ['tool_murules-wizard-step'];
            if ($step === $current) {
                $classes[] = 'is-current';
            } else if ($currentidx !== false && $idx < $currentidx) {
                $classes[] = 'is-done';
            }
            $label = html_writer::tag('span', (string)($idx + 1), ['class' => 'tool_murules-wizard-num', 'aria-hidden' => 'true']);
            $label .= ' ' . get_string('wizard_' . $step, 'tool_murules');
            $attrs = ['class' => implode(' ', $classes)];
            if ($step === $current) {
                $attrs['aria-current'] = 'step';
            }
            $html .= html_writer::tag('li', $label, $attrs);
        }
        $html .= html_writer::end_tag('ol');
        $html .= html_writer::end_tag('nav');
        return $html;
    }

    /**
     * Empty overview callout.
     *
     * @param string $createurl
     * @return string
     */
    public function empty_rules(string $createurl): string {
        $html = html_writer::start_div('tool_murules-empty');
        $html .= html_writer::tag('h3', get_string('norules_title', 'tool_murules'), ['class' => 'tool_murules-empty-title']);
        $html .= html_writer::tag('p', get_string('norules', 'tool_murules'), ['class' => 'tool_murules-empty-body']);
        $html .= html_writer::link($createurl, get_string('createrule', 'tool_murules'), [
            'class' => 'btn btn-primary',
        ]);
        $html .= html_writer::end_div();
        return $html;
    }
}
