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

        

        $mform->addElement('html', '<br /><br /><p>' . get_string('ip_instructions', 'block_rlip', 'http://remote-learner.net/contactme') . '</p>');
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

        $ui = new user_import();
        $data = $ui->get_properties_map();
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
class courseimport_form extends moodleform {
    /**
     * defines layout for the course import form
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('text', 'block_rlip_impcourse_filename', get_string('filename', 'block_rlip') . ': ');
        $plugins = get_import_plugins();
        $mform->addElement('select', 'block_rlip_impcourse_filetype', get_string('filetype', 'block_rlip') . ': ', $plugins);

        $mform->addElement('header', 'course_properties', get_string('course_properties', 'block_rlip'));

        $ci = new course_import();
        $data = $ci->get_properties_map();
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
        
        $si = new enrolment_import();
        $data = $si->get_properties_map();
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