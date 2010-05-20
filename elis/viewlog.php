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

require_once ('../../../config.php');
require_once ('../../../curriculum/config.php');

$context = get_context_instance(CONTEXT_SYSTEM);

require_capability('block/rlip:config', $context);

$filename = optional_param('file', '', PARAM_CLEAN);

$file_location = $CURMAN->config->logfilelocation;

//since we can't include files that are not in a web viewable directory
if(empty($filename)) {
    $files = scandir($file_location);

    if(!empty($files)) {
        $import_files = preg_grep('/import_.*\.log/', $files);
        if(!empty($import_files)) {
            foreach($import_files as $f) {
                $filein = fopen($file_location . '/' . $f, 'r');

                if(!empty($filein)) {
                    while(!feof($filein)) {
                        echo fgets($filein) . '<br />';
                    }
                }

                fclose($filein);
            }
        }
    }
} else if(strpos($filename, 'import_') === 0) {
    @$filein = fopen($file_location . '/' . $filename . '.log', 'r');

    if(!empty($filein)) {
        while(!feof($filein)) {
            echo fgets($filein) . '<br />';
        }
    } else {
        echo "missing file $filename";
    }
} else {
    redirect($CFG->wwwroot);
}


?>