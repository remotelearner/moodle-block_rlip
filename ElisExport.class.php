<?php
require_once($CFG->dirroot . '/curriculum/config.php');
require_once($CFG->dirroot . '/blocks/rlip/elis/lib.php');

class ElisExport {
    function cron($manual = false, $last_cron_time = 0) {
        global $CFG;

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
            foreach ($records as $record) {
                if (!fputcsv($fp, $record)) {
                    $this->log_filer->lfprintln(get_string('filerecordwriteerror', 'block_rlip', implode(', ', $record)));
                    if($manual !== true) {
                        mtrace(get_string('filerecordwriteerror', 'block_rlip', implode(', ', $record)));
                    }
                }
            }
        } else {
            $this->log_filer->lfprintln(get_string('filewriteerror', 'block_rlip', $localfile));
            if($manual !== true) {
                mtrace(get_string('filewriteerror', 'block_rlip', $localfile));
            }
        }

        fclose($fp);
    }


    function get_cm_user_data($manual = false, $include_all = false, $last_cron_time = 0) {
        global $CFG;

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
                WHERE clsenrol.completestatusid = {$passed_status}
                {$time_condition}
                ORDER BY usridnumber DESC";

        $results = get_records_sql($sql);

        return !empty($results) ? $results : array();
    }


    private function get_user_data_header() {
        return $header = array(
                 get_string('export_header_firstname', 'block_rlip'),
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
    }

    private function get_user_data($manual = false, $include_all = false, $last_cron_time = 0) {
        global $CFG;
        
        require_once($CFG->dirroot . '/lib/gradelib.php');
        
        $return = array();
        $i      = 0;

        $users = $this->get_cm_user_data($manual, $include_all, $last_cron_time);

        $userstatus     = 'COMPLETED';

        if(!empty($users)) {
            foreach($users as $userdata) {

                // Check for required fields
                if (empty($userdata->usridnumber) or empty($userdata->crsidnumber)) {
                    $this->log_filer->lfprintln(get_string('skiprecord', 'block_rlip', $userdata));
                    if($manual !== true) {
                        mtrace(get_string('skiprecord', 'block_rlip', $userdata));
                    }
                    continue;
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

                $return[$i] = array();
                array_push($return[$i],
                           $firstname,
                           $lastname,
                           $username,
                           $userno,
                           $coursecode,
                           $userstartdate,
                           $userenddate,
                           $userstatus,
                           $usergrade,
                           $gradeletter);

                $i++;

                $a = new stdClass;
                $a->userno = $userno;
                $a->coursecode = $coursecode;

                $this->log_filer->lfprintln(get_string('recordadded', 'block_rlip', $a));
            }
        }

        if (empty($return)) {
            if($manual !== true) {
                $this->log_filer->lfprintln(get_string('nouserdata', 'block_rlip'));
                mtrace(get_string('nouserdata', 'block_rlip'));
            }
        }

        return $return;
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
