<?php
/**
 * Execute the RLIP cron method. Meant to be used in conjuction with a separate system-level cron
 * process calling just this script.
 *
 * Useful for when handling large files that would prevent the standard Moodle cron process from
 * executing on a regular schedule.
 *
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2011 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage blocks-rlip
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2011 Remote Learner.net Inc http://www.remote-learner.net
 *
 */


require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once('block_rlip.php');


set_time_limit(0);


define('FULLME', 'cron');


$timenow  = time();

mtrace('Server Time: '.date('r', $timenow)."\n\n");

$block    = get_record('block', 'name', 'rlip');
$blockobj = new block_rlip();

/// Adjust some php variables to the execution of this script
@ini_set('max_execution_time', '3000');
if (empty($CFG->extramemorylimit)) {
    raise_memory_limit('128M');
} else {
    raise_memory_limit($CFG->extramemorylimit);
}

mtrace('Processing cron function for '.$block->name.'....','');

if ($blockobj->cron(false, true)) {
    if (!set_field('block', 'lastcron', $timenow, 'id', $block->id)) {
        mtrace('Error: could not update timestamp for '.$block->name);
    }
} else {
    mtrace('Error: could not execute cron() method for '.$block->name);
}

// Get performance data and display that in with the standard output
$perfinfo = get_performance_info();
mtrace("\n\n".$perfinfo['txt']);

?>
