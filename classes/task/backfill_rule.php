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
 * Ad-hoc task: backfill matches for one rule.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backfill_rule extends \core\task\adhoc_task {

    public function get_name(): string {
        return get_string('task_backfill_rule', 'tool_murules');
    }

    public function execute(): void {
        $data = $this->get_custom_data();
        $ruleid = (int)($data->ruleid ?? 0);
        $fromuserid = (int)($data->fromuserid ?? 0);
        if (!$ruleid) {
            return;
        }
        engine::backfill_rule($ruleid, $fromuserid);
    }
}
