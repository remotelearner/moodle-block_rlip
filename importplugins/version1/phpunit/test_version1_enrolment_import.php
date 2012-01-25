<?php

global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once($CFG->dirroot.'/blocks/rlip/phpunit/readmemory.class.php');
require_once(elis::lib('testlib.php'));

/**
 * Class that fetches import files for the enrolment import
 */
class rlip_importprovider_mockenrolment extends rlip_importprovider {
    //fixed data to use as import data
    var $data;

    /**
     * Constructor
     * 
     * @param array $data Fixed file contents
     */
    function __construct($data) {
        $this->data = $data;
    }

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

        return new rlip_fileplugin_readmemory($rows);
    }
}

/**
 * Class for version 1 enrolment import correctness
 */
class version1EnrolmentImportTest extends elis_database_test {
    protected $backupGlobalsBlacklist = array('DB');

    /**
     * Return the list of tables that should be overlayed.
     */
    protected static function get_overlay_tables() {
        return array('context' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     'block_instances' => 'moodle',
                     'course_sections' => 'moodle',
                     'cache_flags' => 'moodle',
                     'enrol' => 'moodle',
                     'role_assignments' => 'moodle',
                     'user' => 'moodle',
                     'role' => 'moodle',
                     'role_context_levels' => 'moodle',
                     'user_enrolments' => 'moodle');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        return array('log' => 'moodle');
    }

    /**
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private function init_contexts_and_site_course() {
        global $DB;

        //set up context records
        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context");

        //set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }
    }

    /**
     * Create a test role
     *
     * @param string $name The role's display name
     * @param string $shortname The role's shortname
     * @param string $description The role's description
     * @return int The created role's id
     */
    private function create_test_role($name = 'rlipname', $shortname = 'rlipshortname',
                                      $description = 'rlipdescription') {
        $roleid = create_role($name, $shortname, $description);
        set_role_contextlevels($roleid, array(CONTEXT_COURSE));

        return $roleid;
    }

    /**
     * Create a test course
     *
     * @param array $extra_data Extra field values to set on the course
     * @return int The created course's id
     */
    private function create_test_course($extra_data = array()) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        $category = new stdClass;
        $category->name = 'rlipcategory';
        $category->id = $DB->insert_record('course_categories', $category);

        $course = new stdClass;
        $course->shortname = 'rlipshortname';
        $course->fullname = 'rlipfullname';
        $course->category = $category->id;

        foreach ($extra_data as $key => $value) {
            $course->$key = $value;
        }

        $course = create_course($course);

        return $course->id; 
    }

    /**
     * Create a test user
     *
     * @param array $extra_data Extra field values to set on the user
     * @return int The created user's id
     */
    private function create_test_user($extra_data = array()) {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');

        $user = new stdClass;
        $user->username = 'rlipusername';
        $user->password = 'Password!0';

        foreach ($extra_data as $key => $value) {
            $user->$key = $value;
        }

        return user_create_user($user);
    }

    /**
     * Creates a default guest user record in the database
     */
    private function create_guest_user() {
        global $CFG, $DB;

        //set up the guest user to prevent enrolment plugins from thinking the
        //created user is the guest user
        if ($record = self::$origdb->get_record('user', array('username' => 'guest',
                                                'mnethostid' => $CFG->mnet_localhost_id))) {
            unset($record->id);
            $DB->insert_record('user', $record);
        }
    }

    /**
     * Asserts, using PHPunit, that no role assignments exist
     */
    private function assert_no_role_assignments_exist() {
        global $DB;

        $exists = $DB->record_exists('role_assignments', array());
        $this->assertEquals($exists, false);
    }

    /**
     * Helper function to get the core fields for a sample enrolment
     *
     * @return array The enrolment data
     */
    private function get_core_enrolment_data() {
        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        return $data;
    }

    /**
     * Helper function that runs the enrolment import for a sample enrolment
     * 
     * @param array $extradata Extra fields to set for the new course
     */
    private function run_core_enrolment_import($extradata, $use_default_data = true) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/rlip/importplugins/version1/version1.class.php');

        if ($use_default_data) {
            $this->create_test_role();
            $this->create_test_course();
            $this->create_test_user();
            $data = $this->get_core_enrolment_data();
        } else {
            $data = array();
        }

        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }
        
        $provider = new rlip_importprovider_mockenrolment($data);

        $importplugin = new rlip_importplugin_version1($provider);
        $importplugin->run();
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
     * Validate that the version 1 plugin supports enrolment actions
     */
    public function testVersion1ImportSupportsEnrolmentActions() {
        $supports = plugin_supports('rlipimport', 'version1', 'enrolment');

        $this->assertEquals($supports, array('create', 'add'));
    }

    /**
     * Validate that the version 1 plugin supports the enrolment create action
     */
    public function testVersion1ImportSupportsEnrolmentCreate() {
        $supports = plugin_supports('rlipimport', 'version1', 'enrolment_create');
        $required_fields = array(array('username', 'email', 'idnumber'),
                                 'context',
                                 'instance',
                                 'role');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that the version 1 plugin supports the enrolment add action
     */
    public function testVersion1ImportSupportsEnrolmentAdd() {
        $supports = plugin_supports('rlipimport', 'version1', 'enrolment_add');
        $required_fields = array(array('username', 'email', 'idnumber'),
                                 'context',
                                 'instance',
                                 'role');

        $this->assertEquals($supports, $required_fields);
    }

    /**
     * Validate that required fields are set to specified values during enrolment creation
     */
    public function testVersion1ImportSetsRequiredEnrolmentFieldsOnCreate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $this->run_core_enrolment_import(array());

        $data = array();
        $data['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));

        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;

        $data['userid'] = $DB->get_field('user', 'id', array('username' => 'rlipusername'));

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that invalid username values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentUsernameOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid email values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentEmailOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid idnumber values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidEnrolmentIdnumberOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid context level values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidContextOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('context' => 'boguscontext'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid context instance values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidInstanceOnCreate() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('instance' => 'bogusshortname'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that invalid role shortname values can't be set on enrolment creation
     */
    public function testVersion1ImportPreventsInvalidRoleOnCreate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('role' => 'bogusshortname'));
        $this->assert_no_role_assignments_exist();

        $roleid = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));
        set_role_contextlevels($roleid, array());
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the import does not set unsupported fields on enrolment creation
     */
    public function testVersion1ImportPreventsSettingUnsupportedEnrolmentFieldsOnCreate() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $roleid = $this->create_test_role('Student', 'student', 'Student');

        $this->run_core_enrolment_import(array('role' => 'student',
                                               'timemodified' => 12345,
                                               'modifierid' => 12345,
                                               'timestart' => 12345));

        $this->assertEquals($DB->count_records('role_assignments'), 1);
        $this->assertEquals($DB->count_records('user_enrolments'), 1);

        $exists = $DB->record_exists('role_assignments', array('timemodified' => 12345));
        $this->assertEquals($exists, false);
        $exists = $DB->record_exists('role_assignments', array('modifierid' => 12345));
        $this->assertEquals($exists, false);

        $exists = $DB->record_exists('user_enrolments', array('timestart' => 12345));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the import does not create duplicate role assignment
     * records on creation
     */
    public function testVersion1ImportPreventsDuplicateRoleAssignmentCreation() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array());
        $this->run_core_enrolment_import($this->get_core_enrolment_data(), false);

        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that the import does not created duplicate enrolment records on
     * creation
     */
    public function testVersion1ImportPreventsDuplicateEnrolmentCreation() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $roleid = $this->create_test_role('Student', 'student', 'Student');
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user();

        //enrol the user
        enrol_try_internal_enrol($courseid, $userid);

        //attempt to re-enrol
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        //compare data
        $this->assertEquals($DB->count_records('user_enrolments'), 1);
    }

    /**
     * Validate that the import enrols students using the 2.0 mechanism when
     * appropriate
     */
    public function testVersion1ImportEnrolsAppropriateUserAsStudent() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //create the test student role
        $roleid = $this->create_test_role('Student', 'student', 'Student');

        $this->run_core_enrolment_import(array('role' => 'student'));

        //compare data
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'manual',
                                                       'courseid' => $courseid));

        $this->assert_record_exists('user_enrolments', array('userid' => $userid,
                                                             'enrolid' => $enrolid));
    }

    /**
     * Validate that the import does not enrol students using the 2.0 mechanism
     * when not appropriate
     */
    public function testVersion1ImportDoesNotEnrolInappropriateUserAsStudent() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array());
        $userid = $DB->get_field('user', 'id', array('username' => 'rlipusername'));
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $enrolid = $DB->get_field('enrol', 'id', array('enrol' => 'manual',
                                                       'courseid' => $courseid));

        //compare data
        $exists = $DB->record_exists('user_enrolments', array('userid' => $userid,
                                                              'enrolid' => $enrolid));
        $this->assertEquals($exists, false);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsername() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $this->run_core_enrolment_import(array());

        $data = array();
        $data['roleid'] = $DB->get_field('role', 'id', array('shortname' => 'rlipshortname'));

        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;

        $data['userid'] = $DB->get_field('user', 'id', array('username' => 'rlipusername'));

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * email
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnEmail() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'email' => 'rlipuser@rlipdomain.com',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('idnumber' => 'rlipidnumber'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username and email
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameEmail() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('idnumber' => 'rlipidnumber'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * email and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnEmailIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com',
                                                'idnumber' => 'rlipidnumber'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $courseid = $DB->get_field('course', 'id', array('shortname' => 'rlipshortname'));
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin can create enrolments based on
     * username, email and idnumber
     */
    public function testVersion1ImportCreatesEnrolmentBasedOnUsernameEmailIdnumber() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        //run the import
        $roleid = $this->create_test_role();
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user(array('email' => 'rlipuser@rlipdomain.com',
                                               'idnumber' => 'rlipidnumber'));

        $data = array('entity' => 'enrolment',
                      'action' => 'create',
                      'username' => 'rlipusername',
                      'email' => 'rlipuser@rlipdomain.com',
                      'idnumber' => 'rlipidnumber',
                      'context' => 'course',
                      'instance' => 'rlipshortname',
                      'role' => 'rlipshortname');
        $this->run_core_enrolment_import($data, false);

        $data = array();
        $data['roleid'] = $roleid;
        $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
        $data['contextid'] = $course_context->id;
        $data['userid'] = $userid;

        //compare data
        $this->assert_record_exists('role_assignments', $data);
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified username if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidUsernameInvalidEmail() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('email' => 'bogususer@bogusdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified username if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidUsernameInvalidIdnumber() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified email if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidEmailInvalidUsername() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('email' => 'rlipuser@rlipdomain.com',
                                               'username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified email if the specified idnumber is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidEmailInvalidIdnumber() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('email' => 'rlipuser@rlipdomain.com',
                                               'idnumber' => 'bogusidnumber'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified idnumber if the specified username is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidIdnumberInvalidUsername() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('idnumber' => 'rlipidnumber',
                                               'username' => 'bogususername'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that the version 1 plugin does not created enrolments for
     * specified idnumber if the specified email is incorrect
     */
    public function testVersion1ImportDoesNotCreateEnrolmentForValidIdnumberInvalidEmail() {
        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('idnumber' => 'rlipidnumber',
                                               'email' => 'rlipuser@rlipdomain.com'));
        $this->assert_no_role_assignments_exist();
    }

    /**
     * Validate that a user can still be enroled in a course even if they
     * already have a role assignment in that course
     */
    public function testVersionImportEnrolsUserAlreadyAssignedARole() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        $roleid = $this->create_test_role('Student', 'student', 'Student');
        $courseid = $this->create_test_course();
        $userid = $this->create_test_user();

        $context = get_context_instance(CONTEXT_COURSE, $courseid);

        role_assign($roleid, $userid, $context->id);

        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        $this->assert_record_exists('user_enrolments', array());
        $this->assertEquals($DB->count_records('role_assignments'), 1);
    }

    /**
     * Validate that an error with the role assignment information prevents
     * course enrolment from taking place
     */
    public function testVersion1ImportRoleErrorPreventsEnrolment() {
        global $DB;

        //setup
        $this->init_contexts_and_site_course();

        $this->run_core_enrolment_import(array('role' => 'bogusshortname'));

        $this->assertEquals($DB->count_records('user_enrolments'), 0);
    }

    /**
     * Validate that course enrolment create action sets start time, time
     * created and time modified appropriately
     */
    public function testVersion1ImportSetsEnrolmentTimestamps() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        //setup
        $this->init_contexts_and_site_course();
        $this->create_guest_user();

        //record the current time
        $starttime = time();

        //data setup
        $this->create_test_role('Student', 'student', 'Student');
        $this->create_test_course(array('startdate' => 12345)); 
        $this->create_test_user();

        //run the import
        $data = $this->get_core_enrolment_data();
        $data['role'] = 'student';
        $this->run_core_enrolment_import($data, false);

        //ideal enrolment start time
        $course_startdate = $DB->get_field('course', 'startdate', array('shortname' => 'rlipshortname'));

        //validate enrolment record
        $where = 'timestart = :timestart AND
                  timecreated >= :timecreated AND
                  timemodified >= :timemodified';
        $params = array('timestart' => 12345,
                        'timecreated' => $starttime,
                        'timemodified' => $starttime);
        $exists = $DB->record_exists_select('user_enrolments', $where, $params);
        $this->assertEquals($exists, true);

        //validate role assignment record
        $where = 'timemodified >= :timemodified';
        $params = array('timemodified' => $starttime);
        $exists = $DB->record_exists_select('role_assignments', $where, $params);
        $this->assertEquals($exists, true);
    }
}