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

use moodle_url;
use tool_murules\local\registry;

/**
 * Wizard step 1: Event (WHEN) configuration for a picked condition.
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
        $changepickurl = $custom['changepickurl'] ?? null;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'step', 'event');
        $mform->setType('step', PARAM_ALPHA);
        $mform->addElement('hidden', 'pick', $conditiontype);
        $mform->setType('pick', PARAM_ALPHANUMEXT);

        $condition = registry::get_condition($conditiontype);
        if (!$condition || !$condition->is_available()) {
            $mform->addElement('static', 'nopick', '', get_string('error_condition', 'tool_murules'));
            $this->add_action_buttons(true, get_string('cancelwizard', 'tool_murules'));
            return;
        }

        $mform->addElement('hidden', 'conditiontype', $conditiontype);
        $mform->setType('conditiontype', PARAM_ALPHANUMEXT);
        $mform->setConstant('conditiontype', $conditiontype);

        $pickedlabel = $condition->get_name();
        if ($changepickurl instanceof moodle_url) {
            $pickedlabel .= ' ' . \html_writer::link(
                $changepickurl,
                get_string('changecondition', 'tool_murules'),
                ['class' => 'ms-2 small']
            );
        }
        $mform->addElement('static', 'picked_condition', get_string('conditiontype', 'tool_murules'), $pickedlabel);

        $mform->addElement('header', 'hdr_config', get_string('conditionconfig', 'tool_murules'));
        $mform->setExpanded('hdr_config', true);
        $condition->add_config_form_elements($mform, $conditionconfig);
        if (!empty($conditionconfig) && !$condition->is_broken($conditionconfig)) {
            $preview = $condition->get_description($conditionconfig);
            $mform->addElement(
                'static',
                'when_preview',
                get_string('when', 'tool_murules'),
                \html_writer::div(s($preview), 'tool_murules-recap tool_murules-recap-when')
            );
        }

        $hasconditions = !empty($custom['hasconditions']);
        $this->add_wizard_buttons($hasconditions);
    }

    /**
     * Add condition / continue / cancel.
     *
     * @param bool $hasconditions
     */
    protected function add_wizard_buttons(bool $hasconditions): void {
        $mform = $this->_form;
        $buttonarray = [];
        // When conditions already exist, primary submit only adds another AND condition.
        $buttonarray[] = $mform->createElement(
            'submit',
            'submitbutton',
            $hasconditions
                ? get_string('addanothercondition', 'tool_murules')
                : get_string('saveandcontinue', 'tool_murules')
        );
        if (!$hasconditions) {
            $buttonarray[] = $mform->createElement(
                'submit',
                'addanother',
                get_string('addanothercondition', 'tool_murules')
            );
        }
        $buttonarray[] = $mform->createElement(
            'cancel',
            'cancel',
            get_string('cancelwizard', 'tool_murules')
        );
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
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
