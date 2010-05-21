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

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * gives a configuration for the elis dataimport functionality
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generalimport_form extends moodleform {
    /**
     * defines layout for the main dataimport form
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('static', 'description', '', get_string('generalimportinfo', 'block_rlip'));

        $ip_enabled_options = array('on'  => get_string('enabled', 'block_rlip'),
                                    'off' => get_string('disabled', 'block_rlip'));
        $ip_enabled_attributes = array('disabled' => true);
        $mform->addElement('select', 'ip_enabled', get_string('ip_enabled', 'block_rlip'), $ip_enabled_options, $ip_enabled_attributes);
        $mform->setHelpButton('ip_enabled', array('dataimportform/ip_enabled', get_string('ip_enabled', 'block_rlip'), 'block_rlip'));

        //file locations should just be a path not a file name
        $mform->addElement('text', 'block_rlip_filelocation', get_string('filelocation', 'block_rlip') . ': ');
        $mform->setHelpButton('block_rlip_filelocation', array('dataimportform/filelocation', get_string('filelocation', 'block_rlip'), 'block_rlip'));

        $mform->addElement('text', 'block_rlip_exportfilelocation', get_string('exportfilelocation', 'block_rlip') . ': ');
        $mform->setHelpButton('block_rlip_exportfilelocation', array('dataimportform/exportfilelocation', get_string('exportfilelocation', 'block_rlip'), 'block_rlip'));

        $mform->addElement('advcheckbox', 'block_rlip_exportfiletimestamp', get_string('exportfiletimestamp', 'block_rlip') . ': ', null, array('group' => null), array(0, 1));
        $mform->setHelpButton('block_rlip_exportfiletimestamp', array('dataimportform/exportfiletimestamp', get_string('exportfiletimestamp', 'block_rlip'), 'block_rlip'));

        $mform->addElement('text', 'block_rlip_logfilelocation', get_string('logfilelocation', 'block_rlip') . ': ');
        $mform->setHelpButton('block_rlip_logfilelocation', array('dataimportform/logfilelocation', get_string('logfilelocation', 'block_rlip'), 'block_rlip'));

        $mform->addElement('text', 'block_rlip_emailnotification', get_string('emailnotification', 'block_rlip') . ': ');
        $mform->setHelpButton('block_rlip_emailnotification', array('dataimportform/emailnotification', get_string('emailnotification', 'block_rlip'), 'block_rlip'));

        $mform->addElement('advcheckbox', 'block_rlip_exportallhistorical', get_string('exportallhistorical', 'block_rlip') . ': ', null, array('group' => null), array(0, 1));
        $mform->setHelpButton('block_rlip_exportallhistorical', array('dataimportform/exportallhistorical', get_string('exportallhistorical', 'block_rlip'), 'block_rlip'));

        $group = array();
        $group[] = $mform->createElement('submit', 'save', get_string('save', 'block_rlip'));
        $group[] = $mform->createElement('submit', 'import', get_string('import_save', 'block_rlip'));

        $mform->addGroup($group, 'save_buttons');

        $mform->addElement('html', '<br /><br /><p>' . get_string('ip_instructions', 'block_rlip', 'http://remote-learner.net/contactme') . '</p>');
    }

    function set_data($default_values, $slashed=false) {

        $default_values = clone $default_values;

        if(!empty($default_values->ip_enabled)) {
            $default_values->ip_enabled = 'on';
        } else {
            //if rlip is installed then this is always on and enabled leaving this in case it needs to be able to disable
            $default_values->ip_enabled = 'on';
        }

        parent::set_data($default_values, $slashed);
    }

    function definition_after_data() {
        global $CURMAN;

        $mform =& $this->_form;

        $value = $mform->getElementValue('ip_enabled');
        if(is_array($value)) {
            foreach($value as $k => $v) {
                $value = $v;
                break;
            }
        }

        if($value == 'off') {
            $warning_element =& $mform->createElement('static', '', '', '<span class="ip_warning">' . get_string('ip_disabled_warning', 'block_rlip') . '</span>');
            $mform->insertElementBefore($warning_element, 'block_rlip_filelocation');
        }
    }

}

/**
 * display form for picking the file and format to import user records with
 */
class userimport_form extends moodleform {
    /**
     * defines the layout for the user import form
     */
    public function definition() {
        $mform = &$this->_form;

        //just the file name not a path
        $mform->addElement('text', 'block_rlip_impuser_filename', get_string('filename', 'block_rlip') . ': ');
        $plugins = get_import_plugins();
        $mform->addElement('select', 'block_rlip_impuser_filetype', get_string('filetype', 'block_rlip') . ': ', $plugins);

        $mform->addElement('header', 'user_properties', get_string('user_properties', 'block_rlip'));

        $data = user_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', $key, $key . ': ');
        }
        $mform->closeHeaderBefore('save_buttons');

        $group = array();
        $group[] = $mform->createElement('submit', 'save', get_string('save', 'block_rlip'));
        $group[] = $mform->createElement('submit', 'import', get_string('import_save', 'block_rlip'));

        $mform->addGroup($group, 'save_buttons');
    }
}

/**
 * display form for picking the file and format to import course, curriculum, class, track records with
 */
class coursesimport_form extends moodleform {
    /**
     * defines layout for the course import form
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('text', 'block_rlip_impcourse_filename', get_string('filename', 'block_rlip') . ': ');
        $plugins = get_import_plugins();
        $mform->addElement('select', 'block_rlip_impcourse_filetype', get_string('filetype', 'block_rlip') . ': ', $plugins);

        $mform->addElement('header', 'course_properties', get_string('course_properties', 'block_rlip'));
        $data = course_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', 'crs_' . $key, $key . ': ');
        }

        //course import form handles class, track, curriculm, and course import
        $mform->addElement('header', 'class_properties', get_string('class_properties', 'block_rlip'));
        $data = cmclass_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', 'cls_' . $key, $key . ': ');
        }

        $mform->addElement('header', 'track_properties', get_string('track_properties', 'block_rlip'));
        $data = track_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', 'trk_' . $key, $key . ': ');
        }

        $mform->addElement('header', 'curr_properties', get_string('curr_properties', 'block_rlip'));
        $data = curriculum_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', 'cur_' . $key, $key . ': ');
        }
        
        $mform->closeHeaderBefore('save_buttons');

        $group = array();
        $group[] = $mform->createElement('submit', 'save', get_string('save', 'block_rlip'));
        $group[] = $mform->createElement('submit', 'import', get_string('import_save', 'block_rlip'));

        $mform->addGroup($group, 'save_buttons');
    }
}

/**
 * display form for picking the file and format to student and instructor association records
 * in classes and tracks
 */
class enrolmentimport_form extends moodleform {
    /**
     * defines layout for the enrolment import form
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('text', 'block_rlip_impenrolment_filename', get_string('filename', 'block_rlip') . ': ');
        $plugins = get_import_plugins();
        $mform->addElement('select', 'block_rlip_impenrolment_filetype', get_string('filetype', 'block_rlip') . ': ', $plugins);

        $mform->addElement('header', 'enrol_properties', get_string('enrol_properties', 'block_rlip'));
        $data = student_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', $key, $key . ': ');
        }
        $mform->closeHeaderBefore('save_buttons');

        $group = array();
        $group[] = $mform->createElement('submit', 'save', get_string('save', 'block_rlip'));
        $group[] = $mform->createElement('submit', 'import', get_string('import_save', 'block_rlip'));

        $mform->addGroup($group, 'save_buttons');
    }
}

/**
 * based on file names and location this parses through to find other import format files
 * to import based on csv, xml or whatever
 * @return array of plugin imports
 */
function get_import_plugins() {
    global $CFG;

    $blockroot = $CFG->dirroot . '/blocks/rlip/lib';
    $retval = array();
    $plugins = get_list_of_plugins('dataimport', '', $blockroot);

    foreach($plugins as $p) {
        $fullplugin = $blockroot . '/dataimport/' . $p;

        if ( is_readable($fullplugin . '/lib.php')) {
            $k = str_replace('import_', '', $p);
            $retval[$k] = $k;
        }
    }

    return $retval;
}
?>