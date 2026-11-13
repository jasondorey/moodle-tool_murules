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

/**
 * Create / edit rule wizard: Event → Actions → Review.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_murules_manage');
require_capability('tool/murules:manage', context_system::instance());

$id = optional_param('id', 0, PARAM_INT);
$step = optional_param('step', 'event', PARAM_ALPHA);
$pick = optional_param('pick', '', PARAM_ALPHANUMEXT);
$sesskeyname = 'tool_murules_wizard';

$draft = [];
if ($id) {
    $rulerec = \tool_murules\local\rule::get($id);
    $actions = \tool_murules\local\rule::get_actions($id);
    $actionrec = reset($actions);
    $draft = [
        'id' => $id,
        'name' => $rulerec->name,
        'enabled' => (int)$rulerec->enabled,
        'actionlimit' => $rulerec->actionlimit,
        'includesuspended' => (int)$rulerec->includesuspended,
        'conditiontype' => $rulerec->conditiontype,
        'conditionconfig' => \tool_murules\local\rule::condition_config($rulerec),
        'actiontype' => $actionrec->actiontype ?? 'allocate_muprog',
        'actionconfig' => \tool_murules\local\action\base::decode_config($actionrec->actionconfig ?? null),
    ];
}

$cached = $SESSION->$sesskeyname ?? null;
if (is_array($cached) && (empty($cached['id']) ? !$id : (int)$cached['id'] === $id)) {
    $draft = array_merge($draft, $cached);
}
if ($id) {
    $draft['id'] = $id;
}

// Type picker reload (single-panel Event/Actions forms).
if ($step === 'event' && $pick !== '') {
    $picked = \tool_murules\local\registry::get_condition($pick);
    if ($picked && $picked->is_available()) {
        if (($draft['conditiontype'] ?? '') !== $pick) {
            $draft['conditiontype'] = $pick;
            $draft['conditionconfig'] = [];
            $SESSION->$sesskeyname = $draft;
        }
    }
}
if ($step === 'actions' && $pick !== '') {
    $picked = \tool_murules\local\registry::get_action($pick);
    if ($picked && $picked->is_available()) {
        if (($draft['actiontype'] ?? '') !== $pick) {
            $draft['actiontype'] = $pick;
            $draft['actionconfig'] = [];
            $SESSION->$sesskeyname = $draft;
        }
    }
}

$PAGE->set_url(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => $step]));
$PAGE->set_title($id ? get_string('editrule', 'tool_murules') : get_string('createrule', 'tool_murules'));
$PAGE->set_heading($PAGE->title);

/** @var \tool_murules\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_murules');

if ($step === 'event') {
    $form = new \tool_murules\form\rule_event(null, [
        'conditiontype' => $draft['conditiontype'] ?? '',
        'conditionconfig' => $draft['conditionconfig'] ?? [],
    ]);
    $form->set_data([
        'id' => $id,
        'conditiontype' => $draft['conditiontype'] ?? '',
    ]);
    if ($form->is_cancelled()) {
        unset($SESSION->$sesskeyname);
        redirect(new moodle_url('/admin/tool/murules/index.php'));
    }
    if ($data = $form->get_data()) {
        $condition = \tool_murules\local\registry::get_condition($data->conditiontype);
        $draft['conditiontype'] = $data->conditiontype;
        $draft['conditionconfig'] = $condition->extract_config((array)$data);
        $draft['id'] = (int)($data->id ?? 0);
        $SESSION->$sesskeyname = $draft;
        redirect(new moodle_url('/admin/tool/murules/edit.php', [
            'id' => $draft['id'],
            'step' => 'actions',
        ]));
    }
    $PAGE->requires->js_call_amd('tool_murules/wizard_event', 'init');
    echo $OUTPUT->header();
    echo $renderer->wizard_exit_nav();
    echo $renderer->wizard_steps('event');
    echo $OUTPUT->heading(get_string('wizard_event', 'tool_murules'), 3);
    echo html_writer::tag('p', get_string('wizard_event_help', 'tool_murules'), ['class' => 'text-muted']);
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

if ($step === 'actions') {
    if (empty($draft['conditiontype'])) {
        redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'event']));
    }
    $form = new \tool_murules\form\rule_actions(null, [
        'actiontype' => $draft['actiontype'] ?? 'allocate_muprog',
        'actionconfig' => $draft['actionconfig'] ?? [],
    ]);
    $form->set_data([
        'id' => $id,
        'actiontype' => $draft['actiontype'] ?? 'allocate_muprog',
    ]);
    if ($form->is_cancelled()) {
        redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'event']));
    }
    if ($data = $form->get_data()) {
        $action = \tool_murules\local\registry::get_action($data->actiontype);
        $draft['actiontype'] = $data->actiontype;
        $draft['actionconfig'] = $action->extract_config((array)$data);
        $draft['id'] = (int)($data->id ?? $id);
        $SESSION->$sesskeyname = $draft;
        redirect(new moodle_url('/admin/tool/murules/edit.php', [
            'id' => $draft['id'],
            'step' => 'review',
        ]));
    }
    $PAGE->requires->js_call_amd('tool_murules/wizard_actions', 'init');
    echo $OUTPUT->header();
    echo $renderer->wizard_exit_nav();
    echo $renderer->wizard_steps('actions');
    echo $OUTPUT->heading(get_string('wizard_actions', 'tool_murules'), 3);
    echo html_writer::tag('p', get_string('wizard_actions_help', 'tool_murules'), ['class' => 'text-muted']);
    if (!empty($draft['conditiontype'])) {
        $cond = \tool_murules\local\registry::get_condition($draft['conditiontype']);
        if ($cond) {
            $whensummary = $cond->get_description($draft['conditionconfig'] ?? []);
            echo html_writer::div(
                html_writer::span(get_string('when', 'tool_murules'), 'tool_murules-label') .
                html_writer::div(s($whensummary), 'tool_murules-recap tool_murules-recap-when'),
                'tool_murules-wizard-context mb-3'
            );
        }
    }
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

// Review.
if (empty($draft['conditiontype']) || empty($draft['actiontype'])) {
    redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'event']));
}

$condition = \tool_murules\local\registry::get_condition($draft['conditiontype']);
$action = \tool_murules\local\registry::get_action($draft['actiontype']);
$estimate = \tool_murules\local\engine::estimate_matching_users(
    (string)$draft['conditiontype'],
    $draft['conditionconfig'] ?? [],
    500
);
$estimatehtml = html_writer::div(
    html_writer::span(
        \tool_murules\local\engine::format_matching_estimate($estimate),
        'tool_murules-match-estimate'
    ) .
    html_writer::tag('div', get_string('matchingusers_estimate_note', 'tool_murules'), [
        'class' => 'tool_murules-match-estimate-note text-muted',
    ])
);
$form = new \tool_murules\form\rule_review(null, [
    'name' => $draft['name'] ?? '',
    'enabled' => $draft['enabled'] ?? 1,
    'actionlimit' => $draft['actionlimit'] ?? 'once',
    'includesuspended' => $draft['includesuspended'] ?? 0,
    'when_summary' => $condition ? $condition->get_description($draft['conditionconfig'] ?? []) : '',
    'then_summary' => $action ? $action->get_description($draft['actionconfig'] ?? []) : '',
    'match_estimate_html' => $estimatehtml,
]);
$form->set_data([
    'id' => $id,
    'name' => $draft['name'] ?? '',
    'enabled' => $draft['enabled'] ?? 1,
    'actionlimit' => $draft['actionlimit'] ?? 'once',
    'includesuspended' => $draft['includesuspended'] ?? 0,
]);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'actions']));
}
if ($data = $form->get_data()) {
    $save = (object)[
        'id' => $id ?: null,
        'name' => $data->name,
        'enabled' => !empty($data->enabled),
        'actionlimit' => $data->actionlimit,
        'includesuspended' => !empty($data->includesuspended),
        'conditiontype' => $draft['conditiontype'],
        'conditionconfig' => $draft['conditionconfig'],
        'actiontype' => $draft['actiontype'],
        'actionconfig' => $draft['actionconfig'],
    ];
    \tool_murules\local\rule::save($save);
    unset($SESSION->$sesskeyname);
    redirect(
        new moodle_url('/admin/tool/murules/index.php'),
        get_string('rulesaved', 'tool_murules', format_string($data->name)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $renderer->wizard_exit_nav();
echo $renderer->wizard_steps('review');
echo $OUTPUT->heading(get_string('wizard_review', 'tool_murules'), 3);
echo html_writer::tag('p', get_string('wizard_review_help', 'tool_murules'), ['class' => 'text-muted']);
$form->display();
echo $OUTPUT->footer();
