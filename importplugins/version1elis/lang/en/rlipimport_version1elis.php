<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage importplugins_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2013 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

$rlipshortname = 'DH';

$string['admin'] = 'Site Administrator';
$string['allowduplicateemails'] = 'Allow duplicate email addresses.';
$string['configallowduplicateemails'] = 'If on, duplicate email addresses will be allowed.';
$string['configemailnotification'] = 'A comma-separated list of email addresses to send import log files to.';
$string['config_enrolment_schedule_file'] = 'The filename of the \'enrolment\' '.$rlipshortname.' import file.';
$string['configlogfilelocation'] = 'The directory path, relative to moodledata, to the location of log files.';
$string['config_course_schedule_file'] = 'The filename of the \'course\' '.$rlipshortname.' import file.';
$string['config_schedule_files_path'] = 'The directory path, relative to moodledata, to the location of import files.';
$string['configfieldstreelink'] = 'Field mapping';
$string['configuretitle'] = 'Plugins Blocks: Configure Version 1 ELIS Import Fields';
$string['configcreateorupdate'] = 'If enabled, all create or update actions on users and courses are converted to create
actions if the entity does not already exist, or update actions if the entity exists.';
$string['config_user_schedule_file'] = 'The filename of the \'user\' '.$rlipshortname.' import file.';
$string['coursefile'] = 'Course file';
$string['course_schedule_file'] = 'Course import filename';
$string['coursetab'] = 'Course fields';
$string['createorupdate'] = 'Create or update';
$string['datahandling'] = 'Data handling';
$string['emails'] = 'Emails';
$string['emailnotification'] = 'Email notification';
$string['enrolmentfile'] = 'Enrolment file';
$string['enrolment_schedule_file'] = 'Enrolment import filename';
$string['enrolmenttab'] = 'Enrolment fields';
$string['import_files_path'] = 'Import files path';
$string['importfilesheading'] = 'Scheduled Import File Locations';
$string['logging'] = 'Logging';
$string['logfilelocation'] = 'Log file location';
$string['mappingssaved'] = 'Field mappings successfully saved';
$string['mappingscancelled'] = 'Previously saved field mappings successfully restored';
$string['mappingsreset'] = 'Default field mappings successfully restored';
$string['newenrolmentemailenabledname'] = 'Send New Enrolment Email Notifications';
$string['newenrolmentemailenableddesc'] = 'When a user is enroled into a course with this import plugin, send them an email using the template below.';
$string['newenrolmentemailfromname'] = 'Send Enrolment Email from';
$string['newenrolmentemailfromdesc'] = 'Set who the email should be from.';
$string['newenrolmentemailsubjectname'] = 'New Enrolment Email Notifications Subject';
$string['newenrolmentemailsubjectdesc'] = 'This is the text to use for the subject line of the email.';
$string['newenrolmentemailtemplatename'] = 'New Enrolment Email Notifications Template';
$string['newenrolmentemailtemplatedesc'] = 'If enabled, send users enroled with this plugin the above text. Note that if the above text is empty, no email will be sent.<br />
<b>The following placeholders are available:</b>
<ul>
<li><b>%%sitename%%</b>: The site\'s name.</li>
<li><b>%%user_username%%</b>: The user\'s username.</li>
<li><b>%%user_idnumber%%</b>: The user\'s idnumber.</li>
<li><b>%%user_firstname%%</b>: The user\'s first name.</li>
<li><b>%%user_lastname%%</b>: The user\'s last name.</li>
<li><b>%%user_fullname%%</b>: The user\'s full name.</li>
<li><b>%%user_email%%</b>: The user\'s email address.</li>
<li><b>%%course_fullname%%</b>: The full name of the course..</li>
<li><b>%%course_shortname%%</b>: The shortname of the course.</li>
<li><b>%%course_idnumber%%</b>: The idnumber of the course.</li>
<li><b>%%course_summary%%</b>: The course\'s summary.</li>
</ul>
';
$string['pluginname'] = 'Version 1 ELIS import';
$string['teacher'] = 'Course Teacher';
$string['userfile'] = 'User file';
$string['user_schedule_file'] = 'User import filename';
$string['usertab'] = 'User fields';
