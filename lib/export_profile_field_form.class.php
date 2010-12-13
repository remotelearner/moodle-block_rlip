<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2009 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2009 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot . '/lib/formslib.php');

//form for configuring profile fields in the export
class export_profile_field_form extends moodleform {
    
    function definition() {
        global $CFG;
        
        $mform = $this->_form;
        
        //hidden field for storing id of the record we are editing, if applicable
        $mform->addElement('hidden', 'editid');
        
        //field for configuring the column header
        $mform->addElement('text', 'column_header', get_string('export_config_form_column_header', 'block_rlip'));
        $mform->setHelpButton('column_header', array('exportconfig/column_header', get_string('export_config_form_column_header', 'block_rlip'), 'block_rlip'));
        $mform->addrule('column_header', null, 'required', null, 'client');
        
        //obtain the list of available fields
        $options = array();
        
        $concat = sql_concat("'profile_field_'", 'shortname');
        
        $user_info_field_table = block_rlip_get_profile_field_table('user_info_field');
        
        $sql = "SELECT user_info_field.*
                FROM
                {$user_info_field_table}";
        
        if ($records = get_records_sql($sql)) {
            foreach ($records as $record) {
                $options['profile_field_' . $record->shortname] = $record->name;
            }
        }
        
        //field for selecting a profile field to pull data from
        $mform->addElement('select', 'profile_field', get_string('export_config_form_profile_field', 'block_rlip'), $options);
        $mform->setHelpButton('profile_field', array('exportconfig/profile_field', get_string('export_config_form_profile_field', 'block_rlip'), 'block_rlip'));
        $mform->addrule('profile_field', null, 'required', null, 'client');
        
        //change the submit button text depending on whether we're creating or editing
        $editid = optional_param('editid', 0, PARAM_INT);
        
        if ($editid == 0) {
            $submit_label = get_string('export_config_form_add', 'block_rlip');    
        } else {
            $submit_label = get_string('export_config_form_update', 'block_rlip');
        }
        
        //add submit and cancel buttons
        $this->add_action_buttons(true, $submit_label);
    }
    
    /**
     * Standard validation method
     * (makes sure column headers are unique)
     */
    function validation($data, $files) {
        global $CFG;

        $errors = parent::validation($data, $files);
        
        $profile_field_table = block_rlip_get_profile_field_table('user_info_field');
            
        //field is generic, so match it specifically with the profile_field prefix
        $concat = sql_concat("'profile_field_'", 'user_info_field.shortname'); 
            
        $sql = "SELECT *
                FROM
                {$CFG->prefix}block_rlip_export_fieldmap fieldmap
                JOIN {$profile_field_table}
                  ON fieldmap.fieldname = {$concat}
                WHERE fieldmap = '" . $data['column_header'] . "'";
                    
        //duplication is ok only if we are editing that same record
        if (!empty($data['editid'])) {
            $sql .= " AND fieldmap.id != {$data['editid']}";
        }

        if (record_exists_sql($sql)) {
            //duplicate value warning
            $errors['column_header'] = get_string('export_config_duplicate_header_error', 'block_rlip');
        }

        return $errors;
    }
    
}

?>