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
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_fileplugin.class.php');

/**
 * Dummy file plugin that ignores write actions
 */
class rlip_fileplugin_nowrite extends rlip_fileplugin_base {
    /**
     * Hook for opening the file
     *
     * @param int $mode One of RLIP_FILE_READ or RLIP_FILE_WRITE, specifying
     *                  the mode in which the file should be opened
     */
    function open($mode) {
        //do nothing
    }

    /**
     * Hook for reading one entry from the file
     *
     * @return array The entry read
     */
    function read() {
        //do nothing
        return array();
    }

    /**
     * Hook for writing one entry to the file
     *
     * @param array $line The entry to write to the file
     */
    function write($entry) {
        //do nothing
    }

    /**
     * Hook for closing the file
     */
    function close() {
        //do nothing
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        //bogus filename
        return 'bogus';
    }
}