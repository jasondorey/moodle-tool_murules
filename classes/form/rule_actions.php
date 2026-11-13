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

namespace tool_murules\form;

use tool_murules\local\registry;

/**
 * Wizard step 2: Actions (THEN).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_actions extends \moodleform {

    protected function definition(): void {
        $mform = $this->_form;
        $custom = $this->_customdata;
        $actiontype = $custom['actiontype'] ?? 'allocate_muprog';
        $actionconfig = $custom['actionconfig'] ?? [];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'step', 'actions');
        $mform->setType('step', PARAM_ALPHA);

        $menu = registry::get_action_menu(true);
        $mform->addElement('select', 'actiontype', get_string('actiontype', 'tool_murules'), $menu);
        $mform->addRule('actiontype', get_string('error_action', 'tool_murules'), 'required', null, 'client');
        $mform->setDefault('actiontype', $actiontype ?: 'allocate_muprog');

        foreach (registry::get_actions() as $type => $action) {
            if (!$action->is_available()) {
                continue;
            }
            $mform->addElement('header', 'ahdr_' . $type, $action->get_name());
            $cfg = ($type === $actiontype) ? $actionconfig : [];
            $action->add_config_form_elements($mform, $cfg);
        }

        $this->add_action_buttons(true, get_string('saveandcontinue', 'tool_murules'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $type = $data['actiontype'] ?? '';
        $action = registry::get_action($type);
        if (!$action) {
            $errors['actiontype'] = get_string('error_action', 'tool_murules');
            return $errors;
        }
        return array_merge($errors, $action->validate_config($data));
    }
}
