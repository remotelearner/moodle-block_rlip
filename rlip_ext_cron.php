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
 * @package    rlip
 * @subpackage blocks_rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

// External RLIP 'cron' processing file
define('CLI_SCRIPT', 1);

require_once(dirname(__FILE__) .'/../../config.php');
require_once($CFG->dirroot .'/elis/core/lib/tasklib.php');
require_once($CFG->dirroot .'/blocks/rlip/lib.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_dataplugin.class.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_fileplugin.class.php');
require_once($CFG->dirroot .'/blocks/rlip/rlip_importprovider_csv.class.php');

$filename = basename(__FILE__);
$disabledincron = get_config('rlip', 'disableincron');
if (empty($disabledincron)) {
    exit(0);
}

// TBD: adjust some php variables for the execution of this script
set_time_limit(0);
@ini_set('max_execution_time', '3000');
if (empty($CFG->extramemorylimit)) {
    raise_memory_limit('128M');
} else {
    raise_memory_limit($CFG->extramemorylimit);
}

mtrace('RLIP external cron start - Server Time: '.date('r', time())."\n\n");

$pluginstorun = array('rlipimport', 'rlipexport');

$timenow = time();
$params = array('timenow' => $timenow);
$tasks = $DB->get_recordset_select('elis_scheduled_tasks', 'nextruntime <= :timenow', $params, 'nextruntime ASC');
if ($tasks && $tasks->valid()) {
    foreach ($tasks as $task) {
        // Make sure we have an import/export task
        list($prefix, $id) = explode('_', $taskname);
        if ($prefix !== 'ipjob') {
            continue;
        }

        // Get ipjob from ip_schedule
        $ipjob = $DB->get_record('ip_schedule', array('id' => $id));
        if (empty($ipjob)) {
            mtrace("{$filename}: DB Error retrieving IP schedule record for taskname '{$task->taskname}' - aborting!");
            exit(1);
        }

        // validate plugin
        $plugin = $ipjob->plugin;
        $plugparts = explode('_', $plugin);
        if (!in_array($plugparts[0], $pluginstorun)) {
            mtrace("{$filename}: RLIP plugin '{$plugin}' not configured to run externally - aborting!");
            exit(1);
        }

        $rlip_plugins = get_plugin_list($plugparts[0]);
        //print_object($rlip_plugins);
        if (!array_key_exists($plugparts[1], $rlip_plugins)) {
            mtrace("{$filename}: RLIP plugin '{$plugin}' unknown!");
            exit(1);
        }

        mtrace("{$filename}: Processing external cron function for: {$plugin}, taskname: {$task->taskname} ...");

        //determine the "ideal" target start time
        $targetstarttime = $ipjob->nextruntime;

        // Set the next run time & lastruntime
        //record last runtime
        $lastruntime = $ipjob->lastruntime;

        //update next runtime on the scheduled task record
        $nextruntime = $ipjob->nextruntime;
        $timenow = time();
        do {
            $nextruntime += (int)rlip_schedule_period_minutes($data['period']) * 60;
        } while ($nextruntime <= $timenow);
        $task->nextruntime = $nextruntime;
        $DB->update_record('elis_scheduled_tasks', $task);

        //update the next runtime on the ip schedule record
        $ipjob->nextruntime = $task->nextruntime;
        $ipjob->lastruntime = $timenow;
        $DB->update_record('ip_schedule', $ipjob);

        switch ($plugparts[0]) {
            case 'rlipimport':
                $baseinstance = rlip_dataplugin_factory::factory($plugin);
                $entity_types = $baseinstance->get_import_entities();
                $files = array();
                $path = get_config($plugin, 'schedule_files_path');
                if (strrpos($path, '/') !== strlen($path) - 1) {
                    $path .= '/';
                }
                foreach ($entity_types as $entity) {
                    $files[$entity] = $path . get_config($plugin, $entity .'_schedule_file');
                }
                $importprovider = new rlip_importprovider_csv($entity_types, $files);
                $instance = rlip_dataplugin_factory::factory($plugin, $importprovider);
                break;

            case 'rlipexport':
                $user = get_complete_user_data('id', $userid);
                $export = rlip_get_export_filename($plugin,
                          empty($user) ? 99 : $user->timezone);
                $fileplugin = rlip_fileplugin_factory::factory($export, NULL, false);
                $instance = rlip_dataplugin_factory::factory($plugin, NULL, $fileplugin);
                break;

            default:
                mtrace("{$filename}: RLIP plugin '{$plugin}' not supported!");
                continue;
        }

        $instance->run($targetstarttime, $lastruntime);
    }
}

mtrace('RLIP external cron end - Server Time: '.date('r', time())."\n\n");

// end of file
