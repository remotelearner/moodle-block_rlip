<?php
/**
 * Returns true if site is using ELIS/CM.
 * Returns false if not ELIS, or if site has been configured to use non-ELIS IP.
 *
 * @param boolean $ignoreoverride Ignores the override ELIS IP setting. Used if
 * we need to know if ELIS is installed regardless of the config setting (like on
 * the settings page).
 */
function is_elis($ignoreoverride=false) {
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
abstract class log_filer {
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

function throwException($message = null, $code = null) {
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

?>