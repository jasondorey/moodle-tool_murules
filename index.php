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
 * Rules overview (WHEN→THEN cards).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_murules_manage');
require_capability('tool/murules:manage', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$filter = optional_param('filter', 'all', PARAM_ALPHA);
$query = trim(optional_param('q', '', PARAM_TEXT));
$allowedfilters = ['all', 'enabled', 'disabled', 'broken'];
if (!in_array($filter, $allowedfilters, true)) {
    $filter = 'all';
}
$returnparams = [];
if ($filter !== 'all') {
    $returnparams['filter'] = $filter;
}
if ($query !== '') {
    $returnparams['q'] = $query;
}

if ($action && $id) {
    require_sesskey();
    $rulerec = \tool_murules\local\rule::get($id);
    if ($action === 'toggle') {
        \tool_murules\local\rule::set_enabled($id, !(int)$rulerec->enabled);
        redirect(new moodle_url('/admin/tool/murules/index.php', $returnparams));
    }
    if ($action === 'delete') {
        $confirm = optional_param('confirm', 0, PARAM_BOOL);
        if ($confirm) {
            \tool_murules\local\rule::delete($id);
            redirect(new moodle_url('/admin/tool/murules/index.php', $returnparams));
        }
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('deleteruleconfirm', 'tool_murules', format_string($rulerec->name)),
            new moodle_url('/admin/tool/murules/index.php', array_merge($returnparams, [
                'action' => 'delete',
                'id' => $id,
                'confirm' => 1,
                'sesskey' => sesskey(),
            ])),
            new moodle_url('/admin/tool/murules/index.php', $returnparams)
        );
        echo $OUTPUT->footer();
        exit;
    }
}

$PAGE->set_url(new moodle_url('/admin/tool/murules/index.php', $returnparams));
$PAGE->set_title(get_string('rulesoverview', 'tool_murules'));
$PAGE->set_heading(get_string('rulesoverview', 'tool_murules'));

/** @var \tool_murules\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_murules');

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('rulesoverview_help', 'tool_murules'), ['class' => 'tool_murules-lead text-muted']);
echo $renderer->rule_cards($filter, $query);
echo $OUTPUT->footer();
