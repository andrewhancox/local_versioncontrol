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
 * @package local_versioncontrol
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2021, Andrew Hancox
 */

require_once(__DIR__ . '/upgradelib.php');

function xmldb_local_versioncontrol_upgrade($oldversion) {

    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016052409) {
        // Define table message_popup_notifications to be created.
        $table = new xmldb_table('local_versioncontrol_commit');

        $field = new xmldb_field('message', XMLDB_TYPE_TEXT);

        // Conditionally launch add field lawfulbases.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Quiz savepoint reached.
        upgrade_plugin_savepoint(true, 2016052409, 'local', 'versioncontrol');
    }

    if ($oldversion < 2021020405) {
        global $CFG;

        $folders = glob($CFG->tempdir . '/local_versioncontrol_*');
        foreach ($folders as $tempfolder) {
            $files = glob($tempfolder . '/*');

            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            rmdir($tempfolder);
        }

        upgrade_plugin_savepoint(true, 2021020405, 'local', 'versioncontrol');
    }

    if ($oldversion < 2025082900) {
        $table = new xmldb_table('local_versioncontrol_enabledevent');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('eventname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025082900, 'local', 'versioncontrol');
    }

    if ($oldversion < 2025083000) {
        global $CFG;

        // Add default enabled events to the table on upgrade.
        local_versioncontrol_upgrader::add_default_enabled_events();

        upgrade_plugin_savepoint(true, 2025083000, 'local', 'versioncontrol');
    }

    return true;
}
