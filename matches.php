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
 * Site-wide match logs (all rules).
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

$statusfilter = optional_param('status', 'all', PARAM_ALPHA);
$allowedstatus = ['all', 'applied', 'skipped', 'failed'];
if (!in_array($statusfilter, $allowedstatus, true)) {
    $statusfilter = 'all';
}
$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;

$urlparams = [];
if ($statusfilter !== 'all') {
    $urlparams['status'] = $statusfilter;
}
$PAGE->set_url(new moodle_url('/admin/tool/murules/matches.php', $urlparams));
$PAGE->set_title(get_string('logs_title', 'tool_murules'));
$PAGE->set_heading(get_string('logs_title', 'tool_murules'));

/** @var \tool_murules\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_murules');

$sqlparams = [];
$wheresql = '1=1';
if ($statusfilter !== 'all') {
    $wheresql = 'm.status = :status';
    $sqlparams['status'] = $statusfilter;
}

$counts = [
    'all' => $DB->count_records('tool_murules_match'),
    'applied' => $DB->count_records('tool_murules_match', ['status' => 'applied']),
    'skipped' => $DB->count_records('tool_murules_match', ['status' => 'skipped']),
    'failed' => $DB->count_records('tool_murules_match', ['status' => 'failed']),
];

echo $OUTPUT->header();
echo $renderer->primary_tabs('logs');
echo html_writer::tag('p', get_string('logs_help', 'tool_murules'), ['class' => 'text-muted']);

$filterhtml = html_writer::start_tag('nav', [
    'class' => 'mb-3',
    'aria-label' => get_string('log_filter_label', 'tool_murules'),
]);
$filterhtml .= html_writer::start_tag('ul', ['class' => 'nav nav-pills flex-wrap gap-1']);
foreach ($allowedstatus as $key) {
    $linkparams = [];
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
            new moodle_url('/admin/tool/murules/matches.php', $linkparams),
            $label . ' ' . html_writer::span((string)$counts[$key], 'badge text-bg-light text-dark'),
            ['class' => $classes] + ($key === $statusfilter ? ['aria-current' => 'page'] : [])
        ),
        ['class' => 'nav-item']
    );
}
$filterhtml .= html_writer::end_tag('ul');
$filterhtml .= html_writer::end_tag('nav');
echo $filterhtml;

if (!$counts['all'] || ($statusfilter !== 'all' && empty($counts[$statusfilter]))) {
    echo html_writer::div(
        html_writer::tag('h3', get_string('logs_empty_title', 'tool_murules'), ['class' => 'h5']) .
        html_writer::tag('p', get_string('logs_empty', 'tool_murules'), ['class' => 'mb-0']),
        'alert alert-secondary'
    );
    echo $OUTPUT->footer();
    exit;
}

$total = (int)$DB->count_records_sql(
    "SELECT COUNT(1) FROM {tool_murules_match} m WHERE $wheresql",
    $sqlparams
);

$records = $DB->get_records_sql(
    "SELECT m.*, r.name AS rulename
       FROM {tool_murules_match} m
       JOIN {tool_murules_rule} r ON r.id = m.ruleid
      WHERE $wheresql
   ORDER BY m.timecreated DESC, m.id DESC",
    $sqlparams,
    $page * $perpage,
    $perpage
);

$table = new html_table();
$table->attributes['class'] = 'generaltable tool_murules-log-table';
$table->head = [
    get_string('time', 'tool_murules'),
    get_string('rule', 'tool_murules'),
    get_string('user', 'tool_murules'),
    get_string('status', 'tool_murules'),
    get_string('reason', 'tool_murules'),
];
$table->data = [];
foreach ($records as $rec) {
    $user = $DB->get_record('user', ['id' => $rec->userid], '*', IGNORE_MISSING);
    if ($user && !(int)$user->deleted) {
        $userlink = html_writer::link(
            new moodle_url('/user/profile.php', ['id' => $user->id]),
            fullname($user)
        );
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
    $table->data[] = [
        userdate($rec->timecreated),
        html_writer::link(
            new moodle_url('/admin/tool/murules/log.php', ['id' => $rec->ruleid]),
            format_string($rec->rulename)
        ),
        $userlink,
        html_writer::span(s($statuslabel), 'badge ' . $statusboot),
        format_text((string)$rec->reason, FORMAT_PLAIN),
    ];
}

echo html_writer::tag(
    'p',
    get_string('log_resultcount', 'tool_murules', (object)[
        'shown' => count($records),
        'total' => $total,
    ]),
    ['class' => 'text-muted']
);
echo html_writer::div(html_writer::table($table), 'tool_murules-log-scroll');
echo $OUTPUT->paging_bar($total, $page, $perpage, $PAGE->url);
echo $OUTPUT->footer();
