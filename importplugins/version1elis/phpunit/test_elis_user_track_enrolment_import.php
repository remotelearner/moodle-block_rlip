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
 * @package    rlip
 * @subpackage importplugins/version1elis/phpunit
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    define('CLI_SCRIPT', true);
}

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))).'/config.php');
global $CFG;
require_once($CFG->dirroot.'/elis/core/lib/testlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

/**
 * Class for validating that enrolment of users into tracks works
 */
class elis_user_track_enrolment_test extends elis_database_test {
    /**
     * Return the list of tables that should be overlayed.
     */
    static protected function get_overlay_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        return array(curriculum::TABLE => 'elis_program',
                     track::TABLE => 'elis_program',
                     user::TABLE => 'elis_program',
                     usertrack::TABLE => 'elis_program');
    }

    /**
     * Return the list of tables that should be ignored for writes.
     */
    static protected function get_ignored_tables() {
        global $CFG;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/usermoodle.class.php'));

        return array('context' => 'moodle',
                     'user' => 'moodle',
                     usermoodle::TABLE => 'elis_program');
    }

    /**
     * Data provider for fields that identify user records
     *
     * @return array Parameter data, as needed by the test methods
     */
    function user_identifier_provider() {
        return array(array('testuserusername', NULL, NULL),
                     array(NULL, 'testuser@email.com', NULL),
                     array(NULL, NULL, 'testuseridnumber'));
    }

    /**
     * Validate that users can be enrolled into tracks
     *
     * @param string $username A sample user's username, or NULL if not used in the import
     * @param string $email A sample user's email, or NULL if not used in the import
     * @param string $idnumber A sample user's idnumber, or NULL if not used in the import
     * @dataProvider user_identifier_provider
     */
    function test_elis_user_track_enrolment_import($username, $email, $idnumber) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/elis/program/lib/setup.php');
        require_once(elispm::lib('data/curriculum.class.php'));
        require_once(elispm::lib('data/track.class.php'));
        require_once(elispm::lib('data/user.class.php'));
        require_once(elispm::lib('data/usertrack.class.php'));

        $user = new user(array('idnumber' => 'testuseridnumber',
                               'username' => 'testuserusername',
                               'firstname' => 'testuserfirstname',
                               'lastname' => 'testuserlastname',
                               'email' => 'testuser@email.com',
                               'country' => 'CA'));
        $user->save();

        $program = new curriculum(array('idnumber' => 'testprogramidnumber'));
        $program->save();

        $track = new track(array('curid' => $program->id,
                                 'idnumber' => 'testtrackidnumber'));
        $track->save();

        //run the course description update action
        $record = new stdClass;
        $record->context = 'track_testtrackidnumber';
        if ($username != NULL) {
            $record->user_username = $user->username;
        }
        if ($email != NULL) {
            $record->user_email = $user->email;
        }
        if ($idnumber != NULL) {
            $record->user_idnumber = $user->idnumber;
        }

        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');
        $importplugin->track_enrolment_create($record, 'bogus', 'testtrackidnumber');

        //validation
        $this->assertTrue($DB->record_exists(usertrack::TABLE, array('userid' => $user->id,
                                                                     'trackid' => $track->id)));
    }
}