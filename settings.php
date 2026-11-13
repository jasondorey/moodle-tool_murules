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
 * Admin settings and pages for tool_murules.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */

if ($hassiteconfig) {
    // Prefer MuTMS Programs category when present; else Users.
    $parent = $ADMIN->locate('tool_muprog') ? 'tool_muprog' : 'users';

    $ADMIN->add($parent, new admin_externalpage(
        'tool_murules_manage',
        get_string('managerules', 'tool_murules'),
        new moodle_url('/admin/tool/murules/index.php'),
        'tool/murules:manage'
    ));

    $settings = new admin_settingpage(
        'tool_murules_settings',
        get_string('settings', 'tool_murules'),
        'moodle/site:config'
    );
    $ADMIN->add($parent, $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configcheckbox(
            'tool_murules/killswitch',
            get_string('killswitch', 'tool_murules'),
            get_string('killswitch_desc', 'tool_murules'),
            0
        ));
        $settings->add(new admin_setting_configcheckbox(
            'tool_murules/realtimeobservers',
            get_string('realtimeobservers', 'tool_murules'),
            get_string('realtimeobservers_desc', 'tool_murules'),
            1
        ));
        $settings->add(new admin_setting_configtext(
            'tool_murules/maxbackfillbatch',
            get_string('maxbackfillbatch', 'tool_murules'),
            get_string('maxbackfillbatch_desc', 'tool_murules'),
            200,
            PARAM_INT
        ));
        $settings->add(new admin_setting_heading(
            'tool_murules_schedulednote',
            '',
            get_string('schedulednote', 'tool_murules')
        ));
    }
}
