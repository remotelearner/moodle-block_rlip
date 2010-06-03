<?php
require_once($CFG->dirroot . '/blocks/rlip/moodle/lib.php');

class MoodleExport {
    public function cron($manual) {
        global $CFG;

        $this->log_filer = new log_filer($CFG->block_rlip_logfilelocation, 'export_' . time());

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

        $all = empty($CFG->block_rlip_exportallhistorical)? false: $CFG->block_rlip_exportallhistorical;
        $records = $this->get_user_data($manual, $all);

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

            if (unlink($localfile)) {
                $this->log_filer->lfprintln(get_string('localfileremoved', 'block_rlip', $localfile));
            } else {
                if($manual !== true) {
                    mtrace(get_string('localfilenotremoved', 'block_rlip', $localfile));
                }
                return false;
            }

        }

        $fp = fopen($localfile, 'w');

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

    private function get_user_data_header() {
        return $header = array(
                 'First Name',
                 'Last Name',
                 'Username',
                 'User Idnumber',
                 'Course Idnumber',
                 'Start Date',
                 'End Date',
                 'Grade'
               );
    }

    private function get_user_data($manual = false, $include_all = false) {
        global $CFG;
        $return = array();
        $i      = 0;

        $sql = "SELECT u.id, u.firstname, u.lastname, u.username usridnumber, c.shortname crsidnumber, gg.finalgrade usergrade, c.startdate timestart
                FROM {$CFG->prefix}grade_items as gi
                JOIN {$CFG->prefix}grade_grades as gg ON gg.itemid = gi.id
                JOIN {$CFG->prefix}user as u ON gg.userid = u.id
                JOIN {$CFG->prefix}course as c ON c.id = gi.courseid
                WHERE itemtype = 'course'
                AND u.deleted = 0";
        
        $users = get_records_sql($sql);
        $now = time();
        
        foreach($users as $userdata) {
            $userdata->timeend = $now;

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
                       $usergrade);

            $i++;

            $a = new stdClass;
            $a->userno = $userno;
            $a->coursecode = $coursecode;

            $this->log_filer->lfprintln(get_string('recordadded', 'block_rlip', $a));
        }


        if (empty($return)) {
            if($manual !== true) {
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
