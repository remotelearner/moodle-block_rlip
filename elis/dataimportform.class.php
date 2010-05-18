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
class generalimport_form extends cmform {
    /**
     * defines layout for the main dataimport form
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('static', 'description', '', get_string('generalimportinfo', 'block_curr_admin'));

        $ip_enabled_options = array('on'  => get_string('enabled', 'block_curr_admin'),
                                    'off' => get_string('disabled', 'block_curr_admin'));
        $ip_enabled_attributes = array('disabled' => true);
        $mform->addElement('select', 'ip_enabled', get_string('ip_enabled', 'block_curr_admin'), $ip_enabled_options, $ip_enabled_attributes);
        $mform->setHelpButton('ip_enabled', array('dataimportform/ip_enabled', get_string('ip_enabled', 'block_curr_admin'), 'block_curr_admin'));

        //file locations should just be a path not a file name
        $mform->addElement('text', 'filelocation', get_string('filelocation', 'block_curr_admin') . ': ');
        $mform->setHelpButton('filelocation', array('dataimportform/filelocation', get_string('filelocation', 'block_curr_admin'), 'block_curr_admin'));

        $mform->addElement('text', 'exportfilelocation', get_string('exportfilelocation', 'block_curr_admin') . ': ');
        $mform->setHelpButton('exportfilelocation', array('dataimportform/exportfilelocation', get_string('exportfilelocation', 'block_curr_admin'), 'block_curr_admin'));

        $mform->addElement('advcheckbox', 'exportfiletimestamp', get_string('exportfiletimestamp', 'block_curr_admin') . ': ', null, array('group' => null), array(0, 1));
        $mform->setHelpButton('exportfiletimestamp', array('dataimportform/exportfiletimestamp', get_string('exportfiletimestamp', 'block_curr_admin'), 'block_curr_admin'));

        $mform->addElement('text', 'logfilelocation', get_string('logfilelocation', 'block_curr_admin') . ': ');
        $mform->setHelpButton('logfilelocation', array('dataimportform/logfilelocation', get_string('logfilelocation', 'block_curr_admin'), 'block_curr_admin'));

        $mform->addElement('text', 'emailnotification', get_string('emailnotification', 'block_curr_admin') . ': ');
        $mform->setHelpButton('emailnotification', array('dataimportform/emailnotification', get_string('emailnotification', 'block_curr_admin'), 'block_curr_admin'));

        $mform->addElement('advcheckbox', 'exportallhistorical', get_string('exportallhistorical', 'block_curr_admin') . ': ', null, array('group' => null), array(0, 1));
        $mform->setHelpButton('exportallhistorical', array('dataimportform/exportallhistorical', get_string('exportallhistorical', 'block_curr_admin'), 'block_curr_admin'));

        $group = array();
        $group[] = $mform->createElement('submit', 'save', get_string('save', 'block_curr_admin'));
        $group[] = $mform->createElement('submit', 'import', get_string('import_save', 'block_curr_admin'));

        $mform->addGroup($group, 'save_buttons');

        $mform->addElement('html', '<br /><br /><p>' . get_string('ip_instructions', 'block_curr_admin', 'http://remote-learner.net/contactme') . '</p>');
    }

    function set_data($default_values, $slashed=false) {

    $default_values = clone $default_values;

        if(!empty($default_values->ip_enabled)) {
            $default_values->ip_enabled = 'on';
        } else {
            $default_values->ip_enabled = 'off';
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
            $warning_element =& $mform->createElement('static', '', '', '<span class="ip_warning">' . get_string('ip_disabled_warning', 'block_curr_admin') . '</span>');
            $mform->insertElementBefore($warning_element, 'filelocation');
        }
    }

}

/**
 * display form for picking the file and format to import user records with
 */
class userimport_form extends cmform {
    /**
     * defines the layout for the user import form
     */
    public function definition() {
        require_once(CURMAN_DIRLOCATION . '/lib/user.class.php');
        $mform = &$this->_form;

        //just the file name not a path
        $mform->addElement('text', 'impuser_filename', get_string('filename', 'block_curr_admin') . ': ');
        $plugins = get_import_plugins();
        $mform->addElement('select', 'impuser_filetype', get_string('filetype', 'block_curr_admin') . ': ', $plugins);

        $mform->addElement('header', 'user_properties', get_string('user_properties', 'block_curr_admin'));

        $data = user_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', $key, $key . ': ');
        }
        $mform->closeHeaderBefore('save_buttons');

        $group = array();
        $group[] = $mform->createElement('submit', 'save', get_string('save', 'block_curr_admin'));
        $group[] = $mform->createElement('submit', 'import', get_string('import_save', 'block_curr_admin'));

        $mform->addGroup($group, 'save_buttons');
    }
}

/**
 * display form for picking the file and format to import course, curriculum, class, track records with
 */
class coursesimport_form extends cmform {
    /**
     * defines layout for the course import form
     */
    public function definition() {
        require_once(CURMAN_DIRLOCATION . '/lib/course.class.php');
        require_once(CURMAN_DIRLOCATION . '/lib/cmclass.class.php');
        require_once(CURMAN_DIRLOCATION . '/lib/track.class.php');
        require_once(CURMAN_DIRLOCATION . '/lib/curriculum.class.php');

        $mform = &$this->_form;

        $mform->addElement('text', 'impcourse_filename', get_string('filename', 'block_curr_admin') . ': ');
        $plugins = get_import_plugins();
        $mform->addElement('select', 'impcourse_filetype', get_string('filetype', 'block_curr_admin') . ': ', $plugins);

        $mform->addElement('header', 'course_properties', get_string('course_properties', 'block_curr_admin'));
        $data = course_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', 'crs_' . $key, $key . ': ');
        }

        //course import form handles class, track, curriculm, and course import
        $mform->addElement('header', 'class_properties', get_string('class_properties', 'block_curr_admin'));
        $data = cmclass_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', 'cls_' . $key, $key . ': ');
        }


        $mform->addElement('header', 'track_properties', get_string('track_properties', 'block_curr_admin'));
          $data = track_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', 'trk_' . $key, $key . ': ');
        }

        $mform->addElement('header', 'curr_properties', get_string('curr_properties', 'block_curr_admin'));
        $data = curriculum_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', 'cur_' . $key, $key . ': ');
        }
        $mform->closeHeaderBefore('save_buttons');

        $group = array();
        $group[] = $mform->createElement('submit', 'save', get_string('save', 'block_curr_admin'));
        $group[] = $mform->createElement('submit', 'import', get_string('import_save', 'block_curr_admin'));

        $mform->addGroup($group, 'save_buttons');
    }
}

/**
 * display form for picking the file and format to student and instructor association records
 * in classes and tracks
 */
class enrolmentimport_form extends cmform {
    /**
     * defines layout for the enrolment import form
     */
    public function definition() {
        require_once(CURMAN_DIRLOCATION . '/lib/student.class.php');
        $mform = &$this->_form;

        $mform->addElement('text', 'impenrolment_filename', get_string('filename', 'block_curr_admin') . ': ');
        $plugins = get_import_plugins();
        $mform->addElement('select', 'impenrolment_filetype', get_string('filetype', 'block_curr_admin') . ': ', $plugins);

        $mform->addElement('header', 'enrol_properties', get_string('enrol_properties', 'block_curr_admin'));
        $data = student_import::get_properties_map();
        foreach($data as $key => $p) {
            $mform->addElement('text', $key, $key . ': ');
        }
        $mform->closeHeaderBefore('save_buttons');

        $group = array();
        $group[] = $mform->createElement('submit', 'save', get_string('save', 'block_curr_admin'));
        $group[] = $mform->createElement('submit', 'import', get_string('import_save', 'block_curr_admin'));

        $mform->addGroup($group, 'save_buttons');
    }
}

/**
 * based on file names and location this parses through to find other import format files
 * to import based on csv, xml or whatever
 * @return array of plugin imports
 */
function get_import_plugins() {
    $retval = array();
    $plugins = get_list_of_plugins('dataimport', '', CURMAN_DIRLOCATION);

    foreach($plugins as $p) {
        $fullplugin = CURMAN_DIRLOCATION . '/dataimport/' . $p;

        if ( is_readable($fullplugin . '/lib.php')) {
            $k = str_replace('import_', '', $p);
            $retval[$k] = $k;
        }
    }

    return $retval;
}
?>
