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
$removecond = optional_param('removecond', -1, PARAM_INT);
$removeaction = optional_param('removeaction', -1, PARAM_INT);
$sesskeyname = 'tool_murules_wizard';

/**
 * Normalize draft conditions list and keep legacy keys in sync with the last/editing slot.
 *
 * @param array $draft
 * @return array
 */
function tool_murules_normalize_draft_conditions(array $draft): array {
    if (empty($draft['conditions']) || !is_array($draft['conditions'])) {
        $draft['conditions'] = [];
        if (!empty($draft['conditiontype'])) {
            $draft['conditions'][] = [
                'conditiontype' => (string)$draft['conditiontype'],
                'conditionconfig' => $draft['conditionconfig'] ?? [],
            ];
        }
    }
    $clean = [];
    foreach ($draft['conditions'] as $cond) {
        if (is_object($cond)) {
            $cond = (array)$cond;
        }
        if (empty($cond['conditiontype'])) {
            continue;
        }
        $clean[] = [
            'conditiontype' => (string)$cond['conditiontype'],
            'conditionconfig' => is_array($cond['conditionconfig'] ?? null) ? $cond['conditionconfig'] : [],
        ];
    }
    $draft['conditions'] = $clean;
    if ($clean) {
        $draft['conditiontype'] = $clean[0]['conditiontype'];
        $draft['conditionconfig'] = $clean[0]['conditionconfig'];
    }
    return $draft;
}

/**
 * WHEN summary for AND conditions.
 *
 * @param array $conditions
 * @return string
 */
function tool_murules_when_summary(array $conditions): string {
    $parts = [];
    foreach ($conditions as $cond) {
        $c = \tool_murules\local\registry::get_condition($cond['conditiontype'] ?? '');
        if ($c) {
            $parts[] = $c->get_description($cond['conditionconfig'] ?? []);
        } else if (!empty($cond['conditiontype'])) {
            $parts[] = (string)$cond['conditiontype'];
        }
    }
    return $parts ? implode(' AND ', $parts) : '';
}

/**
 * Normalize draft actions list and keep legacy keys in sync.
 *
 * @param array $draft
 * @return array
 */
function tool_murules_normalize_draft_actions(array $draft): array {
    if (empty($draft['actions']) || !is_array($draft['actions'])) {
        $draft['actions'] = [];
        if (!empty($draft['actiontype'])) {
            $draft['actions'][] = [
                'actiontype' => (string)$draft['actiontype'],
                'actionconfig' => $draft['actionconfig'] ?? [],
            ];
        }
    }
    $clean = [];
    foreach ($draft['actions'] as $act) {
        if (is_object($act)) {
            $act = (array)$act;
        }
        if (empty($act['actiontype'])) {
            continue;
        }
        $clean[] = [
            'actiontype' => (string)$act['actiontype'],
            'actionconfig' => is_array($act['actionconfig'] ?? null) ? $act['actionconfig'] : [],
        ];
    }
    $draft['actions'] = $clean;
    if ($clean) {
        $draft['actiontype'] = $clean[0]['actiontype'];
        $draft['actionconfig'] = $clean[0]['actionconfig'];
    }
    return $draft;
}

/**
 * THEN summary for multiple actions.
 *
 * @param array $actions
 * @return string
 */
function tool_murules_then_summary(array $actions): string {
    $parts = [];
    foreach ($actions as $act) {
        $a = \tool_murules\local\registry::get_action($act['actiontype'] ?? '');
        if ($a) {
            $parts[] = $a->get_description($act['actionconfig'] ?? []);
        } else if (!empty($act['actiontype'])) {
            $parts[] = (string)$act['actiontype'];
        }
    }
    return $parts ? implode('; ', $parts) : '';
}

$draft = [];
if ($id) {
    $rulerec = \tool_murules\local\rule::get($id);
    $conds = \tool_murules\local\rule::get_conditions_decoded($rulerec);
    $acts = \tool_murules\local\rule::get_actions_decoded($id);
    $draft = [
        'id' => $id,
        'name' => $rulerec->name,
        'enabled' => (int)$rulerec->enabled,
        'actionlimit' => $rulerec->actionlimit,
        'includesuspended' => (int)$rulerec->includesuspended,
        'conditions' => $conds,
        'actions' => $acts,
    ];
}

$cached = $SESSION->$sesskeyname ?? null;
if (is_array($cached) && (empty($cached['id']) ? !$id : (int)$cached['id'] === $id)) {
    $draft = array_merge($draft, $cached);
}
if ($id) {
    $draft['id'] = $id;
}
$draft = tool_murules_normalize_draft_conditions($draft);
$draft = tool_murules_normalize_draft_actions($draft);

// Working condition being edited in the form (defaults to a new empty slot after saved ones).
$editing = $draft['editing'] ?? ['conditiontype' => '', 'conditionconfig' => []];
if (is_object($editing)) {
    $editing = (array)$editing;
}
if (empty($editing['conditiontype']) && empty($draft['conditions'])) {
    // First condition: seed from legacy keys if present.
    $editing = [
        'conditiontype' => (string)($draft['conditiontype'] ?? ''),
        'conditionconfig' => $draft['conditionconfig'] ?? [],
    ];
}

// Working action being edited.
$editingaction = $draft['editingaction'] ?? ['actiontype' => '', 'actionconfig' => []];
if (is_object($editingaction)) {
    $editingaction = (array)$editingaction;
}
if (empty($editingaction['actiontype']) && empty($draft['actions'])) {
    $editingaction = [
        'actiontype' => (string)($draft['actiontype'] ?? 'allocate_muprog'),
        'actionconfig' => $draft['actionconfig'] ?? [],
    ];
}

// Clear the in-progress condition pick and return to the category accordion.
$clearpick = optional_param('clearpick', 0, PARAM_BOOL);
if ($step === 'event' && $clearpick) {
    require_sesskey();
    $draft['editing'] = ['conditiontype' => '', 'conditionconfig' => []];
    $SESSION->$sesskeyname = $draft;
    redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'event']));
}

// Remove a saved AND condition.
if ($step === 'event' && $removecond >= 0) {
    require_sesskey();
    if (isset($draft['conditions'][$removecond])) {
        array_splice($draft['conditions'], $removecond, 1);
        $draft = tool_murules_normalize_draft_conditions($draft);
        $SESSION->$sesskeyname = $draft;
    }
    redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'event']));
}

// Remove a saved THEN action.
if ($step === 'actions' && $removeaction >= 0) {
    require_sesskey();
    if (isset($draft['actions'][$removeaction])) {
        array_splice($draft['actions'], $removeaction, 1);
        $draft = tool_murules_normalize_draft_actions($draft);
        $SESSION->$sesskeyname = $draft;
    }
    redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'actions']));
}

// Type picker reload (single-panel Event/Actions forms).
if ($step === 'event' && $pick !== '') {
    $picked = \tool_murules\local\registry::get_condition($pick);
    if ($picked && $picked->is_available()) {
        if (($editing['conditiontype'] ?? '') !== $pick) {
            $editing = [
                'conditiontype' => $pick,
                'conditionconfig' => [],
            ];
            $draft['editing'] = $editing;
            $SESSION->$sesskeyname = $draft;
        }
    }
}
if ($step === 'actions' && $pick !== '') {
    $picked = \tool_murules\local\registry::get_action($pick);
    if ($picked && $picked->is_available()) {
        if (($editingaction['actiontype'] ?? '') !== $pick) {
            $editingaction = [
                'actiontype' => $pick,
                'actionconfig' => [],
            ];
            $draft['editingaction'] = $editingaction;
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
    $hasconditions = !empty($draft['conditions']);
    $pickedtype = (string)($editing['conditiontype'] ?? '');
    if ($pick === '' || !\tool_murules\local\registry::get_condition($pickedtype)) {
        $pickedtype = '';
    }
    $showpicker = ($pickedtype === '');

    $changepickurl = new moodle_url('/admin/tool/murules/edit.php', [
        'id' => $id,
        'step' => 'event',
        'clearpick' => 1,
        'sesskey' => sesskey(),
    ]);

    if (!$showpicker) {
        $form = new \tool_murules\form\rule_event(null, [
            'conditiontype' => $pickedtype,
            'conditionconfig' => $editing['conditionconfig'] ?? [],
            'hasconditions' => $hasconditions,
            'changepickurl' => $changepickurl,
        ]);
        $form->set_data([
            'id' => $id,
            'conditiontype' => $pickedtype,
            'pick' => $pickedtype,
        ]);
        if ($form->is_cancelled()) {
            unset($SESSION->$sesskeyname);
            redirect(new moodle_url('/admin/tool/murules/rules.php'));
        }
        if ($data = $form->get_data()) {
            $condition = \tool_murules\local\registry::get_condition($data->conditiontype);
            $newcond = [
                'conditiontype' => $data->conditiontype,
                'conditionconfig' => $condition->extract_config((array)$data),
            ];
            $draft['conditions'][] = $newcond;
            $draft['editing'] = ['conditiontype' => '', 'conditionconfig' => []];
            $draft['id'] = (int)($data->id ?? 0);
            $draft = tool_murules_normalize_draft_conditions($draft);
            $SESSION->$sesskeyname = $draft;

            // With existing conditions, submit only adds; otherwise continue unless addanother.
            if ($hasconditions || !empty($data->addanother)) {
                redirect(new moodle_url('/admin/tool/murules/edit.php', [
                    'id' => $draft['id'],
                    'step' => 'event',
                ]));
            }
            redirect(new moodle_url('/admin/tool/murules/edit.php', [
                'id' => $draft['id'],
                'step' => 'actions',
            ]));
        }
        $PAGE->requires->js_call_amd('tool_murules/wizard_event', 'init');
    }

    echo $OUTPUT->header();
    echo $renderer->wizard_exit_nav();
    echo $renderer->wizard_steps('event');
    echo $OUTPUT->heading(get_string('wizard_event', 'tool_murules'), 3);
    echo html_writer::tag('p', get_string('wizard_event_help', 'tool_murules'), ['class' => 'text-muted']);

    echo html_writer::tag('h4', get_string('conditions_and_heading', 'tool_murules'), ['class' => 'h5']);
    echo html_writer::tag('p', get_string('conditions_and_help', 'tool_murules'), ['class' => 'text-muted small']);
    if (!$hasconditions) {
        echo html_writer::div(get_string('noconditionsyet', 'tool_murules'), 'alert alert-secondary');
    } else {
        echo html_writer::start_tag('ul', ['class' => 'list-group mb-3']);
        foreach ($draft['conditions'] as $idx => $cond) {
            $c = \tool_murules\local\registry::get_condition($cond['conditiontype']);
            $label = $c
                ? $c->get_description($cond['conditionconfig'])
                : (string)$cond['conditiontype'];
            $removeurl = new moodle_url('/admin/tool/murules/edit.php', [
                'id' => $id,
                'step' => 'event',
                'removecond' => $idx,
                'sesskey' => sesskey(),
            ]);
            echo html_writer::tag(
                'li',
                html_writer::div(
                    html_writer::span(get_string('condition_n', 'tool_murules', $idx + 1), 'badge text-bg-secondary me-2') .
                    s($label),
                    'me-auto'
                ) .
                html_writer::link($removeurl, get_string('removecondition', 'tool_murules'), [
                    'class' => 'btn btn-outline-danger btn-sm',
                ]),
                ['class' => 'list-group-item d-flex align-items-center justify-content-between gap-2']
            );
        }
        echo html_writer::end_tag('ul');
        echo html_writer::div(
            html_writer::link(
                new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'actions']),
                get_string('continueactions', 'tool_murules'),
                ['class' => 'btn btn-primary']
            ),
            'mb-3'
        );
    }

    if ($showpicker) {
        echo $OUTPUT->heading(
            $hasconditions
                ? get_string('configure_next_condition', 'tool_murules')
                : get_string('pickcondition_heading', 'tool_murules'),
            4,
            'h5 mt-3'
        );
        echo html_writer::tag('p', get_string('pickcondition_help', 'tool_murules'), ['class' => 'text-muted small']);
        echo $renderer->event_condition_picker($id);
        if (!$hasconditions) {
            echo html_writer::div(
                html_writer::link(
                    new moodle_url('/admin/tool/murules/rules.php'),
                    get_string('cancelwizard', 'tool_murules'),
                    ['class' => 'btn btn-link']
                ),
                'mt-3'
            );
        }
    } else {
        echo $OUTPUT->heading(get_string('configure_condition', 'tool_murules'), 4, 'h5 mt-3');
        $form->display();
    }
    echo $OUTPUT->footer();
    exit;
}

if ($step === 'actions') {
    if (empty($draft['conditions'])) {
        redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'event']));
    }
    $hasactions = !empty($draft['actions']);
    $form = new \tool_murules\form\rule_actions(null, [
        'actiontype' => $editingaction['actiontype'] ?? 'allocate_muprog',
        'actionconfig' => $editingaction['actionconfig'] ?? [],
        'hasactions' => $hasactions,
    ]);
    $form->set_data([
        'id' => $id,
        'actiontype' => $editingaction['actiontype'] ?? 'allocate_muprog',
    ]);
    if ($form->is_cancelled()) {
        redirect(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'event']));
    }
    if ($data = $form->get_data()) {
        $action = \tool_murules\local\registry::get_action($data->actiontype);
        $newact = [
            'actiontype' => $data->actiontype,
            'actionconfig' => $action->extract_config((array)$data),
        ];
        $draft['actions'][] = $newact;
        $draft['editingaction'] = ['actiontype' => '', 'actionconfig' => []];
        $draft['id'] = (int)($data->id ?? $id);
        $draft = tool_murules_normalize_draft_actions($draft);
        $SESSION->$sesskeyname = $draft;

        if ($hasactions || !empty($data->addanother)) {
            redirect(new moodle_url('/admin/tool/murules/edit.php', [
                'id' => $draft['id'],
                'step' => 'actions',
            ]));
        }
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
    $whensummary = tool_murules_when_summary($draft['conditions']);
    if ($whensummary !== '') {
        echo html_writer::div(
            html_writer::span(get_string('when', 'tool_murules'), 'small text-muted text-uppercase') .
            html_writer::div(s($whensummary), 'tool_murules-recap'),
            'mb-3'
        );
    }

    echo html_writer::tag('h4', get_string('actions_heading', 'tool_murules'), ['class' => 'h5']);
    echo html_writer::tag('p', get_string('actions_help', 'tool_murules'), ['class' => 'text-muted small']);
    if (!$hasactions) {
        echo html_writer::div(get_string('noactionsyet', 'tool_murules'), 'alert alert-secondary');
    } else {
        echo html_writer::start_tag('ul', ['class' => 'list-group mb-3']);
        foreach ($draft['actions'] as $idx => $act) {
            $a = \tool_murules\local\registry::get_action($act['actiontype']);
            $label = $a
                ? $a->get_description($act['actionconfig'])
                : (string)$act['actiontype'];
            $removeurl = new moodle_url('/admin/tool/murules/edit.php', [
                'id' => $id,
                'step' => 'actions',
                'removeaction' => $idx,
                'sesskey' => sesskey(),
            ]);
            echo html_writer::tag(
                'li',
                html_writer::div(
                    html_writer::span(get_string('action_n', 'tool_murules', $idx + 1), 'badge text-bg-secondary me-2') .
                    s($label),
                    'me-auto'
                ) .
                html_writer::link($removeurl, get_string('removeaction', 'tool_murules'), [
                    'class' => 'btn btn-outline-danger btn-sm',
                ]),
                ['class' => 'list-group-item d-flex align-items-center justify-content-between gap-2']
            );
        }
        echo html_writer::end_tag('ul');
        echo html_writer::div(
            html_writer::link(
                new moodle_url('/admin/tool/murules/edit.php', ['id' => $id, 'step' => 'review']),
                get_string('continuereview', 'tool_murules'),
                ['class' => 'btn btn-primary']
            ),
            'mb-3'
        );
    }

    echo $OUTPUT->heading(
        $hasactions
            ? get_string('configure_next_action', 'tool_murules')
            : get_string('wizard_actions', 'tool_murules'),
        4,
        'h6 mt-3'
    );
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

// Review.
if (empty($draft['conditions']) || empty($draft['actions'])) {
    redirect(new moodle_url('/admin/tool/murules/edit.php', [
        'id' => $id,
        'step' => empty($draft['conditions']) ? 'event' : 'actions',
    ]));
}

$estimate = \tool_murules\local\engine::estimate_matching_users_and($draft['conditions'], 500);
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
    'when_summary' => tool_murules_when_summary($draft['conditions']),
    'then_summary' => tool_murules_then_summary($draft['actions']),
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
        'conditions' => $draft['conditions'],
        'actions' => $draft['actions'],
    ];
    \tool_murules\local\rule::save($save);
    unset($SESSION->$sesskeyname);
    redirect(
        new moodle_url('/admin/tool/murules/rules.php'),
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
