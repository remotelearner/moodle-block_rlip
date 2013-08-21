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
 * @package    rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once(dirname(__FILE__).'/../../../../../elis/core/test_config.php');
global $CFG;
require_once($CFG->dirroot.'/blocks/rlip/tests/other/rlip_test.class.php');

// Libs.
$plugindir = get_plugin_directory('rlipimport', 'version1elis');
require_once($plugindir.'/version1elis.class.php');
require_once($plugindir.'/lib.php');
require_once($CFG->dirroot.'/user/profile/definelib.php');

/**
 * Class for validating import configuration.
 * @group block_rlip
 * @group rlipimport_version1elis
 */
class version1elisimportconfig_testcase extends rlip_elis_test {

    /**
     * Data provider for tab validation
     *
     * @return array Data, containing tab position and entity type
     */
    public function gettabsprovider() {
        return array(
                array(0, 'user'),
                array(1, 'course'),
                array(2, 'enrolment')
        );
    }

    /**
     * Create a custom field category
     *
     * @return int The database id of the new category
     */
    private function create_custom_field_category() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');

        $category = new field_category(array('name' => 'testcategoryname'));
        $category->save();

        return $category->id;
    }

    /**
     * Helper function for creating a Moodle user profile field
     *
     * @param string $name Profile field shortname
     * @param string $datatype Profile field data type
     * @param int $categoryid Profile field category id
     * @return int The id of the created profile field
     */
    private function create_profile_field($name, $datatype, $categoryid, $contextlevelname = 'user') {
        global $CFG;
        require_once($CFG->dirroot.'/elis/core/lib/data/customfield.class.php');
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        $field = new field(array(
            'categoryid' => $categoryid,
            'shortname' => $name,
            'name' => $name
        ));
        $field->save();

        // Field contextlevel.
        $contextlevel = context_elis_helper::get_level_from_name($contextlevelname);
        $fieldcontextlevel = new field_contextlevel(array(
            'fieldid' => $field->id,
            'contextlevel' => $contextlevel
        ));

        $fieldcontextlevel->save();

        return $field->id;
    }

    /**
     * Validate that the method to get tabs works correctly
     *
     * @param $index Position in the tabs array to use
     * @param $entitytype The type of entity that tab represents
     *
     * @dataProvider gettabsprovider
     */
    public function test_gettabsreturnsvalidobjects($index, $entitytype) {
        global $CFG;

        // Get collection of tabs.
        $baseurl = 'base';
        $tabs = rlipimport_version1elis_get_tabs($baseurl);
        $this->assertEquals(count($tabs), 3);

        // The string displayed on the tab.
        $displaystring = get_string($entitytype.'tab', 'rlipimport_version1elis');

        // Data validation.
        $tab = $tabs[$index];
        $this->assertEquals($tab->link->out(), $baseurl.'?tab='.$entitytype);
        $this->assertEquals($tab->text, $displaystring);
        $this->assertEquals($tab->title, $displaystring);
    }

    /**
     * Data provider for validating which import fields are valid for all
     * three entity types
     */
    public function availablefieldsprovider() {
        // Actual data.
        $userfields = array('entity', 'action', 'username', 'auth',
                            'password', 'firstname', 'lastname', 'email',
                            'maildigest', 'autosubscribe', 'trackforums',
                            'screenreader', 'city', 'country', 'timezone',
                            'theme', 'lang', 'description', 'idnumber',
                            'institution', 'department');
        $coursefields = array('entity', 'action', 'shortname', 'fullname',
                              'idnumber', 'summary', 'format', 'numsections',
                              'startdate', 'newsitems', 'showgrades', 'showreports',
                              'maxbytes', 'guest', 'password', 'visible',
                              'lang', 'category', 'link');
        $enrolmentfields = array('entity', 'action', 'username', 'email', 'idnumber', 'context', 'instance', 'role');

        // Necessary data structure.
        return array(
                array('user', $userfields),
                array('course', $coursefields),
                array('enrolment', $enrolmentfields)
        );
    }

    /**
     * Validate that available import fields are reported correctly
     *
     * @param $entitytype
     * @param $fields
     *
     * @dataProvider availablefieldsprovider
     */
    public function test_getavailablefieldsreturnsvaliddata($entitytype, $fields) {
        // Obtain available fields.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $fields = $plugin->get_available_fields($entitytype);

        // Validation.
        $this->assertEquals($fields, $fields);
    }

    /**
     * Validate that false is returned when obtaining available fields for an
     * invalid entity type
     */
    public function testgetavailablefieldsreturnsfalseforinvalidentitytype() {
        // Obtain data.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $fields = $plugin->get_available_fields('bogus');

        // Data validation.
        $this->assertEquals($fields, false);
    }

    /**
     * Data provider for test mapping data
     *
     * @return array Info specifying entity types and a valid field to test
     */
    public function getmappingprovider() {
        return array(
                array('user', 'username'),
                array('course', 'idnumber'),
                array('enrolment', 'user_username')
        );
    }

    /**
     * Validate that mapping retrieval works as needed
     *
     * @param $entitytype The type of entity
     * @param $field A field that is valid for the supplied entity type
     *
     * @dataProvider getmappingprovider
     */
    public function test_getmappingreturnsvaliddata($entitytype, $field) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        // Obtain the entire list of fields.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $availablefields = $plugin->get_available_fields($entitytype);
        // Obtain mapping in default state.
        $fields = rlipimport_version1elis_get_mapping($entitytype);
        // Validate that the two match.
        $this->assertEquals(array_keys($fields), $availablefields);
        $this->assertEquals(array_values($fields), $availablefields);

        // Create a mapping record.
        $mapping = new stdClass;
        $mapping->entitytype = $entitytype;
        $mapping->standardfieldname = $field;
        $mapping->customfieldname = 'custom'.$field;
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $mapping);

        // Data validation.
        $fields = rlipimport_version1elis_get_mapping($entitytype);
        $this->assertEquals(array_keys($fields), $availablefields);
        $this->assertEquals($fields[$field], 'custom'.$field);
    }

    /**
     * Validate that profile fields are supported in the mapping system
     */
    public function testgetmappingincludesprofilefield() {
        // Create a test profile field.
        $categoryid = $this->create_custom_field_category();
        $this->create_profile_field('text', 'text', $categoryid);

        // Obtain the entire list of fields.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $availablefields = $plugin->get_available_fields('user');
        // Obtain mapping in default state.
        $fields = rlipimport_version1elis_get_mapping('user');

        // Validation.
        $shortname = 'text';
        $exists = isset($fields[$shortname]) && $fields[$shortname] == $shortname;
        $this->assertTrue($exists);
    }

    /**
     * Validate that false is returned when obtaining field mapping for an
     * invalid entity type
     */
    public function testgetmappingreturnsfalseforinvalidentitytype() {
        // Obtain data.
        $fields = rlipimport_version1elis_get_mapping('bogus');

        // Data validation.
        $this->assertEquals($fields, false);
    }

    /**
     * Validate that all fields are persisted when saving mapping info
     *
     * @param $entitytype The type of entity
     * @param $field A field that is valid for the supplied entity type
     *
     * @dataProvider getmappingprovider
     */
    public function test_savemappingpersistsalldata($entitytype, $field) {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        // Obtain available fields.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $options = $plugin->get_available_fields($entitytype);

        // Set up mapping data with all fields as defaults.
        $data = array();
        foreach ($options as $option) {
            $data[$option] = $option;
        }

        // Create a nonstandard mapping value.
        $data[$field] = 'custom'.$field;

        // Persist.
        rlipimport_version1elis_save_mapping($entitytype, $options, $data);

        // Construct expected data.
        $i = 1;
        $expecteddata = array();
        foreach ($data as $key => $value) {
            $record = new stdClass;
            $record->id = $i;
            $record->entitytype = $entitytype;
            $record->standardfieldname = $key;
            $record->customfieldname = $value;
            $expecteddata[$i] = $record;
            $i++;
        }

        // Data validation.
        $params = array('entitytype' => $entitytype);
        $this->assertEquals($DB->get_records(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $params, 'id'), $expecteddata);
    }

    /**
     * Validate that saving field mappings updates existing records
     */
    public function testsavemappingupdatesexistingrecords() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        // Obtain the available fields.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $options = $plugin->get_available_fields('user');

        // Persist the default state.
        rlipimport_version1elis_save_mapping('user', $options, array());

        // Update all fields.
        $data = array();
        foreach ($options as $option) {
            $data[$option] = $option.'updated';
        }

        // Save the updated values.
        rlipimport_version1elis_save_mapping('user', $options, $data);

        // Data validation.
        $select = $DB->sql_like('customfieldname', ':suffix');
        $params = array('suffix' => '%updated');
        $count = $DB->count_records_select(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $select, $params);
        $this->assertEquals($count, count($data));
    }

    /**
     * Validate that clearing values during configuration save only happens for
     * the specified entity type
     */
    public function testsavemappingdoesnotdeletemappingsforotherentities() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        // Create a user mapping record.
        $mapping = new stdClass;
        $mapping->entitytype = 'user';
        $mapping->standardfieldname = 'test';
        $mapping->customfieldname = 'customtest';
        $DB->insert_record(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $mapping);

        // Obtain the available fields for course mappings.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $options = $plugin->get_available_fields('user');

        // Store course mapping.
        rlipimport_version1elis_save_mapping('course', $options, array());

        // Data validation.
        $exists = $DB->record_exists(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, array('entitytype' => 'user'));
        $this->assertTrue($exists);
    }

    /**
     * Validate that only valid field mappings can be saved
     */
    public function testsavemappingdoesnotsaveinvalidfields() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        // Obtain available fields.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $options = $plugin->get_available_fields('user');

        // Persist, with additonal bogus field.
        rlipimport_version1elis_save_mapping('user', $options, array('bogus' => 'bogus'));

        // Data validation.
        $count = $DB->count_records(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE);
        $this->assertEquals($count, count($options));
    }

    /**
     * Validate restoring default field mappings
     */
    public function testrestoredefaultmappingupdatesrecords() {
        global $CFG, $DB;
        $file = get_plugin_directory('rlipimport', 'version1elis').'/lib.php';
        require_once($file);

        // Obtain available fields.
        $plugin = new rlip_importplugin_version1elis(null, false);
        $options = $plugin->get_available_fields('user');

        // Persist default field.
        rlipimport_version1elis_save_mapping('user', $options, array());

        // Setup validation.
        $select = 'standardfieldname = customfieldname';
        $count = $DB->count_records_select(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $select);
        $this->assertEquals($count, count($options));

        // Update all mapping values.
        $data = array();
        foreach ($options as $option) {
            $data[$option] = $option.'updated';
        }

        // Persist updated values and validate.
        rlipimport_version1elis_save_mapping('user', $options, $data);
        $select = 'standardfieldname != customfieldname';
        $count = $DB->count_records_select(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $select);
        $this->assertEquals($count, count($options));

        // Reset and validate state.
        rlipimport_version1elis_reset_mappings('user');
        $select = 'standardfieldname = customfieldname';
        $count = $DB->count_records_select(RLIPIMPORT_VERSION1ELIS_MAPPING_TABLE, $select);
        $this->assertEquals($count, count($options));
    }
}