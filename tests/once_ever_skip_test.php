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

namespace tool_murules;

use tool_murules\local\engine;
use tool_murules\local\rule;

/**
 * Once-ever applied users must not generate skip-log spam on rematch.
 *
 * @package    tool_murules
 * @covers     \tool_murules\local\engine
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class once_ever_skip_test extends \advanced_testcase {

    public function test_second_apply_does_not_insert_skip_row(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('killswitch', 0, 'tool_murules');

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student']);

        // Condition that remains true after enrol (cron rematch still hits the user).
        $ruleid = rule::save((object)[
            'name' => 'once-ever enrol smoke',
            'enabled' => 0,
            'actionlimit' => 'once',
            'includesuspended' => 0,
            'conditions' => [[
                'conditiontype' => 'user_created_since',
                'conditionconfig' => ['mode' => 'ever'],
            ]],
            'actions' => [[
                'actiontype' => 'enrol_course',
                'actionconfig' => [
                    'courseid' => (int)$course->id,
                    'enrolmethod' => 'manual',
                    'roleid' => $roleid,
                ],
            ]],
        ]);
        $rulerec = rule::get($ruleid);

        $first = engine::apply_rule_to_user($rulerec, (int)$user->id, null);
        $this->assertNotEmpty($first);
        $this->assertSame('applied', $first[0]['status'] ?? '', json_encode($first));

        $countafterfirst = $DB->count_records('tool_murules_match', [
            'ruleid' => $ruleid,
            'userid' => (int)$user->id,
        ]);
        $this->assertSame(1, $countafterfirst);

        // Second rematch (cron/backfill style): silent — no new match rows.
        $second = engine::apply_rule_to_user($rulerec, (int)$user->id, null);
        $this->assertSame([], $second);
        $this->assertSame(1, $DB->count_records('tool_murules_match', [
            'ruleid' => $ruleid,
            'userid' => (int)$user->id,
        ]));
        $this->assertSame(0, $DB->count_records('tool_murules_match', [
            'ruleid' => $ruleid,
            'userid' => (int)$user->id,
            'status' => 'skipped',
        ]));
    }

    public function test_failed_match_may_retry(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('killswitch', 0, 'tool_murules');

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student']);

        $ruleid = rule::save((object)[
            'name' => 'once-ever failed retry',
            'enabled' => 0,
            'actionlimit' => 'once',
            'includesuspended' => 0,
            'conditions' => [[
                'conditiontype' => 'user_created_since',
                'conditionconfig' => ['mode' => 'ever'],
            ]],
            'actions' => [[
                'actiontype' => 'enrol_course',
                'actionconfig' => [
                    'courseid' => (int)$course->id,
                    'enrolmethod' => 'manual',
                    'roleid' => $roleid,
                ],
            ]],
        ]);
        $rulerec = rule::get($ruleid);
        $actions = rule::get_actions($ruleid);
        $actionid = (int)$actions[0]->id;

        $DB->insert_record('tool_murules_match', (object)[
            'ruleid' => $ruleid,
            'userid' => (int)$user->id,
            'actionid' => $actionid,
            'status' => 'failed',
            'reason' => 'seeded failure',
            'muprogallocationid' => null,
            'timecreated' => time(),
        ]);

        $result = engine::apply_rule_to_user($rulerec, (int)$user->id, null);
        $this->assertNotEmpty($result);
        $this->assertSame('applied', $result[0]['status'] ?? '', json_encode($result));
        $this->assertSame(1, $DB->count_records('tool_murules_match', [
            'ruleid' => $ruleid,
            'userid' => (int)$user->id,
            'status' => 'applied',
        ]));
    }
}
