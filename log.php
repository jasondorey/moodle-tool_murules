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
 * Rule match / apply log.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('tool_murules_manage');
require_capability('tool/murules:manage', context_system::instance());

$id = required_param('id', PARAM_INT);
$rulerec = \tool_murules\local\rule::get($id);

$PAGE->set_url(new moodle_url('/admin/tool/murules/log.php', ['id' => $id]));
$PAGE->set_title(get_string('rulelog', 'tool_murules'));
$PAGE->set_heading(format_string($rulerec->name) . ' — ' . get_string('rulelog', 'tool_murules'));
$PAGE->navbar->add(format_string($rulerec->name));

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::link(new moodle_url('/admin/tool/murules/index.php'), get_string('back', 'tool_murules')),
    'mb-3'
);

$perpage = 50;
$page = optional_param('page', 0, PARAM_INT);
$total = $DB->count_records('tool_murules_match', ['ruleid' => $id]);
if (!$total) {
    echo $OUTPUT->notification(get_string('nologentries', 'tool_murules'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$records = $DB->get_records('tool_murules_match', ['ruleid' => $id], 'timecreated DESC, id DESC', '*', $page * $perpage, $perpage);

$table = new html_table();
$table->head = [
    get_string('time', 'tool_murules'),
    get_string('user', 'tool_murules'),
    get_string('status', 'tool_murules'),
    get_string('reason', 'tool_murules'),
    get_string('allocation', 'tool_murules'),
];
$table->data = [];
foreach ($records as $rec) {
    $user = $DB->get_record('user', ['id' => $rec->userid], 'id, firstname, lastname, email, deleted');
    $userlink = $user
        ? fullname($user) . ' (' . s($user->email) . ')'
        : (string)$rec->userid;
    $statuskey = 'status_' . $rec->status;
    $status = get_string_manager()->string_exists($statuskey, 'tool_murules')
        ? get_string($statuskey, 'tool_murules')
        : $rec->status;
    $table->data[] = [
        userdate($rec->timecreated),
        $userlink,
        $status,
        format_text((string)$rec->reason, FORMAT_PLAIN),
        $rec->muprogallocationid ? (string)$rec->muprogallocationid : '—',
    ];
}

echo html_writer::table($table);
echo $OUTPUT->paging_bar($total, $page, $perpage, $PAGE->url);
echo $OUTPUT->footer();
