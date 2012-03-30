<?php

defined('MOODLE_INTERNAL') || die;

require_once(dirname(__FILE__).'/lib.php');

/**
 * Profile field settings
 */

//header and link for configuring export fields
$url = $CFG->wwwroot.'/blocks/rlip/exportplugins/version1/config_fields.php';
$attributes = array('href' => $url,
                    'target' => '_blank');
$displaystring = get_string('configurelink', 'rlipexport_version1');
$headerstring = get_string('configureheader', 'rlipexport_version1');
$tag = html_writer::tag('a', $displaystring, $attributes);
$settings->add(new admin_setting_heading('rlipexport_version1_profilefields', $headerstring, $tag));

// Export filename settings
$settings->add(new admin_setting_configtext('rlipexport_version1/export_path',
                                            get_string('export_path', 'rlipexport_version1'),
                                            get_string('config_export_path', 'rlipexport_version1'), '/rlip/rlipexport_version1'));

$settings->add(new admin_setting_configtext('rlipexport_version1/export_file',
                                            get_string('export_file', 'rlipexport_version1'),
                                            get_string('config_export_file', 'rlipexport_version1'), 'export_version1.csv'));

// timestamp export file
$settings->add(new admin_setting_configcheckbox('rlipexport_version1/export_file_timestamp', get_string('export_file_timestamp_label', 'rlipexport_version1'), get_string('config_export_file_timestamp', 'rlipexport_version1'), 1));

// Log file location
$settings->add(new admin_setting_configtext('rlipexport_version1/logfilelocation',
                                            get_string('logfilelocation', 'rlipexport_version1'),
                                            get_string('configlogfilelocation', 'rlipexport_version1'), $CFG->dataroot .'/rlip/logs/rlipexport_version1/export.log'));

/**
 * Time period settings 
 */

//header
$headerstring = get_string('timeperiodheader', 'rlipexport_version1');
$settings->add(new admin_setting_heading('rlipexport_version1_timeperiod', $headerstring, ''));

//checkbox for enabling
$displaystring = get_string('nonincremental', 'rlipexport_version1');
$infostring = get_string('confignonincremental', 'rlipexport_version1');
$settings->add(new admin_setting_configcheckbox('rlipexport_version1/nonincremental', $displaystring, $infostring, 0));

//time delta
$displaystring = get_string('incrementaldelta', 'rlipexport_version1');
$infostring = get_string('configincrementaldelta', 'rlipexport_version1');
$setting = new admin_setting_configtext('rlipexport_version1/incrementaldelta', $displaystring, $infostring, '1d');
//callback to sanitize time delta
$setting->set_updatedcallback('rlipexport_version1_incrementaldelta_updatedcallback');
$settings->add($setting);

