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

    public function import_course($file, $header=false) {
        return $this->import_file($file, $header);
    }

    private function sanitize_callback(&$value, $key) {
        $value = trim(clean_param($value, PARAM_CLEAN));
    }

    private function import_file($file_name, $header=false) {
        static $file;
        static $fields;

        $retval = new object();
        $retval->header = array();
        $retval->records = array();

        if (empty($file)) {
            $file = fopen($file_name, 'r');
            
            if(!flock($file, LOCK_EX | LOCK_NB)) {
                $this->log_filer->add_error_record("File $file_name is already in use");
                fclose($file);
                return false;
            }
        }

        if (!empty($file) && !feof($file)) {
            if ($header) {
                $retval->header = $fields = fgetcsv($file, 8192, ',', '"');
                array_walk($retval->header, array($this, 'sanitize_callback'));
                array_walk($fields, array($this, 'sanitize_callback'));
                return $retval;
            } else {
                $retval->header = $fields;
            }

            $field_count = count($fields);

            if (!feof($file)) {
                $record =  fgetcsv($file, 8192, ',', '"');
                if (!is_array($record)) {
                    return $retval;
                }
                $record_count = count($record);
                array_walk($record, array($this, 'sanitize_callback'));

                if($field_count == $record_count) {
                    $records[] = array_combine($fields, $record);
                } else {
                    $records[] = $record;
                }

                $retval->records = $records;
                return $retval;
            }
        }

        flock($file, LOCK_UN);
        fclose($file);
        $file = null;
        return false;
    }
}

?>
