<?php
$string['class_properties'] = 'Class properties map';
$string['course'] = 'Course';
$string['course_properties'] = 'Course properties map';
$string['curr_properties'] = 'Curriculum properties map';
$string['dataimport'] = 'Integration Point';
$string['disabled'] = 'Disabled';
$string['emailnotification'] = 'Email notification';
$string['enabled'] = 'Enabled';
$string['enrolment'] = 'Enrollment';
$string['enrol_properties'] = 'Enrolment properties map';
$string['exportallhistorical'] = 'Include all historical data';
$string['exportfilelocation'] = 'Export file location';
$string['exportfiletimestamp'] = 'Unique export file names';
$string['filelocation'] = 'Import file location';
$string['filename'] = 'File name';
$string['filetype'] = 'File type';
$string['general'] = 'General';
$string['generalimportinfo'] = 'This checks for specially formatted files in a specific location and imports data into the curriculum management system.<br />';
$string['import_save'] = 'Save and process';
$string['ip_disabled_warning'] = 'WARNING! Integration Point functionality is an additional paid service and has not been enabled on this site. As such, the settings in this section will not be used. Please contact Remote-Learner for assistance on this topic via the link provided below.';
$string['ip_enabled'] = 'Integration Point Functionality';
$string['ip_instructions'] = 'Integration Point is an additional paid service offered by Remote-Learner that allows for the import and export of completion data. If this Integration Point functionality is disabled, you may contact <a href=\"$a\" target=\"_blank\">Remote-Learner</a> to sign up. Once enabled, import and export file locations can be set up appropriately on this configuration screen.';
$string['ip_link'] = 'Integration Point page';
$string['logfilelocation'] = 'Log file location';
$string['save'] = 'Save';
$string['title'] = 'Integration Point';
$string['track_properties'] = 'Track properties map';
$string['user'] = 'User';
$string['user_properties'] = 'User properties map';

$string['configfilelocation'] = 'This defines the absolute path to the directory that is checked for import files.';
$string['configexportfilelocation'] = 'This defines the absolute path to the directory that stores export files.';
$string['configexportfiletimestamp'] = 'This allows the Integration Point export to append a unique timestamp to export files.';
$string['configlogfilelocation'] = 'This defines the directory to store all import and export log files.';
$string['configemailnotification'] = 'A comma-separated list of idnumbers of users to be sent the log of actions via email (blank for no notification).';
$string['configexportallhistorical'] = 'Selecting this option will include all historical completion information in both the manual and scheduled executions of the export. If not selected, only data modified within the last day will be included during manual executions, and only data modified since the last scheduled execution is included for scheduled executions.';

$string['ip_export_timespan'] = 'Note: When running a test export from this page, only class completions from up to one day ago will be included, unless the \"Save and process all\" button is used.';

$string['ip_description'] = 'ip description to fill in';

$string['blockname'] = 'Course Completion Export';
$string['createdata'] = 'Create user completion data';
$string['createdemptyfile'] = 'Created export file $a with no data';
$string['filenotdefined'] = 'Export file not defined';
$string['filerecordwriteerror'] = 'Unable to write line $a';
$string['filewriteerror'] = 'Unable to write header to local file $a';
$string['localfileexists'] = 'Local file $a exists, removing local file before proceeding';
$string['localfileremoved'] = 'Local file $a removed';
$string['localfilenotremoved'] = 'Unable to remove old local file $a';
$string['nouserdata'] = 'No user data to process';
$string['nodata'] = 'No user completion data to create';
$string['noparams'] = 'Parameters not initialized, cannot create local csv file for upload';
$string['recordadded'] = 'User Data: User idnumber $a->userno - Course idnumber $a->coursecode has been added';
$string['skiprecord'] = 'Either the user idnumber $a->usridnumber or course idnumber $a->crsidnumber was empty - skipping record because of missing required field(s)';
?>