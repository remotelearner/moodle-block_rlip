<?php //$Id: Exp $
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

/**
 * user, course, enrollment import block for moodle and elis
 */

require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * short description of block_integration_point
 *
 * [long description of block_integration_point]
 */
class block_rlip extends block_base {
    /**
     * block initializations
     */
    public function init() {
        $this->title   = get_string('title', 'block_rlip');
        $this->version = 2010051702;
    }

    /**
     * block contents
     *
     * @return object 
     */
    public function get_content() {
        global $CFG;
        if($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();

        $context = get_context_instance(CONTEXT_SYSTEM);

        if(!file_exists($CFG->dirroot . '/curriculum/config.php') && has_capability('block/rlip:config', $context)) {
            $this->content->text = '<a href="' . $CFG->wwwroot . '/blocks/rlip/moodle/dataimportpage.class.php' . '">' . get_string('ip_link', 'block_rlip') . '</a>';
        } else {
            $this->content->text = '';
        }

        $this->content->footer = '';

        return $this->content;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('all'=>true);
    }

    public function has_config() {
        return true;
    }

    /**
     * post install configurations
     *
     */
    public function after_install() {
        set_config('block_rlip_impuser_filename', 'user.csv');
        set_config('block_rlip_impuser_filetype', 'csv');

        set_config('block_rlip_impcourse_filename', 'enroll.csv');
        set_config('block_rlip_impcourse_filetype', 'csv');

        set_config('block_rlip_impenrolment_filename', 'course.csv');
        set_config('block_rlip_impenrolment_filetype', 'csv');
    }

    /**
     * post delete configurations
     *
     */
    public function before_delete() {
    }
}

?>