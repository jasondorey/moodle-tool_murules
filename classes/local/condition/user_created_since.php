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

namespace tool_murules\local\condition;

use MoodleQuickForm;

/**
 * Time since user was created (G7).
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_created_since extends base {

    public function get_type(): string {
        return 'user_created_since';
    }

    public function get_name(): string {
        return get_string('condition:user_created_since', 'tool_murules');
    }

    public function get_description(array $config): string {
        return get_string('condition:user_created_since_desc', 'tool_murules', (object)[
            'window' => helper::describe_time_window($config),
        ]);
    }

    public function get_events(): array {
        return [
            \core\event\user_created::class,
        ];
    }

    public function is_scheduled(): bool {
        return true;
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        helper::add_time_window_elements($mform, 'ucs_', $config, [
            'during_last', 'prior_to_last',
        ], 'during_last');
    }

    public function validate_config(array $data): array {
        return helper::validate_time_window($data, 'ucs_');
    }

    public function extract_config(array $data): array {
        return helper::extract_time_window($data, 'ucs_');
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $timecreated = $DB->get_field('user', 'timecreated', ['id' => $userid, 'deleted' => 0]);
        if ($timecreated === false) {
            return false;
        }
        return helper::time_matches((int)$timecreated, $config);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $now = time();
        $mode = $config['mode'] ?? 'during_last';
        $seconds = helper::relative_seconds((int)($config['amount'] ?? 0), (string)($config['unit'] ?? 'days'));
        $cutoff = $now - $seconds;
        $guestid = (int)guest_user()->id;

        if ($mode === 'prior_to_last') {
            $sql = "SELECT u.id
                      FROM {user} u
                     WHERE u.deleted = 0
                       AND u.id > :fromuserid
                       AND u.id <> :guestid
                       AND u.timecreated < :cutoff
                       AND u.timecreated > 0
                  ORDER BY u.id ASC";
        } else {
            $sql = "SELECT u.id
                      FROM {user} u
                     WHERE u.deleted = 0
                       AND u.id > :fromuserid
                       AND u.id <> :guestid
                       AND u.timecreated >= :cutoff
                  ORDER BY u.id ASC";
        }
        $records = $DB->get_records_sql($sql, [
            'fromuserid' => $fromuserid,
            'guestid' => $guestid,
            'cutoff' => $cutoff,
        ], 0, $limit);
        return array_map(static fn($r) => (int)$r->id, $records);
    }
}
