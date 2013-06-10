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
require_once(elispm::lib('data/user.class.php'));
require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');

/**
 * Update user identifiers webservices method.
 */
class block_rldh_elis_user_update_identifiers extends external_api {

    /**
     * Gets a description of the user object for use in the return function.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_user_object_description() {
        global $DB;

        $params = array(
            'username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
            'password' => new external_value(PARAM_TEXT, 'User password', VALUE_OPTIONAL),
            'idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
            'firstname' => new external_value(PARAM_TEXT, 'User first name', VALUE_OPTIONAL),
            'lastname' => new external_value(PARAM_TEXT, 'User last name', VALUE_OPTIONAL),
            'mi' => new external_value(PARAM_TEXT, 'User middle initial', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
            'email2' => new external_value(PARAM_TEXT, 'User secondary email', VALUE_OPTIONAL),
            'address' => new external_value(PARAM_TEXT, 'User primary address', VALUE_OPTIONAL),
            'address2' => new external_value(PARAM_TEXT, 'User secondary address', VALUE_OPTIONAL),
            'city' => new external_value(PARAM_TEXT, 'User city', VALUE_OPTIONAL),
            'state' => new external_value(PARAM_TEXT, 'User state/province', VALUE_OPTIONAL),
            'postalcode' => new external_value(PARAM_TEXT, 'User postal code', VALUE_OPTIONAL),
            'country' => new external_value(PARAM_TEXT, 'User country', VALUE_OPTIONAL),
            'phone' => new external_value(PARAM_TEXT, 'User primary phone number', VALUE_OPTIONAL),
            'phone2' => new external_value(PARAM_TEXT, 'User secondary phone number', VALUE_OPTIONAL),
            'fax' => new external_value(PARAM_TEXT, 'User fax number', VALUE_OPTIONAL),
            'birthdate' => new external_value(PARAM_TEXT, 'User birthdate', VALUE_OPTIONAL),
            'gender' => new external_value(PARAM_TEXT, 'User gender', VALUE_OPTIONAL),
            'language' => new external_value(PARAM_TEXT, 'User language', VALUE_OPTIONAL),
            'transfercredits' => new external_value(PARAM_FLOAT, 'Credits user has earned elsewhere', VALUE_OPTIONAL),
            'comments' => new external_value(PARAM_TEXT, 'Comments', VALUE_OPTIONAL),
            'notes' => new external_value(PARAM_TEXT, 'Notes', VALUE_OPTIONAL),
            'inactive' => new external_value(PARAM_BOOL, 'User inactive status', VALUE_OPTIONAL),
        );

        // Add custom fields.
        $sql = 'SELECT shortname, name, datatype
                  FROM {'.field::TABLE.'} f
                  JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?';
        $sqlparams = array(CONTEXT_ELIS_USER);
        $fields = $DB->get_records_sql($sql, $sqlparams);
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            // Convert datatype to param type.
            switch($field->datatype) {
                case 'bool':
                    $paramtype = PARAM_BOOL;
                    break;
                case 'int':
                    $paramtype = PARAM_INT;
                    break;
                default:
                    $paramtype = PARAM_TEXT;
            }

            // Assemble the parameter entry and add to array.
            $params[$fullfieldname] = new external_value($paramtype, $field->name, VALUE_OPTIONAL);
        }

        return $params;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function user_update_identifiers_parameters() {
        return new external_function_parameters(array(
            'data' => new external_single_structure(array(
                'user_username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
                'user_idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
                'user_email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
                'username' => new external_value(PARAM_TEXT, 'User username', VALUE_OPTIONAL),
                'idnumber' => new external_value(PARAM_TEXT, 'User idnumber', VALUE_OPTIONAL),
                'email' => new external_value(PARAM_TEXT, 'User primary email', VALUE_OPTIONAL),
            ))
        ));
    }

    /**
     * Performs updating of user identifiers.
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error editing the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function user_update_identifiers(array $data) {
        global $USER, $DB;

        // Parameter validation.
        $params = self::validate_parameters(self::user_update_identifiers_parameters(), array('data' => $data));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        $userparams = array();
        $data = (object)$data;
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
            throw new data_object_exception('ws_user_update_identifiers_fail_invalid_user', 'block_rlip', '', $a);
        }

        // Capability checking.
        require_capability('elis/program:user_edit', context_elis_user::instance($userid));

        $user = new user($userid);
        $user->load();
        if (isset($data->username)) {
            $user->username = $data->username;
        }
        if (isset($data->idnumber)) {
            $user->idnumber = $data->idnumber;
        }
        if (isset($data->email)) {
            $user->email = $data->email;
        }
        $user->save();

        // Respond.
        $userrec = (array)$DB->get_record(user::TABLE, array('id' => $user->id));
        $userobj = $user->to_array();
        return array(
            'messagecode' => get_string('ws_user_update_identifiers_success_code', 'block_rlip'),
            'message' => get_string('ws_user_update_identifiers_success_msg', 'block_rlip'),
            'record' => array_merge($userrec, $userobj),
        );
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function user_update_identifiers_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_user_object_description())
                )
        );
    }
}