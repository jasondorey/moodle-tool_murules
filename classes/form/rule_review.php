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

/**
 * Wizard step 3: Review / save.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rule_review extends \moodleform {

    protected function definition(): void {
        $mform = $this->_form;
        $custom = $this->_customdata;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'step', 'review');
        $mform->setType('step', PARAM_ALPHA);

        $mform->addElement('text', 'name', get_string('rulename', 'tool_murules'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('error_name', 'tool_murules'), 'required', null, 'client');
        if (!empty($custom['name'])) {
            $mform->setDefault('name', $custom['name']);
        }

        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'tool_murules'));
        $mform->setDefault('enabled', $custom['enabled'] ?? 1);

        $limits = [
            'once' => get_string('actionlimit_once', 'tool_murules'),
            'none' => get_string('actionlimit_none', 'tool_murules'),
        ];
        $mform->addElement('select', 'actionlimit', get_string('actionlimit', 'tool_murules'), $limits);
        $mform->setDefault('actionlimit', $custom['actionlimit'] ?? 'once');

        $mform->addElement('advcheckbox', 'includesuspended', get_string('includesuspended', 'tool_murules'));
        $mform->setDefault('includesuspended', $custom['includesuspended'] ?? 0);

        if (!empty($custom['when_summary'])) {
            $mform->addElement('static', 'when_summary', get_string('when', 'tool_murules'), s($custom['when_summary']));
        }
        if (!empty($custom['then_summary'])) {
            $mform->addElement('static', 'then_summary', get_string('then', 'tool_murules'), s($custom['then_summary']));
        }

        $this->add_action_buttons(true, get_string('save', 'tool_murules'));
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (trim($data['name'] ?? '') === '') {
            $errors['name'] = get_string('error_name', 'tool_murules');
        }
        return $errors;
    }
}
