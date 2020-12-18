<?php
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

    return true;
}
