<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    elis
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_rlipexport_version1elis_upgrade($oldversion=0) {
    global $DB;

    $result = true;

    $dbman = $DB->get_manager();

    if ($result && $oldversion < 2012071200) {
        // Define table rlipexport_version1elis_fld to be created
        $table = new xmldb_table('rlipexport_version1elis_field');

        // Adding fields to table rlipexport_version1elis_field
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fieldid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('header', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fieldorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table rlipexport_version1elis_field
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table rlipexport_version1elis_field
        $table->add_index('fieldid_ix', XMLDB_INDEX_UNIQUE, array('fieldid'));

        // Conditionally launch create table for rlipexport_version1elis_field
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // plugin savepoint reached
        upgrade_plugin_savepoint(true, 2012071200, 'rlipexport', 'version1elis');
    }

    if ($result && $oldversion < 2012080100) {
        $table = new xmldb_table('rlipexport_version1elis_field');
        $result = $result && !empty($table) && $dbman->table_exists($table);
        if ($result) {
            $dbman->rename_table($table, 'rlipexport_version1elis_fld');
            $result = $dbman->table_exists('rlipexport_version1elis_fld');
        }

        // plugin savepoint reached
        upgrade_plugin_savepoint($result, 2012080100, 'rlipexport', 'version1elis');
    }

    if ($result && $oldversion < 2013022801) {

        // Add fieldset field, migrate data, update indexes.
        if ($dbman->field_exists('rlipexport_version1elis_fld', 'fieldset') === false) {
            $existing_fields = $DB->get_recordset('rlipexport_version1elis_fld');

            $table = new xmldb_table('rlipexport_version1elis_fld');

            // Remove old fieldid field and associated index.
            $fieldid_ix = new xmldb_index('fieldid_ix', XMLDB_INDEX_UNIQUE, array('fieldid'));
            $dbman->drop_index($table, $fieldid_ix);
            $dbman->drop_field($table, new xmldb_field('fieldid'));

            // Add new fields.
            $set_field = new xmldb_field('fieldset', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
            $field_field = new xmldb_field('field', XMLDB_TYPE_CHAR, '127', null, XMLDB_NOTNULL, null, null);
            $dbman->add_field($table, $set_field);
            $dbman->add_field($table, $field_field);

            // Update rows.
            foreach ($existing_fields as $orig_rec) {
                $record = new stdClass;
                $record->id = $orig_rec->id;
                $record->fieldset = 'user';
                $record->field = 'field_'.$orig_rec->fieldid;
                $DB->update_record('rlipexport_version1elis_fld', $record);
            }

            // Add index for new fields.
            $index = new xmldb_index('setfield_idx', XMLDB_INDEX_UNIQUE, array('fieldset', 'field'));
            $dbman->add_index($table, $index);
        }

        // Plugin savepoint reached.
        upgrade_plugin_savepoint($result, 2013022801, 'rlipexport', 'version1elis');
    }

    return $result;
}
