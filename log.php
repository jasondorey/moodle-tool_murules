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
$statusfilter = optional_param('status', 'all', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);
$allowedstatus = ['all', 'applied', 'skipped', 'failed'];
if (!in_array($statusfilter, $allowedstatus, true)) {
    $statusfilter = 'all';
}
$rulerec = \tool_murules\local\rule::get($id);

$urlparams = ['id' => $id];
if ($statusfilter !== 'all') {
    $urlparams['status'] = $statusfilter;
}
$PAGE->set_url(new moodle_url('/admin/tool/murules/log.php', $urlparams));
$PAGE->set_title(get_string('rulelog', 'tool_murules'));
$PAGE->set_heading(format_string($rulerec->name) . ' — ' . get_string('rulelog', 'tool_murules'));
$PAGE->navbar->add(format_string($rulerec->name));

$sqlparams = ['ruleid' => $id];
$wheresql = 'ruleid = :ruleid';
if ($statusfilter !== 'all') {
    $wheresql .= ' AND status = :status';
    $sqlparams['status'] = $statusfilter;
}

if ($download === 'csv') {
    require_sesskey();
    $csvrecords = $DB->get_records_select(
        'tool_murules_match',
        $wheresql,
        $sqlparams,
        'timecreated DESC, id DESC',
        '*',
        0,
        10000
    );
    $filename = clean_filename(get_string('log_export_filename', 'tool_murules', $id)) . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['time', 'userid', 'email', 'fullname', 'status', 'reason', 'muprogallocationid']);
    foreach ($csvrecords as $rec) {
        $user = $DB->get_record('user', ['id' => $rec->userid], 'id, email, deleted, firstname, lastname', IGNORE_MISSING);
        $email = $user && !(int)$user->deleted ? $user->email : '';
        $fullname = $user ? fullname($user) : '';
        fputcsv($out, [
            userdate($rec->timecreated, '%Y-%m-%d %H:%M:%S'),
            (int)$rec->userid,
            $email,
            $fullname,
            $rec->status,
            (string)$rec->reason,
            $rec->muprogallocationid ? (string)$rec->muprogallocationid : '',
        ]);
    }
    fclose($out);
    exit;
}

echo $OUTPUT->header();
echo html_writer::div(
    html_writer::link(new moodle_url('/admin/tool/murules/rules.php'), get_string('backtorules', 'tool_murules'), [
        'class' => 'btn btn-link btn-sm px-0',
    ]) .
    ' · ' .
    html_writer::link(new moodle_url('/admin/tool/murules/edit.php', ['id' => $id]), get_string('editrule', 'tool_murules'), [
        'class' => 'btn btn-link btn-sm px-0',
    ]),
    'tool_murules-log-nav mb-3'
);

$perpage = 50;
$page = optional_param('page', 0, PARAM_INT);

$counts = [
    'all' => $DB->count_records('tool_murules_match', ['ruleid' => $id]),
    'applied' => $DB->count_records('tool_murules_match', ['ruleid' => $id, 'status' => 'applied']),
    'skipped' => $DB->count_records('tool_murules_match', ['ruleid' => $id, 'status' => 'skipped']),
    'failed' => $DB->count_records('tool_murules_match', ['ruleid' => $id, 'status' => 'failed']),
];

if (!$counts['all']) {
    echo html_writer::div(
        html_writer::tag('h3', get_string('nologentries_title', 'tool_murules'), ['class' => 'tool_murules-empty-title']) .
        html_writer::tag('p', get_string('nologentries', 'tool_murules'), ['class' => 'tool_murules-empty-body']) .
        html_writer::tag('p', get_string('nologentries_help', 'tool_murules'), ['class' => 'text-muted']),
        'tool_murules-empty'
    );
    echo $OUTPUT->footer();
    exit;
}

$filterhtml = html_writer::start_tag('nav', [
    'class' => 'mb-3',
    'aria-label' => get_string('log_filter_label', 'tool_murules'),
]);
$filterhtml .= html_writer::start_tag('ul', ['class' => 'nav nav-pills flex-wrap gap-1']);
foreach ($allowedstatus as $key) {
    $linkparams = ['id' => $id];
    if ($key !== 'all') {
        $linkparams['status'] = $key;
    }
    $classes = 'nav-link';
    if ($key === $statusfilter) {
        $classes .= ' active';
    }
    $label = get_string('log_filter_' . $key, 'tool_murules');
    $filterhtml .= html_writer::tag(
        'li',
        html_writer::link(
            new moodle_url('/admin/tool/murules/log.php', $linkparams),
            $label . ' ' . html_writer::span((string)$counts[$key], 'badge text-bg-light text-dark'),
            ['class' => $classes] + ($key === $statusfilter ? ['aria-current' => 'page'] : [])
        ),
        ['class' => 'nav-item']
    );
}
$filterhtml .= html_writer::end_tag('ul');
$filterhtml .= html_writer::end_tag('nav');
echo $filterhtml;

if ($statusfilter !== 'all') {
    echo html_writer::tag(
        'p',
        get_string('overview_showing', 'tool_murules', get_string('log_filter_' . $statusfilter, 'tool_murules')),
        ['class' => 'text-muted fw-semibold', 'aria-live' => 'polite']
    );
}

$total = (int)$DB->count_records_select('tool_murules_match', $wheresql, $sqlparams);
if (!$total) {
    echo html_writer::div(
        html_writer::tag('h3', get_string('log_filter_empty_title', 'tool_murules'), ['class' => 'tool_murules-empty-title']) .
        html_writer::tag('p', get_string('log_filter_empty', 'tool_murules'), ['class' => 'tool_murules-empty-body']),
        'tool_murules-empty'
    );
    echo $OUTPUT->footer();
    exit;
}

$records = $DB->get_records_select(
    'tool_murules_match',
    $wheresql,
    $sqlparams,
    'timecreated DESC, id DESC',
    '*',
    $page * $perpage,
    $perpage
);

$table = new html_table();
$table->attributes['class'] = 'generaltable tool_murules-log-table';
$table->head = [
    get_string('time', 'tool_murules'),
    get_string('user', 'tool_murules'),
    get_string('status', 'tool_murules'),
    get_string('reason', 'tool_murules'),
    get_string('allocation', 'tool_murules'),
];
$table->data = [];
foreach ($records as $rec) {
    $user = $DB->get_record('user', ['id' => $rec->userid], '*', IGNORE_MISSING);
    if ($user && !(int)$user->deleted) {
        $userlink = html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $user->id]),
            fullname($user),
            ['title' => $user->email]
        ) . ' (' . s($user->email) . ')';
    } else if ($user) {
        $userlink = fullname($user) . ' (' . get_string('deleteduser', 'tool_murules') . ')';
    } else {
        $userlink = (string)$rec->userid;
    }
    $statuskey = 'status_' . $rec->status;
    $statuslabel = get_string_manager()->string_exists($statuskey, 'tool_murules')
        ? get_string($statuskey, 'tool_murules')
        : $rec->status;
    $statusboot = match ((string)$rec->status) {
        'applied' => 'text-bg-success',
        'skipped' => 'text-bg-warning',
        'failed' => 'text-bg-danger',
        default => 'text-bg-secondary',
    };
    $statushtml = html_writer::span(s($statuslabel), 'badge ' . $statusboot);
    $table->data[] = [
        userdate($rec->timecreated),
        $userlink,
        $statushtml,
        format_text((string)$rec->reason, FORMAT_PLAIN),
        $rec->muprogallocationid ? (string)$rec->muprogallocationid : '—',
    ];
}

$exportparams = array_merge($urlparams, ['download' => 'csv', 'sesskey' => sesskey()]);
echo html_writer::div(
    html_writer::tag(
        'span',
        get_string('log_resultcount', 'tool_murules', (object)[
            'shown' => count($records),
            'total' => $total,
        ]),
        ['class' => 'tool_murules-log-count text-muted']
    ) .
    ' ' .
    html_writer::link(
        new moodle_url('/admin/tool/murules/log.php', $exportparams),
        get_string('log_export_csv', 'tool_murules'),
        ['class' => 'btn btn-secondary btn-sm']
    ),
    'tool_murules-log-toolbar mb-2 d-flex flex-wrap align-items-center justify-content-between gap-2'
);
echo html_writer::div(html_writer::table($table), 'tool_murules-log-scroll');
echo $OUTPUT->paging_bar($total, $page, $perpage, $PAGE->url);
echo $OUTPUT->footer();
