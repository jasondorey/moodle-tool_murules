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

use tool_murules\local\wizard\config_bridge;
use tool_murules\local\wizard\field_schemas;
use tool_murules\local\wizard\state;

/**
 * Wizard schema / config bridge tests.
 *
 * @package    tool_murules
 * @covers     \tool_murules\local\wizard\config_bridge
 * @covers     \tool_murules\local\wizard\field_schemas
 * @covers     \tool_murules\local\wizard\state
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class wizard_bridge_test extends \advanced_testcase {

    public function test_bootstrap_catalog_structure(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $boot = state::bootstrap(0);
        $this->assertArrayHasKey('draft', $boot);
        $this->assertArrayHasKey('conditionCatalog', $boot);
        $this->assertArrayHasKey('actionCatalog', $boot);
        $this->assertArrayHasKey('strings', $boot);
        $this->assertSame(0, $boot['draft']['id']);
        $this->assertIsArray($boot['conditionCatalog']);
        $this->assertNotEmpty($boot['conditionCatalog']);
        $first = $boot['conditionCatalog'][0];
        $this->assertArrayHasKey('key', $first);
        $this->assertArrayHasKey('label', $first);
        $this->assertArrayHasKey('items', $first);
        $this->assertNotEmpty($boot['actionCatalog']);
        $this->assertArrayHasKey('retry', $boot['strings']);
    }

    public function test_condition_schema_user_enrolled(): void {
        $this->resetAfterTest();
        $schema = field_schemas::for_type('condition', 'user_enrolled');
        $this->assertNotEmpty($schema['fields']);
        $names = array_column($schema['fields'], 'name');
        $this->assertContains('courseid', $names);
        $this->assertContains('enrolmethod', $names);
        $this->assertArrayHasKey('defaults', $schema);
    }

    public function test_action_schema_notify_user(): void {
        $this->resetAfterTest();
        $schema = field_schemas::for_type('action', 'notify_user');
        $names = array_column($schema['fields'], 'name');
        $this->assertContains('subject', $names);
        $this->assertContains('body', $names);
    }

    public function test_validate_time_window_amount(): void {
        $this->resetAfterTest();
        $bad = config_bridge::validate_and_extract('condition', 'user_created_since', [
            'mode' => 'prior_to_last',
            'amount' => 0,
            'unit' => 'days',
        ]);
        $this->assertFalse($bad['ok']);
        $this->assertArrayHasKey('amount', $bad['errors']);

        $good = config_bridge::validate_and_extract('condition', 'user_created_since', [
            'mode' => 'prior_to_last',
            'amount' => 14,
            'unit' => 'days',
        ]);
        $this->assertTrue($good['ok']);
        $this->assertSame('prior_to_last', $good['config']['mode']);
        $this->assertSame(14, $good['config']['amount']);
    }

    public function test_validate_notify_action(): void {
        $this->resetAfterTest();
        $bad = config_bridge::validate_and_extract('action', 'notify_user', [
            'subject' => '',
            'body' => '',
        ]);
        $this->assertFalse($bad['ok']);

        $good = config_bridge::validate_and_extract('action', 'notify_user', [
            'subject' => 'Hello',
            'body' => 'Welcome {$a->fullname}',
        ]);
        $this->assertTrue($good['ok']);
        $this->assertSame('Hello', $good['config']['subject']);
    }

    public function test_field_map_time_window_after_mode(): void {
        $this->resetAfterTest();
        $form = config_bridge::to_form_data('condition', 'user_last_login', [
            'mode' => 'after',
            'datetime' => 1700000000,
            'amount' => 0,
            'unit' => 'days',
        ]);
        $this->assertSame('after', $form['ull_mode']);
        $this->assertSame(1700000000, $form['ull_datetime_after']);
    }
}
