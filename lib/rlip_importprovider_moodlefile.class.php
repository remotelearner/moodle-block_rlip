<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');

/**
 * Class that provides file plugins reading from the Moodle file API in a
 * generic way
 */
class rlip_importprovider_moodlefile extends rlip_importprovider {
    var $entity_types;
    var $fileids;
    var $filename;

    /**
     * Constructor
     *
     * @param array $fieldids Array of file records' database ids
     * @param array $entity_types Array of strings representing the entity
     *                            types of import files
     */
    function __construct($entity_types, $fieldids) {
        $this->entity_types = $entity_types;
        $this->fileids = $fieldids;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

        foreach ($this->entity_types as $key => $value) {
            if ($entity == $value) {
                if ($this->fileids[$key] !== false) {
                    return rlip_fileplugin_factory::factory('', $this->fileids[$key]);
                }
            }
        }

        return false;
    }

    /**
     * Provides the object used to log information to the database to the
     * import
     *
     * @return object the DB logger
     */
    function get_dblogger() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dblogger.class.php');

        //for now, this is only used in manual runs
        return new rlip_dblogger_import(true);
    }

    public function set_file_name($filename) {
        $this->filename = $filename;
    }

    public function get_file_name() {
        return $this->filename;
    }

    /**
     * Provides the object used to log information to the file system logfile
     *
     * @param  string $plugin  the plugin
     * @param  string $entity  the entity type
     * @param boolean $manual  Set to true if a manual run
     * @param  integer $starttime the time used in the filename
     * @return object the fslogger
     */
    function get_fslogger($plugin, $entity, $manual = false, $starttime = 0) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fslogger.class.php');

        //set up the file-system logger
        $filepath = get_config($plugin, 'logfilelocation');

        //get filename
        $filename = rlip_log_file_name('import', $plugin, $filepath, $entity, $manual, $starttime);
        if (!empty($filename)) {
            $this->set_file_name($filename);
            $fileplugin = rlip_fileplugin_factory::factory($filename, NULL, true);
            return rlip_fslogger_factory::factory($fileplugin, $manual);
        }
        return null;
    }
}
