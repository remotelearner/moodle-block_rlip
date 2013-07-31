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
 * @package    block_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

$dirname = dirname(__FILE__);
require_once($dirname.'/../../../elis/core/test_config.php');
global $CFG;
require_once($dirname.'/other/rlip_test.class.php');

// Libs.
require_once($dirname.'/../lib.php');
require_once($CFG->libdir.'/externallib.php');
if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
    require_once($CFG->dirroot.'/elis/program/lib/setup.php');
    require_once(elispm::lib('data/clusterassignment.class.php'));
    require_once(elispm::lib('data/clustercurriculum.class.php'));
    require_once(elispm::lib('data/clustertrack.class.php'));
    require_once(elispm::lib('data/userset.class.php'));
    require_once(elispm::lib('data/user.class.php'));
    require_once(elispm::lib('data/usermoodle.class.php'));
    require_once($CFG->dirroot.'/elis/program/enrol/userset/moodle_profile/userset_profile.class.php');
    require_once($dirname.'/../ws/elis/userset_delete.class.php');
}

/**
 * Tests webservice method block_rldh_elis_userset_delete.
 * @group block_rlip
 * @group block_rlip_ws
 */
class block_rlip_ws_elis_userset_delete_testcase extends rlip_test_ws {

    /**
     * Test successful userset delete
     */
    public function test_success() {
        global $DB;

        // Create custom field.
        $fieldcat = new field_category;
        $fieldcat->name = 'Test';
        $fieldcat->save();

        $field = new field;
        $field->categoryid = $fieldcat->id;
        $field->shortname = 'testfield';
        $field->name = 'Test Field';
        $field->datatype = 'text';
        $field->save();

        $fieldctx = new field_contextlevel;
        $fieldctx->fieldid = $field->id;
        $fieldctx->contextlevel = CONTEXT_ELIS_USERSET;
        $fieldctx->save();

        $this->give_permissions(array('elis/program:userset_delete'));

        $userset = array(
            'name' => 'testuserset',
            'recursive' => true
        );

        // Setup userset to delete.
        $us = new userset(array('name' => 'testuserset'));
        $us->save();

        $response = block_rldh_elis_userset_delete::userset_delete($userset);

        $this->assertNotEmpty($response);
        $this->assertInternalType('array', $response);
        $this->assertArrayHasKey('messagecode', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertEquals(get_string('ws_userset_delete_success_code', 'block_rlip',
                    get_string('ws_userset_delete_recursive', 'block_rlip')), $response['messagecode']);
        $this->assertEquals(get_string('ws_userset_delete_success_msg', 'block_rlip',
                    get_string('ws_userset_delete_subsets', 'block_rlip')), $response['message']);
    }

    /**
     * Dataprovider for test_failure()
     * @return array An array of parameters
     */
    public function dataprovider_failure() {
        return array(
                // Test empty input.
                array(
                        array()
                ),
                // Test invalid parent input.
                array(
                        array(
                            'name' => 'testuserset',
                            'recursive' => 'A',
                        )
                )
        );
    }

    /**
     * Test failure conditions.
     * @dataProvider dataprovider_failure
     * @expectedException moodle_exception
     * @param array $us The incoming userset data.
     */
    public function test_failure(array $us) {
        global $DB;

        $this->give_permissions(array('elis/program:userset_delete'));

        // Setup userset to delete.
        $userset = new userset(array('name' => 'testuserset'));
        $userset->save();

        $response = block_rldh_elis_userset_delete::userset_delete($us);
    }
}
