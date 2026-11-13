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

namespace tool_murules\external;

/**
 * Shared capability/context checks for wizard AJAX.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait wizard_helper {

    /**
     * Require manage capability in system context.
     */
    protected static function require_manage(): void {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/murules:manage', $context);
    }

    /**
     * Decode JSON object argument into array.
     *
     * @param string $json
     * @return array
     */
    protected static function decode_json_object(string $json): array {
        if ($json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
