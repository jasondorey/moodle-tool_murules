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
 * Wizard step 1: Event (WHEN).
 *
 * Only the selected condition's fields are rendered so client-side required
 * rules cannot block submit from hidden panels.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_event extends \moodleform {

    protected function definition(): void {
        $mform = $this->_form;
        $custom = $this->_customdata;
        $conditiontype = (string)($custom['conditiontype'] ?? '');
        $conditionconfig = $custom['conditionconfig'] ?? [];

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'step', 'event');
        $mform->setType('step', PARAM_ALPHA);

        $menu = registry::get_condition_menu(true);
        $mform->addElement('selectgroups', 'conditiontype', get_string('conditiontype', 'tool_murules'), $menu);
        $mform->addRule('conditiontype', get_string('error_condition', 'tool_murules'), 'required', null, 'client');

        $selected = $conditiontype;
        if (!$selected || !registry::get_condition($selected)) {
            foreach ($menu as $group) {
                $selected = (string)array_key_first($group);
                break;
            }
        }
        $mform->setDefault('conditiontype', $selected);

        $condition = registry::get_condition($selected);
        if ($condition && $condition->is_available()) {
            $mform->addElement('header', 'hdr_config', $condition->get_name());
            $mform->setExpanded('hdr_config', true);
            $condition->add_config_form_elements($mform, $conditionconfig);
        }

        $this->add_action_buttons(true, get_string('saveandcontinue', 'tool_murules'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        $type = $data['conditiontype'] ?? '';
        $condition = registry::get_condition($type);
        if (!$condition) {
            $errors['conditiontype'] = get_string('error_condition', 'tool_murules');
            return $errors;
        }
        return array_merge($errors, $condition->validate_config($data));
    }
}
