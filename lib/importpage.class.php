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

require_once($CFG->dirroot . '/blocks/rlip/sharedlib.php');

if(is_elis()) {
    require_once($CFG->dirroot . '/curriculum/config.php');

    require_once($CFG->dirroot . '/blocks/rlip/elis/lib.php');
    require_once(RLIP_DIRLOCATION . '/lib/dataimportform.class.php');
} else {
    require_once('../../../config.php');
    require_once($CFG->dirroot . '/blocks/rlip/moodle/newpage.class.php');

            //TODO: change to lib/dataimportform.class.php once more compatible
    require_once($CFG->dirroot . '/blocks/rlip/moodle/dataimportform.class.php');

    require_once('lib.php');
}

class importpage extends newpage {
    var $pagename = 'dim';
    var $section = 'admn';

    function __construct($params=false) {
        $this->tabs = array(
            array('tab_id' => 'default', 'page' => get_class($this), 'params' => array('action' => 'default'), 'name' => get_string('general', 'block_rlip')),
            array('tab_id' => 'user', 'page' => get_class($this), 'params' => array('action' => 'user'), 'name' => get_string('user', 'block_rlip')),
            array('tab_id' => 'course', 'page' => get_class($this), 'params' => array('action' => 'course'), 'name' => get_string('course', 'block_rlip')),
            array('tab_id' => 'enrolment', 'page' => get_class($this), 'params' => array('action' => 'enrolment'), 'name' => get_string('enrolment', 'block_rlip')),
        );

        parent::__construct($params);
    }

    function can_do_default() {
        global $CURMAN;

        $context = get_context_instance(CONTEXT_SYSTEM);

        return has_capability('block/rlip:config', $context);
    }

    function get_title_default() {
        return get_string('dataimport', 'block_rlip');
    }

    function get_navigation_default() {
        return array(
            array('name' => get_string('dataimport', 'block_rlip'),
                  'link' => $this->get_url()),
            );
    }

    function action_default() {
        global $CFG;

        $target = $this->get_new_page(array('action' => 'default'));

        $configform = new generalimport_form($target->get_moodle_url());
//        $configform->set_data($CFG);

        if ($configdata = $configform->get_data()) {
//            if (isset($configdata->block_rlip_filelocation)) {
//                set_config('block_rlip_filelocation', stripslashes($configdata->block_rlip_filelocation));
//            }
//
//            if (isset($configdata->block_rlip_exportfilelocation)) {
//                set_config('block_rlip_exportfilelocation', stripslashes($configdata->block_rlip_exportfilelocation));
//            }
//
//            if (isset($configdata->block_rlip_exportfiletimestamp)) {
//                set_config('block_rlip_exportfiletimestamp', $configdata->block_rlip_exportfiletimestamp);
//            }
//
//            if(isset($configdata->block_rlip_logfilelocation)) {
//                set_config('block_rlip_logfilelocation', stripslashes($configdata->block_rlip_logfilelocation));
//            }
//
//            if(isset($configdata->block_rlip_emailnotification)) {
//                set_config('block_rlip_emailnotification', $configdata->block_rlip_emailnotification);
//            }
//
//            if(isset($configdata->block_rlip_exportallhistorical)) {
//                set_config('block_rlip_exportallhistorical', $configdata->block_rlip_exportallhistorical);
//            }

            if(isset($configdata->import)) {
                include_once(RLIP_DIRLOCATION . '/lib/dataimport.php');
            }

            if(isset($configdata->export)) {
                $export = $this->get_export();
                $export->cron(true);
            }
        }

        $this->print_tabs('default');
        $configform->display();
    }

    function action_user() {    }

    function action_course() {   }

    function action_enrolment() {   }


    /**
     * Prints the tab bar describe by the $tabs instance variable.
     * @param $selected name of tab to display as selected
     * @param $params extra parameters to insert into the tab links, such as an id
     */
    function print_tabs($selected, $params=array()) {
        $row = array();

        foreach($this->tabs as $tab) {
            $target = new $tab['page'](array_merge($tab['params'], $params));
            $row[] = new tabobject($tab['tab_id'], $target->get_url(), $tab['name']);
        }

        print_tabs(array($row), $selected);
    }

    private function array_prefix($prefix, $data) {
        $retval = array();
        if(is_array($data)) {
            foreach($data as $key=>$d) {
                $retval[$prefix.$key] = $d;
            }
        }

        return $retval;
    }
}
?>