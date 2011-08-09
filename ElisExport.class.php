<?php
require_once($CFG->dirroot . '/curriculum/config.php');
require_once($CFG->dirroot . '/blocks/rlip/elis/lib.php');

class ElisExport {
    function cron($manual = false, $last_cron_time = 0) {
        global $CFG;

        set_time_limit(0);

        $include_all = false;

        if(!empty($CFG->block_rlip_exportallhistorical)) {
            $include_all = true;
        }

        $this->log_filer = new ipe_log_filer($CFG->block_rlip_logfilelocation, 'export_' . time());

        $context = get_context_instance(CONTEXT_SYSTEM);
        if (!has_capability('block/rlip:config', $context)) {
            echo get_string('nopermissions', 'block_rlip') . '<br/>';
            return false;
        }

        if(empty($CFG->block_rlip_exportfilelocation)) {
            if($manual !== true) {
                mtrace(get_string('filenotdefined', 'block_rlip') . "\n");
            }
            $this->log_filer->lfprintln(get_string('filenotdefined', 'block_rlip'));
            $this->log_filer->output_log();
            return true;
        }

        $check_empty = trim($CFG->block_rlip_exportfilelocation);

        if(empty($check_empty)) {
            if($manual !== true) {
                mtrace(get_string('filenotdefined', 'block_rlip') . "\n");
            }
            $this->log_filer->lfprintln(get_string('filenotdefined', 'block_rlip'));
            $this->log_filer->output_log();
            return true;
        }

        $exportfilelocation = trim($CFG->block_rlip_exportfilelocation);
        if(is_dir($exportfilelocation)) {
            if(strrpos($exportfilelocation, '/') == strlen($exportfilelocation) - strlen('/')) {
                $exportfilelocation .= 'export.csv';
            } else {
                $exportfilelocation .= '/export.csv';
            }
        } else if(strrpos($exportfilelocation, '/') == strlen($exportfilelocation) - strlen('/')) {
            $exportfilelocation .= 'export.csv';
        }

        if(!empty($CFG->block_rlip_exportfiletimestamp)) {
            $exportfilelocation = $this->add_timestamp_to_filename($exportfilelocation);
        }

        $sourcefiles = array();
        $destfiles = array();

        // Create user completion data
        if($manual !== true) {
            mtrace(get_string('createdata', 'block_rlip'));
        }
        $records = $this->get_user_data($manual, $include_all, $last_cron_time);

        $header = $this->get_user_data_header();

        if (!empty($records)) {
            $this->create_csv($records, $header, $exportfilelocation, $manual);
            array_push($sourcefiles, $exportfilelocation);
            array_push($destfiles, $exportfilelocation);
        } else {
            $this->log_filer->lfprintln(get_string('nodata', 'block_rlip'));
            if($manual !== true) {
                mtrace(get_string('nodata', 'block_rlip'));
            }
            //set file contents to empty
            $this->create_csv(array(), $header, $exportfilelocation, $manual);
            $this->log_filer->lfprintln(get_string('createdemptyfile', 'block_rlip', $exportfilelocation));
        }

        if (!empty($CFG->block_rlip_exportfilelocation)) {
            $this->log_filer->output_log();
        }

        return true;
    }


    function create_csv($records = array(), $header = array(), $filename = '', $manual = false) {
        global $CFG;

        if (empty($header) or empty($filename)) {
            if($manual !== true) {
                mtrace(get_string('noparams', 'block_rlip'));
            }
            return false;
        }

        // Create file
        $localfile  = $filename;

        if (file_exists($localfile)) {

            $this->log_filer->lfprintln(get_string('localfileexists', 'block_rlip', $localfile));

            if($manual !== true) {
                mtrace(get_string('localfileexists', 'block_rlip', $localfile));
            }

            if (@unlink($localfile)) {
                $this->log_filer->lfprintln(get_string('localfileremoved', 'block_rlip', $localfile));
            } else {
                if($manual !== true) {
                    mtrace(get_string('localfilenotremoved', 'block_rlip', $localfile));
                }
                return false;
            }

        }

        // Make sure that we can actually open the file we are attempting to write to.
        if (($fp = fopen($localfile, 'w')) === false) {
            mtrace(get_string('couldnotopenexportfile', 'block_rlip', $localfile));
            return false;
        }

        if (fputcsv($fp, $header)) {
            while (($record = rs_fetch_next_record($records))) {
                $record = $this->record_to_row($record, $manual);
                if ($record === null) {
                    continue;
                }
                if (!fputcsv($fp, $record)) {
                    $this->log_filer->lfprintln(get_string('filerecordwriteerror', 'block_rlip', implode(', ', $record)));
                    if($manual !== true) {
                        mtrace(get_string('filerecordwriteerror', 'block_rlip', implode(', ', $record)));
                    }
                }
            }
            rs_close($records);
        } else {
            $this->log_filer->lfprintln(get_string('filewriteerror', 'block_rlip', $localfile));
            if($manual !== true) {
                mtrace(get_string('filewriteerror', 'block_rlip', $localfile));
            }
        }

        fclose($fp);
    }


    function get_user_data($manual = false, $include_all = false, $last_cron_time = 0) {
        global $CFG;

        require_once($CFG->dirroot . '/lib/gradelib.php');
        require_once($CFG->dirroot . '/curriculum/config.php');
        require_once(CURMAN_DIRLOCATION . '/lib/student.class.php');

        $passed_status = STUSTATUS_PASSED;

        $time_condition = '';

        if($manual === true) {
            if($include_all !== true) {
                $one_day_ago = time() - DAYSECS;
                $time_condition = ' AND clsenrol.completetime > ' . $one_day_ago;
            }
        } else if(!empty($last_cron_time)) {
            if($include_all !== true) {
                $time_condition = ' AND clsenrol.completetime > ' . $last_cron_time;
            }
        }

        $as = sql_as();

        //for storing extra columns we need to include for profile fields
        $extra_columns = "";
        //for storing extra instances of the profile field table
        $profile_field_joins = "";

        $mapping = block_rlip_get_profile_field_mapping(true);

        $profile_field_num = 1;

        foreach ($mapping as $key => $value) {
            $contextlevel = context_level_base::get_custom_context_level('user', 'block_curr_admin');
            $field = new field(field::get_for_context_level_with_name($contextlevel, $value));

            //main user query to hook into profile fields
            $sql = "SELECT user_field.id
                    FROM
                    {$CFG->prefix}crlm_field user_field
                    JOIN {$CFG->prefix}crlm_field_category user_field_category
                      ON user_field.categoryid = user_field_category.id
                    JOIN {$CFG->prefix}crlm_field_category_context user_field_category_context
                      ON user_field_category.id = user_field_category_context.categoryid
                      AND user_field_category_context.contextlevel = {$contextlevel}
                    WHERE user_field.shortname = '{$value}'";

            if ($profile_field_id = get_field_sql($sql)) {
                //profile field joins
                $profile_field_joins .= "LEFT JOIN {$CFG->prefix}context context_{$profile_field_num}
                                           ON context_{$profile_field_num}.contextlevel = {$contextlevel}
                                           AND usr.id = context_{$profile_field_num}.instanceid
                                         LEFT JOIN {$CFG->prefix}{$field->data_table()} user_info_data_{$profile_field_num}
                                           ON context_{$profile_field_num}.id = user_info_data_{$profile_field_num}.contextid
                                           AND user_info_data_{$profile_field_num}.fieldid = {$profile_field_id}
                                         LEFT JOIN {$CFG->prefix}{$field->data_table()} user_info_default_{$profile_field_num}
                                           ON user_info_default_{$profile_field_num}.fieldid = {$profile_field_id}
                                           AND user_info_default_{$profile_field_num}.contextid IS NULL
                                         LEFT JOIN {$CFG->prefix}crlm_field user_info_field_{$profile_field_num}
                                           ON user_info_field_{$profile_field_num}.id = {$profile_field_id}
                                        ";
            } else {
                //bogus join to fill in same structure as profile field joins
                $profile_field_joins .= "LEFT JOIN {$CFG->prefix}crlm_field_data_int user_info_data_{$profile_field_num}
                                           ON 0 = 1
                                         LEFT JOIN {$CFG->prefix}{$field->data_table()} user_info_default_{$profile_field_num}
                                           ON 0 = 1
                                         LEFT JOIN {$CFG->prefix}crlm_field user_info_field_{$profile_field_num}
                                           ON 0 = 1
                                        ";
            }

            //add the profile field data column to our list of extra columns
            $extra_columns .= ",
                               user_info_data_{$profile_field_num}.data {$as} value_{$profile_field_num},
                               user_info_default_{$profile_field_num}.data {$as} default_{$profile_field_num},
                               user_info_field_{$profile_field_num}.datatype {$as} datatype_{$profile_field_num}";

            $profile_field_num++;
        }

        $this->profile_field_num = $profile_field_num;

        //query to return user info, CM class enrolment info,
        //and grade info from the associated Moodle course
        $sql = "SELECT clsenrol.id,
                       usr.idnumber {$as} usridnumber,
                       usr.firstname,
                       usr.lastname,
                       crs.idnumber {$as} crsidnumber,
                       crs.cost,
                       clsenrol.enrolmenttime {$as} timestart,
                       clsenrol.completetime {$as} timeend,
                       clsenrol.grade {$as} usergrade,
                       moodle_user.username,
                       gg.finalgrade {$as} mdlusergrade,
                       gi.id {$as} gradeitemid
                       {$extra_columns}
                FROM {$CFG->prefix}crlm_class_enrolment clsenrol
                JOIN {$CFG->prefix}crlm_class cls
                  ON clsenrol.classid = cls.id
                JOIN {$CFG->prefix}crlm_course crs
                  ON crs.id = cls.courseid
                JOIN {$CFG->prefix}crlm_user usr
                  ON usr.id = clsenrol.userid
                LEFT JOIN {$CFG->prefix}user moodle_user
                  ON usr.idnumber = moodle_user.idnumber
                LEFT JOIN {$CFG->prefix}crlm_class_moodle clsmdl
                  ON cls.id = clsmdl.classid
                LEFT JOIN {$CFG->prefix}course mdlcrs
                  ON clsmdl.moodlecourseid = mdlcrs.id
                LEFT JOIN {$CFG->prefix}grade_items gi
                  ON mdlcrs.id = gi.courseid
                  AND gi.itemtype = 'course'
                LEFT JOIN {$CFG->prefix}grade_grades gg
                  ON gi.id = gg.itemid
                  AND moodle_user.id = gg.userid
                {$profile_field_joins}
                WHERE clsenrol.completestatusid = {$passed_status}
                {$time_condition}
                ORDER BY usridnumber DESC";

        $results = get_recordset_sql($sql);

        return !empty($results) ? $results : array();
    }


    private function get_user_data_header() {
        $header = array(get_string('export_header_firstname', 'block_rlip'),
                        get_string('export_header_lastname', 'block_rlip'),
                        get_string('export_header_username', 'block_rlip'),
                        get_string('export_header_user_idnumber', 'block_rlip'),
                        get_string('export_header_course_idnumber', 'block_rlip'),
                        get_string('export_header_start_date', 'block_rlip'),
                        get_string('export_header_end_date', 'block_rlip'),
                        get_string('export_header_status', 'block_rlip'),
                        get_string('export_header_grade', 'block_rlip'),
                        get_string('export_header_letter', 'block_rlip')
                       );

        //retrieve the configured mapping
        $mapping = block_rlip_get_profile_field_mapping(true);

        //the array keys are the configured column headers
        return array_merge($header, array_keys($mapping));
    }

    private function record_to_row($userdata, $manual = false) {
        $userstatus     = 'COMPLETED';

        // Check for required fields
        if (empty($userdata->usridnumber) or empty($userdata->crsidnumber)) {
            $this->log_filer->lfprintln(get_string('skiprecord', 'block_rlip', $userdata));
            if($manual !== true) {
                mtrace(get_string('skiprecord', 'block_rlip', $userdata));
            }
            return;
        }

        $firstname      = $userdata->firstname;
        $lastname       = $userdata->lastname;
        $username       = empty($userdata->username) ? '' : $userdata->username;
        $userno         = $userdata->usridnumber;
        $coursecode     = $userdata->crsidnumber;
        $userstartdate  = empty($userdata->timestart) ? date("m/d/Y",time()) : date("m/d/Y", $userdata->timestart);
        $userenddate    = empty($userdata->timeend) ? date("m/d/Y",time()) : date("m/d/Y", $userdata->timeend);
        $usergrade      = $userdata->usergrade;

        //calculate the Moodle course grade letter from the value provided by get_cm_user_data
        $gradeletter = '-';
        if ($grade_item = grade_item::fetch(array('id' => $userdata->gradeitemid))) {
            $gradeletter    = grade_format_gradevalue($userdata->mdlusergrade, $grade_item, true, GRADE_DISPLAY_TYPE_LETTER);
        }

        //guaranteed fields
        $row = array($firstname,
                     $lastname,
                     $username,
                     $userno,
                     $coursecode,
                     $userstartdate,
                     $userenddate,
                     $userstatus,
                     $usergrade,
                     $gradeletter);

        //add profile field data
        for ($j = 1; $j < $this->profile_field_num; $j++) {
            $field_name = "value_{$j}";

            if (isset($userdata->$field_name)) {
                $row[] = $userdata->$field_name;
            } else {
                //default value
                $default_field_name = "default_{$j}";

                if (is_null($userdata->$default_field_name)) {
                    //do some datatype-specific default setting
                    $type_field_name = "datatype_{$j}";
                    if ('int' == $userdata->$type_field_name || 'bool' == $userdata->$type_field_name) {
                        $row[] = '0';
                    } else {
                        $row[] = '';
                    }
                } else {
                    //actually a have valid default
                    $row[] = $userdata->$default_field_name;
                }
            }
        }

        $a = new stdClass;
        $a->userno = $userno;
        $a->coursecode = $coursecode;

        $this->log_filer->lfprintln(get_string('recordadded', 'block_rlip', $a));

        return $row;
    }

    private function add_timestamp_to_filename($filename) {
        $timestamp = time();

        $last_slash_position = strrpos($filename, '/');

        if($last_slash_position === false) {
            $search_start = 0;
        } else {
            $search_start = $last_slash_position + 1;
        }

        $last_dot_position = strrpos($filename, '.', $search_start);

        if($last_dot_position === false) {
            return $filename . '_' . $timestamp;
        } else {
            return substr($filename, 0, $last_dot_position) . '_' . $timestamp . substr($filename, $last_dot_position);
        }
    }
}
?>
