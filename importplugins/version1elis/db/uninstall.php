<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
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
 * @package    rlip
 * @subpackage blocks_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../lib.php');

/**
 * Uninstall hook for this import plugin
 */
function xmldb_rlipimport_version1elis_uninstall() {
    global $DB;
    $dbman = $DB->get_manager();

    //remove the field mapping table
    $mapping_table = new xmldb_table(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE);
    if ($dbman->table_exists($mapping_table)) {
        $dbman->drop_table($mapping_table);
    }

    //clear config_plugins entries
    unset_all_config_for_plugin('rlipimport_version1elis');

    return true;
}