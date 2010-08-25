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
 * @copyright  (C) 2008-2010 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

        //TODO: change to lib/dataimportform.class.php once more compatible
require_once($CFG->dirroot . '/blocks/rlip/lib/importpage.class.php');

class dataimportpage extends importpage {

    function get_export() {
        require_once($CFG->dirroot . '/blocks/rlip/MoodleExport.class.php');

        return new MoodleExport();
    }

    function action_user() {
        $this->do_action(new user_import(), 'user');
    }

    function action_course() {
        $this->do_action(new course_import(), 'course');
    }

    function action_enrolment() {
        $this->do_action(new enrolment_import(), 'enrolment');
    }

    private function do_action($import, $action) {
        global $CFG;
        
        $target = $this->get_new_page(array('action' => $action));

        $map = $import->get_properties_map();

        $form_class = "{$action}import_form";
        $configform = new $form_class($target->get_moodle_url());

        $configform->set_data($CFG);
        $configform->set_data($map);

        if($configdata = $configform->get_data()) {
            foreach($map as $key=>$value) {
                if(!empty($configdata->$key)) {
                    if(strcmp($value, $configdata->$key) !== 0) {
                        $import->set_property_map($key, $configdata->$key);
                    }
                } else {
                    //something has gone terribly wrong everybody panic
                }
            }

            $property = "block_rlip_imp{$action}_filename";
            if(!empty($configdata->$property)) {
                set_config($property, $configdata->$property);
            }

            $property = "block_rlip_imp{$action}_filetype";
            if(!empty($configdata->$property)) {
                set_config($property, $configdata->$property);
            }

            if(isset($configdata->save_buttons['import'])) {
                include_once(RLIP_DIRLOCATION. '/lib/dataimport.php');
            }
        }

        // temporary fix until I can figure out what the hell is going on here
        $configform->set_data(array('sesskey' => sesskey()));

        $this->print_tabs($action);
        $configform->display();
    }
}
?>