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
 * @copyright  (C) 2008-2009 Remote Learner.net Inc http://www.remote-learner.net
 *
 */
 
    //only care about this if we have the Integration Point enabled
    if(empty($CURMAN->config->ip_enabled)) {
        return;
    }
    
    require_once (dirname(__FILE__) . '/../config.php');
    require_once (CURMAN_DIRLOCATION . '/dataimport/lib.php');

    global $CURMAN, $CFG;

    $context = get_context_instance(CONTEXT_SYSTEM);

    require_capability('block/curr_admin:config', $context);

    $basedir = CURMAN_DIRLOCATION . '/dataimport/';

    if(!isset($action)) {
        $action = cm_get_param('action', 'default');
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
        
        $variable = "imp{$i}_filetype";
        $plugin_name = 'import_' . $CURMAN->config->$variable;
        $plugin = CURMAN_DIRLOCATION . '/dataimport/' . $plugin_name . '/lib.php';

        @include_once($plugin);

        if(class_exists($plugin_name)) {
            $importer = new $plugin_name($logfile);

            if(is_subclass_of($importer, 'elis_import')) {
                $variable = "imp{$i}_filename";
                $success = $importer->import_records($CURMAN->config->filelocation . '/' . $CURMAN->config->$variable, $i);
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
        print '<br />view log at <a href="' . $CFG->wwwroot . '/curriculum/dataimport/viewlog.php?file=' . $logfile . '">log file</a>';
    }
?>