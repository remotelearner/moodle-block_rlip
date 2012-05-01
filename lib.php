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

defined('MOODLE_INTERNAL') || die();

define('IP_SCHEDULE_TIMELIMIT', 2 * 60); // max schedule run time in secs
define('RLIP_MAXRUNTIME_MIN', 28); // minimum maxruntime in secs

//constant for how many log records to show per page
define('RLIP_LOGS_PER_PAGE', 20);

//database table constant
define('RLIP_LOG_TABLE', 'block_rlip_summary_logs');
define('RLIP_SCHEDULE_TABLE', 'block_rlip_schedule');

//constants for temporary import & export directories (wildcard for plugin)
define('RLIP_EXPORT_TEMPDIR', '/rlip/%s/temp/');
define('RLIP_IMPORT_TEMPDIR', '/rlip/%s/temp/');

//the default log path
define('RLIP_DEFAULT_LOG_PATH', '/rlip/log');

require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

/**
 * Settings page that can have child pages
 *
 * Note: This class must implement parentable_part_of_admin_tree in order for
 * children to show up
 */
class rlip_category_settingpage extends admin_settingpage implements parentable_part_of_admin_tree {
    /**
     * Method that satisfies requirements of parent interface but delegates to
     * the admin_settingpage functionality, depsite methods being
     * non-equivalent
     *
     * @param object $setting is the admin_setting object you want to add
     * @param string $bogus only defined to satisfy interface
     * @return bool true if successful, false if not
     */
    public function add($setting, $bogus = '') {
        //note: this is only called as is done for admin_settingpage
        return parent::add($setting);
    }
}

/**
 * External page that can have child pages
 *
 * Note: This class must implement parentable_part_of_admin_tree in order for
 * children to show up
 */
class rlip_category_externalpage extends admin_externalpage implements parentable_part_of_admin_tree {
    /**
    * Method that satisfies requirements of parent interface but delegates to
    * the admin_externalpage functionality, depsite methods being non-equivalent
    *
    * @param object $external is the admin_external object you want to add
    * @param string $bogus only defined to satisfy interface
    * @return bool true if successful, false if not
    */
    public function add($external, $bogus = '') {
        //note: this is only called as is done for admin_externalpage
        return parent::add($external);
    }
}

/**
 * Add extra admintree configuration structure to the main administration menu tree.
 *
 * @uses $CFG
 * @param object &$adminroot Reference to an admin tree object as generated via admin_get_root().
 * @return none
 */
function rlip_admintree_setup(&$adminroot) {
    global $CFG;
    require_once($CFG->dirroot.'/blocks/rlip/lib/rlip_dataplugin.class.php');

    $plugintypes = array('rlipimport', 'rlipexport');

    $displaystring = get_string('plugins', 'block_rlip');
    $externcat = new admin_category('rlipmanageplugins', $displaystring);
    $adminroot->add('blocksettings', $externcat);

    $displaystring = get_string('rlipmanageplugins', 'block_rlip');
    $url = $CFG->wwwroot.'/blocks/rlip/plugins.php';
    $page = new admin_externalpage('rlipsettingplugins', $displaystring, $url, 'moodle/site:config');
    $adminroot->add('rlipmanageplugins', $page);

    foreach ($plugintypes as $plugintype) {
        //obtain the list of plugins of the current type
        if ($plugins = get_plugin_list($plugintype)) {
            ksort($plugins);

            foreach ($plugins as $plugin => $path) {
                $plugsettings = $path.'/settings.php';

                if (file_exists($plugsettings)) {
                    //the plugin has a settings file, so add it to the tree
                    $name = "rlipsetting{$plugintype}_{$plugin}";
                    $displaystring = get_string('pluginname', "{$plugintype}_$plugin");
                    $settings = new rlip_category_settingpage($name, $displaystring);

                    //add the actual settings to the list
                    include($plugsettings);
                    $adminroot->add('rlipmanageplugins', $settings);

                    //perform any customization required by the plugin
                    $instance = rlip_dataplugin_factory::factory("{$plugintype}_{$plugin}");
                    $instance->admintree_setup($adminroot, "rlipsetting{$plugintype}_{$plugin}");
                }
            }
        }
    }

    //add a link for viewing logs
    $displaystring = get_string('logs', 'block_rlip');
    $url = $CFG->wwwroot.'/blocks/rlip/viewlogs.php';
    $page = new admin_externalpage('rliplogs', $displaystring, $url,
                                   'moodle/site:config');
    $adminroot->add('reports', $page);
}

/**
 * Perform page setup for the page that allows you to run tasks manually
 *
 * @param string $baseurl The base page url
 * @param string $plugin_display The display name of the plugin
 */
function rlip_manualrun_page_setup($baseurl, $plugin_display) {
    global $PAGE, $SITE;

    //set up the basic page info
    $PAGE->set_url($baseurl);
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $displaystring = get_string('configuretitle', 'rlipexport_version1');
    $PAGE->set_title("$SITE->shortname: ".$displaystring);
    $PAGE->set_heading($SITE->fullname);

    //use the default admin layout
    $PAGE->set_pagelayout('admin');

    //add navigation items
    $PAGE->navbar->add(get_string('administrationsite'));
    $PAGE->navbar->add(get_string('plugins', 'admin'));
    $PAGE->navbar->add(get_string('blocks'));
    $PAGE->navbar->add(get_string('plugins', 'block_rlip'));
    $PAGE->navbar->add(get_string('rlipmanageplugins', 'block_rlip'), new moodle_url('/blocks/rlip/plugins.php'));
    $PAGE->navbar->add(get_string('runmanually', 'block_rlip'));

    //block css file
    $PAGE->requires->css('/blocks/rlip/styles.css');
}

/**
 * Perform the handling of an uploaded file, including moving it to a non-draft
 * area
 *
 * @param object $data The data submitted by the file upload form
 * @param string $key The key that represents the field containing the file
 *                    "itemid"
 * @return mixed The file record id on success, or false if not selected
 */
function rlip_handle_file_upload($data, $key) {
    global $USER, $DB;

    $result = false;

    //get general file storage object
    $fs = get_file_storage();

    //obtain the listing of files just uploaded
    $usercontext = get_context_instance(CONTEXT_USER, $USER->id);
    $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->$key);

    if ($instanceid = $DB->get_field('block_instances', 'id', array('blockname' => 'rlip'))) {
        //try to use the block context
        $context = get_context_instance(CONTEXT_BLOCK, $instanceid);
    } else {
        //fall back to site context
        $context = get_context_instance(CONTEXT_SYSTEM);
    }

    //set up file parameters
    $file_record = array('contextid' => $context->id,
                         'component' => 'block_rlip',
                         'filearea' => 'files',
                         'filepath' => '/manualupload/');

    //transfer files to a specific area
    foreach ($files as $draftfile) {

        //file API seems to always upload a directory record, so ignore that
        if (!$draftfile->is_directory()) {
            $exists = false;

            //maintain the same filename
            $draft_filename = $draftfile->get_filename();
            $file = $fs->get_file($context->id, 'block_rlip', 'files',
                                  $data->$key, '/manualupload/', $draft_filename);

            if ($file) {
                //file exists
                $exists = true;
                $samesize = ($file->get_filesize() == $draftfile->get_filesize());
                $sametime = ($file->get_timemodified() == $draftfile->get_timemodified());

                //if not the same file, delete it
                if ((!$samesize || !$sametime) && $file->delete()) {
                    $exists = false;
                }
            }

            if (!$exists) {
                //create as new file
                $file = $fs->create_file_from_storedfile($file_record, $draftfile);
            }

            //delete the draft file
            $draftfile->delete();

            //obtain the file record id
            $result = $file->get_id();
        }
    }

    return $result;
}

/**
 * Displays the error message passed
 *
 * @param string $error The error message to display
 */
function rlip_print_error($error = NULL) {
    global $DB, $OUTPUT;

    if (!empty($error)) {
        //display error message as passed
        echo $OUTPUT->box($error, 'generalbox warning manualstatusbox');
    }
}

/**
 * Sanitizes time strings and applies a default value if necessary
 *
 * @param string $time_string A user-entered time string
 * @param string $default The field default
 * @return string The time string with proper formatting and invalid data
 *                removed
 */
function rlip_sanitize_time_string($time_string, $default = '') {
    //valid time units - hours, minutes, seconds
    $valid_units = array('d', 'h', 'm');

    $result = '';
    //track the current "group", e.g. 2d
    $current_group = '';

    //iterate through characters
    for ($i = 0; $i < strlen($time_string); $i++) {
        //retrieve current character
        $character = strtolower(substr($time_string, $i, 1));

        if ($character >= '0' && $character <= '9') {
            //append digit
            $current_group .= $character;
        } else {
            if (in_array($character, $valid_units)) {
                //time unit is valid
                if ($current_group != '') {
                    //a number was specified, so append the "group" to the
                    //result
                    $current_group .= $character;
                    $result .= $current_group;
                }
            }

            //looking for new entry
            $current_group = '';
        }
    }

    if ($result == '') {
        //no valid data, so use the default
        return $default;
    }

    return $result;
}

/**
 * Converts a sanitized time string to a numerical offset
 *
 * @param string $time_string A properly formatted time string
 * @return int The equivalent offset, in seconds
 */
function rlip_time_string_to_offset($time_string) {
    //valid time units - hours, minutes, seconds - plus time values
    $valid_units = array('d' => DAYSECS,
                         'h' => HOURSECS,
                         'm' => MINSECS);

    $result = 0;
    //track the current "group", e.g. 2d
    $current_group = '';

    //iterate through characters
    for ($i = 0; $i < strlen($time_string); $i++) {
        //retrieve current character
        $character = substr($time_string, $i, 1);

        if ($character >= '0' && $character <= '9') {
            //append digit
            $current_group .= $character;
        } else {
            //look up the value of the time unit
            $multiplier = $valid_units[$character];
            //value based on numeric string
            $value = (int)$current_group;
            //add to result
            $result += $multiplier * $value;

            $current_group = '';
        }
    }

    return $result;
}

/**
 * Get scheduled IP jobs
 *
 * @param  string $plugin The IP plugin type:
                          'rlipimport_version1', 'rlipexport_version1', ...
 * @param  int    $userid The desired schedule owner or (default) 0 for all.
 * @uses   $DB
 * @return mixed         Either list of scheduled jobs for IP plugin
 *                       or false if none.
 */
function rlip_get_scheduled_jobs($plugin, $userid = 0) {
    global $DB;
    $taskname = $DB->sql_concat("'ipjob_'", 'ipjob.id');
    $params = array('plugin' => $plugin);
    $sql = "SELECT ipjob.*, usr.username, usr.firstname, usr.lastname,
                   usr.timezone, task.lastruntime, task.nextruntime
              FROM {elis_scheduled_tasks} task
              JOIN {".RLIP_SCHEDULE_TABLE."} ipjob
                ON task.taskname = {$taskname}
              JOIN {user} usr
                ON ipjob.userid = usr.id
             WHERE ipjob.plugin = :plugin ";
    if ($userid) {
        $sql .= 'AND ipjob.userid = :userid ';
        $params['userid'] = $userid;
    }
    return $DB->get_recordset_sql($sql, $params);
}

/**
 * Get scheduled period in minutes
 *
 * @param  string $period  The schedule period in: *d*h*m format
 * @return int             The schedule period in minutes, -1 on error
 */
function rlip_schedule_period_minutes($period) {
    $period_elems = array('d' => DAYSECS/60,
                          'h' => HOURSECS/60,
                          'm' => 1);
    $parray = str_split($period);
    $num = '';
    $min = 0;
    foreach ($parray as $char) {
        if (ctype_space($char)) {
            continue;
        } else if (ctype_digit($char)) {
            $num .= $char;
        } else {
            if (!array_key_exists($char, $period_elems)) {
                return -1; // error
            }
            $multiplier = $period_elems[$char];
            $min += intval($num) * $multiplier;
            $num = '';
        }
    }
    return $min;
}

/**
 * Add schedule job for IP
 *
 * @param  mixed  $data   The scheduled jobs form parameters.
 * @uses   $DB
 * @uses   $USER
 * @return bool           true on success, false on error.
 */
function rlip_schedule_add_job($data) {
    global $DB, $USER;

    //calculate the next run time, for use in both records
    $nextruntime = (int)(time() + rlip_schedule_period_minutes($data['period']) * 60);

    $userid = isset($data['userid']) ? $data['userid'] : $USER->id;
    $data['timemodified'] = time();
    if (isset($data['submitbutton'])) { // formslib!
        unset($data['submitbutton']);
    }
    $ipjob  = new stdClass;
    $ipjob->userid = $userid;
    $ipjob->plugin = $data['plugin'];
    $ipjob->config = serialize($data);

    //store as a redundant copy in order to prevent elis task strangeness
    $ipjob->nextruntime = $nextruntime;

    if (!empty($data['id'])) {
        $ipjob->id = $data['id'];
        $DB->update_record(RLIP_SCHEDULE_TABLE, $ipjob);
        // Must delete any existing task records for the old schedule
        $taskname = 'ipjob_'. $ipjob->id;
        $DB->delete_records('elis_scheduled_tasks', array('taskname' => $taskname));
    } else {
        $ipjob->id = $DB->insert_record(RLIP_SCHEDULE_TABLE, $ipjob);
    }

    $task = new stdClass;
    $task->plugin        = 'block/rlip';
    $task->taskname      = 'ipjob_'. $ipjob->id;
    $task->callfile      = '/blocks/rlip/lib.php';
    $task->callfunction  = serialize('run_ipjob'); // TBD
    $task->lastruntime   = 0;
    $task->blocking      = 0;
    $task->minute        = 0;
    $task->hour          = 0;
    $task->day           = '*';
    $task->month         = '*';
    $task->dayofweek     = '*';
    $task->timezone      = 0;
    $task->enddate       = null;
    $task->runsremaining = null;
    $task->nextruntime   = $nextruntime;
    return $DB->insert_record('elis_scheduled_tasks', $task);
}

/**
 * Delete schedule job for IP
 *
 * @param  int $id  The ID of the scheduled job to delete.
 * @uses   $DB
 * @return bool           true on success, false on error.
 */
function rlip_schedule_delete_job($id) {
    global $DB;
    $DB->delete_records(RLIP_SCHEDULE_TABLE, array('id' => $id));
    $taskname = 'ipjob_'. $id;
    $DB->delete_records('elis_scheduled_tasks', array('taskname' => $taskname));
    return true;
}

/**
 * Get Export filename with optional timestamp in RLIP_EXPORT_TEMPDIR location
 *
 * @param  string         $plugin The RLIP plugin
 * @param  int or string  $tz     The exporting user's timezone
 * @uses   $CFG
 * @return string         The export filename with temp path.
 */
function rlip_get_export_filename($plugin, $tz = 99) {
    global $CFG;
    $tempexportdir = $CFG->dataroot . sprintf(RLIP_EXPORT_TEMPDIR, $plugin);
    $export = basename(get_config($plugin, 'export_file'));
    $timestamp = get_config($plugin, 'export_file_timestamp');
    if (!empty($timestamp)) {
        $timestamp = userdate(time(), get_string('export_file_timestamp',
                                                 $plugin), $tz);
        if (($extpos = strrpos($export, '.')) !== false) {
            $export = substr($export, 0, $extpos) .
                      "_{$timestamp}" . substr($export, $extpos);
        } else {
            $export .= "_{$timestamp}.csv";
        }
    }
    if (!file_exists($tempexportdir) && !@mkdir($tempexportdir, 0777, true)) {
        error_log("/blocks/rlip/lib.php::rlip_get_export_filename('{$plugin}', {$tz}) - Error creating directory: '{$tempexportdir}'");
    }
    return $tempexportdir . $export;
}

/**
 *  sub-function for running scheduled IP jobs
 *
 * @param  string  $prefix    mtrace prefix string
 * @param  string  $plugin    The plugin name
 * @param  string  $type      The plugin type (i.e. rlipimport, rlipexport)
 * @param  int     $userid    the scheduled job's Moodle userid
 * @param  object  $state     the scheduled job's past state object
 * @uses   $CFG
 * @uses   $DB
 * @return object  import/export instance to run,
                   null on error (for unsupported plugin)
 */
function rlip_get_run_instance($prefix, $plugin, $type, $userid, $state) {
    global $CFG, $DB;
    $instance = null;
    switch ($type) { // TBD
        case 'rlipimport':
            $baseinstance = rlip_dataplugin_factory::factory($plugin);
            $entity_types = $baseinstance->get_import_entities();
            $files = array();
            $dataroot = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR);
            $path = $dataroot . DIRECTORY_SEPARATOR .
                    trim(get_config($plugin, 'schedule_files_path'),
                         DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $temppath = sprintf($dataroot . RLIP_IMPORT_TEMPDIR, $plugin);
            if (!file_exists($temppath) && !@mkdir($temppath, 0777, true)) {
                mtrace("{$prefix}: Error creating directory '{$temppath}' ... using '{$path}'");
                //TBD*** just use main directory???
                $temppath = $path;
            }
            foreach ($entity_types as $entity) {
                $entity_filename = get_config($plugin, $entity .'_schedule_file');
                if (empty($entity_filename)) {
                    // TBD: need dummy so we're not testing directories!
                    $entity_filename = $entity .'.csv';
                }
                //echo "\n get_config('{$plugin}', '{$entity}_schedule_file') => {$entity_filename}";
                $files[$entity] = $temppath . $entity_filename;
                if ($state == null && $path !== $temppath &&
                    file_exists($path . $entity_filename) &&
                    !@rename($path . $entity_filename,
                             $temppath . $entity_filename)) {
                    mtrace("{$prefix}: Error moving '".
                           $path . $entity_filename . "' to '".
                           $temppath . $entity_filename . "'");
                }
            }
            $importprovider = new rlip_importprovider_csv($entity_types, $files);
            $instance = rlip_dataplugin_factory::factory($plugin, $importprovider);
            break;

        case 'rlipexport':
            $tz = $DB->get_field('user', 'timezone', array('id' => $userid));
            $export = rlip_get_export_filename($plugin,
                          ($tz === false) ? 99 : $tz);
            $fileplugin = rlip_fileplugin_factory::factory($export, NULL, false);
            $instance = rlip_dataplugin_factory::factory($plugin, NULL, $fileplugin);
            break;

        default:
            mtrace("{$prefix}: IP plugin '{$plugin}' not supported!");
            break;
    }
    return $instance;
}

/**
 *  Callback function for elis_scheduled_tasks IP jobs
 *
 * @param  string  $taskname  The task name, in the form ipjob_{id}, where id
 *                            is the IP job's schedule id
 * @param  int   $maxruntime  Maximum number of secs allowed to process job
 *
 * @return boolean            true on success, otherwise false
 */
function run_ipjob($taskname, $maxruntime = 0) {
    global $CFG, $DB;

    $fcnname = "run_ipjob({$taskname}, {$maxruntime})";
    $disabledincron = get_config('block_rlip', 'disableincron');

    if (empty($maxruntime)) {
        $maxruntime = IP_SCHEDULE_TIMELIMIT;
    }

    require_once($CFG->dirroot .'/blocks/rlip/lib/rlip_dataplugin.class.php');
    require_once($CFG->dirroot .'/blocks/rlip/lib/rlip_fileplugin.class.php');
    require_once($CFG->dirroot .'/blocks/rlip/lib/rlip_importprovider_csv.class.php');

    // Get the schedule record
    list($prefix, $id) = explode('_', $taskname);
    $ipjob = $DB->get_record(RLIP_SCHEDULE_TABLE, array('id' => $id));
    if (empty($ipjob)) {
        mtrace("{$fcnname}: DB Error retrieving IP schedule record - aborting!");
        return false;
    }

    $plugin = $ipjob->plugin;
    $data = unserialize($ipjob->config);
    $state = isset($data['state']) ? $data['state'] : null;

    //determine the "ideal" target start time
    $targetstarttime = $ipjob->nextruntime;

    // Set the next run time & lastruntime
    if ($task = $DB->get_record('elis_scheduled_tasks',
                                array('taskname' => $taskname))) {

        if (empty($disabledincron)) {
            //record last runtime
            $lastruntime = (int)($ipjob->lastruntime);

            //update next runtime on the scheduled task record
            $nextruntime = $ipjob->nextruntime;
            $timenow = time();
            do {
                $nextruntime += (int)rlip_schedule_period_minutes($data['period']) * 60;
            } while ($nextruntime <= ($timenow + 59));
            $task->nextruntime = $nextruntime;

            //update the next runtime on the ip schedule record
            $ipjob->nextruntime = $task->nextruntime;
            $DB->update_record(RLIP_SCHEDULE_TABLE, $ipjob);
        } else {
            // running RLIP cron externally, put times back to pre-run state
            $task->nextruntime = $ipjob->nextruntime;
            $task->lastruntime = $ipjob->lastruntime;
        }
        $DB->update_record('elis_scheduled_tasks', $task);
    } else {
        mtrace("{$fcnname}: DB Error retrieving task record!");
        //todo: return false?
    }

    // Must set last & next run times before exiting!
    if (!empty($disabledincron)) {
        mtrace("{$fcnname}: Internal IP cron disabled by settings - aborting job!");
        return false; // TBD
    }

    // Perform the IP scheduled action
    $instance = rlip_get_run_instance($fcnname, $plugin, $data['type'],
                                      $ipjob->userid, $state);
    if ($instance == null) {
        return false;
    }

    $ipjob->lastruntime = $task->lastruntime;

    //run the task, specifying the ideal start time, maximum run time & state
    if (($newstate = $instance->run($targetstarttime, $lastruntime, $maxruntime, $state)) !== null) {
        // Task did not complete - RESET nextruntime back & save new state!
        mtrace("{$fcnname}: IP scheduled task exceeded time limit of {$maxruntime} secs");
        //update next runtime on the scheduled task record
        $task->nextruntime = $targetstarttime;
        $task->lastruntime = $ipjob->lastruntime = $lastruntime;
        $DB->update_record('elis_scheduled_tasks', $task);
        //update the next runtime on the ip schedule record
        $ipjob->nextruntime = $task->nextruntime;
        $data['state'] = $newstate;
        $ipjob->config = serialize($data);
    } else if ($state !== null) {
        unset($data['state']);
        $ipjob->config = serialize($data);
    }
    $DB->update_record(RLIP_SCHEDULE_TABLE, $ipjob);

    return true;
}

/**
 * Obtains the number of log records currently available for viewing
 *
 * @param string $extrasql  Any extra SQL conditions, like filters ...
 * @param array  $params    Any required SQL parameters
 * @uses  $DB
 */
function rlip_count_logs($extrasql = '', $params = array()) {
    global $DB;

    if (!empty($extrasql)) {
        $extrasql = " WHERE {$extrasql} ";
    }

    //retrieve count
    $sql = "SELECT COUNT(*)
            FROM {".RLIP_LOG_TABLE."} log
            JOIN {user} user
              ON log.userid = user.id
            {$extrasql}
            ORDER BY log.starttime DESC";
    return $DB->count_records_sql($sql, $params);
}

/**
 * Obtains a recordset representing the log records to display for the
 * specified page
 *
 * @param string $where Additional SQL condition to add
 * @param array $params Parameters needed in additional SQL condition
 * @param int $page The page to display, from 0 to n - 1
 * @return object The recordset representing the appropriate data
 */
function rlip_get_logs($where = '', $params = array(), $page = 0) {
    global $DB;

    //where clause
    $where_clause = '';
    if (!empty($where)) {
        $where_clause = "WHERE {$where}";
    }
    //offset, in records
    $offset = $page * RLIP_LOGS_PER_PAGE;
    //retrieve data
    $sql = "SELECT log.*,
                   user.firstname,
                   user.lastname
            FROM {".RLIP_LOG_TABLE."} log
            JOIN {user} user
              ON log.userid = user.id
            {$where_clause}
            ORDER BY log.starttime DESC";
    return $DB->get_recordset_sql($sql, $params, $offset, RLIP_LOGS_PER_PAGE);
}

/**
 * Obtains a table object representing the current page of logs
 *
 * @param object $logs The recordset representing our log data
 * @return object The html table object representing our data set
 */
function rlip_get_log_table($logs) {
    global $DB;

    //used for the display of all time values in this table
    $timeformat = get_string('displaytimeformat', 'block_rlip');

    $table = new html_table();
    //alignment
    $table->align = array('left', 'left', 'left', 'left', 'left',
                          'left', 'left', 'right', 'right', 'left');
    //column headers
    $table->head = array(get_string('logtasktype', 'block_rlip'),
                         get_string('logplugin', 'block_rlip'),
                         get_string('logexecution', 'block_rlip'),
                         get_string('loguser', 'block_rlip'),
                         get_string('logscheduledstart', 'block_rlip'),
                         get_string('logstart', 'block_rlip'),
                         get_string('logend', 'block_rlip'),
                         get_string('logfilesuccesses', 'block_rlip'),
                         get_string('logfilefailures', 'block_rlip'),
                         get_string('logstatus', 'block_rlip'),
                         get_string('logentitytype', 'block_rlip'),
                         get_string('logdownload', 'block_rlip'));

    $table->data = array();

    $logstr = get_string('log', 'block_rlip');

    //fill in table data
    foreach ($logs as $log) {
        // TODO: cache user records here so we aren't constantly fetching records from the DB?
        $user = $DB->get_record('user', array('id' => $log->userid), 'firstname, lastname');

        if ($log->export == 1) {
            //export case

            $plugintype = get_string('export', 'block_rlip');
            //can't have failures in export files
            $filefailures = get_string('na', 'block_rlip');

            $entitytype = get_string('na', 'block_rlip');
        } else {
            $plugintype = get_string('import', 'block_rlip');
            //use tracked number of failures for display
            $filefailures = $log->filefailures;

            $entitytype = $log->entitytype;
        }

        if ($log->targetstarttime == 0) {
            //process was run manually
            $executiontype = get_string('manual', 'block_rlip');
            $targetstarttime = get_string('na', 'block_rlip');
        } else {
            //process was run automatically (cron)
            $executiontype = get_string('automatic', 'block_rlip');
            $targetstarttime = userdate($log->targetstarttime, $timeformat, 99, false);
        }

        // ELIS-5199 Only display a link to the file if a viable file exists
        if (rlip_log_file_exists($log)) {
            $link = "<a href=\"download.php?id=$log->id\">$logstr</a>";
        } else {
            $link = '';
        }

        //construct data row
        $table->data[] = array(
            $plugintype,
            get_string('pluginname', $log->plugin),
            $executiontype,
            fullname($user),
            $targetstarttime,
            userdate($log->starttime, $timeformat, 99, false),
            userdate($log->endtime, $timeformat, 99, false),
            $log->filesuccesses,
            $filefailures,
            $log->statusmessage,
            $entitytype,
            $link
        );
    }

    return $table;
}

/**
 * Convert a table of logs to html
 *
 * @param object $table The html table object to convert
 * @return string The html representing the table
 */
function rlip_log_table_html($table) {
    global $OUTPUT;

    if (empty($table->data)) {
        //no table data, so instead return message
        return $OUTPUT->heading(get_string('nologmessage', 'block_rlip'));
    }

    //obtain table html
    return html_writer::table($table);
}

/**
 * Return the properly formatted log file name
 * @param string $plugin_type Import or Export
 * @param string $plugin The name of the plugin
 * @param string $filepath The path of the log to append to the standardized filename
 * @param boolean $manual True if this is a manual import
 * @param string $timestamp The timestamp used for this import
 * @param string $timeformat The format to use
 * @param string $timezone The timezone being used
 * @return string $logfilename The name of the log file
 */
function rlip_log_file_name($plugin_type, $plugin, $filepath, $entity = '', $manual = false, $timestamp = 0, $format = null, $timezone = 99) {
    global $CFG;

    //if no timeformat is set, set it to logfile timestamp format
    if (empty($format)) {
        $format = get_string('logfile_timestamp','block_rlip');
    }

    //add scheduled/manual to the logfile name
    $scheduling = empty($manual) ? strtolower(get_string('scheduled','block_rlip'))
                                 : strtolower(get_string('manual','block_rlip'));
    //use timestamp passed or time()
    if (empty($timestamp)) {
        $timestamp = time();
    }

    //logfile path is relative to dataroot
    if (!empty($filepath)) {
        $filepath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                    trim($filepath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    } else {
        $filepath = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR) . RLIP_DEFAULT_LOG_PATH . DIRECTORY_SEPARATOR;
    }

    // create directory if it doesn't exist
    if (!file_exists($filepath) && !@mkdir($filepath, 0777, true)) {
        // TBD: log this error to UI and/or elsewhere
        error_log("/blocks/rlip/lib.php::rlip_log_file_name('{$plugin_type}', '{$plugin}', '{$filepath}', '{$entity}', {$manual}, {$timestamp}, {$format}, {$timezone}) - Error creating directory: '{$filepath}'");
    }

    $pluginparts = explode('_', $plugin);
    if (empty($pluginparts[1])) {
        $pluginparts[1] = 'unknown';
    }
    //create filename
    $filename = $filepath . $plugin_type .'_'. $pluginparts[1] .'_'. $scheduling .'_';
    if ($plugin_type == 'import') { //include entity
        $filename .= $entity .'_';
    }
    $filename .= userdate($timestamp, $format, $timezone) .'.log';

    //make sure the filename is unique
    $count = 0;
    $unique_filename = $filename;
    while (file_exists($unique_filename)) {
        $filename_prefix = explode('.',$filename);
        $filename_part = explode('_',$filename_prefix[0]);
        $unique_filename = $filename_prefix[0].'_'.$count.'.log';
        $count++;
    }
    return $unique_filename;
}

/**
 * Task to create a zip file from today's log files
 *
 * @param string $taskname elis_scheduled_tasks task name (unused).
 * @param int    $runtime  elis_scheduled tasks suggested run time (unused).
 * @param int    $time     day to archive logs - default (0) => yesterday's logs
 *                         (used for testing)
 * @uses $CFG
 * @return array           names of zip files created (used for testing)
 */
function rlip_compress_logs_cron($taskname, $runtime = 0, $time = 0) {
    global $CFG;
    $zipfiles = array();
    require_once($CFG->libdir .'/filestorage/zip_archive.php');

    if (empty($time)) {
        $time = time() - DAYSECS; //get yesterday's date
    }

    //the types of plugins we are considering
    $plugintypes = array('rlipimport' => 'import', 'rlipexport' => 'export');
    //lookup for the directory paths for plugins
    $directories = get_plugin_types();
    //Loop through all plugins...
    $timestamp = userdate($time, get_string('logfiledaily_timestamp','block_rlip'), 99);

    foreach ($plugintypes as $plugintype => $pluginvalue) {
        //base directory
        $directory = $directories[$plugintype];

        //obtain plugins and iterate through them
        $plugins = get_plugin_list($plugintype);

        foreach ($plugins as $name => $path) {
            //skip plugins used for testing only
            $instance = rlip_dataplugin_factory::factory("{$plugintype}_{$name}");
            if ($instance->is_test_plugin()) {
                continue;
            }

            //get the display name from the plugin-specific language string
            $plugin_name = "{$plugintype}_{$name}";
            $logfilelocation = get_config($plugin_name, 'logfilelocation');
            $logfilelocation = rtrim($CFG->dataroot, DIRECTORY_SEPARATOR)  . RLIP_DEFAULT_LOG_PATH .
                               DIRECTORY_SEPARATOR . trim($logfilelocation, DIRECTORY_SEPARATOR);
            $logfileprefix = "{$pluginvalue}_{$name}";
            $logfiledate = $timestamp;

            //do a glob of all log files of this plugin name and of the previous day's date
            $files = array();
            foreach (glob("{$logfilelocation}/{$logfileprefix}*{$logfiledate}*.log") as $file) {
                $files[] = $file;
            }

            //create a zip file if there are files to archive
            if (!empty($files)) {
                $zipfile = "{$logfilelocation}/{$logfileprefix}_{$timestamp}.zip";
                //create the archive
                $zip = new zip_archive();
                if (!$zip->open($zipfile)) {
                    continue;
                }
                $zipfiles[] = $zipfile;

                foreach ($files as $file) {
                    //add the file
                    $zip->add_file_from_pathname(basename($file), $file);
                }
                //close the zip -- done!
                $zip->close();

                //remove the archived file(s) from the system
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
    }

    return $zipfiles;
}

/**
 * Compress logs for emailing
 *
 * @param string $plugin The plugin for which we are sending logs
 * @param array $logids The list of database record ids pointing to log files
 * @param boolean $manual True if manual, false if scheduled
 * @return string The name of the appropriate zip file
 */
function rlip_compress_logs_email($plugin, $logids, $manual = false) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/filestorage/zip_archive.php');

    if (empty($logids)) {
        //nothing to compress
        return false;
    }

    //set up the archive
    $archive_name = rlip_email_archive_name($plugin, 0, $manual);
    $path = $CFG->dataroot.'/'.$archive_name;
    $archive = new zip_archive();
    $result = $archive->open($path, file_archive::CREATE);

    //sql fragments to get the logs
    list($sql, $params) = $DB->get_in_or_equal($logids);
    $select = "id {$sql}";

    //add files from log records, tracking whether a valid log path was found
    $found = false;

    if ($records = $DB->get_records_select(RLIP_LOG_TABLE, $select, $params)) {
        foreach ($records as $record) {
            if ($record->logpath != NULL) {
                $archive->add_file_from_pathname(basename($record->logpath), $record->logpath);
                //have at least one file in the zip
                $found = true;
            }
        }
    }

    $archive->close();

    if (!$found) {
        //no logs, so signal that we don't need to send the email
        return false;
    }

    return $archive_name;
}

/**
 * Get the maxruntime for MANUAL import/export runs
 *
 * @return int  The allowed maxruntime (php.ini::max_execution_time - 2)
 *              in seconds
 */
function rlip_get_maxruntime() {
    $maxruntime = (int)ini_get('max_execution_time');
    $maxruntime -= 2; // TBD: MUST STOP BEFORE time limit is reached!
    //echo "\nrlip_get_maxruntime(b):{$maxruntime}\n";
    if ($maxruntime < RLIP_MAXRUNTIME_MIN) {
        $maxruntime = RLIP_MAXRUNTIME_MIN;
    }
    return $maxruntime;
}

/**
 * Initialise the ELIS scheduled tasks system for us, only if scheduled tasks is present and setup on the system.
 *
 * @param none
 * @return none
 */
function rlip_schedulding_init() {
    global $CFG, $DB;

    // Check whether the scheduled tasks table exists
    $dbman = $DB->get_manager();
    $table = new xmldb_table('elis_scheduled_tasks');
    if (!$dbman->table_exists($table)) {
        return;
    }

    // If we haven't setup a scheduled task for the block yet, do so now
    if (!$DB->record_exists('elis_scheduled_tasks', array('plugin' => 'block_rlip'))) {
        require_once($CFG->dirroot.'/elis/core/lib/tasklib.php');

        // Add a cron task for the RLIP block
        elis_tasks_update_definition('block_rlip');
    }
}

/**
 * Obtains the list of email addresses to send zipped logs to
 *
 * @param string $plugin The plugin for which we are sending logs
 * @return array The list of email addresses
 */
function rlip_get_notification_emails($plugin) {
    //obtain the config setting that signals who to send logs to
    $setting = get_config($plugin, 'emailnotification');
    $setting = trim($setting);

    if (empty($setting)) {
        //not set
        return array();
    }

    $result = explode(',', $setting);

    //parse and create a clean array with no extra whitespace in emails, etc
    foreach ($result as $key => $value) {
        $trimmed = trim($value);
        if ($trimmed != '' && validate_email($trimmed)) {
            $result[$key] = $trimmed;
        } else {
            unset($result[$key]);
        }
    }

    //need to re-index for exact comparrison in unit tests
    return array_values($result);
}

/**
 * Obtain an object that we can use as the target user in email_to_user
 *
 * @param string $email Email of the target recipient
 * @return object A user object, either from the db, or a simulated user record
 *                for non-moodle emails
 */
function rlip_get_email_recipient($email) {
    global $DB;

    if ($result = $DB->get_record('user', array('email' => $email))) {
        //user exists, so use their user record
        return $result;
    }

    //fake user record for non-moodle recipient
    $result = new stdClass;
    $result->email = $email;

    return $result;
}

/**
 * Send a log email to a specific recipient
 *
 * @param string $plugin The plugin for which we are sending logs
 * @param object $recipient User record containing email of recipient
 * @param string $archive_name The name of the zip file
 */
function rlip_send_log_email($plugin, $recipient, $archive_name) {
    global $CFG;

    $admin = get_admin();

    //obtain email contents
    $plugindisplay = get_string('pluginname', $plugin);
    $subject = get_string('notificationemailsubject', 'block_rlip', $plugindisplay);
    $message = get_string('notificationemailmessage', 'block_rlip');

    //send the email
    email_to_user($recipient, $admin, $subject, $message, '', $archive_name, $archive_name);
}

/**
 * Send log emails to all appropriate users for one plugin run
 *
 * @param string $plugin The plugin for which we are sending logs
 * @param array $logids The list of database record ids pointing to log files
 * @param boolean $manual True if manual, false if scheduled
 */
function rlip_send_log_emails($plugin, $logids, $manual = false) {
    global $CFG;

    //obtain the sanitized list of emails
    $emails = rlip_get_notification_emails($plugin);

    //create the zip file
    $archive_name = rlip_compress_logs_email($plugin, $logids, $manual);
    if ($archive_name === false) {
        //no logs to send
        return false;
    }

    //send to all appropriate users
    foreach ($emails as $email) {
        $recipient = rlip_get_email_recipient($email);
        rlip_send_log_email($plugin, $recipient, $archive_name);
    }

    @unlink($CFG->dataroot.'/'.$archive_name);

    return true;
}

/**
 * Obtain the standardized name of a log archive file to be included in an email
 *
 * @param string $plugin The plugin for which we are sending logs
 * @param int $time A fixed time to user for naming (use current time if zero)
 * @param boolean $manual True if manual, false if scheduled
 */
function rlip_email_archive_name($plugin, $time = 0, $manual = false) {
    if ($time == 0) {
        //default ot using default time
        $time = time();
    }

    //convert plugin name to prefix
    $plugin_display = $plugin;
    $importpos = strpos($plugin_display, 'rlipimport_');
    $exportpos = strpos($plugin_display, 'rlipexport_');

    if ($importpos === 0) {
       $plugin_display = 'import_'.substr($plugin_display, strlen('rlipimport_'));
    } else if ($exportpos === 0) {
        $plugin_display = 'export_'.substr($plugin_display, strlen('rlipexport_'));
    }

    //execution type logic
    if ($manual) {
        $executiontype = 'manual';
    } else {
        $executiontype = 'scheduled';
    }

    //date component of the file anme
    $date_display = date('M_d_Y_His', $time);

    return $plugin_display.'_'.$executiontype.'_'.$date_display.'.zip';
}

/*
 * Generate the filename used for an archive log based on a given DB log summary record
 *
 * @uses $CFG
 * @uses $DB
 * @@param object|integer $logorid The log record from the DB or the record ID to detect a file for
 * @return string,boolean The full filesystem path to the log file or, False otherwise
 */
function rlip_get_archive_log_filename($logorid) {
    global $CFG, $DB;

    // Check whether a record or record ID was passed in, also return false if neither was specified
    if (is_integer($logorid)) {
        $log = $DB->get_record(RLIP_LOG_TABLE, array('id' => $logorid));
    } else if (is_object($logorid)) {
        $log = $logorid;
    } else {
        return false;
    }

    $pluginname = str_replace(array('rlipimport_', 'rlipexport_'), '', $log->plugin);

    $timestamp  = userdate($log->starttime, get_string('logfiledaily_timestamp','block_rlip'), 99);;
    $archivelog = ($log->export == 1 ? 'export' : 'import').'_'.$pluginname.'_'.$timestamp.'.zip';

    $logflielocation  = '';
    $logfilelocation = get_config($log->plugin, 'logfilelocation');

    if (empty($logfilelocation)) {
        $logprefix = $CFG->dataroot.'/';
    } else {
        // Be sure to handle the fact that there might be a back-slash at the beginning or end of the path config variable
        $logprefix = $CFG->dataroot.(substr($logfilelocation, 0, 1) != '/' ? '/' : '').$logfilelocation.
                     (substr($logfilelocation, -1, 1) != '/' ? '/' : '');
    }

    return $logprefix.$archivelog;
}

/**
 * Determine if a given log record has a log present on the filesystem (handles both archived and non-archive logs).
 *
 * @uses $DB
 * @param object|integer $logorid The log record from the DB or the record ID to detect a file for
 * @return boolean True if a viable file exists, False otherwise
 */
function rlip_log_file_exists($logorid) {
    global $DB;

    // Check whether a record or record ID was passed in, also return false if neither was specified
    if (is_integer($logorid)) {
        $log = $DB->get_record(RLIP_LOG_TABLE, array('id' => $logorid));
    } else if (is_object($logorid)) {
        $log = $logorid;
    } else {
        return false;
    }

    // Check if the log file still exists on the filesystem
    if (!empty($log->logpath) && file_exists($log->logpath)) {
        return true;
    }

    // Check if a zip archive exists for the date the job was started on
    $archivelog = rlip_get_archive_log_filename($log);
    if (!empty($archivelog) && file_exists($archivelog)) {
        return true;
    }

    return false;
}

/**
 * Given an absolute data root path, extract the relative path
 *
 * @param string $path The pre-upgrade filesystem path (relative to root:/)
 * @return boolean|string False when no data root path found; returns the relative path or an empty string when data root
 *                        path is matched
 */
function rlip_data_root_path_translation($path) {
    global $CFG;

    $dataroot = $CFG->dataroot;

    if (strpos($path, $dataroot) === 0) {
        $relativepath = substr($path, strlen($dataroot));
        // Remove trailing slash
        if (substr($relativepath, -1) == DIRECTORY_SEPARATOR) {
            $relativepath = substr($relativepath, 0, -1);
        }

        $leadingslash = substr($relativepath, 0, 1);

        if (!empty($relativepath) && $leadingslash == DIRECTORY_SEPARATOR) {
            return $relativepath;
        } else if (empty($relativepath)) {
            //relativepath is dataroot - return an empty string
            return '';
        }
    }

    return false;
}

