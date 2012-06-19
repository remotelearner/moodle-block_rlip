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
 * @subpackage importplugins_version1
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

defined('MOODLE_INTERNAL') || die();

$string['columnheader'] = 'Column Header';
$string['completestatusstring'] = 'COMPLETED';
$string['config_export_file'] = 'Enter the filename template to use for exporting data.';
$string['configfieldstreelink'] = 'Field mapping';
$string['configincrementaldelta'] = 'The time delta specifies how far back the manual export includes data from. The time delta must be specified in \*d\*h\*m format, with values representing days, hours and minutes.
This setting only takes effect when using the incremental manual export, and is based on the time at which a user\'s course grade was last modified.
This setting is not used in a scheduled incremental backup. The scheduled incremental backup uses the last schedule run time.';
$string['confignonincremental'] = 'Include all historical data in manual exports';
$string['configuretitle'] = 'Plugins Blocks: Configure Version 1 Export Fields';
$string['configplaceholdersetting'] = 'Replace with real settings';
$string['export_file'] = 'Export filename';
$string['exportfilesheading'] = 'Scheduled Export File Locations';
$string['header_courseidnumber'] = 'Course Idnumber';
$string['header_firstname'] = 'First Name';
$string['header_grade'] = 'Grade';
$string['header_lastname'] = 'Last Name';
$string['header_letter'] = 'Letter';
$string['header_startdate'] = 'Start Date';
$string['header_enddate'] = 'End Date';
$string['header_status'] = 'Status';
$string['header_useridnumber'] = 'User Idnumber';
$string['header_username'] = 'Username';
$string['incrementaldelta'] = 'Time delta for incremental manual export';
$string['nonincremental'] = 'Enable non-incremental export';
$string['placeholdersetting'] = 'Placeholder for settings';
$string['pluginname'] = 'Version 1 ELIS export';
$string['profilefieldname'] = 'Profile Field Name';
$string['profilefieldnotconfig'] = 'There are no profile fields configured.';
$string['timeperiodheader'] = 'Time Period Settings';