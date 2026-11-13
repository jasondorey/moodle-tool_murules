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
 * User last login (G10) — uses user.lastaccess.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_last_login extends base {

    public function get_type(): string {
        return 'user_last_login';
    }

    public function get_name(): string {
        return get_string('condition:user_last_login', 'tool_murules');
    }

    public function get_category(): string {
        return 'user';
    }

    public function get_description(array $config): string {
        return get_string('condition:user_last_login_desc', 'tool_murules', (object)[
            'window' => helper::describe_time_window($config),
        ]);
    }

    public function get_events(): array {
        return [
            \core\event\user_loggedin::class,
        ];
    }

    public function is_scheduled(): bool {
        return true;
    }

    public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void {
        helper::add_time_window_elements($mform, 'ull_', $config, [
            'ever', 'never', 'before', 'after', 'during_last', 'prior_to_last',
        ], 'prior_to_last');
    }

    public function validate_config(array $data): array {
        return helper::validate_time_window($data, 'ull_');
    }

    public function extract_config(array $data): array {
        return helper::extract_time_window($data, 'ull_');
    }

    public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool {
        global $DB;
        $lastaccess = $DB->get_field('user', 'lastaccess', ['id' => $userid, 'deleted' => 0]);
        if ($lastaccess === false) {
            return false;
        }
        $ts = ((int)$lastaccess > 0) ? (int)$lastaccess : null;
        return helper::time_matches($ts, $config);
    }

    public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array {
        global $DB;
        $mode = $config['mode'] ?? 'prior_to_last';
        $guestid = (int)guest_user()->id;
        $params = [
            'fromuserid' => $fromuserid,
            'guestid' => $guestid,
        ];

        if ($mode === 'never') {
            $sql = "SELECT u.id
                      FROM {user} u
                     WHERE u.deleted = 0
                       AND u.id > :fromuserid
                       AND u.id <> :guestid
                       AND (u.lastaccess IS NULL OR u.lastaccess = 0)
                  ORDER BY u.id ASC";
            $records = $DB->get_records_sql($sql, $params, 0, $limit);
            return array_map(static fn($r) => (int)$r->id, $records);
        }

        $sql = "SELECT u.id, u.lastaccess
                  FROM {user} u
                 WHERE u.deleted = 0
                   AND u.id > :fromuserid
                   AND u.id <> :guestid
                   AND u.lastaccess > 0
              ORDER BY u.id ASC";
        $fetch = max($limit * 3, 500);
        $records = $DB->get_records_sql($sql, $params, 0, $fetch);
        $matched = [];
        foreach ($records as $rec) {
            if (helper::time_matches((int)$rec->lastaccess, $config)) {
                $matched[] = (int)$rec->id;
                if (count($matched) >= $limit) {
                    break;
                }
            }
        }
        return $matched;
    }
}
