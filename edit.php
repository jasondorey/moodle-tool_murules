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
 * Create / edit rule wizard (React): Event → Actions → Review.
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
if ($id) {
    // Ensure the rule exists before mounting the editor.
    \tool_murules\local\rule::get($id);
}

$PAGE->set_url(new moodle_url('/admin/tool/murules/edit.php', $id ? ['id' => $id] : []));
$PAGE->set_title($id ? get_string('editrule', 'tool_murules') : get_string('createrule', 'tool_murules'));
$PAGE->set_heading($PAGE->title);

/** @var \tool_murules\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_murules');

$returnurl = (new moodle_url('/admin/tool/murules/rules.php'))->out(false);

echo $OUTPUT->header();
echo $renderer->wizard_exit_nav();
echo $OUTPUT->render_from_template('tool_murules/wizard', [
    'ruleid' => $id,
    // Quote for JSON string inside mustache react helper.
    'returnurl' => json_encode($returnurl),
]);
echo $OUTPUT->footer();
