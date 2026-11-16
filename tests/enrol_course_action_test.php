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

use tool_murules\local\action\enrol_course;
use tool_murules\local\wizard\config_bridge;
use tool_murules\local\wizard\field_schemas;

/**
 * Enrol-in-course action tests.
 *
 * @package    tool_murules
 * @covers     \tool_murules\local\action\enrol_course
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class enrol_course_action_test extends \advanced_testcase {

    public function test_schema_and_bridge(): void {
        $this->resetAfterTest();
        $schema = field_schemas::for_type('action', 'enrol_course');
        $names = array_column($schema['fields'], 'name');
        $this->assertContains('courseid', $names);
        $this->assertContains('roleid', $names);

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        // Bridge validation for a fresh (not-yet-enrolled) course.
        $target = $this->getDataGenerator()->create_course();
        $roleid = (int)$schema['defaults']['roleid'];
        $good = config_bridge::validate_and_extract('action', 'enrol_course', [
            'courseid' => (int)$target->id,
            'roleid' => (string)$roleid,
        ]);
        $this->assertTrue($good['ok'], json_encode($good['errors']));
        $this->assertSame((int)$target->id, $good['config']['courseid']);
        $this->assertSame('manual', $good['config']['enrolmethod']);
        $this->assertSame($roleid, $good['config']['roleid']);
        $this->assertNotEmpty($student->id);
    }

    public function test_execute_enrols_and_skips_existing(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $roleid = (int)$DB->get_field('role', 'id', ['shortname' => 'student']);

        $action = new enrol_course();
        $config = [
            'courseid' => (int)$course->id,
            'enrolmethod' => 'manual',
            'roleid' => $roleid,
        ];
        $rule = (object)['id' => 1, 'name' => 't'];
        $actionrec = (object)['id' => 1];

        $first = $action->execute((int)$user->id, $config, $rule, $actionrec);
        $this->assertSame('applied', $first['status'], $first['reason'] ?? '');
        $this->assertTrue(is_enrolled(\context_course::instance($course->id), $user, '', false));

        $second = $action->execute((int)$user->id, $config, $rule, $actionrec);
        $this->assertSame('skipped', $second['status']);
        $this->assertStringContainsString('already enrolled', strtolower($second['reason']));
    }
}
