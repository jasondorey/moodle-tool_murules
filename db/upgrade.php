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

/**
 * Upgrade steps for tool_murules.
 *
 * @package    tool_murules
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade tool_murules.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_tool_murules_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Wave B adds condition classes + observers only (no schema change).
    // Bumping version refreshes classmap / event observers via Moodle upgrade.
    if ($oldversion < 2026111301) {
        upgrade_plugin_savepoint(true, 2026111301, 'tool', 'murules');
    }
    if ($oldversion < 2026111302) {
        // Wave C: MuTMS conditions/actions + message provider (no schema change).
        upgrade_plugin_savepoint(true, 2026111302, 'tool', 'murules');
    }
    if ($oldversion < 2026111303) {
        // Wizard polish: course→CM AJAX webservice + Review matching estimate.
        upgrade_plugin_savepoint(true, 2026111303, 'tool', 'murules');
    }
    if ($oldversion < 2026111304) {
        // Fix Event/Actions Save-and-continue: single-panel forms (no hidden requireds).
        upgrade_plugin_savepoint(true, 2026111304, 'tool', 'murules');
    }
    if ($oldversion < 2026111305) {
        // UX marathon: empty states, wizard step a11y, save notice, CM redisplay options.
        upgrade_plugin_savepoint(true, 2026111305, 'tool', 'murules');
    }
    if ($oldversion < 2026111306) {
        // Log status pills styling.
        upgrade_plugin_savepoint(true, 2026111306, 'tool', 'murules');
    }
    if ($oldversion < 2026111307) {
        // Overview filters, allocate empty hint, review recap styling.
        upgrade_plugin_savepoint(true, 2026111307, 'tool', 'murules');
    }
    if ($oldversion < 2026111308) {
        // Kill-switch overview banner + log status filters.
        upgrade_plugin_savepoint(true, 2026111308, 'tool', 'murules');
    }
    if ($oldversion < 2026111309) {
        // Review help buttons, overview lead copy, log profile links.
        upgrade_plugin_savepoint(true, 2026111309, 'tool', 'murules');
    }
    if ($oldversion < 2026111310) {
        // Empty-state hints for deallocate/cohort actions.
        upgrade_plugin_savepoint(true, 2026111310, 'tool', 'murules');
    }
    if ($oldversion < 2026111311) {
        // Card/filter focus-visible and hover affordances.
        upgrade_plugin_savepoint(true, 2026111311, 'tool', 'murules');
    }
    if ($oldversion < 2026111312) {
        // Delete confirm copy + Event WHEN preview when editing saved config.
        upgrade_plugin_savepoint(true, 2026111312, 'tool', 'murules');
    }
    if ($oldversion < 2026111313) {
        // Wizard back-to-rules link, Actions WHEN context, notify subject help.
        upgrade_plugin_savepoint(true, 2026111313, 'tool', 'murules');
    }
    if ($oldversion < 2026111314) {
        // Overview settings link + matching-estimate note on Review.
        upgrade_plugin_savepoint(true, 2026111314, 'tool', 'murules');
    }
    if ($oldversion < 2026111315) {
        // Overview banner when realtime observers are disabled.
        upgrade_plugin_savepoint(true, 2026111315, 'tool', 'murules');
    }
    if ($oldversion < 2026111316) {
        // Overview name search.
        upgrade_plugin_savepoint(true, 2026111316, 'tool', 'murules');
    }
    if ($oldversion < 2026111317) {
        // Card last-applied timestamp from match log.
        upgrade_plugin_savepoint(true, 2026111317, 'tool', 'murules');
    }
    if ($oldversion < 2026111318) {
        // Overview "Showing:" label for active filter/search.
        upgrade_plugin_savepoint(true, 2026111318, 'tool', 'murules');
    }
    if ($oldversion < 2026111319) {
        // Wizard Cancel vs Back button labels.
        upgrade_plugin_savepoint(true, 2026111319, 'tool', 'murules');
    }
    if ($oldversion < 2026111320) {
        // Log showing context + result count line.
        upgrade_plugin_savepoint(true, 2026111320, 'tool', 'murules');
    }
    if ($oldversion < 2026111321) {
        // Empty-state copy polish + create CTA on filter-empty.
        upgrade_plugin_savepoint(true, 2026111321, 'tool', 'murules');
    }
    if ($oldversion < 2026111322) {
        // Overview "Showing N of M rules" count line.
        upgrade_plugin_savepoint(true, 2026111322, 'tool', 'murules');
    }
    if ($oldversion < 2026111323) {
        // Log CSV export + Back to rules nav label.
        upgrade_plugin_savepoint(true, 2026111323, 'tool', 'murules');
    }
    if ($oldversion < 2026111324) {
        // Card "Not applied yet" + named aria-labels on actions.
        upgrade_plugin_savepoint(true, 2026111324, 'tool', 'murules');
    }
    if ($oldversion < 2026111325) {
        // Wizard "Step N of 3" / Last step progress line.
        upgrade_plugin_savepoint(true, 2026111325, 'tool', 'murules');
    }
    if ($oldversion < 2026111326) {
        // Delete confirm shows WHEN/THEN recap.
        upgrade_plugin_savepoint(true, 2026111326, 'tool', 'murules');
    }
    if ($oldversion < 2026111327) {
        // Enable/disable/delete success notices on overview redirect.
        upgrade_plugin_savepoint(true, 2026111327, 'tool', 'murules');
    }
    if ($oldversion < 2026111328) {
        // Stronger contrast for log status + card enabled/broken badges.
        upgrade_plugin_savepoint(true, 2026111328, 'tool', 'murules');
    }
    if ($oldversion < 2026111329) {
        // Disabled badge fill + card/actions labelledby a11y.
        upgrade_plugin_savepoint(true, 2026111329, 'tool', 'murules');
    }
    if ($oldversion < 2026111330) {
        // Search autofocus when query present + hint for screen readers.
        upgrade_plugin_savepoint(true, 2026111330, 'tool', 'murules');
    }
    if ($oldversion < 2026111331) {
        // Sticky log table header inside scroll region.
        upgrade_plugin_savepoint(true, 2026111331, 'tool', 'murules');
    }
    if ($oldversion < 2026111332) {
        // Card "No matches yet" when match stats are all zero.
        upgrade_plugin_savepoint(true, 2026111332, 'tool', 'murules');
    }
    if ($oldversion < 2026111333) {
        // Log status tabs no longer overwrite SQL :ruleid params.
        upgrade_plugin_savepoint(true, 2026111333, 'tool', 'murules');
    }
    if ($oldversion < 2026111334) {
        // Prefer Bootstrap / Moodle Design System components over custom chrome.
        upgrade_plugin_savepoint(true, 2026111334, 'tool', 'murules');
    }
    if ($oldversion < 2026111335) {
        // Local Overview dashboard + Rules/Logs tabs.
        upgrade_plugin_savepoint(true, 2026111335, 'tool', 'murules');
    }

    if ($oldversion < 2026111336) {
        // Multi-condition AND: child condition rows + migrate legacy single columns.
        $table = new xmldb_table('tool_murules_condition');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('conditiontype', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('conditionconfig', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('ruleid', XMLDB_KEY_FOREIGN, ['ruleid'], 'tool_murules_rule', ['id']);
        $table->add_index('ruleid_sort', XMLDB_INDEX_NOTUNIQUE, ['ruleid', 'sortorder']);
        $table->add_index('type_rule', XMLDB_INDEX_NOTUNIQUE, ['conditiontype', 'ruleid']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        $rules = $DB->get_records('tool_murules_rule', null, 'id ASC', 'id, conditiontype, conditionconfig');
        foreach ($rules as $rulerec) {
            if ($DB->record_exists('tool_murules_condition', ['ruleid' => $rulerec->id])) {
                continue;
            }
            if ($rulerec->conditiontype === '' || $rulerec->conditiontype === null) {
                continue;
            }
            $DB->insert_record('tool_murules_condition', (object)[
                'ruleid' => (int)$rulerec->id,
                'sortorder' => 0,
                'conditiontype' => (string)$rulerec->conditiontype,
                'conditionconfig' => (string)($rulerec->conditionconfig ?? '{}'),
            ]);
        }

        upgrade_plugin_savepoint(true, 2026111336, 'tool', 'murules');
    }

    if ($oldversion < 2026111337) {
        // Multi-action wizard UI (engine already supported multiple THEN actions).
        upgrade_plugin_savepoint(true, 2026111337, 'tool', 'murules');
    }

    return true;
}
