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
 * Base class for rule conditions.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {

    /**
     * Stable type key stored in tool_murules_rule.conditiontype.
     *
     * @return string
     */
    abstract public function get_type(): string;

    /**
     * Human name.
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Picker / menu category key (enrollment, course, activity, …).
     *
     * @return string
     */
    public function get_category(): string {
        return 'general';
    }

    /**
     * Human WHEN summary for a config.
     *
     * @param array $config
     * @return string
     */
    abstract public function get_description(array $config): string;

    /**
     * Add wizard form elements for this condition.
     *
     * @param MoodleQuickForm $mform
     * @param array $config
     */
    abstract public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void;

    /**
     * Validate submitted config.
     *
     * @param array $data
     * @return array field => error
     */
    abstract public function validate_config(array $data): array;

    /**
     * Extract config array from form data.
     *
     * @param array $data
     * @return array
     */
    abstract public function extract_config(array $data): array;

    /**
     * Fully-qualified event class names this condition listens to.
     *
     * @return string[]
     */
    public function get_events(): array {
        return [];
    }

    /**
     * Whether cron must scan for matches.
     *
     * @return bool
     */
    public function is_scheduled(): bool {
        return false;
    }

    /**
     * Does this user currently match?
     *
     * @param int $userid
     * @param array $config
     * @param \core\event\base|null $event
     * @return bool
     */
    abstract public function user_matches(int $userid, array $config, ?\core\event\base $event = null): bool;

    /**
     * User ids matching for backfill / scheduled scan (batched).
     *
     * @param array $config
     * @param int $limit
     * @param int $fromuserid exclusive lower bound for paging
     * @return int[]
     */
    abstract public function get_matching_user_ids(array $config, int $limit = 500, int $fromuserid = 0): array;

    /**
     * Config references missing entities.
     *
     * @param array $config
     * @return bool
     */
    public function is_broken(array $config): bool {
        return false;
    }

    /**
     * Whether this condition can be offered in the wizard.
     *
     * @return bool
     */
    public function is_available(): bool {
        return true;
    }

    /**
     * Decode JSON config safely.
     *
     * @param string|null $json
     * @return array
     */
    public static function decode_config(?string $json): array {
        if ($json === null || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
