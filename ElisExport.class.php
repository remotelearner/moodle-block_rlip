<?php
require_once($CFG->dirroot . '/curriculum/config.php');
require_once(CURMAN_DIRLOCATION . '/dataimport/lib.php');

class ElisExport {
    function cron($manual = false) {
        global $CFG, $CURMAN;

        $include_all = false;

        if(!empty($CURMAN->config->exportallhistorical)) {
            $include_all = true;
        }

        //only care about this if we have the Integration Point enabled
        if(empty($CURMAN->config->ip_enabled)) {
            return true;
        }

        $this->log_filer = new log_filer($CURMAN->config->logfilelocation, 'export_' . time());

        if(empty($CURMAN->config->exportfilelocation)) {
            if($manual !== true) {
                mtrace(get_string('filenotdefined', 'block_completion_export') . "\n");
            }
            $this->log_filer->lfprintln(get_string('filenotdefined', 'block_completion_export'));
            $this->log_filer->output_log();
            return true;
        }

        $check_empty = trim($CURMAN->config->exportfilelocation);

        if(empty($check_empty)) {
            if(manual !== true) {
                mtrace(get_string('filenotdefined', 'block_completion_export') . "\n");
            }
            $this->log_filer->lfprintln(get_string('filenotdefined', 'block_completion_export'));
            $this->log_filer->output_log();
            return true;
        }

        $exportfilelocation = trim($CURMAN->config->exportfilelocation);
        if(!empty($CURMAN->config->exportfiletimestamp)) {
            $exportfilelocation = $this->add_timestamp_to_filename($exportfilelocation);
        }

        $sourcefiles = array();
        $destfiles = array();

        // Create user completion data
        if($manual !== true) {
            mtrace(get_string('createdata', 'block_completion_export'));
        }
        $records = $this->get_user_data($manual, $include_all);

        $header = $this->get_user_data_header();

        if (!empty($records)) {
            $this->create_csv($records, $header, $exportfilelocation, $manual);
            array_push($sourcefiles, $exportfilelocation);
            array_push($destfiles, $exportfilelocation);
        } else {
            $this->log_filer->lfprintln(get_string('nodata', 'block_completion_export'));
            if($manual !== true) {
                mtrace(get_string('nodata', 'block_completion_export'));
            }
            //set file contents to empty
            $this->create_csv(array(), $header, $exportfilelocation, $manual);
            $this->log_filer->lfprintln(get_string('createdemptyfile', 'block_completion_export', $exportfilelocation));
        }

        if (!empty($CURMAN->config->logfilelocation)) {
            $this->log_filer->output_log();
        }

        return true;
    }


    function create_csv($records = array(), $header = array(), $filename = '', $manual = false) {
        global $CFG;

        if (empty($header) or empty($filename)) {
            if($manual !== true) {
                mtrace(get_string('noparams', 'block_completion_export'));
            }
            return false;
        }

        // Create file
        $localfile  = $filename;

        if (file_exists($localfile)) {

            $this->log_filer->lfprintln(get_string('localfileexists', 'block_completion_export', $localfile));

            if($manual !== true) {
                mtrace(get_string('localfileexists', 'block_completion_export', $localfile));
            }

            if (unlink($localfile)) {
                $this->log_filer->lfprintln(get_string('localfileremoved', 'block_completion_export', $localfile));
            } else {
                if($manual !== true) {
                    mtrace(get_string('localfilenotremoved', 'block_completion_export', $localfile));
                }
                return false;
            }

        }

        $fp = fopen($localfile, 'w');

        if (fputcsv($fp, $header)) {
            foreach ($records as $record) {
                if (!fputcsv($fp, $record)) {
                    $this->log_filer->lfprintln(get_string('filerecordwriteerror', 'block_completion_export', implode(', ', $record)));
                    if($manual !== true) {
                        mtrace(get_string('filerecordwriteerror', 'block_completion_export', implode(', ', $record)));
                    }
                }
            }
        } else {
            $this->log_filer->lfprintln(get_string('filewriteerror', 'block_completion_export', $localfile));
            if($manual !== true) {
                mtrace(get_string('filewriteerror', 'block_completion_export', $localfile));
            }
        }

        fclose($fp);
    }


    function get_cm_user_data($manual = false, $include_all = false) {
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
        } else if($last_cron_time = get_field('block', 'lastcron', 'name', 'completion_export')) {
            if($include_all !== true) {
                $time_condition = ' AND clsenrol.completetime > ' . $last_cron_time;
            }
        }

        $sql = "SELECT clsenrol.id, usr.idnumber AS usridnumber, usr.firstname, usr.lastname, crs.idnumber AS crsidnumber, crs.cost,
                clsenrol.enrolmenttime AS timestart, clsenrol.completetime AS timeend, clsenrol.grade AS usergrade, moodle_user.username
                FROM {$CFG->prefix}crlm_class_enrolment clsenrol
                JOIN {$CFG->prefix}crlm_class cls ON clsenrol.classid = cls.id
                JOIN {$CFG->prefix}crlm_course crs ON crs.id = cls.courseid
                JOIN {$CFG->prefix}crlm_user usr ON usr.id = clsenrol.userid
                LEFT JOIN {$CFG->prefix}user moodle_user ON usr.idnumber = moodle_user.idnumber
                WHERE clsenrol.completestatusid = {$passed_status}
                {$time_condition}
                ORDER BY usridnumber DESC";

        $results = get_records_sql($sql);

        return !empty($results) ? $results : array();
    }


    private function get_user_data_header() {
        return $header = array(
                 'First Name',
                 'Last Name',
                 'Username',
                 'User Idnumber',
                 'Course Idnumber',
                 'Start Date',
                 'End Date',
                 'Status',
                 'Grade'
               );
    }

    private function get_user_data($manual = false, $include_all = false) {
        $return = array();
        $i      = 0;

        $users = $this->get_cm_user_data($manual, $include_all);

        $userstatus     = 'COMPLETED';

        foreach($users as $userdata) {

            // Check for required fields
            if (empty($userdata->usridnumber) or empty($userdata->crsidnumber)) {
                $this->log_filer->lfprintln(get_string('skiprecord', 'block_completion_export', $userdata));
                if($manual !== true) {
                    mtrace(get_string('skiprecord', 'block_completion_export', $userdata));
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
                       $usergrade);

            $i++;

            $a = new stdClass;
            $a->userno = $userno;
            $a->coursecode = $coursecode;

            $this->log_filer->lfprintln(get_string('recordadded', 'block_completion_export', $a));
        }

        if (empty($return)) {
            if($manual !== true) {
                mtrace(get_string('nouserdata', 'block_completion_export'));
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
