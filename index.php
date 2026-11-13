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
 * Rules dashboard — local metrics, 7-day chart, recent activity.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_murules_manage');
require_capability('tool/murules:manage', context_system::instance());

$PAGE->set_url(new moodle_url('/admin/tool/murules/index.php'));
$PAGE->set_title(get_string('dashboard_title', 'tool_murules'));
$PAGE->set_heading(get_string('dashboard_title', 'tool_murules'));

/** @var \tool_murules\output\renderer $renderer */
$renderer = $PAGE->get_renderer('tool_murules');

echo $OUTPUT->header();
echo $renderer->primary_tabs('overview');
echo $renderer->dashboard();
echo $OUTPUT->footer();
