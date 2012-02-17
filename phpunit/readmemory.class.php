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

require_once($CFG->dirroot.'/blocks/rlip/rlip_fileplugin.class.php');

/**
 * Mock file plugin that provides a fixed set of data
 */
class rlip_fileplugin_readmemory extends rlip_fileplugin_base {
    //current file position
    var $index;
    //file data
    var $data;

    /**
     * Mock file plugin constructor
     *
     * @param array $data The data represented by this file
     */
    function __construct($data) {
        $this->index = 0;
        $this->data = $data;
    }

    /**
     * Open the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    function open($mode) {
        //nothing to do
    }

    /**
     * Read one entry from the file
     *
     * @return array The entry read
     */
    function read() {
        if ($this->index < count($this->data)) {
            //more lines to read, fetch next one
            $result = $this->data[$this->index];
            //move "line pointer"
            $this->index++;
            return $result;
        }

        //out of lines
        return false;
    }

    /**
     * Write one entry to the file
     *
     * @param array $entry The entry to write to the file
     */
    function write($entry) {
        //nothing to do
    }

    /**
     * Close the file
     */
    function close() {
        //nothing to do
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        return 'memoryfile';
    }

    /**
     * Specifies the extension of the current open file
     *
     * @return string The file extension
     */
    function get_extension() {
        return 'readmemory';
    }
}