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
class ipb_generalimport_form extends moodleform {
    /**
     * defines layout for the main dataimport form
     */
    public function definition() {
        global $CFG;

        $mform = &$this->_form;

        if(!empty($CFG->block_rlip_filelocation) || !empty($CFG->block_rlip_exportfilelocation)) {
            if(!empty($CFG->block_rlip_filelocation)) {
                $mform->addElement('submit', 'import', get_string('import_all', 'block_rlip'));
            } else {
                $mform->addElement('html', '<p>' . get_string('import_location_missing', 'block_rlip'));
            }

            if(!empty($CFG->block_rlip_exportfilelocation)) {
                $mform->addElement('submit', 'export', get_string('export_now', 'block_rlip'));
            } else {
                $mform->addElement('html', '<p>' . get_string('export_location_missing', 'block_rlip'));
            }
        } else {
            $mform->addElement('html', '<p>' . get_string('ip_description', 'block_rlip') . '</p>');

            $mform->addElement('html', '<br /><br /><p>' . get_string('ip_instructions', 'block_rlip', 'http://remote-learner.net/contactme') . '</p>');
        }
    }
}

/**
 * display form for picking the file and format to import user records with
 */
class ipb_userimport_form extends moodleform {
    /**
     * defines the layout for the user import form
     */
    public function definition() {
        $mform = &$this->_form;

        //just the file name not a path
        $mform->addElement('text', 'block_rlip_impuser_filename', get_string('filename', 'block_rlip') . ': ');
        $plugins = ipb_get_import_plugins();

        if(count($plugins) > 1) {
            $mform->addElement('select', 'block_rlip_impuser_filetype', get_string('filetype', 'block_rlip') . ': ', $plugins);
        } else {
            $mform->addElement('hidden', 'block_rlip_impuser_filetype', current($plugins));
        }

        $mform->addElement('header', 'user_properties', get_string('user_properties', 'block_rlip'));

        $ui = new ipb_user_import();
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
class ipb_courseimport_form extends moodleform {
    /**
     * defines layout for the course import form
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('text', 'block_rlip_impcourse_filename', get_string('filename', 'block_rlip') . ': ');
        $plugins = ipb_get_import_plugins();

        if(count($plugins) > 1) {
            $mform->addElement('select', 'block_rlip_impcourse_filetype', get_string('filetype', 'block_rlip') . ': ', $plugins);
        } else {
            $mform->addElement('hidden', 'block_rlip_impcourse_filetype', current($plugins));
        }

        $mform->addElement('header', 'course_properties', get_string('course_properties', 'block_rlip'));

        $ci = new ipb_course_import();
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
class ipb_enrolmentimport_form extends moodleform {
    /**
     * defines layout for the enrolment import form
     */
    public function definition() {
        $mform = &$this->_form;

        $mform->addElement('text', 'block_rlip_impenrolment_filename', get_string('filename', 'block_rlip') . ': ');
        
        $plugins = ipb_get_import_plugins();
        if(count($plugins) > 1) {
            $mform->addElement('select', 'block_rlip_impenrolment_filetype', get_string('filetype', 'block_rlip') . ': ', $plugins);
        } else {
            $mform->addElement('hidden', 'block_rlip_impenrolment_filetype', current($plugins));
        }

        $mform->addElement('header', 'enrol_properties', get_string('enrol_properties', 'block_rlip'));
        
        $si = new ipb_enrolment_import();
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
function ipb_get_import_plugins() {
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