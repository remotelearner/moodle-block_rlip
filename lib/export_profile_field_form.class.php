<?php

require_once($CFG->dirroot . '/lib/formslib.php');

class export_profile_field_form extends moodleform {
    
    function definition() {
        global $CFG;
        
        $mform = $this->_form;
        
        $mform->addElement('hidden', 'editid');
        
        $mform->addElement('text', 'column_header', get_string('export_config_form_column_header', 'block_rlip'));
        $mform->addrule('column_header', null, 'required', null, 'client');
        
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
        
        $mform->addElement('select', 'profile_field', get_string('export_config_form_profile_field', 'block_rlip'), $options);
        $mform->addrule('profile_field', null, 'required', null, 'client');
        
        $mform->addElement('submit', 'add', get_string('export_config_form_add', 'block_rlip'));
    }
    
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
                WHERE fieldmap = '" . addslashes($data['column_header']) . "'";
                    
        if (!empty($data['editid'])) {
            $sql .= " AND fieldmap.id != {$data['editid']}";
        }

        if (record_exists_sql($sql)) {
            $errors['column_header'] = get_string('export_config_duplicate_header_error', 'block_rlip');
        }

        return $errors;
    }
    
}

?>