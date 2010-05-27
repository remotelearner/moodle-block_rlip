<?php

/**
 * Refactored code from SBCC
 * Actually perform the course content rollover operation.
 * Rollover will restore into a new blank course
 *
 * @param int $from The course ID we are taking content from.
 * @return bool True on success, False otherwise.
 */
function content_rollover($from) {
    global $CFG;

    require_once $CFG->dirroot . '/backup/lib.php';
    require_once $CFG->dirroot . '/backup/backuplib.php';
    require_once $CFG->libdir . '/blocklib.php';
    require_once $CFG->libdir . '/adminlib.php';
    require_once $CFG->libdir . '/xmlize.php';
    require_once $CFG->dirroot . '/course/lib.php';
    require_once $CFG->dirroot . '/backup/restorelib.php';
    require_once $CFG->dirroot . '/backup//bb/restore_bb.php';
    require_once $CFG->libdir . '/wiki_to_markdown.php';


/// Make sure the destination course has the same "format" and structure as the template.
    $coursefrom   = get_record('course', 'id', $from);

/// Proceed with the content rollover...

/// Check necessary functions exists.
    backup_required_functions();

/// Adjust some php variables to the execution of this script
    @ini_set('max_execution_time', '3000');
    if (empty($CFG->extramemorylimit)) {
        raise_memory_limit('128M');
    } else {
        raise_memory_limit($CFG->extramemorylimit);
    }

/// Check backup_version.
//    upgrade_backup_db('curriculum/index.php?s=cur&section=curr');

    $prefs = array(
        'backup_metacourse'   => 0,
        'backup_users'        => 2,
        'backup_logs'         => 0,
        'backup_user_files'   => 0,
        'backup_course_files' => 1,
        'backup_site_files'   => 1,
        'backup_messages'     => 0
    );

    $errorstr = '';

    if (($filename = rollover_backup_course_silently($from, $prefs, $errorstr)) === false) {
        error($errorstr);
        return false;
    }

    flush();

/// Handle the import.
    $errorstr = '';

    $prefs = array(
        'restore_metacourse'   => 0,
        'restore_logs'         => 0,
        'restore_site_files'   => 1,
        'restore_course_files' => 1,
        'restore_messages'     => 0
    );

    $newcourseid = false;
    if (!$newcourseid = rollover_import_backup_file_silently($filename, 0, false, false, $prefs)) {
        error('Error importing course data');
        return false;
    }

    flush();

/// Delete the backup file that was created during this process.
    fulldelete($filename);

    return $newcourseid;
}


/**
* Function to generate the $preferences variable that
* backup uses.  This will back up all modules and instances in a course.
*
* @param object $course course object
* @param array $prefs can contain:
    backup_metacourse
    backup_users
    backup_logs
    backup_user_files
    backup_course_files
    backup_site_files
    backup_messages
* and if not provided, they will not be included.
*/
function rollover_backup_generate_preferences_artificially($course, $prefs) {
    global $CFG;
    $preferences = new StdClass;
    $preferences->backup_unique_code = time();
    $preferences->backup_name = backup_get_zipfile_name($course, $preferences->backup_unique_code);
    $count = 0;

    if ($allmods = get_records("modules") ) {
        foreach ($allmods as $mod) {
            $modname = $mod->name;
            $modfile = "$CFG->dirroot/mod/$modname/backuplib.php";
            $modbackup = $modname."_backup_mods";
            $modbackupone = $modname."_backup_one_mod";
            $modcheckbackup = $modname."_check_backup_mods";
            if (!file_exists($modfile)) {
                continue;
            }
            include_once($modfile);
            if (!function_exists($modbackup) || !function_exists($modcheckbackup)) {
                continue;
            }
            $var = "exists_".$modname;
            $preferences->$var = true;
            $count++;
            // check that there are instances and we can back them up individually
            if (!count_records('course_modules','course',$course->id,'module',$mod->id) || !function_exists($modbackupone)) {
                continue;
            }
            $var = 'exists_one_'.$modname;
            $preferences->$var = true;
            $varname = $modname.'_instances';
            $preferences->$varname = get_all_instances_in_course($modname, $course, NULL, true);
            $instancestopass = array();
            $countinstances = 0;
            foreach ($preferences->$varname as $instance) {
                $preferences->mods[$modname]->instances[$instance->id]->name = $instance->name;
                $var = 'backup_'.$modname.'_instance_'.$instance->id;
                $preferences->$var = true;
                $preferences->mods[$modname]->instances[$instance->id]->backup = true;
                $var = 'backup_user_info_'.$modname.'_instance_'.$instance->id;
                $preferences->$var = false;
                $preferences->mods[$modname]->instances[$instance->id]->userinfo = false;
                $var = 'backup_'.$modname.'_instances';
                $preferences->$var = 1; // we need this later to determine what to display in modcheckbackup.

                $var1 = 'backup_'.$modname.'_instance_'.$instance->id;
                $var2 = 'backup_user_info_'.$modname.'_instance_'.$instance->id;
                if (!empty($preferences->$var1)) {
                    $obj = new StdClass;
                    $obj->name = $instance->name;
                    $obj->userdata = $preferences->$var2;
                    $obj->id = $instance->id;
                    $instancestopass[$instance->id]= $obj;
                    $countinstances++;
                }
            }

            $modcheckbackup($course->id,$preferences->$varname,$preferences->backup_unique_code,$instancestopass);

            //Check data
            //Check module info
            $preferences->mods[$modname]->name = $modname;

            $var = "backup_".$modname;
            $preferences->$var = true;
            $preferences->mods[$modname]->backup = true;

            //Check include user info
            $var = "backup_user_info_".$modname;
            $preferences->$var = false;
            $preferences->mods[$modname]->userinfo = false;

        }
    }

    //Check other parameters
    $preferences->backup_metacourse = (isset($prefs['backup_metacourse']) ? $prefs['backup_metacourse'] : 0);
    $preferences->backup_users = (isset($prefs['backup_users']) ? $prefs['backup_users'] : 0);
    $preferences->backup_logs = (isset($prefs['backup_logs']) ? $prefs['backup_logs'] : 0);
    $preferences->backup_user_files = (isset($prefs['backup_user_files']) ? $prefs['backup_user_files'] : 0);
    $preferences->backup_course_files = (isset($prefs['backup_course_files']) ? $prefs['backup_course_files'] : 0);
    $preferences->backup_site_files = (isset($prefs['backup_site_files']) ? $prefs['backup_site_files'] : 0);
    $preferences->backup_messages = (isset($prefs['backup_messages']) ? $prefs['backup_messages'] : 0);
    $preferences->backup_gradebook_history = (isset($prefs['backup_gradebook_history']) ? $prefs['backup_gradebook_history'] : 0);
    $preferences->backup_blogs = (isset($prefs['backup_blogs']) ? $prefs['backup_blogs'] : 0);
    $preferences->backup_course = $course->id;
    backup_add_static_preferences($preferences);
    return $preferences;
}

/**
 * Function to backup an entire course silently and create a zipfile.
 *
 * @param int $courseid the id of the course
 * @param array $prefs see {@link backup_generate_preferences_artificially}
 */
function rollover_backup_course_silently($courseid, $prefs, &$errorstring) {
    global $CFG, $preferences; // global preferences here because something else wants it :(
    if (!defined('BACKUP_SILENTLY')) {
        define('BACKUP_SILENTLY', 1);
    }
    if (!$course = get_record('course', 'id', $courseid)) {
        debugging("Couldn't find course with id $courseid in backup_course_silently");
        return false;
    }
    $preferences = rollover_backup_generate_preferences_artificially($course, $prefs);
    if (backup_execute($preferences, $errorstring)) {
        return $CFG->dataroot . '/' . $course->id . '/backupdata/' . $preferences->backup_name;
    }
    else {
        return false;
    }
}


/**
 * This function will restore an entire backup.zip into the specified course
 * using standard moodle backup/restore functions, but silently.
 *
 * @see /backup/lib.php
 * @param string $pathtofile the absolute path to the backup file.
 * @param int $destinationcourse the course id to restore to.
 * @param boolean $emptyfirst whether to delete all coursedata first.
 * @param boolean $userdata whether to include any userdata that may be in the backup file.
 * @param array $preferences optional, 0 will be used.  Can contain:
 *   metacourse
 *   logs
 *   course_files
 *   messages
 */
function rollover_import_backup_file_silently($pathtofile,$destinationcourse,$emptyfirst=false,$userdata=false, $preferences=array()) {
    global $CFG,$SESSION,$USER; // is there such a thing on cron? I guess so..
    global $restore; // ick
    if (empty($USER)) {
        $USER = get_admin();
        $USER->admin = 1; // not sure why, but this doesn't get set
    }

    if (!defined('RESTORE_SILENTLY')) {
        define('RESTORE_SILENTLY', true); // don't output all the stuff to us.
    }

    $debuginfo    = 'import_backup_file_silently: ';
    $cleanupafter = false;
    $errorstr     = ''; // passed by reference to restore_precheck to get errors from.

    // first check we have a valid file.
    if (!file_exists($pathtofile) || !is_readable($pathtofile)) {
        mtrace($debuginfo.'File '.$pathtofile.' either didn\'t exist or wasn\'t readable');
        return false;
    }

    // now make sure it's a zip file
    require_once($CFG->dirroot.'/lib/filelib.php');
    $filename = substr($pathtofile,strrpos($pathtofile,'/')+1);
    $mimetype = mimeinfo("type", $filename);
    if ($mimetype != 'application/zip') {
        mtrace($debuginfo.'File '.$pathtofile.' was of wrong mimetype ('.$mimetype.')' );
        return false;
    }

    // restore_precheck wants this within dataroot, so lets put it there if it's not already..
    if (strstr($pathtofile,$CFG->dataroot) === false) {
        // first try and actually move it..
        if (!check_dir_exists($CFG->dataroot.'/temp/backup/',true)) {
            mtrace($debuginfo.'File '.$pathtofile.' outside of dataroot and couldn\'t move it! ');
            return false;
        }
        if (!copy($pathtofile,$CFG->dataroot.'/temp/backup/'.$filename)) {
            mtrace($debuginfo.'File '.$pathtofile.' outside of dataroot and couldn\'t move it! ');
            return false;
        } else {
            $pathtofile = 'temp/backup/'.$filename;
            $cleanupafter = true;
        }
    } else {
        // it is within dataroot, so take it off the path for restore_precheck.
        $pathtofile = substr($pathtofile,strlen($CFG->dataroot.'/'));
    }

    if (!backup_required_functions()) {
        mtrace($debuginfo.'Required function check failed (see backup_required_functions)');
        return false;
    }

    @ini_set('max_execution_time', '3000');
    if (empty($CFG->extramemorylimit)) {
        raise_memory_limit('128M');
    } else {
        raise_memory_limit($CFG->extramemorylimit);
    }

    /*if (!$backup_unique_code = restore_precheck($destinationcourse,$pathtofile,$errorstr,true)) {*/
    if (!$backup_unique_code = restore_precheck(/*NOT NEEDED*/0,$pathtofile,$errorstr,true)) {
        mtrace($debuginfo.'Failed restore_precheck (error was '.$errorstr.')');
        return false;
    }

    $SESSION->restore = new StdClass;

    // add on some extra stuff we need...
    $SESSION->restore->metacourse   = $restore->metacourse = (isset($preferences['restore_metacourse']) ? $preferences['restore_metacourse'] : 0);
    $SESSION->restore->users        = $restore->users = $userdata;
    $SESSION->restore->logs         = $restore->logs = (isset($preferences['restore_logs']) ? $preferences['restore_logs'] : 0);
    $SESSION->restore->user_files   = $restore->user_files = $userdata;
    $SESSION->restore->messages     = $restore->messages = (isset($preferences['restore_messages']) ? $preferences['restore_messages'] : 0);
    //$SESSION->restore->restoreto    = 0; // Make sure we delete content and add everything from the source course.
    $SESSION->restore->restoreto    = RESTORETO_NEW_COURSE;
    $SESSION->restore->course_id    = $restore->course_id = $destinationcourse;
    $SESSION->restore->deleting     = $emptyfirst;
    $SESSION->restore->restore_course_files = $restore->course_files = (isset($preferences['restore_course_files']) ? $preferences['restore_course_files'] : 0);
    $SESSION->restore->restore_site_files = $restore->restore_site_files = (isset($preferences['restore_site_files']) ? $preferences['restore_site_files'] : 0);
    $SESSION->restore->backup_version = $SESSION->info->backup_backup_version;

    // TODO: SET THE PROPER START DATE WITH A CORRECT OFFSET
    $SESSION->restore->course_startdateoffset   = $SESSION->course_header->course_startdate;
    // Set restore groups to 0
    $SESSION->restore->groups                   = $restore->groups = RESTORE_GROUPS_NONE;
    // Set restore cateogry to 0, restorelib.php will look in the backup xml file
    $SESSION->restore->restore_restorecatto     = $restore->restore_restorecatto = 0;
    $SESSION->restore->blogs                    = $restore->blogs = 0;

    restore_setup_for_check($SESSION->restore,$backup_unique_code);

    // maybe we need users (defaults to 2 in restore_setup_for_check)

/*        if (!empty($userdata)) {
        $SESSION->restore->users = 1;
    }
*/
    // we also need modules...
    if ($allmods = get_records('modules')) {
        foreach ($allmods as $mod) {
            $modname = $mod->name;
            //Now check that we have that module info in the backup file
            if (isset($SESSION->info->mods[$modname]) && $SESSION->info->mods[$modname]->backup == "true") {
                $SESSION->restore->mods[$modname]->restore = true;
                $SESSION->restore->mods[$modname]->userinfo = $userdata;
            }
            else {
                // avoid warnings
                $SESSION->restore->mods[$modname]->restore = false;
                $SESSION->restore->mods[$modname]->userinfo = false;
            }
        }
    }
    $restore = clone($SESSION->restore);

    if (!restore_execute($SESSION->restore,$SESSION->info,$SESSION->course_header,$errorstr)) {
        mtrace($debuginfo.'Failed restore_execute (error was '.$errorstr.')');
        return false;
    }

    rebuild_course_cache($SESSION->restore->course_id);

    return $SESSION->restore->course_id;
}
?>
