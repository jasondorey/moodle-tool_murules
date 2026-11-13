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

namespace tool_murules\task;

use tool_murules\local\engine;

/**
 * Ad-hoc task: evaluate rules for a user after an event.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class process_event extends \core\task\adhoc_task {

    public function get_name(): string {
        return get_string('task_process_event', 'tool_murules');
    }

    public function execute(): void {
        $data = $this->get_custom_data();
        $userid = (int)($data->userid ?? 0);
        $ruleids = [];
        if (!empty($data->ruleids) && is_array($data->ruleids)) {
            $ruleids = array_map('intval', $data->ruleids);
        } else if (!empty($data->ruleids) && is_object($data->ruleids)) {
            $ruleids = array_map('intval', (array)$data->ruleids);
        }
        if (!$userid) {
            return;
        }
        engine::process_user($userid, $ruleids, null);
    }
}
