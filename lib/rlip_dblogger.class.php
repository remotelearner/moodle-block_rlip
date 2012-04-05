<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/blocks/rlip/lib.php');

/**
 * Class for storing import / export progress and logging end result to the
 * database
 */
abstract class rlip_dblogger {
    //this plugin that doing the work
    var $plugin = '';
    //the user running the task
    var $userid;

    //timing values
    var $targetstarttime = 0;
    var $starttime = 0;
    var $endtime = 0;

    //counts were are tracking

    //number of rows successfuly imported from file
    var $filesuccesses = 0;
    //number of rows with error from file
    var $filefailures = 0;
    //number of stored rows successfully impored
    var $storedsuccesses = 0;
    //number of stored rows with error
    var $storedfailures = 0;

    //number of db operations used
    var $dbops = -1;

    //tracks whether an unmet dependency was encountered
    var $unmetdependency = 0;

    //tracks whether maxruntime has been exceeded
    var $maxruntimeexceeded = false;

    // total records to process (import only)
    var $totalrecords = 0;

    //tracks whether we're performing a manual or scheduled run
    var $manual;

    /**
     * DB logger constructor
     *
     * @param boolean $manual true if manual, otherwise false
     */
    function __construct($manual = false) {
        global $USER;

        //set the userid to the global user id
        $this->userid = $USER->id;

        $this->manual = $manual;
    }

    /**
     * Set the plugin that we are logging for
     *
     * @param string $plugin The plugin shortname, such as rlipimport_version1
     */
    function set_plugin($plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Set the target (planned) start time
     *
     * @param int $targetstarttime The target (planned) start time
     */
    function set_targetstarttime($targetstarttime) {
        $this->targetstarttime = $targetstarttime;
    }

    /**
     * Get the target (planned) start time
     *
     * @return int The target (planned) start time
     */
    function get_targetstarttime() {
        return $this->targetstarttime;
    }

    /**
     * Sets the actual start time
     *
     * @param int $starttime The actual start time
     */
    function set_starttime($starttime) {
        $this->starttime = $starttime;
    }

    /**
     * Sets the actual end time
     *
     * @param int $starttime The actual end time
     */
    function set_endtime($endtime) {
        $this->endtime = $endtime;
    }

    /**
     * Store the result of a current row's action
     *
     * @param boolean $success true if row was successfully imported, otherwise
     *                         false
     * @param boolean $fromfile true if the action corresponds to row imported
     *                          from a file, otherwise false
     */
    function track_success($success, $fromfile) {
        if ($fromfile && $success) {
            //from file, success
            $this->filesuccesses++;
        } else if ($fromfile) {
            //from file, failure
            $this->filefailures++;
        } else if (!$fromfile && $success) {
            //stored record, success
            $this->storedsuccesses++;
        } else {
            //stored record, failure
            $this->storedfailures++;
        }
    }

    /**
     * Sets the number of DB ops used
     *
     * @param int $dbops The number of DB ops used
     */
    function set_dbops($dbops) {
        $this->dbops = $dbops;
    }

    /**
     * Signals that an unmet dependency was encountered
     */
    function signal_unmetdependency() {
        $this->unmetdependency = 1;
    }

    /**
     * Reset the state of the logger between executions
     */
    function reset_state() {
        $this->starttime = 0;
        $this->endtime = 0;

        //set all counts back to zero
        $this->filesuccesses = 0;
        $this->filefailures = 0;
        $this->storedsuccesses = 0;
        $this->storedfailures = 0;

        $this->dbops = -1;
        $this->unmetdependency = 0;
        $this->maxruntimeexceeded = false;
        $this->totalrecords = 0;
    }

    /**
     * Flush the current information to a record in the database and reset the
     * state of the logging object
     *
     * @param string $filename The filename for which processing is finished
     */
    function flush($filename) {
        global $DB, $USER;

        //set up our basic log record fields
        $record = new stdClass;
        $record->plugin = $this->plugin;
        $record->userid = $this->userid;
        $record->targetstarttime = $this->targetstarttime;
        $record->starttime = $this->starttime;
        $record->endtime = $this->endtime;
        $record->filesuccesses = $this->filesuccesses;
        $record->filefailures = $this->filefailures;
        $record->storedsuccesses = $this->storedsuccesses;
        $record->storedfailures = $this->storedfailures;
        $record->dbops = $this->dbops;
        $record->unmetdependency = $this->unmetdependency;
        $record->maxruntimeexceeded = $this->maxruntimeexceeded;
        $record->totalrecords = $this->totalrecords;

        //perform any necessary data specialization
        $record = $this->customize_record($record, $filename);

        //persist
        $DB->insert_record(RLIP_LOG_TABLE, $record);
        
        //display, if appropriate
        $this->display_log($record, $filename);

        //reset state
        $this->reset_state();
    }

    /**
     * Specialization function for log records
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @return object The customized version of the record
     */
    abstract function customize_record($record, $filename);

    /**
     * Specialization function for displaying log records in the UI
     *
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     */
    abstract function display_log($record, $filename); 

    /**
     * Sets the total number of records to process
     *
     * @param int $total  the total number of records to process
     */
    function set_totalrecords($total) {
        $this->totalrecords = $total;
    }

    /**
     * Signals that the maxruntime has been exceeded
     */
    function signal_maxruntime_exceeded() {
        $this->maxruntimeexceeded = true;
    }
}

/**
 * Database logging class for imports
 */
class rlip_dblogger_import extends rlip_dblogger {
    /**
     * Specialization function for log records
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @return object The customized version of the record
     */
    function customize_record($record, $filename) {
        if ($this->maxruntimeexceeded) {
            // maxruntime exceeded message
            $record->statusmessage = get_string('manualimportexceedstimelimit',
                                        'block_rlip',
                                        array('filename' => $filename,
                                        'filesuccesses' => $record->filesuccesses,
                                        'totalrecords' => $record->totalrecords));
        } else if ($this->filefailures == 0) {
            //success message
            $record->statusmessage = "All lines from import file {$filename} were successfully processed.";
        } else {
            $record->statusmessage = "One or more lines from import file {$filename} failed because they contain data errors. Please fix the import file and re-upload it.";
        }
        return $record;
    }

    /**
     * Specialization function for displaying log records in the UI
     *
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @uses  $OUTPUT
     */
    function display_log($record, $filename) {
        global $OUTPUT;

        if ($this->manual) {
            if ($this->maxruntimeexceeded) {
                $displaystring = get_string('manualimportexceedstimelimit',
                                     'block_rlip',
                                     array('filename' => $filename,
                                     'filesuccesses' => $record->filesuccesses,
                                     'totalrecords' => $record->totalrecords));
                $css = 'errorbox manualstatusbox';
            } else {
                //total rows = successes + failures
                $record->total = $record->filesuccesses + $record->filefailures;

                //display status message with successes and total records
                $displaystring = get_string('manualstatus', 'block_rlip', $record);
                $css = 'generalbox manualstatusbox';
            }
            echo $OUTPUT->box($displaystring, $css);
        }
    }
}

/**
 * Database logging class for exports
 */
class rlip_dblogger_export extends rlip_dblogger {
    /**
     * Specialization function for log records
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @return object The customized version of the record
     */
    function customize_record($record, $filename) {
        //flag as export
        $record->export = 1;
        //message
        if ($this->maxruntimeexceeded) {
            $record->filesuccesses = 0; // TBD
            // maxruntime exceeded message
            $record->statusmessage = "Export file {$filename} not created - time limit exceeded!";
        } else {
            $record->statusmessage = "Export file {$filename} successfully created.";
        }
        return $record;
    }

    /**
     * Specialization function for displaying log records in the UI
     *
     * @param object $record The log record, with all standard fields included
     * @param string $filename The filename for which processing is finished
     * @uses  $OUTPUT
     */
    function display_log($record, $filename) {
        if ($this->manual && $this->maxruntimeexceeded) {
            global $OUTPUT;
            echo $OUTPUT->box("Export file {$filename} not created - time limit exceeded!", 'errorbox manualstatusbox'); // TBD
        }
    }
}