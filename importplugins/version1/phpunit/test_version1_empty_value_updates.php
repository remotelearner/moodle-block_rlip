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
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');

/**
 * Class that fetches import files for the user import
 */
class rlip_importprovider_emptyuser extends rlip_importprovider_multi_mock {

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
class rlip_importprovider_emptycourse extends rlip_importprovider_multi_mock {

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
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_emptyenrolment extends rlip_importprovider_multi_mock {

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
 * Class for testing how Version 1 deals with "empty" values in updates
 */
class version1EmptyValueUpdatesTest extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static function get_overlay_tables() {
        global $CFG;

        //custom fields in "elis core"
        require_once($CFG->dirroot.'/elis/core/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        $file = get_plugin_directory('rlipimport', 'version1').'/lib.php';
        require_once($file);

        $tables = array(
            'user' => 'moodle',
            'course' => 'moodle',
            'course_categories' => 'moodle',
            'role' => 'moodle',
            'role_context_levels' => 'moodle',
            'context' => 'moodle',
            'config_plugins' => 'moodle',
            'role_assignments' => 'moodle',
            RLIPIMPORT_VERSION1_MAPPING_TABLE => 'rlipimport_version1',
            field_data_int::TABLE => 'elis_core',
            field_data_char::TABLE => 'elis_core',
            field_data_text::TABLE => 'elis_core',
            'config' => 'moodle'
        );

        // Detect if we are running this test on a site with the ELIS PM system in place
        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
            require_once(elispm::lib('data/user.class.php'));
            require_once(elispm::lib('data/usermoodle.class.php'));

            $tables[user::TABLE] = 'elis_program';
            $tables[usermoodle::TABLE] = 'elis_program';
        }

        return $tables;
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/lib.php');

        return array(RLIP_LOG_TABLE => 'block_rlip',
                     'block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'enrol' => 'moodle',
                     'log' => 'moodle',
                     'user_enrolments' => 'moodle');
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
     * Validates that the version 1 update ignores empty values and does not
     * blank out fields for users
     */
    function testVersion1UserUpdateIgnoresEmptyValues() {
        global $CFG;

        set_config('createorupdate', 0, 'rlipimport_version1');

        //create, then update a user
        $data = array(array('entity' => 'user',
                            'action' => 'create',
                            'username' => 'rlipusername',
                            'password' => 'Rlippassword!0',
                            'firstname' => 'rlipfirstname',
                            'lastname' => 'rliplastname',
                            'email' => 'rlipuser@rlipdomain.com',
                            'city' => 'rlipcity',
                            'country' => 'CA',
                            'idnumber' => 'rlipidnumber'),
                      array('entity' => 'user',
                            'action' => 'update',
                            'username' => 'rlipusername',
                            'password' => '',
                            'firstname' => 'updatedrlipfirstname',
                            'lastname' => '',
                            'email' => '',
                            'city' => '',
                            'country' => '',
                            'idnumber' => ''));
        $provider = new rlip_importprovider_emptyuser($data);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $importplugin->run();

        //validation
        $params = array('username' => 'rlipusername',
                        'mnethostid' => $CFG->mnet_localhost_id,
                        'password' => hash_internal_user_password('Rlippassword!0'),
                        'firstname' => 'updatedrlipfirstname',
                        'lastname' => 'rliplastname',
                        'email' => 'rlipuser@rlipdomain.com',
                        'city' => 'rlipcity',
                        'country' => 'CA',
                        'idnumber' => 'rlipidnumber');
        $this->assert_record_exists('user', $params);
    }

    /**
     * Validates that the version 1 update ignores empty values and does not
     * blank out fields for courses
     */
    function testVersion1CourseUpdateIgnoresEmptyValues() {
        global $CFG, $DB;

        set_config('createorupdate', 0, 'rlipimport_version1');

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        //create key context records
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //create, then update a course
        $data = array(array('entity' => 'course',
                            'action' => 'create',
                            'shortname' => 'rlipshortname',
                            'fullname' => 'rlipfullname',
                            'category' => 'rlipcategory'),
                      array('entity' => 'course',
                            'action' => 'update',
                            'shortname' => 'rlipshortname',
                            'fullname' => '',
                            'category' => 'updatedrlipcategory'));
        $provider = new rlip_importprovider_emptycourse($data);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $importplugin->run();

        //category validation
        $this->assert_record_exists('course_categories', array('name' => 'updatedrlipcategory'));
        $categoryid = $DB->get_field('course_categories', 'id', array('name' => 'updatedrlipcategory'));

        //main validation
        $params = array('shortname' => 'rlipshortname',
                        'fullname' => 'rlipfullname',
                        'category' => $categoryid);
        $this->assert_record_exists('course', $params);
    }

    /**
     * Validates that the version 1 update ignores empty values and does not
     * blank out fields for enrolments
     */
    function testVersion1EnrolmentCreateAndDeleteIgnoreEmptyValues() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');

        set_config('createorupdate', 0, 'rlipimport_version1');
        set_config('gradebookroles', '');

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        //create key context records
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));

        //create category
        $category = new stdClass;
        $category->name = 'rlipcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        //create course
        $course = new stdClass;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course->category = $category->id;

        $course = create_course($course);

        //create user
        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->idnumber = 'rlipidnumber';
        $user->email = 'rlipuser@rlipdomain.com';
        $user->password = 'Password!0';
        $user->idnumber = 'rlipidnumber';

        $user->id = user_create_user($user);

        //create role
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $roleid = create_role('rliprole', 'rliprole', 'rliprole');
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        //create an enrolment
        $data = array(array('entity' => 'enrolment',
                            'action' => 'create',
                            'username' => 'rlipusername',
                            'email' => '',
                            'idnumber' => '',
                            'context' => 'course',
                            'instance' => 'rlipshortname',
                            'role' => 'rliprole'));

        $provider = new rlip_importprovider_emptyenrolment($data);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $importplugin->run();

        $this->assert_record_exists('role_assignments', array('userid' => $user->id,
                                                              'contextid' => $context->id,
                                                              'roleid' => $roleid));

        //delete an enrolment
        $data[0]['action'] = 'delete';

        $provider = new rlip_importprovider_emptyenrolment($data);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $importplugin->run();

        //validation
        $exists = $DB->record_exists('role_assignments', array('userid' => $user->id,
                                                               'contextid' => $context->id,
                                                               'roleid' => $roleid));
        $this->assertFalse($exists);
    }

    /**
     * Validates that the version 1 update ignores empty fields that could
     * potentially be used to identify a user
     */
    function testVersion1UserUpdateIgnoresEmptyIdentifyingFields() {
        global $CFG;

        require_once($CFG->dirroot.'/user/lib.php');

        //create a user
        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->idnumber = 'rlipidnumber';
        $user->email = 'rlipuser@rlipdomain.com';
        $user->password = 'Password!0';
        $user->idnumber = 'rlipidnumber';

        $user->id = user_create_user($user);

        //update a user with blank email and idnumber
        $data = array(array('entity' => 'user',
                            'action' => 'update',
                            'username' => 'rlipusername',
                            'email' => '',
                            'idnumber' => '',
                            'firstname' => 'updatedrlipfirstname1'));

        $provider = new rlip_importprovider_emptyuser($data);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $importplugin->run();

        //validation
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => 'rlipidnumber',
                                                  'email' => 'rlipuser@rlipdomain.com',
                                                  'firstname' => 'updatedrlipfirstname1'));

        //update a user with a blank username
        $data = array(array('entity' => 'user',
                            'action' => 'update',
                            'username' => '',
                            'email' => 'rlipuser@rlipdomain.com',
                            'idnumber' => 'rlipidnumber',
                            'firstname' => 'updatedrlipfirstname2'));

        $provider = new rlip_importprovider_emptyuser($data);

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1', $provider);
        $importplugin->run();

        //validation
        $this->assert_record_exists('user', array('username' => 'rlipusername',
                                                  'mnethostid' => $CFG->mnet_localhost_id,
                                                  'idnumber' => 'rlipidnumber',
                                                  'email' => 'rlipuser@rlipdomain.com',
                                                  'firstname' => 'updatedrlipfirstname2'));

    }
}
