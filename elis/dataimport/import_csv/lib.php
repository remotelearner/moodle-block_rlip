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

define('usercsv', 'user.csv');
define('inputcsv', 'input.csv');
define('enrollcsv', 'enroll.csv');
define('coursecsv', 'course.csv');

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
class import_csv extends elis_import {
    public function import_user($file) {        
        return $this->import_file($file);
    }

    public function import_enrolment($file) {
        return $this->import_file($file);
    }

    public function import_course($file) {
        return $this->import_file($file);
    }

    private function import_file($file_name) {
        $retval = new object();

        $file = fopen($file_name, 'r');
        $count = 0;

        if(!empty($file) && !feof($file)) {
            $line = fgets($file);

            $retval->header = $fields = preg_split("/\s*,\s*/", trim($line));

            while(!feof($file)) {
                $count++;
                $line =  fgets($file);

                $record = preg_split("/\s*,\s*/", trim($line));

                if(count($fields) === count($record)) {
                    $records[] = array_combine($fields, $record);
                } else {
                    $records[] = $record;
                }
            }
        }

        fclose($file);

        $retval->records = $records;
        return $retval;
    }
}

?>
