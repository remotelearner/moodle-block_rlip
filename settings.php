<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


                //PARAM_PATH does not work with windows directories because it will strip away the : symbol and return an error
$settings->add(new admin_setting_configtext('block_rlip_filelocation', get_string('filelocation', 'block_rlip'),
                   get_string('configfilelocation', 'block_rlip'), '', PARAM_PATH, 50));

$settings->add(new admin_setting_configtext('block_rlip_exportfilelocation', get_string('exportfilelocation', 'block_rlip'),
                   get_string('configexportfilelocation', 'block_rlip'), '', PARAM_PATH, 50));

$settings->add(new admin_setting_configcheckbox('block_rlip_exportfiletimestamp', get_string('exportfiletimestamp', 'block_rlip'),
                   get_string('configexportfiletimestamp', 'block_rlip'), '0'));     //unique export file log name

$settings->add(new admin_setting_configtext('block_rlip_logfilelocation', get_string('logfilelocation', 'block_rlip'),
                   get_string('configlogfilelocation', 'block_rlip'), '', PARAM_PATH, 50));

$settings->add(new admin_setting_configtext('block_rlip_emailnotification', get_string('emailnotification', 'block_rlip'),
                   get_string('configemailnotification', 'block_rlip'), '', PARAM_NOTAGS, 50));

$settings->add(new admin_setting_configcheckbox('block_rlip_exportallhistorical', get_string('exportallhistorical', 'block_rlip'),
                   get_string('configexportallhistorical', 'block_rlip'), '0'));

$settings->add(new admin_setting_configcheckbox('block_rlip_creategroups', get_string('creategroups', 'block_rlip'),
                   get_string('configcreategroups', 'block_rlip'), '0'));

$choices = array('M/D/Y' => 'M/D/Y', 'D-M-Y' => 'D-M-Y', 'Y.M.D' => 'Y.M.D');
$settings->add(new admin_setting_configselect('block_rlip_dateformat', get_string('dateformat', 'block_rlip'),
                   get_string('configdateformat', 'block_rlip'), 'M/D/Y', $choices));
?>