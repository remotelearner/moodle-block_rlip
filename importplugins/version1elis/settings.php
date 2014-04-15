<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @package    block_rlip
 * @subpackage rlipimport_version1elis
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote Learner.net Inc (http://www.remote-learner.net)
 *
 */


//start of "data handling" section
$settings->add(new admin_setting_heading('rlipimport_version1elis/datahandling',
                                         get_string('datahandling', 'rlipimport_version1elis'),
                                         null));

// New identifying field selection section
$settings->add(new admin_setting_configcheckbox('rlipimport_version1elis/identfield_idnumber',
        get_string('identfield_idnumber', 'rlipimport_version1elis'), '', 1));
$settings->add(new admin_setting_configcheckbox('rlipimport_version1elis/identfield_username',
        get_string('identfield_username', 'rlipimport_version1elis'), '', 1));
$settings->add(new admin_setting_configcheckbox('rlipimport_version1elis/identfield_email',
        get_string('identfield_email', 'rlipimport_version1elis'), get_string('configidentfield', 'rlipimport_version1elis'), 1));

//setting for "create or update"
$settings->add(new admin_setting_configcheckbox('rlipimport_version1elis/createorupdate',
                                                get_string('createorupdate', 'rlipimport_version1elis'),
                                                get_string('configcreateorupdate', 'rlipimport_version1elis'), 0));

//start of "scheduling" section
$settings->add(new admin_setting_heading('rlipimport_version1elis/scheduling',
                                         get_string('importfilesheading', 'rlipimport_version1elis'), ''));

//setting for schedule_files_path
$settings->add(new admin_setting_configtext('rlipimport_version1elis/schedule_files_path',
                                            get_string('import_files_path', 'rlipimport_version1elis'),
                                            get_string('config_schedule_files_path', 'rlipimport_version1elis'), '/rlip/rlipimport_version1elis'));

//setting for user_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1elis/user_schedule_file',
                                            get_string('user_schedule_file', 'rlipimport_version1elis'),
                                            get_string('config_user_schedule_file', 'rlipimport_version1elis'), 'user.csv'));

//setting for course_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1elis/course_schedule_file',
                                            get_string('course_schedule_file', 'rlipimport_version1elis'),
                                            get_string('config_course_schedule_file', 'rlipimport_version1elis'), 'course.csv'));

//setting for enrolment_schedule_file
$settings->add(new admin_setting_configtext('rlipimport_version1elis/enrolment_schedule_file',
                                            get_string('enrolment_schedule_file', 'rlipimport_version1elis'),
                                            get_string('config_enrolment_schedule_file', 'rlipimport_version1elis'), 'enroll.csv'));

//start of "logging" section
$settings->add(new admin_setting_heading('rlipimport_version1elis/logging',
                                         get_string('logging', 'rlipimport_version1elis'),
                                         ''));

//log file location
$settings->add(new admin_setting_configtext('rlipimport_version1elis/logfilelocation',
                                            get_string('logfilelocation', 'rlipimport_version1elis'),
                                            get_string('configlogfilelocation', 'rlipimport_version1elis'), RLIP_DEFAULT_LOG_PATH));

//email notification
$settings->add(new admin_setting_configtext('rlipimport_version1elis/emailnotification',
                                            get_string('emailnotification', 'rlipimport_version1elis'),
                                            get_string('configemailnotification', 'rlipimport_version1elis'), ''));

$settings->add(new admin_setting_configcheckbox('rlipimport_version1elis/allowduplicateemails',
                                            get_string('allowduplicateemails','rlipimport_version1elis'),
                                            get_string('configallowduplicateemails','rlipimport_version1elis'), ''));

// Start of "emails" section.
$settings->add(new admin_setting_heading('rlipimport_version1elis/emails', get_string('emails', 'rlipimport_version1elis'), ''));

// Toggle new user email notifications.
$newuseremailenabled = 'rlipimport_version1elis/newuseremailenabled';
$newuseremailenabledname = get_string('newuseremailenabledname', 'rlipimport_version1elis');
$newuseremailenableddesc = get_string('newuseremailenableddesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_configcheckbox($newuseremailenabled, $newuseremailenabledname, $newuseremailenableddesc, '0'));

$newuseremailsubject = 'rlipimport_version1elis/newuseremailsubject';
$newuseremailsubjectname = get_string('newuseremailsubjectname', 'rlipimport_version1elis');
$newuseremailsubjectdesc = get_string('newuseremailsubjectdesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_configtext($newuseremailsubject, $newuseremailsubjectname, $newuseremailsubjectdesc, ''));

$newuseremailtemplate = 'rlipimport_version1elis/newuseremailtemplate';
$newuseremailtemplatename = get_string('newuseremailtemplatename', 'rlipimport_version1elis');
$newuseremailtemplatedesc = get_string('newuseremailtemplatedesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_confightmleditor($newuseremailtemplate, $newuseremailtemplatename, $newuseremailtemplatedesc, '',
        PARAM_RAW, '60', '20'));

// Toggle new enrolment email notifications.
$settingkey = 'rlipimport_version1elis/newenrolmentemailenabled';
$settingname = get_string('newenrolmentemailenabledname', 'rlipimport_version1elis');
$settingdesc = get_string('newenrolmentemailenableddesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_configcheckbox($settingkey, $settingname, $settingdesc, '0'));

$settingkey = 'rlipimport_version1elis/newenrolmentemailfrom';
$settingname = get_string('newenrolmentemailfromname', 'rlipimport_version1elis');
$settingdesc = get_string('newenrolmentemailfromdesc', 'rlipimport_version1elis');
$choices = array(
    'admin' => get_string('admin', 'rlipimport_version1elis'),
    'teacher' => get_string('teacher', 'rlipimport_version1elis')
);
$settings->add(new admin_setting_configselect($settingkey, $settingname, $settingdesc, 'admin', $choices));

$settingkey = 'rlipimport_version1elis/newenrolmentemailsubject';
$settingname = get_string('newenrolmentemailsubjectname', 'rlipimport_version1elis');
$settingdesc = get_string('newenrolmentemailsubjectdesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_configtext($settingkey, $settingname, $settingdesc, ''));

$settingkey = 'rlipimport_version1elis/newenrolmentemailtemplate';
$settingname = get_string('newenrolmentemailtemplatename', 'rlipimport_version1elis');
$settingdesc = get_string('newenrolmentemailtemplatedesc', 'rlipimport_version1elis');
$settings->add(new admin_setting_confightmleditor($settingkey, $settingname, $settingdesc, '', PARAM_RAW, '60', '20'));
