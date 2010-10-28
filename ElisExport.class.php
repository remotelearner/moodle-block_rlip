<?php
require_once($CFG->dirroot . '/curriculum/config.php');
require_once($CFG->dirroot . '/blocks/rlip/elis/lib.php');

class ElisExport {
    function cron($manual = false) {
        global $CFG;

        $include_all = false;

        if(!empty($CFG->block_rlip_exportallhistorical)) {
            $include_all = true;
        }

        $this->log_filer = new ipe_log_filer($CFG->block_rlip_logfilelocation, 'export_' . time());

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
            if(manual !== true) {
                mtrace(get_string('filenotdefined', 'block_rlip') . "\n");
            }
            $this->log_filer->lfprintln(get_string('filenotdefined', 'block_rlip'));
            $this->log_filer->output_log();
            return true;
        }

        $exportfilelocation = trim($CFG->block_rlip_exportfilelocation);
        if(!empty($CFG->block_rlip_exportfiletimestamp)) {
            $exportfilelocation = $this->add_timestamp_to_filename($exportfilelocation);
        }

        $sourcefiles = array();
        $destfiles = array();

        // Create user completion data
        if($manual !== true) {
            mtrace(get_string('createdata', 'block_rlip'));
        }
        $records = $this->get_user_data($manual, $include_all);

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
        } else if($last_cron_time = get_field('block', 'lastcron', 'name', 'rlip')) {
            if($include_all !== true) {
                $time_condition = ' AND clsenrol.completetime > ' . $last_cron_time;
            }
        }

        $as = sql_as();
        
        $sql = "SELECT clsenrol.id, usr.idnumber {$as} usridnumber, usr.firstname, usr.lastname, crs.idnumber {$as} crsidnumber, crs.cost,
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
