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

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
require_once(dirname(__FILE__) .'/rlip_mock_provider.class.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/fileplugins/csv.class.php');
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');

/**
 * Class that delays reading import file
 */
class rlip_fileplugin_csv_mock extends rlip_fileplugin_csv {
    private $readdelay = 3; // 3 sec delay before reads

    function read() {
        if (!empty($this->readdelay)) {
            sleep($this->readdelay);
        }
        return parent::read();
    }
}

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_loguser extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_logcourse extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'course') {
            return false;
        }

        return parent::get_import_file($entity);
    }
}

/**
 * Class that fetches import files for the course import
 */
class rlip_importprovider_logenrolment extends rlip_importprovider_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'enrolment') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * File plugin that reads from memory and reports a dynamic filename
 */
class rlip_fileplugin_readmemory_dynamic extends rlip_fileplugin_readmemory {

    /**
     * Mock file plugin constructor
     *
     * @param array $data The data represented by this file
     * @param string $filename The name of the file to report
     */
    function __construct($rows, $filename) {
        parent::__construct($rows);
        $this->filename = $filename;
    }

    /**
     * Specifies the name of the current open file
     *
     * @return string The file name, not including the full path
     */
    function get_filename() {
        return $this->filename;
    }
}

/**
 * Import provider that allow for multiple user records to be passed to the
 * import plugin
 */
class rlip_importprovider_multiuser extends rlip_importprovider_multi_mock {

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }
        return parent::get_import_file($entity);
    }
}

/**
 * Import provider that allows for user processing and specifies a dynamic
 * filename to the file plugin
 */
class rlip_importprovider_loguser_dynamic extends rlip_importprovider_loguser {
    var $data;
    var $filename;

    /**
     * Constructor
     *
     * @param array $data Fixed file contents
     * @param string $filename The name of the file to report
     */
    function __construct($data, $filename) {
        $this->data = $data;
        $this->filename = $filename;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        //turn an associative array into rows of data
        $rows = array();
        $rows[] = array();
        foreach (array_keys($this->data) as $key) {
            $rows[0][] = $key;
        }
        $rows[] = array();
        foreach (array_values($this->data) as $value) {
            $rows[1][] = $value;
        }

        return new rlip_fileplugin_readmemory_dynamic($rows, $this->filename);
    }
}

class rlip_importprovider_userfile extends rlip_importprovider {
    var $filename;

    function __construct($filename) {
        $this->filename = $filename;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        return rlip_fileplugin_factory::factory($this->filename);
    }
}

class rlip_importprovider_userfile2 extends rlip_importprovider {
    var $filename;

    function __construct($filename) {
        $this->filename = $filename;
    }

    /**
     * Hook for providing a file plugin for a particular
     * import entity type
     *
     * @param string $entity The type of entity
     * @return object The file plugin instance, or false if not applicable
     */
    function get_import_file($entity) {
        if ($entity != 'user') {
            return false;
        }

        return new rlip_fileplugin_csv_mock($this->filename);
    }
}

/**
 * Class for testing database logging with the version 1 plugin
 */
class version1DatabaseLoggingTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB', 'USER');

    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        return array('block_rlip_summary_log' => 'block_rlip',
                     'user' => 'moodle',
                     'context' => 'moodle',
                     'user_enrolments' => 'moodle',
                     'cohort_members' => 'moodle',
                     'groups_members' => 'moodle',
                     'user_preferences' => 'moodle',
                     'user_info_data' => 'moodle',
                     'user_lastaccess' => 'moodle',
                     'block_instances' => 'moodle',
                     'block_positions' => 'moodle',
                     'filter_active' => 'moodle',
                     'filter_config' => 'moodle',
                     'comments' => 'moodle',
                     'rating' => 'moodle',
                     'role_assignments' => 'moodle',
                     'role_capabilities' => 'moodle',
                     'role_names' => 'moodle',
                     'cache_flags' => 'moodle',
                     'events_handlers' => 'moodle',
                     'course_categories' => 'moodle',
                     'course' => 'moodle',
                     'course_sections' => 'moodle',
                     'enrol' => 'moodle',
                     'course_completion_criteria' => 'moodle',
                     'course_completion_aggr_methd' => 'moodle',
                     'course_completions' => 'moodle',
                     'course_completion_crit_compl' => 'moodle',
                     'grade_categories' => 'moodle',
                     'grade_categories_history' => 'moodle',
                     'grade_items' => 'moodle',
                     'grade_items_history' => 'moodle',
                     'grade_outcomes_courses' => 'moodle',
                     'grade_settings' => 'moodle',
                     'grade_letters' => 'moodle',
                     'course_modules_completion' => 'moodle',
                     'course_modules' => 'moodle',
                     'course_modules_availability' => 'moodle',
                     'modules' => 'moodle',
                     'groupings' => 'moodle',
                     'groupings_groups' => 'moodle',
                     'groups' => 'moodle',
                     'course_display' => 'moodle',
                     'backup_courses' => 'moodle',
                     'backup_log' => 'moodle',
                     'role' => 'moodle',
                     'role_context_levels' => 'moodle',
                     'files' => 'moodle',
                     //this prevents createorupdate from being used
                     'config_plugins' => 'moodle',
                     'elis_scheduled_tasks' => 'elis_core',
                     'ip_schedule' => 'block_rlip');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('log' => 'moodle',
                     'event' => 'moodle',
                     'external_tokens' => 'moodle',
                     //usually written to during course delete
                     'grade_grades' => 'moodle',
                     'grade_grades_history' => 'moodle',
                     'external_services_users' => 'moodle');
    }

    /**
     * Determines whether a db log with the specified message exists
     *
     * @param string $message The message, or NULL to use the default success
     *                        message
     * @return boolean true if found, otherwise false
     */
    private function log_with_message_exists($message = NULL) {
        global $DB;

        if ($message === NULL) {
            $message = 'All lines from import file memoryfile were successfully processed.';
        }

        $select = "{$DB->sql_compare_text('statusmessage')} = :statusmessage";
        $params = array('statusmessage' => $message);
        return $DB->record_exists_select('block_rlip_summary_log', $select, $params);
    }

    /**
     * Asserts that a record in the given table exists
     *
     * @param string $table The database table to check
     * @param array $params The query parameters to validate against
     */
    private function assert_record_exists($table, $params = array()) {
        global $DB;

        $exists = $DB->record_exists($table, $params);
        $this->assertEquals($exists, true);
    }


    /**
     * Run the user import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_user_import($data) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $provider = new rlip_importprovider_loguser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
    }

    /**
     * Run the course import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_course_import($data) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $provider = new rlip_importprovider_logcourse($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
    }

    /**
     * Run the enrolment import with a fixed set of data
     *
     * @param array $data The data to include in the import
     */
    private function run_enrolment_import($data) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $provider = new rlip_importprovider_logenrolment($data);

        $importplugin = new rlip_importplugin_version1($provider);
        return $importplugin->run();
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * create
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnUserCreate() {
        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user create
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedUserCreate() {
        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'boguscountry');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * update
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnUserUpdate() {
        global $DB;

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'user',
                      'action' => 'update',
                      'username' => 'rlipusername',
                      'firstname' => 'rlipfirstname2');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user update
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedUserUpdate() {
        $data = array('entity' => 'user',
                      'action' => 'update',
                      'username' => 'rlipusername',
                      'firstname' => 'rlipfirstname2');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful user
     * delete
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnUserDelete() {
        global $DB;

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'user',
                      'action' => 'delete',
                      'username' => 'rlipusername');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * user delete
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedUserDelete() {
        $data = array('entity' => 'user',
                      'action' => 'delete',
                      'username' => 'rlipusername');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * create
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //set up the site course context
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course create
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedCourseCreate() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory',
                      'format' => 'bogusformat');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * update
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseUpdate() {
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //prevent problem with cached contexts
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        //set up the site course context
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'course',
                      'action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname2');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course update
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedCourseUpdate() {
        global $CFG;

        $data = array('entity' => 'course',
                      'action' => 'update',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname2');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful course
     * delete
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnCourseDelete() {
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        //prevent problem with cached contexts
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $data = array('entity' => 'course',
                      'action' => 'create',
                      'shortname' => 'rlipshortname',
                      'fullname' => 'rlipfullname',
                      'category' => 'rlipcategory');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'course',
                      'action' => 'delete',
                      'shortname' => 'rlipshortname');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * course delete
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedCourseDelete() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $data = array('entity' => 'course',
                      'action' => 'delete',
                      'shortname' => 'rlipshortname');
        $result = $this->run_course_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful enrolment
     * create
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnEnrolmentCreate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');

        //set up the site course context
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $category = new stdClass;
        $category->name = 'testcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course = create_course($course);

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->id = user_create_user($user);

        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * enrolment create
     */
    public function testVersion1DBLoggingDoesNotLogSuccessMessageOnFailedEnrolmentCreate() {
        global $CFG;

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging logs a success message on successful enrolment
     * delete
     */
    public function testVersion1DBLoggingLogsSuccessMessageOnEnrolmentDelete() {
        global $CFG, $DB, $UNITTEST;
        require_once($CFG->dirroot.'/user/lib.php');

        //prevent problem with cached contexts
        $UNITTEST->running = true;
        accesslib_clear_all_caches_for_unit_testing();
        unset($UNITTEST->running);

        //set up the site course context
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        $category = new stdClass;
        $category->name = 'testcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->category = $category->id;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course = create_course($course);

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->id = user_create_user($user);

        $roleid = create_role('rlipname', 'rlipshortname', 'rlipdescription');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');

        $data = array('entity' => 'enrolment',
                      'action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging does not log a success message on unsuccessful
     * enrolment delete
     */
    public function tesVersion1tDBLoggingDoesNotLogSuccessMessageOnFailedEnrolmentDelete() {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $data = array('entity' => 'enrolment',
                      'action' => 'delete',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $result = $this->run_enrolment_import($data);
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging includes the correct file name / path in the
     * success summary log message
     */
    public function testVersion1DBLoggingLogsCorrectFileNameOnSuccess() {
        global $DB;

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $provider = new rlip_importprovider_loguser_dynamic($data, 'fileone');

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $message = 'All lines from import file fileone were successfully processed.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);

        //prevent db conflicts
        $DB->delete_records('block_rlip_summary_log');
        $DB->delete_records('user');

        $provider = new rlip_importprovider_loguser_dynamic($data, 'filetwo');

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $message = 'All lines from import file filetwo were successfully processed.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging object correctly persists values and resets its state
     * when flushing data to the DB
     */
    public function testVersion1DBLoggingSuccessTrackingStoresCorrectValuesViaAPI() {
        global $USER;

        //set up the logger object
        $logger = new rlip_dblogger_import();

        //provide appropriate times
        $logger->set_plugin('plugin');
        $logger->set_targetstarttime(1000000000);
        $logger->set_starttime(1000000001);
        $logger->set_endtime(1000000002);

        //give it one of each "status"
        $logger->track_success(true, true);
        $logger->track_success(true, false);
        $logger->track_success(false, true);
        $logger->track_success(false, false);

        //specify number of db ops
        $logger->set_dbops(5);

        $logger->signal_unmetdependency();

        //validate setup
        $this->assertEquals($logger->plugin, 'plugin');
        $this->assertEquals($logger->userid, $USER->id);
        $this->assertEquals($logger->targetstarttime, 1000000000);
        $this->assertEquals($logger->starttime, 1000000001);
        $this->assertEquals($logger->endtime, 1000000002);
        $this->assertEquals($logger->filesuccesses, 1);
        $this->assertEquals($logger->filefailures, 1);
        $this->assertEquals($logger->storedsuccesses, 1);
        $this->assertEquals($logger->storedfailures, 1);
        $this->assertEquals($logger->dbops, 5);
        $this->assertEquals($logger->unmetdependency, 1);

        //flush
        $logger->flush('bogusfilename');

        //validate that the values were correctly persisted
        $params = array('plugin' => 'plugin',
                        'userid' => $USER->id,
                        'targetstarttime' => 1000000000,
                        'starttime' => 1000000001,
                        'endtime' => 1000000002,
                        'filesuccesses' => 1,
                        'filefailures' => 1,
                        'storedsuccesses' => 1,
                        'storedfailures' => 1,
                        'dbops' => 5,
                        'unmetdependency' => 1);
        $this->assert_record_exists('block_rlip_summary_log', $params);

        //validate that the state is reset
        $this->assertEquals($logger->plugin, 'plugin');
        $this->assertEquals($logger->userid, $USER->id);
        $this->assertEquals($logger->targetstarttime, 1000000000);
        $this->assertEquals($logger->starttime, 0);
        $this->assertEquals($logger->endtime, 0);
        $this->assertEquals($logger->filesuccesses, 0);
        $this->assertEquals($logger->filefailures, 0);
        $this->assertEquals($logger->storedsuccesses, 0);
        $this->assertEquals($logger->storedfailures, 0);
        $this->assertEquals($logger->dbops, -1);
        $this->assertEquals($logger->unmetdependency, 0);
    }

    /**
     * Validate that correct values are stored after an actual run of a
     * "version 1" import
     */
    public function testVersion1DBLoggingStoresCorrectValuesOnRun() {
        global $DB;

        //capture the earliest possible start time
        $mintime = time();

        $data = array(array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname',
                            'lastname' => 'rliplastname',
                            'email' => 'rlipuser@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'CA'),
                      array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername2',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname2',
                            'lastname' => 'rliplastname2',
                            'email' => 'rlipuse2r@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'boguscountry'));

        //run the import
        $provider = new rlip_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        //capture the latest possible end time
        $maxtime = time();

        //validate that values were persisted correctly
        $select = "plugin = :plugin AND
                   filesuccesses = :filesuccesses AND
                   filefailures = :filefailures AND
                   starttime >= :minstarttime AND
                   starttime <= :maxstarttime AND
                   endtime >= :minendtime AND
                   endtime <= :maxendtime";
        $params = array('plugin' => 'rlipimport_version1',
                        'filesuccesses' => 1,
                        'filefailures' => 1,
                        'minstarttime' => $mintime,
                        'maxstarttime' => $maxtime,
                        'minendtime' => $mintime,
                        'maxendtime' => $maxtime);
        $exists = $DB->record_exists_select('block_rlip_summary_log', $select, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that filenames are correctly stored when an import is run from
     * a file on the file system
     */
    public function testVersion1DBLoggingStoresCorrectFilenameOnRun() {
        global $CFG, $DB;

        //set the log file name to a fixed value
        $filename = $CFG->dataroot.'/rliptestfile.log';
        set_config('logfilelocation', $filename, 'rlipimport_version1');

        //set up a "user" import provider, using a single fixed file
        $file = $CFG->dirroot.'/blocks/rlip/importplugins/version1/phpunit/userfile.csv';
        $provider = new rlip_importprovider_userfile($file);

        //run the import
        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        //data validation
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'All lines from import file userfile.csv were successfully processed.');
        $exists = $DB->record_exists_select('block_rlip_summary_log', $select, $params);
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that import obeys maxruntime
     */
    public function testVersion1ImportObeysMaxRunTime() {
        global $CFG, $DB;

        //set the log file name to a fixed value
        $filename = $CFG->dataroot.'/rliptestfile.log';
        set_config('logfilelocation', $filename, 'rlipimport_version1');

        //set up a "user" import provider, using a single fixed file
        $file = $CFG->dirroot.'/blocks/rlip/importplugins/version1/phpunit/userfile2.csv';
        $provider = new rlip_importprovider_userfile2($file);

        //run the import
        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run(0, 1); // maxruntime 1 sec
        $this->assertNotNull($result);
        if (!empty($result)) {
            //print_object($result);
            $this->assertFalse($result->result);
            $this->assertEquals($result->entity, 'user');
            $this->assertEquals($result->filelines, 4);
            $this->assertEquals($result->linenumber, 1);
        }
    }

    /**
     * Validate that import starts from saved state
     */
    public function testVersion1ImportFromSavedState() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        //set up the import file path & entities filenames
        set_config('schedule_files_path', dirname(__FILE__),
                   'rlipimport_version1');
        set_config('user_schedule_file', 'userfile2.csv',
                   'rlipimport_version1');
        set_config('course_schedule_file', 'course.csv',
                   'rlipimport_version1');
        set_config('enrolment_schedule_file', 'enroll.csv',
                   'rlipimport_version1');
        // log file
        set_config('logfilelocation',
                   $CFG->dataroot .'/rlipimport_testVersion1ImportFromSavedState.log',
                   'rlipimport_version1');

        //create a scheduled job
        $data = array('plugin' => 'rlipimport_version1',
                      'period' => '5m',
                      'label' => 'bogus',
                      'type' => 'rlipimport');
        $taskid = rlip_schedule_add_job($data);

        //change the next runtime to a known value in the past
        $task = new stdClass;
        $task->id = $taskid;
        $task->id = $taskid;
        $task->nextruntime = 99;
        $DB->update_record('elis_scheduled_tasks', $task);

        $job = $DB->get_record('ip_schedule', array('plugin' => 'rlipimport_version1'));
        $job->nextruntime = 99;
        $state = new stdClass;
        $state->result = false;
        $state->entity = 'user';
        $state->filelines = 4;
        $state->linenumber = 3; // Should start at line 3 of userfile2.csv
        $ipjobdata = unserialize($job->config);
        $ipjobdata['state'] = $state;
        $job->config = serialize($ipjobdata);
        $DB->update_record('ip_schedule', $job);

        //run the import
        $taskname = $DB->get_field('elis_scheduled_tasks', 'taskname', array('id' => $taskid));
        run_ipjob($taskname);
        // verify the 1st & 2nd lines were NOT processed
        $notexists1 = $DB->record_exists('user', array('username' => 'testusername'));
        $this->assertFalse($notexists1);
        $notexists2 = $DB->record_exists('user', array('username' => 'testusername2'));
        $this->assertFalse($notexists2);
        $exists = $DB->record_exists('user', array('username' => 'testusername3'));
        $this->assertTrue($exists);
    }

    /**
     * Validate that filenames are correctly stored when an import is run
     * based on a Moodle file-system file
     */
    public function testVersion1DBLoggingStoresCorrectFilenameOnRunWithMoodleFile() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/rlip/rlip_importprovider_moodlefile.class.php');

        //set the log file name to a fixed value
        $filename = $CFG->dataroot.'/rliptestfile.log';
        set_config('logfilelocation', $filename, 'rlipimport_version1');

        //store it at the system context
        $context = get_context_instance(CONTEXT_SYSTEM);

        //file path and name
        $file_path = $CFG->dirroot.'/blocks/rlip/importplugins/version1/phpunit/';
        $file_name = 'userfile.csv';

        //file information
        $fileinfo = array('contextid' => $context->id,
                          'component' => 'system',
                          'filearea'  => 'draft',
                          'itemid'    => 9999,
                          'filepath'  => $file_path,
                          'filename'  => $file_name
                    );

        //create a file in the Moodle file system with the right content
        $fs = get_file_storage();
        $fs->create_file_from_pathname($fileinfo, "{$file_path}{$file_name}");
        $fileid = $DB->get_field_select('files', 'id', "filename != '.'");

        //run the import
        $entity_types = array('user', 'bogus', 'bogus');
        $fileids = array($fileid, false, false);
        $provider = new rlip_importprovider_moodlefile($entity_types, $fileids);

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        //data validation
        $select = "{$DB->sql_compare_text('statusmessage')} = :message";
        $params = array('message' => 'All lines from import file userfile.csv were successfully processed.');
        $exists = $DB->record_exists_select('block_rlip_summary_log', $select, $params);
        $this->assertEquals($exists, true);
    }


    /**
     * Validate that DB logging does not log a success message when a mixtures
     * of successes and failures is encountered
     */
    public function testVersion1DBLoggingDoesNotLogSuccessOnMixedResults() {
        $data = array(array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname',
                            'lastname' => 'rliplastname',
                            'email' => 'rlipuser@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'CA'),
                      array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername2',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname2',
                            'lastname' => 'rliplastname2',
                            'email' => 'rlipuse2r@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'boguscountry'));

        $provider = new rlip_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $exists = $this->log_with_message_exists();
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that DB logging records the correct number of successes and
     * failues from import file
     */
    public function tesVersion1tDBLoggingLogsCorrectCountsForManualImport() {
        global $DB;

        $data = array(array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname',
                            'lastname' => 'rliplastname',
                            'email' => 'rlipuser@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'CA'),
                      array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername2',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname2',
                            'lastname' => 'rliplastnam2e',
                            'email' => 'rlipuser2@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'boguscountry'),
                      array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername3',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname3',
                            'lastname' => 'rliplastname3',
                            'email' => 'rlipuser3@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'boguscountry'));

        $provider = new rlip_importprovider_multiuser($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $result = $importplugin->run();
        $this->assertNull($result);

        $exists = $DB->record_exists('block_rlip_summary_log', array('filesuccesses' => 1,
                                                                     'filefailures' => 2));
        $this->assertEquals($exists, true);
    }

    /**
     * Validate that DB logging stores the current user id when processing
     * import files
     */
    public function testVersion1DBLoggingLogsCorrectUseridForManualImport() {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        $USER->id = 9999;

        $data = array('entity' => 'user',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $exists = $DB->record_exists('block_rlip_summary_log', array('userid' => $USER->id));
        $this->assertEquals($exists, true);
    }

    /**
     * Validates the standard failure message
     */
    public function testVersion1DBLoggingLogsFailureMessage() {
        set_config('createorupdate', 0, 'rlipimport_version1');

        $data = array('entity' => 'user',
                      'action' => 'update',
                      'username' => 'rlipusername',
                      'password' => 'Rlippassword!0',
                      'firstname' => 'rlipfirstname',
                      'lastname' => 'rliplastname',
                      'email' => 'rlipuser@rlipdomain.com',
                      'city' => 'rlipcity',
                      'country' => 'CA');
        $result = $this->run_user_import($data);
        $this->assertNull($result);

        $message = 'One or more lines from import file memoryfile failed because they contain data errors. '.
                   'Please fix the import file and re-upload it.';
        $exists = $this->log_with_message_exists($message);
        $this->assertEquals($exists, true);
    }
}
