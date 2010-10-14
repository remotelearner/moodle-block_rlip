<?php
$string['class_properties'] = 'Class properties map';
$string['couldnotopenexportfile'] = 'Could not open export file: $a';
$string['course'] = 'Course';
$string['course_properties'] = 'Course properties map';
$string['curr_properties'] = 'Curriculum properties map';
$string['dataimport'] = 'Integration Point';
$string['delete_failed'] = 'Unable to remove import file<br />';
$string['disabled'] = 'Disabled';
$string['emailnotification'] = 'Email notification';
$string['enabled'] = 'Enabled';
$string['enrolment'] = 'Enrollment';
$string['enrol_properties'] = 'Enrolment properties map';

$string['export_location_missing'] = 'Export file location is missing. Please ensure rlip was set up correctly.';
$string['export_now'] = 'Export now';
$string['exportallhistorical'] = 'Include all historical data';
$string['exportfilelocation'] = 'Export file location';
$string['exportfiletimestamp'] = 'Unique export file names';

$string['filelocation'] = 'Import file location';
$string['filename'] = 'File name';
$string['filetype'] = 'File type';
$string['general'] = 'General';
$string['generalimportinfo'] = 'This checks for specially formatted files in a specific location and imports data into the curriculum management system.<br />';

$string['import_all'] = 'Import all';
$string['import_location_missing'] = 'Import file location is missing. Please ensure rlip was set up correctly.';
$string['import_save'] = 'Save and process';

$string['ip_disabled_warning'] = 'WARNING! Integration Point functionality is an additional paid service and has not been enabled on this site. As such, the settings in this section will not be used. Please contact Remote-Learner for assistance on this topic via the link provided below.';
$string['ip_enabled'] = 'Integration Point Functionality';
$string['ip_instructions'] = 'Integration Point is an additional paid service offered by Remote-Learner that allows for the import and export of completion data. If this Integration Point functionality is disabled, you may contact <a href=\"$a\" target=\"_blank\">Remote-Learner</a> to sign up. Once enabled, import and export file locations can be set up appropriately on this configuration screen.';
$string['ip_link'] = 'Integration Point page';
$string['ip_log'] = 'Integration point Log';

$string['logfilelocation'] = 'Log file location';
$string['overrideelisip'] = 'Use IP Basic';
$string['save'] = 'Save';
$string['title'] = 'Integration Point';
$string['track_properties'] = 'Track properties map';
$string['user'] = 'User';
$string['user_properties'] = 'User properties map';

$string['configcreategroups'] = 'If set, will automatically create new groups and groupings in the specified course.';
$string['configdateformat'] = 'Set date format expected from import.';
$string['configemailnotification'] = 'A comma-separated list of idnumbers of users to be sent the log of actions via email (blank for no notification).';
$string['configexportallhistorical'] = 'Selecting this option will include all historical completion information in both the manual and scheduled executions of the export. If not selected, only data modified within the last day will be included during manual executions, and only data modified since the last scheduled execution is included for scheduled executions.';
$string['configexportfilelocation'] = 'This defines the absolute path to the export file that you wish to use. This ' .
                                      'is not a directory location, but the exact location of the export file itself. ' .
                                      'If you do not with to create an export file, leave this option empty.';
$string['configexportfiletimestamp'] = 'This allows the Integration Point export to append a unique timestamp to export files.';
$string['configfilelocation'] = 'This defines the absolute path to the directory that is checked for import files.';
$string['configlogfilelocation'] = 'This defines the directory to store all import and export log files.';
$string['configoverrideelisip'] = 'Ignore ELIS IP and use IP Basic (Moodle only)';

$string['ip_export_timespan'] = 'Note: When running a test export from this page, only class completions from up to one day ago will be included, unless the \"Save and process all\" button is used.';

$string['ip_description'] = 'Integration Point is used to import data in a CSV format from a back end system, in a standard, documented, tested, and supported (upgrade safe) manner. Integration Point is pluggable and standardized and enables incremental data import. <br />
Integration Point sets input data as profile fields and assigns roles from the imported data, as well as providing a logging interface to enable Remote-Learner support and client side testing and ongoing QA and failure recovery processes. The User, Course, and Enrollment tabs contain a filename input field, which determines the name of the file used for the import, within the “Import File Location” (set in the Integration Point global block configuration options) directory. <br />
Each tab also specifies the mapping between each moodle fields and the fields within the import file. The import is done on a regular schedule every 24 hours or whenever save and import is selected, and export is run every 24 hours. For sample file formats and examples please contact Remote-Learner.';

$string['blockname'] = 'Course Completion Export';
$string['createdata'] = 'Create user completion data';
$string['createdemptyfile'] = 'Created export file $a with no data';
$string['creategroups'] = 'Create groups and groupings';
$string['dateformat'] = 'Import date format';
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