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

require_once($CFG->dirroot . '/blocks/rlip/elis/lib.php');
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
        $value = clean_param($value, PARAM_CLEAN);
    }

    private function import_file($file_name, $header=false) {
        static $file;
        static $fields;

        $retval = new object();
        $retval->header = array();
        $retval->records = array();

        if (!isset($file)) {
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
        return false;
    }
}

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
class import_csv_elis extends elis_import {
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
        $value = clean_param($value, PARAM_CLEAN);
    }

    private function import_file($file_name, $header=false) {
        static $file;
        static $fields;

        $retval = new object();
        $retval->header = array();
        $retval->records = array();

        if (!isset($file)) {
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
        return false;
    }
}

//abstract class import {
//    private $records;
//    private $columns;
//
//    protected abstract function import_records();
//    protected abstract function import_columns();
//
//
//    public function __construct($handle) {
//        $this->columns = $this->import_columns();
//        $this->records = $this->import_records();
//    }
//
//    public function current() {
//        return current($this->records);
//    }
//
//    public function next() {
//        next($this->records);
//        return current($this->records);
//    }
//
//    public function get_columns() {
//        return $this->columns;
//    }
//}
//
//abstract class csv_import extends import {
//    public function __construct($handle) {
//        $file = fopen($handle, 'r');
//
//        if(empty($file)) {
////            throwException("missing file $file_name");
//            return;
//        }
//
//        if(feof($file)) {
////            throwException("no records to import in file $file_name")
//            return;
//        }
//
//        parent::__construct($file);
//        fclose($file);
//    }
//
//    protected function import_columns() {
//        return fgetcsv($file);
//    }
//
//    protected function import_records($file) {
//        $records = array();
//
//        while(!feof($file)) {
//            $record =  fgetcsv($file);
//
//            if(count($fields) > count($record)) {
//                $records[] = "too many fields";
//            } else if(count($fields) < count($record)) {
//                $records[] = "too few fields";
//            } else {
//                $records[] = array_combine($fields, $record);
//            }
//        }
//
//        return $records;
//    }
//}

?>
