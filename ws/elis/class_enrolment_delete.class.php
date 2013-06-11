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
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/elis/program/lib/setup.php');
require_once(elispm::lib('data/student.class.php'));
require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');

/**
 * Class enrolment delete webservices method.
 */
class block_rldh_elis_class_enrolment_delete extends external_api {

    /**
     * Gets a description of the class_enrolment object for use in the parameter.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_class_enrolment_object_description() {
        return array(
            'class_idnumber' => new external_value(PARAM_TEXT, 'Class idnumber', VALUE_REQUIRED),
            'user_username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
            'user_idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
            'user_email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function class_enrolment_delete_parameters() {
        $params = array('data' => new external_single_structure(static::get_class_enrolment_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs class_enrolment deletion
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error deleting the association.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function class_enrolment_delete(array $data) {
        global $DB, $USER;

        // Parameter validation.
        $params = self::validate_parameters(self::class_enrolment_delete_parameters(), array('data' => $data));

        // Context validation.
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        $data = (object)$data;

        // Parse class
        if (empty($data->class_idnumber) || !($classid = $DB->get_field('crlm_class', 'id', array('idnumber' => $data->class_idnumber)))) {
            throw new data_object_exception('ws_class_enrolment_delete_fail_invalid_class', 'block_rlip', '', $data);
        }

        // Capability checking.
        require_capability('elis/program:class_enrol', context_elis_class::instance($classid));

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        $userparams = array();
        $userid = $importplugin->get_userid_from_record($data, '', $userparams);
        if ($userid == false) {
            $a = new stdClass;
            if (empty($userparams)) {
                $a->userparams = '{empty}';
            } else {
                $a->userparams = '';
                foreach ($userparams as $userfield => $uservalue) {
                    $subfield = strpos($userfield, '_');
                    $userfield = substr($userfield, ($subfield === false) ? 0 : $subfield + 1);
                    if (!empty($a->userparams)) {
                        $a->userparams .= ', ';
                    }
                    $a->userparams .= "{$userfield}: '{$uservalue}'";
                }
            }
            throw new data_object_exception('ws_class_enrolment_delete_fail_invalid_user', 'block_rlip', '', $a);
        }

        $id = $DB->get_field(student::TABLE, 'id', array('classid' => $classid, 'userid' => $userid));

        // Respond.
        if (!empty($id) && ($stu = new student($id))) {
            $stu->delete();
            return array(
                'messagecode' => get_string('ws_class_enrolment_delete_success_code', 'block_rlip'),
                'message' => get_string('ws_class_enrolment_delete_success_msg', 'block_rlip'),
            );
        } else {
            throw new data_object_exception('ws_class_enrolment_delete_fail', 'block_rlip');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function class_enrolment_delete_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                )
        );
    }
}