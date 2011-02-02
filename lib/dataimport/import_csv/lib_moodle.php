<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2009 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2009 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot . '/blocks/rlip/moodle/lib.php');
require_once($CFG->dirroot . '/blocks/rlip/sharedlib.php');

/*
 Add:
    * If user exists, add to log - "username exists"
    * If user does not exist, add new user record, add to log - "username added", send user email

Update:

    * If user exists, update data and add to log "username data - listdata changed", send user email
    * If user does not exists, add to log "username does not exist"

Disable

    * If user exists, set them disabled and add to log "username disabled", send user email

 */
class import_csv_moodle extends moodle_import {
    public function import_user($file, $header=false) {
        return $this->import_file($file, $header);
    }

    public function import_enrolment($file, $header=false) {
        return $this->import_file($file, $header);
    }

    public function import_course($file, $header=false, $properties=null) {
        return $this->import_file($file, $header, $properties);
    }

    /**
     * Sanitizes data pulled in from a CSV input file
     * 
     * @param   string reference  $value  The value read in (updated in-place)
     * @param   mixed             $key    Not used
     */
    private function sanitize_callback(&$value, $key) {
        $value = trim(clean_param($value, PARAM_CLEAN));
    }

    /**
     * Reads in a single line from an input file
     * 
     * @param   string   $file_name  Name of the file we are reading from 
     * @param   boolean  $header     If true, read in a header row of the return object
     * 
     * @return  mixed                Object containing appropriate header and records data
     *                               (currently for ONLY ONE ACTUAL RECORD), or FALSE if no data left
     */
    private function import_file($file_name, $header=false, $properties=null) {
        //static reference to open file
        //todo: re-write this method so that it doesn't assume the same file and ignore the
        //      $file_name parameter on subsequent calls
        static $file;
        //static reference to most recent row read in
        static $fields;

        //the return object, in case of success
        $retval = new object();
        $retval->header = array();
        $retval->records = array();

        //file handle not set, so we need to open the file
        if (empty($file)) {

            //register the filter for correctly handling newlines
            $filter_set = block_rlip_init_input_filter();
            
            if (!$filter_set) {
                //failed to register the filter
                $this->log_filer->add_error_record("Could not register input filter");
                return false;
            }

            //open the file
            $file = fopen($file_name, 'r');
            //use the newline filter
            stream_filter_append($file, 'convert.rlip_newline');

            //lock the input file to prevent concurrent access in case the cron overlaps
            if(!flock($file, LOCK_EX | LOCK_NB)) {
                //report error and close file
                $this->log_filer->add_error_record("File $file_name is already in use");
                fclose($file);
                return false;
            }
        }

        //file is open - try reading from it
        if (!empty($file) && !feof($file)) {

            if ($header) {
                //we are setting the header entry
                $test_fields = block_rlip_get_csv_entry($file);

                if ($test_fields === FALSE) {
                    //double-check failed - close file and terminate
                    flock($file, LOCK_UN);
                    fclose($file);
                    $file = null;
                    return FALSE;
                }

                //set header and sanitize
                $retval->header = $fields = $test_fields;
                array_walk($retval->header, array($this, 'sanitize_callback'));
                array_walk($fields, array($this, 'sanitize_callback'));
                return $retval;
            } else {
                //set header to an empty value?
                $retval->header = $fields;
            }

            $field_count = count($fields);

            if (!feof($file)) {
                //retrieve a record
                $record =  block_rlip_get_csv_entry($file);
                if (!is_array($record)) {
                    //failed to load a valid row - row *should* be cleaned up on next call
                    return $retval;
                }
                $record_count = count($record);
                
                //get the mapped category name
                $category_header = $properties['category'];
                
                foreach($record as $key => $value) {
                    
                    if (is_array($retval->header)) {
                        //have some sort of header array set, so search for the category header
                        $position = array_search($category_header, $retval->header); 
                    } else {
                        //header is not valid
                        //todo: consider more proper error handling
                        $position = -1;
                    }

                    
                    //don't clean category names because of escaping
                    if($key === $position) {
                        $record[$key] = trim($value);
                    } else {
                        $record[$key] = trim(clean_param($value, PARAM_CLEAN));
                    }
                }

                //append record
                if($field_count == $record_count) {
                    $records[] = array_combine($fields, $record);
                } else {
                    $records[] = $record;
                }

                $retval->records = $records;
                return $retval;
            }
        }

        //done with file, clean up
        flock($file, LOCK_UN);
        fclose($file);
        $file = null;
        return FALSE;
    }
}

?>
