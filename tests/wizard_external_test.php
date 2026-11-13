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

/**
 * Wizard AJAX web service tests.
 *
 * @package    tool_murules
 * @covers     \tool_murules\external\get_wizard_bootstrap
 * @covers     \tool_murules\external\get_editor_schema
 * @covers     \tool_murules\external\validate_editor_config
 * @covers     \tool_murules\external\save_rule
 * @covers     \tool_murules\external\search_courses
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class wizard_external_test extends \core_external\tests\externallib_testcase {

    public function test_bootstrap_and_schema_and_save(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $boot = \tool_murules\external\get_wizard_bootstrap::execute(0);
        $payload = json_decode($boot['payload'], true);
        $this->assertIsArray($payload);
        $this->assertNotEmpty($payload['conditionCatalog']);

        $schema = \tool_murules\external\get_editor_schema::execute('condition', 'user_created_since');
        $schemaData = json_decode($schema['payload'], true);
        $this->assertNotEmpty($schemaData['fields']);

        $validated = \tool_murules\external\validate_editor_config::execute(
            'condition',
            'user_created_since',
            json_encode(['mode' => 'prior_to_last', 'amount' => 21, 'unit' => 'days'])
        );
        $this->assertTrue($validated['ok']);

        $course = $this->getDataGenerator()->create_course(['fullname' => 'Murules Search Course']);
        $search = \tool_murules\external\search_courses::execute('Murules Search', 0, 20);
        $ids = array_column($search['courses'], 'id');
        $this->assertContains((int)$course->id, $ids);

        $save = \tool_murules\external\save_rule::execute(json_encode([
            'name' => 'PHPUnit react wizard',
            'enabled' => 0,
            'actionlimit' => 'once',
            'includesuspended' => 0,
            'conditions' => [[
                'conditiontype' => 'user_created_since',
                'conditionconfig' => ['mode' => 'prior_to_last', 'amount' => 21, 'unit' => 'days'],
            ]],
            'actions' => [
                [
                    'actiontype' => 'notify_user',
                    'actionconfig' => ['subject' => 'One', 'body' => 'Body one'],
                ],
                [
                    'actiontype' => 'notify_user',
                    'actionconfig' => ['subject' => 'Two', 'body' => 'Body two'],
                ],
            ],
        ]));
        $this->assertTrue($save['ok'], $save['message']);
        $this->assertGreaterThan(0, $save['ruleid']);
        $this->assertEquals(1, $DB->count_records('tool_murules_condition', ['ruleid' => $save['ruleid']]));
        $this->assertEquals(2, $DB->count_records('tool_murules_action', ['ruleid' => $save['ruleid']]));
    }

    public function test_save_requires_name(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $save = \tool_murules\external\save_rule::execute(json_encode([
            'name' => '',
            'conditions' => [[
                'conditiontype' => 'user_created_since',
                'conditionconfig' => ['mode' => 'prior_to_last', 'amount' => 7, 'unit' => 'days'],
            ]],
            'actions' => [[
                'actiontype' => 'notify_user',
                'actionconfig' => ['subject' => 'S', 'body' => 'B'],
            ]],
        ]));
        $this->assertFalse($save['ok']);
        $errors = json_decode($save['errorsjson'], true);
        $this->assertArrayHasKey('name', $errors);
    }
}
