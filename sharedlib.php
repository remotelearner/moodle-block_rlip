<?php
/**
 * Returns true if site is using ELIS/CM.
 * Returns false if not ELIS, or if site has been configured to use non-ELIS IP.
 *
 * @param boolean $ignoreoverride Ignores the override ELIS IP setting. Used if
 * we need to know if ELIS is installed regardless of the config setting (like on
 * the settings page).
 */
function block_rlip_is_elis($ignoreoverride=false) {
    global $CFG;

    if (!$ignoreoverride && !empty($CFG->block_rlip_overrideelisip)) {
        return false;
    }

    if (!file_exists($CFG->dirroot.'/curriculum/config.php')) {
        // return early!
        return false;
    }

    if (!record_exists('block', 'name', 'curr_admin')) {
        return false;
    }

    return true;
}

/**
 * used to log messages to a file
 */
abstract class block_rlip_log_filer {
    private $endl = "\n"; //new line delimiter
    private $warning = '';
    private $logs = array();
    private $count = 1; //holds the current record being logged to the file
    private $filename = '';

    /**
     * opens a file to append to with the given file name in the given file location
     * @param string $file location where log file is to be put
     * @param string $filename name of log file
     */
    function __construct($file, $filename) {
        if(!empty($file) && is_dir(addslashes($file))) {
            $this->filename = addslashes($file) . '/' . $filename . '.log';
        }
    }

    /**
     * print a string to the file with a new line at the end
     * @param string $line what to print
     */
    function lfprintln($line = '') {
        $this->lfprint($line . $this->endl);
    }

    /**
     * prints a string to the file
     * @param string $str what to print
     */
    function lfprint($str = '') {
        $this->logs[] = $str;
    }

    /**
     * ues the count to display what record contained the error
     * @param string $line prints an error message to the file for a particular record
     */
    function add_error_record($line='error') {
        $this->lfprintln("error with record #$this->count: $line $this->warning");
        $this->warning = '';
        $this->count++;
    }

    /**
     * adds an error message to the log file
     * @param string $line error message
     */
    function add_error($line='error') {
        $this->lfprintln("error: $line $this->warning");
        $this->warning = '';
    }

    /**
     * adds indication of successfully used the record
     * @param string $line success message
     */
    function add_success($line='success') {
        $this->lfprintln("success with record #$this->count: $line $this->warning");
        $this->warning = '';
        $this->count++;
    }

    /**
     * adds a warning to the log fil for the current record
     * @param string $line warning message
     */
    function add_warning($line='warning') {
        if(empty($this->warning)) {
            $this->warning = ' WARNING ' . $line;
        } else {
            $this->warning .= ', ' . $line;
        }
    }

    /**
     * prints all the messages to the log file
     * @global object $CURMAN
     * @global object $USER
     * @param object $file name of the file to log to
     */
    function output_log($file=null) {
        global $CFG, $USER;

        if(empty($this->logs)) {
            return;
        }

        if(empty($file)) {
            $file = fopen($this->filename, 'a');
        }

        if(!empty($file)) {
            $message = '';
            foreach($this->logs as $log) {
                $message .= $log . "\n";
            }

            if(!empty($message)) {
                fwrite($file, $message);

                $idnumbers = explode(',', $CFG->block_rlip_emailnotification);

                $subject = get_string('ip_log', 'block_rlip');

                foreach($idnumbers as $idnum) {
                    if(!empty($idnum)) {
                        $this->notify_user($idnum, $subject, $message);
                    }
                }
            }
        }
    }
    
    abstract function notify_user($idnumber, $subject, $message);

    /**
     * close the file when this object loses focus, may not be needed but there
     * as a precaucion
     */
    function __destruct() {
        if(!empty($this->file)) {
            fclose($this->file);
        }
    }
}

function block_rlip_throwException($message = null, $code = null) {
    throw new Exception($message, $code);
}

function block_rlip_import_period_updated() {
    global $CFG;
    
    $value = 0;
    if(!empty($CFG->block_rlip_importperiod)) {
        $value = block_rlip_sanitize_time_string($CFG->block_rlip_importperiod, '30m');
    }
    set_config('block_rlip_importperiod', $value);
}

function block_rlip_export_period_updated() {
    global $CFG;
    
    $value = 0;
    if(!empty($CFG->block_rlip_exportperiod)) {
        $value = block_rlip_sanitize_time_string($CFG->block_rlip_exportperiod, '1d');
    }
    set_config('block_rlip_exportperiod', $value);
}

function block_rlip_sanitize_time_string($time_string, $default) {
    $values = array('d', 'h', 'm');
    
    $result = '';
    $current_group = '';
    
    for($i = 0; $i < strlen($time_string); $i++) {
        $char = strtolower(substr($time_string, $i, 1));
        
        if($char >= '0' && $char <= '9') {
            $current_group .= $char;
        } else if(in_array($char, $values)) {
            if(!empty($current_group)) {
                $current_group .= $char;
                $result .= $current_group;
            }
            $current_group = '';
        }
    }
    
    if(empty($result)) {
        $result = $default;
    }
    
    return $result;
}

function block_rlip_time_string_to_seconds($time_string) {
    $seconds = 0;
    $current_num = 0;
    
    $values = array('d' => DAYSECS,
                    'h' => HOURSECS,
                    'm' => MINSECS);
    
    for($i = 0; $i < strlen($time_string); $i++) {
        $char = strtolower(substr($time_string, $i, 1));
        
        if($char >= '0' && $char <= '9') {
            $current_num = 10 * $current_num + (int)$char;
        } else if(in_array($char, array_keys($values))) {
            $seconds += $current_num * $values[$char];
            $current_num = 0;
        }
    }
    
    return $seconds;
}

/**
 * Handles the deletion of a profile field mapping configured
 * for export purposes
 */
function block_rlip_handle_export_mapping_delete() {
    global $CFG;
    
    //attempt to retrieve the id of the record to be deleted
    $deleteid = optional_param('deleteid', 0, PARAM_INT);

    //make sure the delete action is taking place
    if ($deleteid != 0) {
        //get the existing field's order
        $fieldorder = get_field('block_rlip_export_fieldmap', 'fieldorder', 'id', $deleteid);

        //delete the appropriate item
        delete_records('block_rlip_export_fieldmap', 'id', $deleteid);
            
        //shift higher orders down
        $sql = "UPDATE {$CFG->prefix}block_rlip_export_fieldmap
                SET fieldorder = fieldorder - 1
                WHERE fieldorder > {$fieldorder}";
            
        execute_sql($sql, false);
        
        redirect(block_rlip_get_base_export_config_url(), '', 0);
    }
}

/**
 * Handle reordering of the profile fields
 * 
 * @param  string  $param      The parameter to check for the id of the record to be shifted
 * @param  string  $direction  The direction the record is being moved in, either 'up' or 'down'
 */
function block_rlip_handle_export_mapping_reorder($param, $direction, $force_cm = false) {
    global $CFG;
    
    //attempt to retrieve the id of the record to be moved
    $moving_record = optional_param($param, 0, PARAM_INT);
    
    //make sure the move action is taking place
    if ($moving_record != 0) {
        $fieldorder = get_field('block_rlip_export_fieldmap', 'fieldorder', 'id', $moving_record);
        
        //set up query parameters to find the closest visible entry in the appropriate direction
        if ($direction == 'up') {
            $operation = 'MAX';
            $compare_operator = '<';
        } else {
            $operation = 'MIN';
            $compare_operator = '>';
        }
        
        //map configured fieldnames to profile field shortnames
        $concat = sql_concat("'profile_field_'", 'profile_field_info.shortname');
        
        //this allows us to handle the Moodle and CM cases similarly
        $profile_field_table = block_rlip_get_profile_field_table('profile_field_info', $force_cm);
        
        //query to retrieve the closest entry in the applicable direction
        $sql = "SELECT {$operation}(fieldorder)
                FROM {$CFG->prefix}block_rlip_export_fieldmap
                WHERE fieldorder {$compare_operator} {$fieldorder}
                AND fieldname IN (
                  SELECT {$concat}
                  FROM {$profile_field_table} 
                )";
        
        if (!($new_position = get_field_sql($sql))) {
            //nothing to swap with
            return;
        }
        
        //prevents us from violating the unique constraint on fieldorder
        set_field('block_rlip_export_fieldmap', 'fieldorder', 0, 'id', $moving_record);
        
        //shift the other record in the opposite direction
        set_field('block_rlip_export_fieldmap', 'fieldorder', $fieldorder, 'fieldorder', $new_position);
            
        //move the specified record
        set_field('block_rlip_export_fieldmap', 'fieldorder', $new_position, 'id', $moving_record);
        
        redirect(block_rlip_get_base_export_config_url(), '', 0);
    }
}

/**
 * Handles the processing of the form used to add user profile fields
 * and also the displaying of that form
 * 
 * @param  newpage or ipb_newpage  $target  The page containing info about the form URL
 */
function block_rlip_handle_export_field_form($target) {
    global $CFG;
        
    require_once($CFG->dirroot . '/blocks/rlip/lib/export_profile_field_form.class.php');
    
    //construct our config form
    $form = new export_profile_field_form($target->get_moodle_url());
        
    $editid = optional_param('editid', 0, PARAM_INT);
    
    if ($form->is_cancelled()) {
        redirect(block_rlip_get_base_export_config_url(), '', 0);
    }
    
    //process the adding of a field, if applicable
    if ($data = $form->get_data()) {

        if (!empty($data->editid)) {
            $mapping_record = new stdClass;
            $mapping_record->id = $data->editid;
            $mapping_record->fieldname = $data->profile_field;
            $mapping_record->fieldmap = $data->column_header;
            update_record('block_rlip_export_fieldmap', $mapping_record);
            
            redirect(block_rlip_get_base_export_config_url(), '', 0);
        } else {
            $mapping_record = new stdClass;
            //right now, we only care about the user context
            $mapping_record->context = 'user';
            $mapping_record->fieldname = $data->profile_field;
            $mapping_record->fieldmap = $data->column_header;
        
            //determine the sort order insertion position
            $sql = "SELECT MAX(fieldorder)
                    FROM {$CFG->prefix}block_rlip_export_fieldmap";
            
            if (!($max_sort = get_field_sql($sql))) {
                $max_sort = 0;
            }
            
            $mapping_record->fieldorder = $max_sort + 1; 
           
            //commit to the database
            insert_record('block_rlip_export_fieldmap', $mapping_record);
            
            redirect(block_rlip_get_base_export_config_url(), '', 0);
        }
    }
    
    if ($editid != 0 and
        $mapping_record = get_record('block_rlip_export_fieldmap', 'id', $editid)) {
        
        $data_object = new stdClass;
        $data_object->profile_field = $mapping_record->fieldname;
        $data_object->column_header = $mapping_record->fieldmap;
        $data_object->editid = $editid;
        
        $form->set_data($data_object);
    }
    
    //display the add form
    $form->display();
}

/**
 * Constructs the apropriate HTML for an icon on the profile field export page
 * 
 * @param  string   $param_name        Name of the URL parameter the link action will use
 * @param  string   $image_name        Name of the image to display, not including file extension
 * @param  int      $record_id         Id to add to the URL action
 * @param  boolean  $blank             If true, the blank spacer image will be used instead of the supplied image
 * @param  string   $blank_image_name  Name of the image file used for the spacer image (excluding extension)
 * @param  string   $image_extension   File extension used for all images (includes "." prefix)
 */
function block_rlip_get_export_icon_html($param_name, $image_name, $record_id, $blank = false, $blank_image_name = 'blank', $image_extension = '.png') {
    global $CFG;
    
    //base page url
    $baseurl = block_rlip_get_base_export_config_url();
    
    //base image url path
    $base_image_path = $CFG->wwwroot . '/blocks/rlip/pix/';
    
    if ($blank) {
        //image tag for the "spacer" image
        $result = "<img src=\"{$base_image_path}{$blank_image_name}{$image_extension}\"/>";
    } else {
        //image url fo rthe supplied image
        $result = "<img src=\"{$base_image_path}{$image_name}{$image_extension}\"/>";
        //link to the appropriate URL action
        $result = "<a href=\"{$baseurl}&{$param_name}={$record_id}\">{$result}</a>";  
    }
    
    return $result;
}

/**
 * Displays a table containing the existing mappings between column headers and profile fields
 * 
 * @param  boolean  $force_cm  If true, force the usage of CM mappings regardless of any other factors
 */
function block_rlip_display_export_field_mappings($force_cm = false) {
    global $CFG;
    
    //construct our output table
    $table = new stdClass;
    $table->head = array(get_string('mapping_column_header', 'block_rlip'),
                         get_string('mapping_profile_field', 'block_rlip'),
                         '');
    $table->data = array();
        
    //map configured fieldnames to profile field shortnames
    $concat = sql_concat("'profile_field_'", 'infofield.shortname');
        
    //dynamically determine which table(s) we need for profile fields
    $user_info_field_table = block_rlip_get_profile_field_table('infofield', $force_cm);
    
    //query that connects configured values to profile fields
    $sql = "SELECT fieldmap.id,
                   fieldmap.fieldmap,
                   infofield.name
            FROM
            {$CFG->prefix}block_rlip_export_fieldmap fieldmap
            JOIN {$user_info_field_table}
              ON fieldmap.fieldname = {$concat}
            ORDER BY fieldmap.fieldorder";

    //retrieve the current URL to refer back to this page
    $baseurl = block_rlip_get_base_export_config_url();
    
    if ($records = get_records_sql($sql)) {
        
        //track record number for first / last record special cases
        $i = 1;
        
        foreach ($records as $record) {
            $action_items  = block_rlip_get_export_icon_html('deleteid', 'delete', $record->id);
            //display for rows 2 - n
            $action_items .= block_rlip_get_export_icon_html('moveupid', 'up_arrow', $record->id, $i == 1);
            //display for rows 1 - (n-1)
            $action_items .= block_rlip_get_export_icon_html('movedownid', 'down_arrow', $record->id, $i == count($records));
            $action_items .= block_rlip_get_export_icon_html('editid', 'edit', $record->id);
                
            $table->data[] = array($record->fieldmap, $record->name, $action_items);
            
            $i++;
        }
        
        print_table($table);
    } else {
        print_box(get_string('export_config_instructions', 'block_rlip'));
    }
    
}

/**
 * Specifies a query porition that represent user-based profile field definition
 * for either Moodle or CM, depending on the site setup
 * 
 * @param   string   $alias     A table alias to use in the calculated SQL fragment
 * @param   boolean  $force_cm  If true, force the usage of the CM tables
 * 
 * @return  string              The appropriate SQL fragment
 */
function block_rlip_get_profile_field_table($alias, $force_cm = false) {
    global $CFG;
    
    if (block_rlip_is_elis() || $force_cm) {
        //CM case
        
        //obtain the user context level
        $user_context_level = context_level_base::get_custom_context_level('user', 'block_curr_admin');
            
        //use the category context info to only select user-level fields
        $user_info_field_table = "({$CFG->prefix}crlm_field {$alias}
                                   JOIN {$CFG->prefix}crlm_field_category user_info_category
                                     ON {$alias}.categoryid = user_info_category.id
                                   JOIN {$CFG->prefix}crlm_field_category_context user_info_category_context
                                     ON user_info_category.id = user_info_category_context.categoryid
                                     AND user_info_category_context.contextlevel = {$user_context_level})";
    } else {
        //Moodle user info fields are always user-based
        $user_info_field_table = "{$CFG->prefix}user_info_field {$alias}";
    }
    
    return $user_info_field_table;
}

/**
 * Calculates the current mapping between export column headers and profile field names
 * 
 * @param  boolean  $force_cm  If true, force the usage of CM mappings regardless of any other factors
 */
function block_rlip_get_profile_field_mapping($force_cm = false) {
    global $CFG;
    
    //field is generic, so match it specifically with the profile_field prefix
    $concat = sql_concat("'profile_field_'", 'user_info_field.shortname');                       

    //retrieve the field info based on linking the configured names up to profile field shortnames
    $user_info_field_table = block_rlip_get_profile_field_table('user_info_field', $force_cm);
        
    $sql = "SELECT fieldmap.fieldmap,
                   user_info_field.shortname
            FROM
            {$CFG->prefix}block_rlip_export_fieldmap fieldmap
            JOIN {$user_info_field_table}
              ON fieldmap.fieldname = {$concat}
            ORDER BY fieldmap.fieldorder";

    $result = array();            
            
    if ($records = get_records_sql($sql)) {
        foreach ($records as $record) {
            //map the column header to a field shortname
            $result[$record->fieldmap] = $record->shortname;
        }
    }

    return $result;
}

/**
 * Calculates the URL used to point to the export page
 * 
 * @return  string  The appropriate URL, based on the version of IP installed
 */
function block_rlip_get_base_export_config_url() {
    global $CFG;
    
    //retrieve the current URL to refer back to this page
    if (strpos(qualified_me(), $CFG->wwwroot . '/curriculum/') !== FALSE) {
        $baseurl = $CFG->wwwroot . '/curriculum/index.php?action=export&s=dim';
    } else {
        $baseurl = $CFG->wwwroot . '/blocks/rlip/moodle/displaypage.php?action=export';
    }
    
    return $baseurl;
}

?>