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

namespace tool_murules\local\action;

use MoodleQuickForm;
use stdClass;

/**
 * Base class for rule actions.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {

    /**
     * @return string
     */
    abstract public function get_type(): string;

    /**
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * @param array $config
     * @return string
     */
    abstract public function get_description(array $config): string;

    /**
     * @param MoodleQuickForm $mform
     * @param array $config
     */
    abstract public function add_config_form_elements(MoodleQuickForm $mform, array $config = []): void;

    /**
     * @param array $data
     * @return array
     */
    abstract public function validate_config(array $data): array;

    /**
     * @param array $data
     * @return array
     */
    abstract public function extract_config(array $data): array;

    /**
     * Execute action for one user.
     *
     * Return array with keys: status (applied|skipped|failed), reason, muprogallocationid (optional).
     *
     * @param int $userid
     * @param array $config
     * @param stdClass $rule
     * @param stdClass $actionrec
     * @return array
     */
    abstract public function execute(int $userid, array $config, stdClass $rule, stdClass $actionrec): array;

    /**
     * @param array $config
     * @return bool
     */
    public function is_broken(array $config): bool {
        return false;
    }

    /**
     * @return bool
     */
    public function is_available(): bool {
        return true;
    }

    /**
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
