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

/**
 * Update program webservices method.
 */
class block_rldh_elis_program_update extends external_api {

    /**
     * Require ELIS dependencies if ELIS is installed, otherwise return false.
     * @return bool Whether ELIS dependencies were successfully required.
     */
    public static function require_elis_dependencies() {
        global $CFG;
        if (file_exists($CFG->dirroot.'/elis/program/lib/setup.php')) {
            require_once($CFG->dirroot.'/elis/program/lib/setup.php');
            require_once(elispm::lib('data/curriculum.class.php'));
            require_once(elispm::lib('datedelta.class.php'));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets program custom fields
     * @return array An array of custom program fields
     */
    public static function get_program_custom_fields() {
        global $DB;

        if (static::require_elis_dependencies() === true) {
            // Get custom fields.
            $sql = 'SELECT shortname, name, datatype, multivalued
                      FROM {'.field::TABLE.'} f
                      JOIN {'.field_contextlevel::TABLE.'} fctx ON f.id = fctx.fieldid AND fctx.contextlevel = ?';
            $sqlparams = array(CONTEXT_ELIS_PROGRAM);
            return $DB->get_records_sql($sql, $sqlparams);
        } else {
            return array();
        }
    }

    /**
     * Gets a description of the program/currciulum object for use in the parameter and return functions.
     * @return array An array of external_value objects describing a user record in webservice terms.
     */
    public static function get_program_object_description() {
        global $DB;
        $params = array(
            'idnumber' => new external_value(PARAM_TEXT, 'Program idnumber', VALUE_REQUIRED),
            'name' => new external_value(PARAM_TEXT, 'Program name', VALUE_OPTIONAL),
            'description' => new external_value(PARAM_TEXT, 'Program description', VALUE_OPTIONAL),
            'reqcredits' => new external_value(PARAM_FLOAT, 'Program required credits', VALUE_OPTIONAL),
            'timetocomplete' => new external_value(PARAM_TEXT, 'Program time to complete', VALUE_OPTIONAL),
            'frequency' => new external_value(PARAM_TEXT, 'Program frequency', VALUE_OPTIONAL),
            'priority' => new external_value(PARAM_INT, 'Program priority', VALUE_OPTIONAL),
        );

        $fields = self::get_program_custom_fields();
        foreach ($fields as $field) {
            // Generate name using custom field prefix.
            $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

            if ($field->multivalued) {
                $paramtype = PARAM_TEXT;
            } else {
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
    public static function program_update_parameters() {
        $params = array('data' => new external_single_structure(static::get_program_object_description()));
        return new external_function_parameters($params);
    }

    /**
     * Performs program update
     * @throws moodle_exception If there was an error in passed parameters.
     * @throws data_object_exception If there was an error creating the entity.
     * @param array $data The incoming data parameter.
     * @return array An array of parameters, if successful.
     */
    public static function program_update(array $data) {
        global $USER, $DB;

        if (static::require_elis_dependencies() !== true) {
            throw new moodle_exception('ws_function_requires_elis', 'block_rlip');
        }

        // Parameter validation.
        $params = self::validate_parameters(self::program_update_parameters(), array('data' => $data));

        // Context validation.
        $context = get_context_instance(CONTEXT_USER, $USER->id);
        self::validate_context($context);

        $data = (object)$data;

        // Validate program exists
        if (!($curid = $DB->get_field(curriculum::TABLE, 'id', array('idnumber' => $data->idnumber)))) {
            throw new data_object_exception('ws_program_update_fail_invalid_idnumber', 'block_rlip', '', $data);
        }

        // Capability checking.
        require_capability('elis/program:program_edit', context_elis_program::instance($curid));

        // More validation
        if (isset($data->reqcredits)) {
            $reqcredits = (string)$data->reqcredits;
            $digits = strlen($reqcredits);
            $decies = 0;
            if (($decpos = strpos($reqcredits, '.')) !== false) {
                $decies = $digits - $decpos - 1;
                $digits = $decpos;
            }
            if (!is_numeric($reqcredits) || $digits > 8 || $decies > 2) {
                throw new data_object_exception('ws_program_update_fail_invalid_reqcredits', 'block_rlip', '', $data);
            }
        }

        if (isset($data->timetocomplete)) {
            $datedelta = new datedelta($data->timetocomplete);
            if (!$datedelta->getDateString()) {
                throw new data_object_exception('ws_program_update_fail_invalid_timetocomplete', 'block_rlip', '', $data);
            }
        }

        if (isset($data->frequency)) {
            $datedelta = new datedelta($data->frequency);
            if (!$datedelta->getDateString()) {
                throw new data_object_exception('ws_program_update_fail_invalid_frequency', 'block_rlip', '', $data);
            }
        }

        if (isset($data->priority)) {
            if ($data->priority < 0 || $data->priority > 10) {
                throw new data_object_exception('ws_program_update_fail_invalid_priority', 'block_rlip', '', $data);
            }
        }

        $prg = new curriculum($curid);
        $prg->load();
        $prg->set_from_data($data);
        $prg->save();

        // Respond.
        if (!empty($prg->id)) {
            $prgrec = (array)$DB->get_record(curriculum::TABLE, array('id' => $prg->id));
            $prgobj = $prg->to_array();
            // convert multi-valued custom field arrays to comma-separated listing
            $fields = self::get_program_custom_fields();
            foreach ($fields as $field) {
                // Generate name using custom field prefix.
                $fullfieldname = data_object_with_custom_fields::CUSTOM_FIELD_PREFIX.$field->shortname;

                if ($field->multivalued && isset($prgobj[$fullfieldname]) && is_array($prgobj[$fullfieldname])) {
                    $prgobj[$fullfieldname] = implode(',', $prgobj[$fullfieldname]);
                }
            }
            return array(
                'messagecode' => get_string('ws_program_update_success_code', 'block_rlip'),
                'message' => get_string('ws_program_update_success_msg', 'block_rlip'),
                'record' => array_merge($prgrec, $prgobj)
            );
        } else {
            throw new data_object_exception('ws_program_update_fail', 'block_rlip');
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure Object describing return parameters for this webservice method.
     */
    public static function program_update_returns() {
        return new external_single_structure(
                array(
                    'messagecode' => new external_value(PARAM_TEXT, 'Response Code'),
                    'message' => new external_value(PARAM_TEXT, 'Response'),
                    'record' => new external_single_structure(static::get_program_object_description())
                )
        );
    }
}
