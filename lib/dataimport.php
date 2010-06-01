<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2009 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage curriculummanagement
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */
    
    global $CFG;
    
    require_once ($CFG->dirroot . '/blocks/rlip/elis/lib.php');

    $context = get_context_instance(CONTEXT_SYSTEM);

    require_capability('block/rlip:config', $context);

    if(!isset($action)) {
        $action = optional_param('action', 'default');
    }

    if(empty($action) || strcmp($action, 'default') === 0) {
        $imports = array('user', 'course', 'enrolment');
    } else if(!is_array($action)) {
        $imports = array($action);
    } else {
        $imports = $action;
    }

    $logfile = 'import_' . time();

    $any_success = false;

    foreach($imports as $i) {
        $success = false;
        
        $variable = "block_rlip_imp{$i}_filetype";
        $plugin_name = 'import_' . $CFG->$variable;
        $plugin = RLIP_DIRLOCATION . '/lib/dataimport/' . $plugin_name . '/lib.php';

        @include_once($plugin);

        if(class_exists($plugin_name)) {
            $importer = new $plugin_name($logfile);

            if(is_subclass_of($importer, 'elis_import')) {
                $variable = "block_rlip_imp{$i}_filename";
                $success = $importer->import_records($CFG->block_rlip_filelocation . '/' . $CFG->$variable, $i);
                $any_success = true;
            }
        }

        if(!empty($success)) {
            print 'successfully imported from file: ' . $i . '<br />';
        } else {
            print 'failed to import from file: ' . $i . '<br />';
        }
    }

    if($any_success === true) {
        print '<br />view log at <a href="' . $CFG->wwwroot . '/blocks/rlip/lib/viewlog.php?file=' . $logfile . '">log file</a>';
    }
?>