<?php
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
