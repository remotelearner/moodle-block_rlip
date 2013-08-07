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

require_once(dirname(__FILE__).'/../../lib.php');

/**
 * Update class webservices method.
 */
class block_rldh_elis_class_update extends external_api {

    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
            require_once(elispm::lib('data/pmclass.class.php'));
            require_once(elispm::lib('data/user.class.php'));
            require_once(dirname(__FILE__).'/../../importplugins/version1elis/version1elis.class.php');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get custom fields for classes.
     * @return array An Array of class custom fields.
     */
    public static function get_class_custom_fields() {
        global $DB;

        if (static::require_elis_dependencies() === true) {
            $sql = 'SELECT shortname, name, datatype, multivalued
                      FROM {'.field::TABLE.'} f
                      JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?';
            $sqlparams = array(CONTEXT_ELIS_CLASS);
            return $DB->get_records_sql($sql, $sqlparams);
        } else {
            return array();
        }
    }

    /**
     * Get custom fields for classes for use in parameter description functions.
     * @return array An array of custom field parameter definitions.
     */
    public static function get_class_custom_fields_parameters() {
        $params = array();

        // Get custom fields.
        $fields = static::get_class_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            // Convert datatype to param type.
            if ($field->multivalued) {
                $paramtype = PARAM_TEXT;
            } else {
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
            }

            // Assemble the parameter entry and add to array.
            $params[$fullfieldname] = new external_value($paramtype, $field->name, VALUE_OPTIONAL);
        }
        return $params;
    }

    /**
     * Gets a description of the class object for use in the parameter function.
     * @return array An array of external_value objects describing a class record in webservice terms.
     */
    public static function get_class_object_description() {
        global $DB;
        $params = array(
            'idnumber' => new external_value(PARAM_TEXT, 'Class idnumber', VALUE_REQUIRED),
            'startdate' => new external_value(PARAM_TEXT, 'Start date of the class as MMM/DD/YYYY ex. Jan/01/2013', VALUE_OPTIONAL),
            'enddate' => new external_value(PARAM_TEXT, 'End date of the class as MMM/DD/YYYY ex. Jan/01/2013', VALUE_OPTIONAL),
            'starttimehour' => new external_value(PARAM_INT, 'Class start time hour. 0-23', VALUE_OPTIONAL),
            'starttimeminute' => new external_value(PARAM_INT, 'Class start time minute. 0-55 in multiples of 5', VALUE_OPTIONAL),
            'endtimehour' => new external_value(PARAM_INT, 'Class end time hour. 0-23', VALUE_OPTIONAL),
            'endtimeminute' => new external_value(PARAM_INT, 'Class end time minute. 0-55 in multiples of 5', VALUE_OPTIONAL),
            'maxstudents' => new external_value(PARAM_INT, 'Max number of students. 0=Unlimited.', VALUE_OPTIONAL),
            'enrol_from_waitlist' => new external_value(PARAM_BOOL, 'Enrol users from the waitlist.', VALUE_OPTIONAL),
            'assignment' => new external_value(PARAM_TEXT, 'idnumber of the parent ELIS course description', VALUE_OPTIONAL),
            'track' => new external_value(PARAM_TEXT, 'The idnumber of the track the class is on. For this to work the course
                    description the class is an instance of has to be part of the program the track is an instance of.',
                    VALUE_OPTIONAL),
            'autoenrol' => new external_value(PARAM_BOOL, 'Sets the class to auto-enrol if it is part of a track', VALUE_OPTIONAL),
            'link' => new external_value(PARAM_TEXT, 'Enter shortname of Moodle course to link to, or "auto" to auto-create a new
                    Moodle course from template.', VALUE_OPTIONAL),
        );

        return array_merge($params, static::get_class_custom_fields_parameters());
    }

    /**
     * Gets a description of the class object for use in the return function.
     * @return array An array of external_value objects describing a class record in webservice terms.
     */
    public static function get_class_object_return_description() {
        global $DB;
        $params = array(
            'courseid' => new external_value(PARAM_INT, 'Assigned course ID', VALUE_REQUIRED),
            'idnumber' => new external_value(PARAM_TEXT, 'Class idnumber', VALUE_REQUIRED),
            'startdate' => new external_value(PARAM_INT, 'Start date of the class as unix timestamp', VALUE_OPTIONAL),
            'enddate' => new external_value(PARAM_INT, 'End date of the class as unix timestamp', VALUE_OPTIONAL),
            'duration' => new external_value(PARAM_INT, 'Class duration', VALUE_OPTIONAL),
            'starttimehour' => new external_value(PARAM_INT, 'Class start time hour. 0-23', VALUE_OPTIONAL),
            'starttimeminute' => new external_value(PARAM_INT, 'Class start time minute. 0-55 in multiples of 5', VALUE_OPTIONAL),
            'endtimehour' => new external_value(PARAM_INT, 'Class end time hour. 0-23', VALUE_OPTIONAL),
            'endtimeminute' => new external_value(PARAM_INT, 'Class end time minute. 0-55 in multiples of 5', VALUE_OPTIONAL),
            'maxstudents' => new external_value(PARAM_INT, 'Max number of students. 0=Unlimited.', VALUE_OPTIONAL),
            'environmentid' => new external_value(PARAM_INT, 'Class environment id.', VALUE_OPTIONAL),
            'enrol_from_waitlist' => new external_value(PARAM_BOOL, 'Enrol users from the waitlist.', VALUE_OPTIONAL),
        );

        return array_merge($params, static::get_class_custom_fields_parameters());
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters The parameters object for this webservice method.
     */
    public static function class_update_parameters() {
        $params = array('data' => new external_single_structure(static::get_class_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs class updating
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error editing the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function class_update(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'block_rlip');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::class_update_parameters(), array('data' => $data));

        // Context validation.
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Initialize version1elis importplugin for utility functions.
        $importplugin = rlip_dataplugin_factory::factory('rlipimport_version1elis');

        $data = (object)$data;

        // Get the class to be updated.
        $clsid = $DB->get_field('crlm_class', 'id', array('idnumber' => $data->idnumber));
        if (empty($clsid)) {
            throw new moodle_exception('ws_class_update_fail_badidnumber', 'block_rlip');
        }

        // Capability checking.
        require_capability('elis/program:class_edit', context_elis_class::instance($clsid));

        // Cannot move classes between courses.
        if (isset($data->assignment)) {
            throw new moodle_exception('ws_class_update_fail_cannotreassign', 'block_rlip');
        }

        // Handle custom fields.
        $data = $importplugin->add_custom_field_prefixes($data);

        // Parse startdate and enddate.
        foreach (array('startdate', 'enddate') as $date) {
            if (isset($data->$date)) {
                $data->$date = $importplugin->parse_date($data->$date);
            }
        }

        $pmclass = new pmclass($clsid);
        $pmclass->set_from_data($data);
        $pmclass->save();

        // Associate this class instance to a track, if necessary.
        $importplugin->associate_class_to_track($data, $pmclass->id);
        // Associate this class instance to a Moodle course, if necessary.
        $importplugin->associate_class_to_moodle_course($data, $pmclass->id);

        $classrec = (array)$DB->get_record(pmclass::TABLE, array('id' => $pmclass->id));
        $classobj = $pmclass->to_array();

        // Convert multi-valued custom field arrays to comma-separated listing.
        $fields = static::get_class_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            if ($field->multivalued && isset($classobj[$fullfieldname]) && is_array($classobj[$fullfieldname])) {
                $classobj[$fullfieldname] = implode(',', $classobj[$fullfieldname]);
            }
        }

        return array(
            'messagecode' => get_string('ws_class_update_success_code', 'block_rlip'),
            'message' => get_string('ws_class_update_success_msg', 'block_rlip'),
            'record' => array_merge($classrec, $classobj),
        );
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function class_update_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_class_object_return_description())
                )
        );
    }
}