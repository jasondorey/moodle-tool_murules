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
 * Only the selected action's fields are rendered (same pattern as Event step).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_actions extends \moodleform {

    protected function definition(): void {
        $mform = $this->_form;
        $custom = $this->_customdata;
        $actiontype = (string)($custom['actiontype'] ?? 'allocate_muprog');
        $actionconfig = $custom['actionconfig'] ?? [];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'step', 'actions');
        $mform->setType('step', PARAM_ALPHA);

        $menu = registry::get_action_menu(true);
        $mform->addElement('select', 'actiontype', get_string('actiontype', 'tool_murules'), $menu);
        $mform->addRule('actiontype', get_string('error_action', 'tool_murules'), 'required', null, 'client');

        if (!$actiontype || !registry::get_action($actiontype)) {
            $actiontype = (string)array_key_first($menu);
        }
        $mform->setDefault('actiontype', $actiontype);

        $action = registry::get_action($actiontype);
        if ($action && $action->is_available()) {
            $mform->addElement('header', 'ahdr_config', $action->get_name());
            $mform->setExpanded('ahdr_config', true);
            $action->add_config_form_elements($mform, $actionconfig);
        }

        $this->add_wizard_buttons(
            get_string('saveandcontinue', 'tool_murules'),
            get_string('back', 'tool_murules')
        );
    }

    /**
     * @param string $submitlabel
     * @param string $cancellabel
     */
    protected function add_wizard_buttons(string $submitlabel, string $cancellabel): void {
        $mform = $this->_form;
        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = $mform->createElement('cancel', 'cancel', $cancellabel);
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
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
