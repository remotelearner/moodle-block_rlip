<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    rlipexport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

// Required classes / libraries.
require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/setup.php');
require_once(elis::lib('testlib.php'));
require_once($CFG->dirroot.'/blocks/rlip/exportplugins/version1elis/phpunit/rlip_fileplugin_export.class.php');
require_once(dirname(__FILE__).'/../lib.php');
require_once(dirname(__FILE__).'/mock_obj.php');

/**
 * Test class for validating basic export data during a manual, nonincremental
 * export
 */
class version1elisManualIncrementalExportTest extends elis_database_test {
    /**
     * Fetches our export data as a multi-dimensional array
     *
     * @return array The export data
     */
    protected function get_export_data($manual = true, $targetstarttime = 0, $lastruntime = 0) {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');
        // set the incremental time delta
        set_config('incrementaldelta', '1d', 'rlipexport_version1elis');

        // plugin for file IO
        $fileplugin = new rlip_fileplugin_export();
        $fileplugin->open(RLIP_FILE_WRITE);

        // our specific export
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin, $manual);
        $exportplugin->init($targetstarttime, $lastruntime);
        $exportplugin->export_records(0);
        $exportplugin->close();

        $fileplugin->close();

        return $fileplugin->get_data();
    }

    /**
     * Load in our test data from CSV files
     */
    protected function load_csv_data($multiple_users = false) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        $dataset = new PHPUnit_Extensions_Database_DataSet_CsvDataSet();

        if ($multiple_users) {
            // data for multiple users
            $dataset->addTable(course::TABLE, dirname(__FILE__).'/pmcourses.csv');
            $dataset->addTable(pmclass::TABLE, dirname(__FILE__).'/pmclasses.csv');
            $dataset->addTable(student::TABLE, dirname(__FILE__).'/students.csv');
            $dataset->addTable(user::TABLE, dirname(__FILE__).'/pmusers.csv');
        } else {
            // data for a single user
            $dataset->addTable(course::TABLE, dirname(__FILE__).'/pmcourse.csv');
            $dataset->addTable(pmclass::TABLE, dirname(__FILE__).'/pmclass.csv');
            $dataset->addTable(student::TABLE, dirname(__FILE__).'/student.csv');
            $dataset->addTable(user::TABLE, dirname(__FILE__).'/pmuser.csv');
        }

        load_phpunit_data_set($dataset, true, self::$overlaydb);
    }

    /**
     * Return the list of tables that should be overlayed.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_overlay_tables() {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/lib.php';
        require_once($file);
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        return array('config_plugins' => 'moodle',
                     'context' => 'moodle',
                     'course' => 'moodle',
                     'course_categories' => 'moodle',
                     'grade_letters' => 'moodle',
                     RLIPEXPORT_VERSION1ELIS_FIELD_TABLE => 'rlipexport_version1elis',
                     classmoodlecourse::TABLE => 'elis_program',
                     course::TABLE => 'elis_program',
                     pmclass::TABLE => 'elis_program',
                     student::TABLE => 'elis_program',
                     user::TABLE => 'elis_program',
                     field::TABLE                       => 'elis_core',
                     field_category::TABLE              => 'elis_core',
                     field_category_contextlevel::TABLE => 'elis_core',
                     field_contextlevel::TABLE          => 'elis_core',
                     field_data_char::TABLE             => 'elis_core',
                     field_data_int::TABLE              => 'elis_core',
                     field_data_num::TABLE              => 'elis_core',
                     field_data_text::TABLE             => 'elis_core',
                     field_owner::TABLE                 => 'elis_core',
                     );
    }

    /**
     * Return the list of tables that should be ignored for writes.
     *
     * @return array Mapping of tables to components
     */
    static protected function get_ignored_tables() {

        require_once(elispm::lib('data/usermoodle.class.php'));

        return array('block_instances' => 'moodle',
                     'cache_flags' => 'moodle',
                     'course_sections' => 'moodle',
                     'enrol' => 'moodle',
                     'log' => 'moodle',
                     'user' => 'moodle',
                     usermoodle::TABLE => 'elis_program');
    }

    /**
     * Set up the course and context records needed for many of the
     * unit tests
     */
    private static function init_contexts_and_site_course() {
        global $DB;

        $prefix = self::$origdb->get_prefix();
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ?", array(CONTEXT_SYSTEM));
        $DB->execute("INSERT INTO {context}
                      SELECT * FROM
                      {$prefix}context
                      WHERE contextlevel = ? and instanceid = ?", array(CONTEXT_COURSE, SITEID));
        // set up the site course record
        if ($record = self::$origdb->get_record('course', array('id' => SITEID))) {
            unset($record->id);
            $DB->insert_record('course', $record);
        }

        build_context_path();
    }

    /**
     * Create a custom field category
     *
     * @return int The database id of the new category
     */
    private function create_custom_field_category() {
        global $DB;

        $category = new stdClass;
        $category->sortorder = $DB->count_records(field_category::TABLE) + 1;
        $category->id = $DB->insert_record(field_category::TABLE, $category);

        return $category->id;
    }

    /**
     * Creates a PM custom field data record associated to the entity
     *
     * @param int $userid The PM user's id
     * @param int $fieldid The PM custom field id
     * @param string $data The data to set
     */
    private function update_data_record($entitytype = 'user', $entity, $field, $data) {
        require_once(elispm::lib('data/'.$entitytype.'.class.php'));

        // save the value for the custom field for the entity
        $contextlevel = context_elis_helper::get_level_from_name($entitytype);
        $contextclass = context_elis_helper::get_class_for_level($contextlevel);
        $context = $contextclass::instance($entity->id);
        $classname = 'field_data_'.$field->datatype;
        $field_data = new $classname(array('fieldid' => $field->id));
        $result = $field_data->set_for_context_and_field($context, $field, $data);
        return $result;

    }

    /**
     * Create the test custom profile field and owner
     *
     * @param string $contextlevelname The name of the custom context level to create the field at
     * @param string $name PM custom field shortname
     * @param string $data_type The string identifier of the data type to use
     * @param string $ui_type The string identifier of the UI / control type to use
     * @param int $categoryid PM custom field category id
     * @param string $options Extra parameter, used for select options
     * @param string $defaultdata Default value.
     *
     * @return int The id of the created field
     */
    private function create_test_field($contextlevelname = 'user', $name = 'testfieldname', $data_type, $ui_type, $categoryid,
                                       $options = null, $defaultdata = null) {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elis::lib('data/customfield.class.php'));

        // category contextlevel
        $contextlevel = context_elis_helper::get_level_from_name($contextlevelname);
        $field_category_contextlevel = new field_category_contextlevel(array('categoryid' => $categoryid,
                                                                             'contextlevel' => $contextlevel));
        $field_category_contextlevel->save();

        // field
        $field = new field(array('shortname' => 'testfieldshortname',
                                 'name' => $name,
                                 'categoryid' => $categoryid,
                                 'datatype' => $data_type));

        $field->save();

        // field_data if a default value needs to be set
        if ($defaultdata !== null) {
            $classname = 'field_data_'.$data_type;
            $field_data = new $classname(array('fieldid' => $field->id,
                                               'data'    => $defaultdata));
            $field_data->save();
        }

        // field contextlevel
        $field_contextlevel = new field_contextlevel(array('fieldid' => $field->id,
                                                           'contextlevel' => $contextlevel));

        $field_contextlevel->save();

        // field owner
        $owner_data = array('control' => $ui_type);

        if ($options !== null) {
            // set options
            $options = (is_array($options)) ? implode("\n", $options) : $options;
            $owner_data['options'] = $options;
        }

        field_owner::ensure_field_owner_exists($field, 'manual', $owner_data);

        return $field;
    }

    /**
     * Create a database record maps a field to an export column.
     *
     * @param string $fieldset   The fieldset to map.
     * @param string    $field      The field to map.
     * @param string $header     The string to display as a CSV column header
     * @param int    $fieldorder A number used to order fields in the export
     */
    protected function create_field_mapping($fieldset, $field, $header = 'Header', $fieldorder = null) {
        global $DB;

        // Set up and insert the record.
        $mapping = new stdClass;
        $mapping->fieldset = $fieldset;
        $mapping->field = $field;
        if (!empty($header)) {
            $mapping->header = $header;
        }

        if ($fieldorder !== null) {
            $mapping->fieldorder = $fieldorder;
        } else {
            $mapping->fieldorder = ($DB->get_field_sql('SELECT MAX(fieldorder) FROM {'.RLIPEXPORT_VERSION1ELIS_FIELD_TABLE.'}')+1);
        }

        $DB->insert_record(RLIPEXPORT_VERSION1ELIS_FIELD_TABLE, $mapping);
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
     * Provider to be used for validating the export header
     *
     * @return array The expected header structure
     */
    public function valid_header_provider() {
        $expectedheader = array(
            get_string('header_firstname', 'rlipexport_version1elis'),
            get_string('header_lastname', 'rlipexport_version1elis'),
            get_string('header_username', 'rlipexport_version1elis'),
            get_string('header_useridnumber', 'rlipexport_version1elis'),
            get_string('header_courseidnumber', 'rlipexport_version1elis'),
            get_string('header_startdate', 'rlipexport_version1elis'),
            get_string('header_enddate', 'rlipexport_version1elis'),
            get_string('header_status', 'rlipexport_version1elis'),
            get_string('header_grade', 'rlipexport_version1elis'),
            get_string('header_letter', 'rlipexport_version1elis')
        );
        return array(array($expectedheader));
    }

    /**
     * Validate that the export contains the appropriate headers
     *
     * @param array $expectedheader The expected header data
     * @dataProvider valid_header_provider
     */
    public function test_export_contains_correct_header($expectedheader) {
        // setup
        $data = $this->get_export_data();

        // validation
        $this->assertEquals($expectedheader, $data[0]);
    }

    /**
     * Provider for basic (core) export data
     *
     * @return array The expected column data
     */
    public function valid_data_provider() {
        $expecteddata = array('exportfirstname', 'exportlastname', 'exportusername', 'exportidnumber',
                              'testcourseidnumber', date('M/d/Y', 1000000000), date('M/d/Y', 1500000000),
                              'COMPLETED', '70.00000', 'C-');
        return array(array($expecteddata));
    }

    /**
     * Validate that the export contains the necessary data when the
     * approriate data is present in the database
     *
     * @param array $expecteddata The expected column data
     * @dataProvider valid_data_provider
     */
    public function test_export_contains_valid_data($expecteddata) {
        // setup
        $this->load_csv_data();
        $data = $this->get_export_data();

        // validation
        $this->assertEquals(2, count($data));
        $this->assertEquals($expecteddata, $data[1]);
    }

    /**
     * Validate that the export works with the minimum amount of required data
     */
    public function test_export_works_with_minimal_associations() {
        global $CFG, $DB;

        // setup
        $this->load_csv_data();
        $data = $this->get_export_data();

        // validation
        $this->assertEquals(2, count($data));
    }

    /**
     * Data provider for necessary associations / entities
     *
     * @return array The list of tables containing required data
     */
    public function necessary_associations_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/classmoodlecourse.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/pmclass.class.php'));
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/user.class.php'));

        return array(array(course::TABLE),
                     array(pmclass::TABLE),
                     array(student::TABLE),
                     array(user::TABLE));
    }

    /**
     * Validate that removing any one required association or entity will
     * invalidate data for consideration in the export
     *
     * @param string $tablename The name of a necessary database table
     * @dataProvider necessary_associations_provider
     */
    public function test_exportrespects_necessaryassociations($tablename) {
        global $DB;

        // setup
        $this->load_csv_data();
        // delete records from required table
        $DB->delete_records($tablename);
        $data = $this->get_export_data();

        // validation (should only have the header row)
        $this->assertEquals(1, count($data));
    }

    /**
     * Data provider used to validate all the various completion status states
     *
     * @return array An array containing each possible completion status state
     */
    public function completion_status_provider() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/pmclass.class.php'));

        return array(array(student::STUSTATUS_NOTCOMPLETE),
                     array(student::STUSTATUS_FAILED),
                     array(student::STUSTATUS_PASSED));
    }

    /**
     * Validate that the export only includes passed enrolments
     *
     * @param int $status A completion status to use on the student enrolment
     * @dataProvider completion_status_provider
     */
    public function test_exportrespects_completionstatus($status) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));

        // setup
        $this->load_csv_data();
        $sql = "UPDATE {".student::TABLE."}
                SET completestatusid = ?";
        $params = array($status);
        $DB->execute($sql, $params);

        $data = $this->get_export_data();

        // validation
        if ($status == student::STUSTATUS_PASSED) {
            // the record should be included
            $this->assertEquals(2, count($data));
        } else {
            // should only have a header
            $this->assertEquals(1, count($data));
        }
    }

    /**
     * Data provider for grade letters
     *
     * @return array An array specifying grade-"letter" pairs
     */
    public function grade_letter_provider() {
        return array(array(10, 1),
                     array(20, 2),
                     array(30, 3),
                     array(40, 4),
                     array(50, 5),
                     array(60, 6),
                     array(70, 7),
                     array(80, 8),
                     array(90, 9),
                     array(100, 10));
    }

    /**
     * Validate that the export respects grade letter boundaries and the
     * existence of an associated Moodle course
     *
     * @param int $grade The enrolment grade
     * @param int $letter The expected enrolment grade letter
     * @dataProvider grade_letter_provider
     */
    public function test_exportrespects_gradeletters($grade, $letter) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));
        require_once(elispm::lib('data/classmoodlecourse.class.php'));

        // setup
        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // create a Moodle course category
        $categorydata = new stdClass;
        $categorydata->name = 'testcategory';
        $categorydata->id = $DB->insert_record('course_categories', $categorydata);

        // create a Moodle course
        $coursedata = new stdClass;
        $coursedata->category = $categorydata->id;
        $coursedata->fullname = 'testcourse';
        $coursedata = create_course($coursedata);

        // associate the PM class instance to the Moodle course
        $classmoodle = new classmoodlecourse(array('classid' => 200,
                                                   'moodlecourseid' => $coursedata->id));
        $classmoodle->save();

        // create grade letter mappings
        $context = context_course::instance($coursedata->id);
        $mappings = array(10 => 1,
                          20 => 2,
                          30 => 3,
                          40 => 4,
                          50 => 5,
                          60 => 6,
                          70 => 7,
                          80 => 8,
                          90 => 9,
                          100 => 10);
        foreach ($mappings as $insertlowerboundary => $insertletter) {
            $record = new stdClass;
            $record->contextid = $context->id;
            $record->lowerboundary = $insertlowerboundary;
            $record->letter = $insertletter;
            $DB->insert_record('grade_letters', $record);
        }

        // set the enrolment grade
        $sql = "UPDATE {".student::TABLE."}
                SET grade = ?";
        $params = array($grade);
        $DB->execute($sql, $params);

        $data = $this->get_export_data();

        // validation
        $this->assertEquals(2, count($data));
        $this->assertEquals((string)$letter, $data[1][9]);
    }

    /**
     * Data provider for testing sort orders
     *
     * @return array Data needed for testing sort orders
     */
    public function sort_order_provider() {
        $data = array();

        // set up some times that will be valid
        $firsttime = time();
        $secondtime = $firsttime + DAYSECS;

        // sort first based on idnumber
        $data[] = array(array('idnumber' => 'a'),
                        array(),
                        array(),
                        array('idnumber' => 'b'),
                        array(),
                        array(),
                        3,
                        'a');
        $data[] = array(array('idnumber' => 'b'),
                        array(),
                        array(),
                        array('idnumber' => 'a'),
                        array(),
                        array(),
                        3,
                        'a');
        // sort second based on course idnumber
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array(),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'b'),
                        array(),
                        4,
                        'a');
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'b'),
                        array(),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array(),
                        4,
                        'a');
        // sort third based on completion time / date
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => $firsttime),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => $secondtime),
                        6,
                        date("M/d/Y", $firsttime));
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => $secondtime),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => $firsttime),
                        6,
                        date("M/d/Y", $firsttime));
        // sort fourth based on completion grade
        $data[] = array(array('idnumber' => 'a'),
                       array('idnumber' => 'a'),
                       array('completetime' => $firsttime,
                             'grade' => 2),
                       array('idnumber' => 'a'),
                       array('idnumber' => 'a'),
                       array('completetime' => $firsttime,
                             'grade' => 1),
                       8,
                       2);
        $data[] = array(array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => $firsttime,
                              'grade' => 1),
                        array('idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => $firsttime,
                              'grade' => 2),
                        8,
                        2);
        // sort last based on record id
        $data[] = array(array('username' => 'firstuser',
                              'idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => $firsttime,
                              'grade' => 1),
                        array('username' => 'seconduser',
                              'idnumber' => 'a'),
                        array('idnumber' => 'a'),
                        array('completetime' => $firsttime,
                              'grade' => 1),
                        2,
                        'firstuser');

        // return all the appropriate data
        return $data;
    }

    /**
     * Validate that the export respects the pre-defined sort order
     *
     * @param array $user1attributes Attributes for the first PM user
     * @param array $cd1attributes Attributes for the first course description
     * @param array $enrolment1attributes Attributes for the first enrolment
     * @param array $user2attributes Attributes for the second PM user
     * @param array $cd2attributes Attributes for the second course description
     * @param array $enrolment2attributes Attributes for the second enrolment
     * @param int checkfield Index of the field to check in the export data
     * @param string checkvalue Value to valdate atains
     * @dataProvider sort_order_provider
     */
    public function test_export_respects_sortorder($user1attributes, $cd1attributes, $enrolment1attributes,
                                                   $user2attributes, $cd2attributes, $enrolment2attributes,
                                                   $checkfield, $checkvalue) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/course.class.php'));
        require_once(elispm::lib('data/student.class.php'));

        // setup
        $this->load_csv_data(true);

        // persist the specified attributes
        if (count($user1attributes) > 0) {
            $record = (object)$user1attributes;
            $record->id = 200;
            $DB->update_record(user::TABLE, $record);
        }

        if (count($cd1attributes) > 0) {
            $record = (object)$cd1attributes;
            $record->id = 200;
            $DB->update_record(course::TABLE, $record);
        }

        if (count($enrolment1attributes) > 0) {
            $record = (object)$enrolment1attributes;
            $record->id = 200;
            $DB->update_record(student::TABLE, $record);
        }

        if (count($user2attributes) > 0) {
            $record = (object)$user2attributes;
            $record->id = 201;
            $DB->update_record(user::TABLE, $record);
        }

        if (count($cd2attributes) > 0) {
            $record = (object)$cd2attributes;
            $record->id = 201;
            $DB->update_record(course::TABLE, $record);
        }

        if (count($enrolment2attributes) > 0) {
            $record = (object)$enrolment2attributes;
            $record->id = 201;
            $DB->update_record(student::TABLE, $record);
        }

        $data = $this->get_export_data();

        // validation
        $this->assertEquals(3, count($data));
        $this->assertEquals($checkvalue, $data[1][$checkfield]);
    }

    /**
     * Data provider for testing completion times
     *
     * @return array A variety of completion times
     */
    public function completion_time_provider() {
        return array(
                array(0, 1),
                array(1000000000, 1),
                array(time() - 25 * HOURSECS, 1),
                array(time() - 23 * HOURSECS, 2),
                array(time(), 2)
        );
    }

    /**
     * Validate that export does not respect completion time
     *
     * @param int $completiontime The completion time to assign the student
     * @param int $numrows The total number of rows to expect, including the header
     * @dataProvider completion_time_provider
     */
    public function test_export_respects_completiontime($completiontime, $numrows) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/student.class.php'));

        // data setup
        $this->load_csv_data();
        $sql = "UPDATE {".student::TABLE."}
                SET completetime = ?";
        $params = array($completiontime);
        $DB->execute($sql, $params);

        // validation
        $data = $this->get_export_data();
        $this->assertEquals($numrows, count($data));
    }

    /**
     * Validate that the export resets state appropriately
     */
    public function test_export_resetsstate() {
        global $CFG;
        $file = get_plugin_directory('rlipexport', 'version1elis').'/version1elis.class.php';
        require_once($file);

        // data setup
        $this->load_csv_data();

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        // plugin for file IO
        $fileplugin = new rlip_fileplugin_export();
        $fileplugin->open(RLIP_FILE_WRITE);

        // our specific export
        $exportplugin = new rlip_exportplugin_version1elis($fileplugin, true);
        $exportplugin->init(0, 0);

        // validate setup
        $this->assertTrue($exportplugin->recordset->valid());

        $exportplugin->close();

        // validate result
        $this->assertNull($exportplugin->recordset);

        $fileplugin->close();
    }

    public function entity_provider() {
        return array(array('user', 'crlm_user'));
    }

    /**
     * Validate that the version 1 export includes custom field headers in the output
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_correct_customfield_header_info($entityname, $entitytable) {
        global $CFG, $DB;

        $file = get_plugin_directory('rlipexport', 'version1elis').'/lib.php';
        require_once($file);

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        // set up necessary custom field information in the database
        // create categpry
        $categoryid = $this->create_custom_field_category();
        // create custom field
        $field = $this->create_test_field($entityname, 'rliptext', 'char', 'text', $categoryid);
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        $this->assertEquals(count($data), 1);

        $expectedheader = array(
                get_string('header_firstname', 'rlipexport_version1elis'),
                get_string('header_lastname', 'rlipexport_version1elis'),
                get_string('header_username', 'rlipexport_version1elis'),
                get_string('header_useridnumber', 'rlipexport_version1elis'),
                get_string('header_courseidnumber', 'rlipexport_version1elis'),
                get_string('header_startdate', 'rlipexport_version1elis'),
                get_string('header_enddate', 'rlipexport_version1elis'),
                get_string('header_status', 'rlipexport_version1elis'),
                get_string('header_grade', 'rlipexport_version1elis'),
                get_string('header_letter', 'rlipexport_version1elis'),
                'Header'
        );

        // make sure the data matches the expected header
        $this->assertEquals($expectedheader, $data[0]);
    }

    /**
     * Validate that the version 1 export includes custom field checkbox data in the output.
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_checkbox_data($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');;

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();

        // create custom field
        $field = $this->create_test_field($entityname, 'rlipcheckbox', 'int', 'checkbox', $categoryid);
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);

        // create data record for custom field
        $entity = $DB->get_records($entitytable);
        $entity = current($entity);
        $result = $this->update_data_record($entityname, $entity, $field, 0);

        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals(0, $row[10]);

        // test with other value
        $result = $this->update_data_record($entityname, $entity, $field, 1);

        $data = $this->get_export_data();

        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals(1, $row[10]);
    }

    /**
     * Validate that the version 1 export uses custom field default values for checkbox fields.
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_checkbox_default($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');
        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();
        // create custom field
        $field = $this->create_test_field($entityname, 'rlipcheckbox', 'int', 'checkbox', $categoryid, null, '0');
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);

        // get data record for custom field
        $entity = $DB->get_records($entitytable);
        $entity = current($entity);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals(0, $row[10]);

        // test with other value
        $result = $this->update_data_record($entityname, $entity, $field, 1);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals(1, $row[10]);
    }

    /**
     * Validate that the version 1 export includes custom field datetime data in the output.
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_datetime_data($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();
        // create custom field
        $field = $this->create_test_field($entityname, 'rlipdate', 'int', 'datetime', $categoryid);
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);
        // create data record for custom field
        $entity = $DB->get_records($entitytable);
        $entity = current($entity);
        $result = $this->update_data_record($entityname, $entity, $field, mktime(0, 0, 0, 1, 1, 2012));
        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals('Jan/01/2012', $row[10]);

        // set inctime to true
        $field_owner = new field_owner($field->owners['manual']->id);
        $field_owner->load();
        $field_owner->param_inctime = 1;
        $field_owner->save();

        // test with other value
        $value = mktime(10, 10, 0, 1, 1, 2012);
        $result = $this->update_data_record($entityname, $entity, $field, $value);

        $data = $this->get_export_data();

        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals('Jan/01/2012:10:10', $row[10]);
    }

    /**
     * Validate that the version 1 export adds a special marker for unset datetime custom fields.
     *
     * @dataProvider entity_provider
     */
    public function test_exporthandles_customfield_datetime_unset($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();
        // create custom field
        $field = $this->create_test_field($entityname, 'rlipdate', 'int', 'datetime', $categoryid);
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);
        // create data record for custom field
        $entity = $DB->get_records($entitytable);
        $entity = current($entity);
        $result = $this->update_data_record($entityname, $entity, $field, 0);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $marker = get_string('nodatemarker', 'rlipexport_version1');
        $this->assertEquals($marker, $row[10]);
    }

    /**
     * datetime currently doesn't support default values
     */

    /**
     * Validate that the version 1 export includes custom field menu data in the output
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_menu_data($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();
        // create custom field
        $field = $this->create_test_field($entityname, 'rlipmenu', 'char', 'menu', $categoryid, 'rlipoption1');
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);
        // create data record for custom field
        $entity = $DB->get_records($entitytable);
        $entity = current($entity);
        $result = $this->update_data_record($entityname, $entity, $field, 'rlipoption1');

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals('rlipoption1', $row[10]);
    }

    /**
     * Validate that the version 1 export uses custom field default values for menu fields.
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_menu_default($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();
        // create custom field
        $options = "rlipoption1
                    rlipoption2";
        $field = $this->create_test_field($entityname, 'rlipmenu', 'char', 'menu', $categoryid, $options, 'rlipoption2');
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals('rlipoption2', $row[10]);
    }

    /**
     * Validate that the version 1 export includes custom field textarea data in the output
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_textarea_data($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();

        // create custom field
        $field = $this->create_test_field($entityname, 'rliptextarea', 'char', 'textarea', $categoryid);
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);

        // create data record for custom field
        $entity = $DB->get_records($entitytable);
        $entity = current($entity);
        $result = $this->update_data_record($entityname, $entity, $field, 'rliptextarea');

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals('rliptextarea', $row[10]);
    }

    /**
     * Validate that the version 1 export uses custom field default values for textarea fields
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_textarea_default($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();
        // create custom field
        $field = $this->create_test_field($entityname, 'rliptextarea', 'char', 'textarea', $categoryid, null, 'rliptextareadefault');
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals('rliptextareadefault', $row[10]);
    }

    /**
     * Validate that the version 1 export includes custom field textinput data in the output.
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_textinput_data($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();

        // create custom field
        $field = $this->create_test_field($entityname, 'rliptext', 'char', 'text', $categoryid);
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);

        // create data record for custom field and attach to entity
        $entity = $DB->get_records($entitytable);
        $entity = current($entity);
        $result = $this->update_data_record($entityname, $entity, $field, 'rliptext');

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals('rliptext', $row[10]);
    }

    /**
     * Validate that the version 1 export uses custom field default values for
     * textinput fields
     *
     * @dataProvider entity_provider
     */
    public function test_exportincludes_customfield_textinput_default($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        // create category
        $categoryid = $this->create_custom_field_category();
        // create custom field
        $field = $this->create_test_field($entityname, 'rliptext', 'char', 'text', $categoryid, null, 'rliptextdefault');
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals(2, count($data));
        $row = $data[1];
        $this->assertEquals(11, count($row));
        $this->assertEquals('rliptextdefault', $row[10]);
    }

    /**
     * Validate that the version 1 export does not include information about delete custom fields
     *
     * @dataProvider entity_provider
     */
    public function test_exportignores_deleted_customfields($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database
        $this->create_field_mapping('testcustomfields', 'field_1');

        // set up the expected output
        $expected_header = array(
                get_string('header_firstname', 'rlipexport_version1elis'),
                get_string('header_lastname', 'rlipexport_version1elis'),
                get_string('header_username', 'rlipexport_version1elis'),
                get_string('header_useridnumber', 'rlipexport_version1elis'),
                get_string('header_courseidnumber', 'rlipexport_version1elis'),
                get_string('header_startdate', 'rlipexport_version1elis'),
                get_string('header_enddate', 'rlipexport_version1elis'),
                get_string('header_status', 'rlipexport_version1elis'),
                get_string('header_grade', 'rlipexport_version1elis'),
                get_string('header_letter', 'rlipexport_version1elis'),
        );
        $expected_body = array(
                'exportfirstname',
                'exportlastname',
                'exportusername',
                'exportidnumber',
                'testcourseidnumber',
                date('M/d/Y', 1000000000),
                date('M/d/Y', 1500000000),
                'COMPLETED',
                '70.00000',
                'C-'
        );
        $expected_data = array($expected_header, $expected_body);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals($expected_data, $data);
    }

    /**
     * Validate that the version 1 export shows custom field columns in the configured order, after non-configurable fields
     *
     * @dataProvider entity_provider
     */
    public function test_exportrespects_customfieldorder($entityname, $entitytable) {
        global $CFG, $DB;

        // set the export to be incremental
        set_config('nonincremental', 0, 'rlipexport_version1elis');

        // this loads a single user, so get_records only ever returns a single object
        $this->init_contexts_and_site_course();
        $this->load_csv_data();

        // set up necessary custom field information in the database

        // create category
        $categoryid = $this->create_custom_field_category();

        // create custom field
        $field = $this->create_test_field($entityname, 'rliptext2', 'char', 'text', $categoryid);
        // create data record for custom field and attach to entity
        $entity = $DB->get_records($entitytable);
        $entity = current($entity);
        $result = $this->update_data_record($entityname, $entity, $field, 'rliptext2');
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id, 'Header2', 1);

        // create second custom field
        $field = $this->create_test_field($entityname, 'rliptext', 'char', 'text', $categoryid);
        // create data record for custom field and attach to entity
        $entity->idnumber = 'testuseridnumber2';
        $entity->username = 'testuserusername2';
        $result = $this->update_data_record($entityname, $entity, $field, 'rliptext');
        $this->create_field_mapping('testcustomfields', 'field_'.$field->id, 'Header', 0);

        // set up the expected output
        $expected_header = array(
                get_string('header_firstname', 'rlipexport_version1elis'),
                get_string('header_lastname', 'rlipexport_version1elis'),
                get_string('header_username', 'rlipexport_version1elis'),
                get_string('header_useridnumber', 'rlipexport_version1elis'),
                get_string('header_courseidnumber', 'rlipexport_version1elis'),
                get_string('header_startdate', 'rlipexport_version1elis'),
                get_string('header_enddate', 'rlipexport_version1elis'),
                get_string('header_status', 'rlipexport_version1elis'),
                get_string('header_grade', 'rlipexport_version1elis'),
                get_string('header_letter', 'rlipexport_version1elis'),
                'Header',
                'Header2'
        );
        $expected_body = array(
                'exportfirstname',
                'exportlastname',
                'exportusername',
                'exportidnumber',
                'testcourseidnumber',
                date('M/d/Y', 1000000000),
                date('M/d/Y', 1500000000),
                'COMPLETED',
                '70.00000',
                'C-',
                'rliptext',
                'rliptext2'
        );
        $expected_data = array($expected_header, $expected_body);

        // obtain our export data based on the current DB state
        $data = $this->get_export_data();

        // data validation
        $this->assertEquals($expected_data, $data);
    }
}