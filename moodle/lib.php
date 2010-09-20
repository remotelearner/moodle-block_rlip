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

require_once ('backuplib.php');

define('RLIP_DIRLOCATION', $CFG->dirroot . '/blocks/rlip');

/**
 * all IP import plugins must extend this class
 */
abstract class elis_import {
    protected $headers;

    protected $log_filer;


    //each of the import functions return an object containing
    //$header which gives the field name for each field
    //$records an array of arrays of each record's fields values
    public abstract function import_user($file);
    public abstract function import_enrolment($file);
    public abstract function import_course($file);

//    public abstract function import_coursecategories($file = null);

    /**
     * sets the log file for outputing the import report
     * @global object $CURMAN
     * @param string $logfile name of the log file
     */
    public function __construct($logfile=null) {
        global $CFG;

        $this->log_filer = new log_filer($CFG->block_rlip_logfilelocation, $logfile);
    }

    /**
     * calls import_user, import_enrolment, iimport_course and calls the associated methods to
     * import the records
     * @param string $file name and location of the file to import
     * @param string $type type of records being imported
     */
    public function import_records($file, $type) {
        $retval = false;
        $context = get_context_instance(CONTEXT_SYSTEM);

        if(has_capability('block/rlip:config', $context)) {
            try {
                is_file($file) OR throwException("file $file not found");

                $get_class = "{$type}_import";
                class_exists($get_class) OR throwException("unimplemented import $type");
                $this->get(new $get_class(), $file, $type);

                $retval = true;

                if(!@unlink($file)) {
                    print_string('delete_failed', 'block_rlip');
                }
            } catch(Exception $e) {
                $this->log_filer->add_error($e->getMessage());
            }

            $this->log_filer->output_log();
        }

        return $retval;
    }

    private function get($import, $file, $type) {

        if (RLIP_DEBUG_TIME) $start = microtime(true);

        $method = "import_$type";
        method_exists($this, $method) OR throwException("unimplemented import $type");
        $data = $this->$method($file, true);  // Get the header line of the csv file.
        $columns = $data->header;

        $properties = $import->get_properties_map();

        in_array($properties['execute'], $columns) OR throwException('header must contain an action field');

        $missing_fields = $import->get_missing_required_columns($columns);
        if(!empty($missing_fields)) {
            $missing = implode(', ', $missing_fields);

            throwException("missing required column $missing");
        }

        /// Process each line of the CSV file. THIS CAN TAKE A LOT OF TIME!
        set_time_limit(0);
        while ($data = $this->$method($file)) {
            $records = $data->records;
            $items = $import->get_items($records);
            $this->process($items, $type);
        }

        if (RLIP_DEBUG_TIME) {
            $end  = microtime(true);
            $time = $end - $start;
            mtrace("elis_import.get('$file', '$type'): $time");
        }

//        return $items;
        return true;
    }

    public function process($records, $type) {
//        if (RLIP_DEBUG_TIME) $start = microtime(true);

        foreach($records as $record) {
            try {
                if(empty($record['execute'])) {
                    throwException('missing action');
                } else {
                    $method = "{$type}_{$record['execute']}";
                    $this->$method($record);
                }
            } catch(Exception $e) {
                $this->log_filer->add_error_record($e->getMessage());
            }
        }

//        if (RLIP_DEBUG_TIME) {
//            $end  = microtime(true);
//            $time = $end - $start;
//            mtrace("elis_import.process('$type'): $time");
//        }
    }

    /**
     * logs an error if a non-existant method is called
     * eg if given a bad action or record type
     * @param string $name name of method
     * @param array $arguments
     */
    public function __call($name, $arguments) {
        $this->log_filer->add_error("invalid action $name");
    }

    /**
     * creates a course given a new course record
     * @global object $CURMAN
     * @param array $r course record
     */
    private function course_create($r) {
        $ci = new course_import();

        $missing = $ci->get_missing_required_fields($r);
        if(!empty($missing)){
            $required = implode(', ', $missing);

            throwException("missing required fields $required");
        }

        $ci->check_new($r);

        if(!empty($r['link'])) {
            $courseid = get_field('course', 'id', 'shortname', $r['link']);

            if(!empty($courseid)) {
                $r['id'] = content_rollover($courseid, (empty($r['startdate']) ? NULL : $r['startdate']));
            }

            // Rename the fullname, shortname and idnumber of the restored course

            if(update_record('course', (object)$r)) {
                $this->log_filer->add_success("course {$r['fullname']} added");
            } else {
                throwException("failed to create course {$r['fullname']}");
            }
        } else {
            if(create_course((object)$r)) {
                $this->log_filer->add_success("course {$r['fullname']} added");
            } else {
                throwException("failed to create course {$r['fullname']}");
            }

        }
    }

    /**
     * updates an elis course
     *
     * @global object $CURMAN
     * @param array $r
     */
    public function course_update($r) {
        $ci = new course_import();
        $ci->check_old($r);

        $r['id'] = get_field('course', 'id', 'shortname', $r['shortname']);

        // We must specify a category if one hasn't been in the file
        if (empty($r['category'])) {
            throwException("invalid category provided");
        }

        if(update_record('course', (object)$r)) {
            $this->log_filer->add_success("course {$r['fullname']} updated");
        } else {
            throwException("course {$r['fullname']} not updated");
        }
    }

    /**
     * delete a course record
     * @param array $r record to be deleted from the db
     */
    public function course_delete($r) {
        $ci = new course_import();
        $ci->check_old($r);

        $course = get_record('course', 'shortname', $r['shortname']);

        if(delete_course($course, false)) {
            $this->log_filer->add_success("course {$course->fullname} deleted");
        } else {
            throwException("failed to delete course {$r['fullname']}");
        }
    }

    /**
     * add a user entry
     * @param object $user user object
     */
    public function user_add($user) {
        global $CFG;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        if (RLIP_DEBUG_TIME) $start = microtime(true);

        $ui = new user_import();
        $ui->check_new($user);

        /// Add a new user
        $user['password']   = $user['password'];
        $user['timemodified']   = time();
        $user['mnethostid'] = $CFG->mnet_localhost_id;
        $user['confirmed'] = 1;
        $user = (object)$user;
        $user->id = insert_record('user', $user);

        /// Save custom profile fields.
        if ($fields = get_records('user_info_field')) {
            foreach ($fields as $field) {
                require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                $newfield = 'profile_field_'.$field->datatype;
                $formfield = new $newfield($field->id, $user->id);
                $formfield->edit_save_data($user);
            }
        }

        if (RLIP_DEBUG_TIME) {
            $end  = microtime(true);
            $time = $end - $start;
            mtrace("elis_import.user_add(): $time");
        }

        if(!empty($user->id)) {
            $this->log_filer->add_success("user {$user->username} added");
        } else {
            throwException("user {$user->username} not added");
        }
    }

    /**
     * updates a given user record
     * @param object $user user to be updated
     */
    public function user_update($user) {
        $ui = new user_import();
        $ui->check_old($user);

        /// Update an existing user
        $user['password']   = $user['password'];
        $user['timemodified']   = time();

        $user['id'] = get_field('user', 'id', 'username', $user['username']);

        if(update_record('user', (object)$user)) {
            $this->log_filer->add_success("user {$user['username']} updated");
        } else {
            throwException("user {$user['username']} not updated");
        }
    }

    /**
     * delete given user
     * @param object $user user to be deleted
     */
    public function user_disable($user) {
        $ui = new user_import();
        $ui->check_old($user);

        $userid = get_record('user', 'username', $user['username'], 'deleted', '0');

        delete_user($userid);
        $this->log_filer->add_success("user {$user['username']} deleted");
    }

    /**
     * enroll students in a class or track based on the return value from enrolment_import
     * @param array $item student record to enrol
     */
    public function enrolment_add($item) {
        global $CFG;

        $ei = new enrolment_import();
        $ei->check_new($item);
        $context = $ei->get_context_instance($item);

        $userid = get_field('user', 'id', 'username', $item['username']);
        $roleid = get_field('role', 'id', 'shortname', $item['role']);

        $timestart = empty($item['timestart'])?0:$item['timestart'];
        $timeend = empty($item['timeend'])?0:$item['timeend'];
        role_assign($roleid, $userid, 0, $context->id, $timestart, $timeend, 0, 'manual');
        build_context_path();

        /// Handle any groups and groupings...
        if (!empty($item['group'])) {
            if ($courseid = get_field('course', 'id', 'shortname', $item['instance'])) {
                if (!($groupid = groups_get_group_by_name($courseid, $item['group']))) {
                    if ($CFG->block_rlip_creategroups) {
                        /// Group needs to be created if configured that way...
                        $group = new Object();
                        $group->name = addslashes($item['group']);
                        $group->courseid = $courseid;
                        if (!$groupid = groups_create_group($group)) {
                            throwException("Failed to create group {$item['group']}");
                        }
                    } else {
                        throwException("Invalid group {$item['group']} specified");
                    }
                }
                if (!groups_add_member($groupid, $userid)) {
                    throwException("Assigning user {$item['username']} to group {$item['group']} failed");
                }

                if (!empty($item['grouping'])) {
                    if (!($groupingid = groups_get_grouping_by_name($courseid, $item['grouping']))) {
                        if ($CFG->block_rlip_creategroups) {
                            /// Groupings needs to be created if configured that way...
                            $grouping = new Object();
                            $grouping->name = addslashes($item['grouping']);
                            $grouping->courseid = $courseid;
                            if (!$groupingid = groups_create_grouping($grouping)) {
                                throwException("Failed to create grouping {$item['grouping']}");
                            }
                        } else {
                            throwException("Invalid grouping {$item['grouping']} specified");
                        }
                    }
                    if (!groups_assign_grouping($groupingid, $groupid)) {
                        throwException("Assigning group {$item['group']} to grouping {$item['grouping']} failed");
                    }
                }
            } else {
                throwException("Course {$item['instance']} does not exist");
            }
        }

        $this->log_filer->add_success("assigned user {$item['username']} to the {$item['role']} role of the {$item['context']} {$item['instance']}");
    }

    /**
     * unenroll a student from a class
     * @param array $item record of student to enrol
     */
    public function enrolment_delete($item) {
        $ei = new enrolment_import();
        $ei->check_old($item);
        $context = $ei->get_context_instance($item);

        $userid = get_field('user', 'id', 'username', $item['username']);
        $roleid = get_field('role', 'id', 'shortname', $item['role']);

        $timestart = empty($item['timestart'])?0:$item['timestart'];
        $timeend = empty($item['timeend'])?0:$item['timeend'];

        role_unassign($roleid, $userid, 0, $context->id);

        build_context_path();
        $this->log_filer->add_success("unassigned user {$item['username']} from the {$item['role']} role of the {$item['context']} {$item['instance']}");
    }

    /**
     *
     * @param <type> $item
     */
    public function enrolment_update($item) {
        $ei = new enrolment_import();
        $ei->check_old($item);
        $context = $ei->get_context_instance($item);

        $userid = get_field('user', 'id', 'username', $item['username']);
        $roleid = get_field('role', 'id', 'shortname', $item['role']);

        $timestart = empty($item['timestart'])?0:$item['timestart'];
        $timeend = empty($item['timeend'])?0:$item['timeend'];

        role_assign($roleid, $userid, 0, $context->id, $timestart, $timeend, 0, 'manual');
        build_context_path();

        $this->log_filer->add_success("updated user {$item['username']} to the {$item['role']} role of the {$item['context']} {$item['instance']}");
    }
}

/**
 * used to log messages to a file
 */
class log_filer {
    private $endl = "\n"; //new line delimiter
    private $warning = '';
    private $logs = array();
    private $count = 1; //holds the current record being logged to the file
    private $filename = '';

    /**
     * opens a file to append to with the given file name in the given file location
     * @param string $file location where log file is to be put
     * @param string $filename name of log file
     */
    function __construct($file, $filename) {
        if(!empty($file) && is_dir(addslashes($file))) {
            $this->filename = addslashes($file) . '/' . $filename . '.log';
        }
    }

    /**
     * print a string to the file with a new line at the end
     * @param string $line what to print
     */
    function lfprintln($line = '') {
        $this->lfprint($line . $this->endl);
    }

    /**
     * prints a string to the file
     * @param string $str what to print
     */
    function lfprint($str = '') {
        $this->logs[] = $str;
    }

    /**
     * ues the count to display what record contained the error
     * @param string $line prints an error message to the file for a particular record
     */
    function add_error_record($line='error') {
        $this->lfprintln("error with record #$this->count: $line $this->warning");
        $this->warning = '';
        $this->count++;
    }

    /**
     * adds an error message to the log file
     * @param string $line error message
     */
    function add_error($line='error') {
        $this->lfprintln("error: $line $this->warning");
        $this->warning = '';
    }

    /**
     * adds indication of successfully used the record
     * @param string $line success message
     */
    function add_success($line='success') {
        $this->lfprintln("success with record #$this->count: $line $this->warning");
        $this->warning = '';
        $this->count++;
    }

    /**
     * adds a warning to the log fil for the current record
     * @param string $line warning message
     */
    function add_warning($line='warning') {
        if(empty($this->warning)) {
            $this->warning = ' WARNING ' . $line;
        } else {
            $this->warning .= ', ' . $line;
        }
    }

    /**
     * prints all the messages to the log file
     * @global object $CURMAN
     * @global object $USER
     * @param object $file name of the file to log to
     */
    function output_log($file=null) {
        global $CFG, $USER;

        if(empty($this->logs)) {
            return;
        }

        if(empty($file)) {
            $file = fopen($this->filename, 'a');
        }

        if(!empty($file)) {
            $message = '';
            foreach($this->logs as $log) {
                $message .= $log . "\n";
            }

            if(!empty($message)) {
                fwrite($file, $message);

                $idnumbers = explode(',', $CFG->block_rlip_emailnotification);

                $subject = get_string('ip_log', 'block_rlip');

                foreach($idnumbers as $idnum) {
                    if(!empty($idnum)) {
                        $user = get_record('user', 'idnumber', $idnum, 'deleted', '0');   //have to assume that idnumbers are unique

                        if(!empty($user)) {
                            email_to_user($user, $USER, $subject, $message);
                        }
                    }
                }
            }
        }
    }

    /**
     * close the file when this object loses focus, may not be needed but there
     * as a precaucion
     */
    function __destruct() {
        if(!empty($this->file)) {
            fclose($this->file);
        }
    }
}

/**
 *
 */
class user_import extends import {
    protected $context = 'user';
    protected $required = array('username',
                                'password',
                                'firstname',
                                'lastname',
                                'email',
                                'city',
                                'country');
    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    protected function get_fields() {
        $retval = array('idnumber',
                        'auth',         //auth plugins list get_list_of_plugins('auth');
                        'username',         //unique key(username+mnethostid)
                        'password',     //md5 hash
                        'email',
                        'firstname',
                        'lastname',
                        'city',
                        'country',      //country list get_list_of_countries();
                        'maildigest',   //yes/no
                        'autosubscribe',    //yes/no
                        'trackforums',  //yes/no
                        'timezone',
                        'language',     //list of languages
                        'theme',        //list of themes
                        'screenreader', //yes/no
                        'description',
                        'idnumber',
                        'institution',
                        'department');

        $retval = array_combine($retval, $retval);

        $retval['execute'] = 'action';

        $custom_fields = get_records('user_info_field');
        if(!empty($custom_fields)) {
            foreach($custom_fields as $cf) {
                $retval["profile_field_{$cf->shortname}"] = "profile_field_{$cf->shortname}";
            }
        }

        return $retval;
    }


    protected function get_auth($auth) {
        static $auth_plugins;

        if (empty($auth_plugins)) {
            $auth_plugins = get_list_of_plugins('auth');
        }

        if(in_array($auth, $auth_plugins)) {
            return $auth;
        }

        return 'manual';
    }

    protected function get_password($password) {
        return hash_internal_user_password($password);
    }

    protected function get_country($country) {
        static $country_list;

        if (empty($country_list)) {
            $country_list = get_list_of_countries();
        }

        if (($ckey = array_search($country, $country_list)) !== false) {
            return $ckey;
        }

        return '';
    }

    protected function get_maildigest($maildigest) {
        return $this->boolean_get($maildigest);
    }

    protected function get_autosubscribe($autosubscribe) {
        return $this->boolean_get($autosubscribe);
    }

    protected function get_trackforums($trackforums) {
        return $this->boolean_get($trackforums);
    }

    protected function get_language($language) {
        static $language_list;

        if (empty($language_list)) {
            $language_list = get_list_of_languages();
        }

        if(in_array($language, $language_list)) {
            return $language;
        }

        return '';
    }

    protected function get_theme($theme) {
        static $theme_list;

        if (empty($theme_list)) {
            $theme_list = get_list_of_themes();
        }

        if(in_array($theme, $theme_list)) {
            return $theme;
        }

        return '';
    }

    protected function get_screenreader($screenreader) {
        return $this->boolean_get($screenreader);
    }

    public function check_new($record) {
        global $CFG;

        if (RLIP_DEBUG_TIME) $start = microtime(true);

        $retval = true;

        $retval = $retval && !record_exists('user', 'mnethostid', $CFG->mnet_localhost_id, 'username', $record['username'], 'deleted', 0);

        if (RLIP_DEBUG_TIME) {
            $end  = microtime(true);
            $time = $end - $start;
            mtrace("elis_import.check_new(): $time");
        }

        if (!$retval) {
            throwException("user {$record['username']} already exists");
        } else {
            return $retval;
        }
    }

    public function check_old($record) {
        $retval = true;

        $retval = $retval &&
                    record_exists('user', 'username', $record['username'], 'deleted', 0) or
                    throwException("user {$record['username']} does not exist");

        return $retval;
    }
}

/**
 *
 */
class enrolment_import extends import {
    protected $context = 'student';
    protected $required = array('role',
                                'username',
                                'context',
                                'instance');
    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    protected function get_fields() {
        $retval = array('username',
                        'role',
                        'useridnumber',
                        'context',
                        'timestart',        //date to unix timestamp
                        'timeend',          //date to unix timestamp
                        'instance',
                        'group',
                        'grouping');

        $retval = array_combine($retval, $retval);
        $retval['execute'] = 'action';

        return $retval;
    }

    protected function get_timestart($timestart) {
        return strtotime($timestart);
    }

    protected function get_timeend($timeend) {
        return strtotime($timeend);
    }

    public function get_context_instance($record) {
         $contexts = array('system', 'user', 'coursecat', 'course', 'module', 'block');
        //check username exists
        //check context exists
        //check instance exists

        in_array($record['context'], $contexts) or
                    throwException("invalid context {$record['context']} does not exist");

        if(strcmp($record['context'], 'user') === 0) {
            $instanceid = get_field('user', 'id', 'username', $record['instance']);
            $contextlevel = CONTEXT_USER;
        } else if(strcmp($record['context'], 'user') === 0) {
            record_exists('user', 'username', $record['instance']) or
                    throwException("invalid user {$record['instance']} does not exist");

            $instanceid = get_field('user', 'id', 'username', $record['instance']);
            $contextlevel = CONTEXT_USER;
        } else if(strcmp($record['context'], 'coursecat') === 0) {
            record_exists('course_categories', 'name', $record['instance']) or
                    throwException("invalid course category {$record['instance']} does not exist");

            $instanceid = get_field('coursecat', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_COURSECAT;
        } else if(strcmp($record['context'], 'course') === 0) {
            record_exists('course', 'shortname', $record['instance']) or
                    throwException("invalid course {$record['instance']} does not exist");

            $instanceid = get_field('course', 'id', 'shortname', $record['instance']);
            $contextlevel = CONTEXT_COURSE;
        } else if(strcmp($record['context'], 'module') === 0) {
            record_exists('modules', 'name', $record['instance']) or
                    throwException("invalid module {$record['instance']} does not exist");

            $instanceid = get_field('module', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_MODULE;
        } else if(strcmp($record['context'], 'block') === 0) {
            record_exists('block', 'name', $record['instance']) or
                    throwException("invalid block {$record['instance']} does not exist");

            $instanceid = get_field('block', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_BLOCK;
        }

        $contextid = get_field('context', 'id', 'contextlevel', $contextlevel, 'instanceid', $instanceid);
        $userid = get_field('user', 'id', 'username', $record['username']);
        $roleid = get_field('role', 'id', 'shortname', $record['role']);

        return get_context_instance($contextlevel, $instanceid);
    }

     public function check_new($record) {
         $contexts = array('system', 'user', 'coursecat', 'course', 'module', 'block');
        //check username exists
        //check context exists
        //check instance exists

        record_exists('role', 'shortname', $record['role']) or
                    throwException("invalid role {$record['role']} does not exist");

        record_exists('user', 'username', $record['username'], 'deleted', 0) or
                    throwException("invalid user {$record['username']} does not exist");

        in_array($record['context'], $contexts) or
                    throwException("invalid context {$record['context']} does not exist");

        if(strcmp($record['context'], 'user') === 0) {
            $instanceid = get_field('user', 'id', 'username', $record['instance']);
            $contextlevel = CONTEXT_USER;
        } else if(strcmp($record['context'], 'user') === 0) {
            record_exists('user', 'username', $record['instance']) or
                    throwException("invalid user {$record['instance']} does not exist");

            $instanceid = get_field('user', 'id', 'username', $record['instance']);
            $contextlevel = CONTEXT_USER;
        } else if(strcmp($record['context'], 'coursecat') === 0) {
            record_exists('course_categories', 'name', $record['instance']) or
                    throwException("invalid course category {$record['instance']} does not exist");

            $instanceid = get_field('coursecat', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_COURSECAT;
        } else if(strcmp($record['context'], 'course') === 0) {
            record_exists('course', 'shortname', $record['instance']) or
                    throwException("invalid course {$record['instance']} does not exist");

            $instanceid = get_field('course', 'id', 'shortname', $record['instance']);
            $contextlevel = CONTEXT_COURSE;
        } else if(strcmp($record['context'], 'module') === 0) {
            record_exists('modules', 'name', $record['instance']) or
                    throwException("invalid module {$record['instance']} does not exist");

            $instanceid = get_field('module', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_MODULE;
        } else if(strcmp($record['context'], 'block') === 0) {
            record_exists('block', 'name', $record['instance']) or
                    throwException("invalid block {$record['instance']} does not exist");

            $instanceid = get_field('block', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_BLOCK;
        }

        $contextid = get_field('context', 'id', 'contextlevel', $contextlevel, 'instanceid', $instanceid);
        $userid = get_field('user', 'id', 'username', $record['username']);
        $roleid = get_field('role', 'id', 'shortname', $record['role']);

        !record_exists('role_assignments', 'contextid', $contextid, 'userid', $userid, 'roleid', $roleid) or
                    throwException("{$record['username']} already assigned as {$record['role']} in {$record['instance']}");

        return true;
    }

    public function check_old($record) {
        $contexts = array('system', 'user', 'coursecat', 'course', 'module', 'block');

        record_exists('user', 'username', $record['username'], 'deleted', 0) or
                    throwException("invalid user {$record['username']} does not exist");

        in_array($record['context'], $contexts) or
                    throwException("invalid context {$record['context']} does not exist");

        if(strcmp($record['context'], 'user') === 0) {
            $instanceid = get_field('user', 'id', 'username', $record['instance']);
            $contextlevel = CONTEXT_USER;
        } else if(strcmp($record['context'], 'user') === 0) {
            record_exists('user', 'username', $record['instance']) or
                    throwException("invalid user {$record['instance']} does not exist");

            $instanceid = get_field('user', 'id', 'username', $record['instance']);
            $contextlevel = CONTEXT_USER;
        } else if(strcmp($record['context'], 'coursecat') === 0) {
            record_exists('course_categories', 'name', $record['instance']) or
                    throwException("invalid course category {$record['instance']} does not exist");

            $instanceid = get_field('coursecat', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_COURSECAT;
        } else if(strcmp($record['context'], 'course') === 0) {
            record_exists('course', 'shortname', $record['instance']) or
                    throwException("invalid course {$record['instance']} does not exist");

            $instanceid = get_field('course', 'id', 'shortname', $record['instance']);
            $contextlevel = CONTEXT_COURSE;
        } else if(strcmp($record['context'], 'module') === 0) {
            record_exists('modules', 'name', $record['instance']) or
                    throwException("invalid module {$record['instance']} does not exist");

            $instanceid = get_field('module', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_MODULE;
        } else if(strcmp($record['context'], 'block') === 0) {
            record_exists('block', 'name', $record['instance']) or
                    throwException("invalid block {$record['instance']} does not exist");

            $instanceid = get_field('block', 'id', 'name', $record['instance']);
            $contextlevel = CONTEXT_BLOCK;
        }

        $contextid = get_field('context', 'id', 'contextlevel', $contextlevel, 'instanceid', $instanceid);
        $userid = get_field('user', 'id', 'username', $record['username']);
        $roleid = get_field('role', 'id', 'shortname', $record['role']);

        record_exists('role_assignments', 'contextid', $contextid, 'userid', $userid, 'roleid', $roleid) or
                    throwException("{$record['username']} not assigned as {$record['role']} in {$record['instance']}");

        return true;
    }
}

/**
 *
 */
class course_import extends import {
    protected $context = 'course';
    protected $required = array('category',
                                'fullname',
                                'shortname');

    /**
     *
     * @return array    user fields
     */
    protected function get_fields() {
        $retval = array('category', //list of choices
                'format',       //topic weeks etc. get_list_of_plugins('course/format');
                'fullname',
                'guest',        //yes/no
                'idnumber',
                'lang',         //lang shortname
                'maxbytes',
                'metacourse',       //yes/no
                'newsitems',
                'notifystudents',   //yes/no
                'numsections',
                'password',         //md5hash
                'shortname',            //unique key
                'showgrades',       //yes/no
                'showreports',      //yes/no
                'sortorder',
                'startdate',        //date to unix timestamp
                'summary',
                'timecreated',      //date to unix timstamp
                'visible',          //yes/no
                'link');            //list of choices

        $retval = array_combine($retval, $retval);

        $retval['execute'] = 'action';

        return $retval;
    }

    protected function get_format($format) {
        $valid_formats = get_list_of_plugins('course/format');

        if(in_array($format, $valid_formats)) {
            return $format;
        }

        return '';
    }

    protected function get_guest($guest) {
        return $this->boolean_get($guest);
    }

    protected function get_lang($lang) {
        $valid_langs = get_list_of_languages();

        if(in_array($lang, $valid_langs)) {
            return $lang;
        }

        return '';
    }

    protected function get_metacourse($metacourse) {
        return $this->boolean_get($metacourse);
    }

    protected function get_showreports($showreports) {
        return $this->boolean_get($showreports);
    }

    protected function get_notifystudents($notifystudents) {
        return $this->boolean_get($notifystudents);
    }

    protected function get_showgrades($showgrades) {
        return $this->boolean_get($showgrades);
    }

    protected function get_startdate($startdate) {
        return strtotime($startdate);
    }

    protected function get_timecreated($timecreated) {
        return strtotime($timecreated);
    }

    protected function get_visible($visible) {
        return $this->boolean_get($visible);
    }

    protected function get_category($category) {
        if(is_numeric($category)) {
            if(record_exists('course_categories', 'id', $category)) {
                return $category;
            }
        } else {
            $categoryid = get_field('course_categories', 'id', 'name', $category);
            if(!empty($categoryid)) {
                return $categoryid;
            }
        }

        return null;
    }

    public function check_new($record) {
        //check shortname doesn't exist
        //check link is empty or does exist
        $retval = true;

        if(!empty($record['link'])) {
            $retval = $retval && record_exists('course', 'shortname', $record['link']) or
                        throwException("course {$record['link']} does not exist");
        }

        $retval = $retval && !record_exists('course', 'shortname', $record['shortname']) or
                    throwException("course {$record['shortname']} already exists");

        return $retval;
    }

    public function check_old($record) {
        $retval = true;

        //don't need to check the link when updating or deleting a coures only when creating

        $retval = $retval && record_exists('course', 'shortname', $record['shortname']) or
                    throwException("course {$record['shortname']} does not exist");

        return $retval;
    }
}

abstract class import {
    protected $context;

    public abstract function check_new($record);
    public abstract function check_old($record);

    protected abstract function get_fields();

    public function __call($name, $args) {
        if(strncmp($name, 'get_', 4) === 0) {
            if(!empty($args[0])) {
                return $args[0];
            }
        }
    }

    protected function get_item($record) {
        $retval = array();
        $properties = $this->get_properties_map();

        foreach($properties as $key=>$p) {
            if(!empty($record[$p])) {
                $method = "get_$p";
                $retval[$key] = $this->$method($record[$p]);
            }
        }

        return $retval;
    }


    protected function boolean_get($item) {
        if($item == 1 || strcmp($item, 'yes') === 0) {
            return true;
        }

        return false;
    }

    /**
     *
     * @param <type> $fields
     * @return <type>
     */
    public function get_missing_required_fields($fields) {
        $retval = array();
        $map = $this->get_properties_map();

        foreach($this->required as $r) {
            if(empty($fields[$r])) {
                $retval[] = $map[$r];
            }
        }

        return $retval;
    }



    /**
     *
     * @param <type> $columns
     * @return <type>
     */
    public function get_missing_required_columns($columns) {
        $retval = array();

        $map = $this->get_properties_map();

        foreach($this->required as $r) {
            if(!in_array($map[$r], $columns)) {
                $retval[] = $map[$r];
            }
        }

        return $retval;
    }

    /**
     *
     * @param <type> $records
     * @return <type>
     */
    public function get_items($records) {
        $retval = array();

        foreach($records as $rec) {
            $retval[] = $this->get_item($rec);
        }

        return $retval;
    }

    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    public function get_properties_map() {
        static $properties_map;

        $retval = $this->get_fields();

        if (!isset($properties_map)) {
            $properties_map = get_records('block_rlip_fieldmap', 'context', $this->context);
        }

        if(!empty($properties_map)) {
            foreach($properties_map as $pm) {
                $retval[$pm->fieldname] = $pm->fieldmap;
            }
        }

        return $retval;
    }

    /**
     *
     * @global object $CURMAN
     * @param string $key
     * @param string $value
     * @return object
     */
    public function set_property_map($key, $value) {
        $map = $this->get_properties_map();

        if(!record_exists('block_rlip_fieldmap', 'context', $this->context, 'fieldname', $key)) {
            if(!empty($map[$key]) && strcmp($map[$key], $value) !== 0) {
                $dataobject->context = $this->context;
                $dataobject->fieldname = $key;
                $dataobject->fieldmap = $value;

                return insert_record('block_rlip_fieldmap', $dataobject);
            } else {
                //invalid key value
                return false;
            }
        } else {
            return set_field('block_rlip_fieldmap', 'fieldmap', $value, 'context', $this->context, 'fieldname', $key);
        }
    }
}

function throwException($message = null, $code = null) {
    throw new Exception($message, $code);
}

?>
