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
        $this->version = 2010051700;
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
        $this->content->text = '<a href="' . $CFG->wwwroot . '/blocks/rlip/dataimportpage.class.php' . '">' . get_string('ip_link', 'block_rlip') . '</a>';
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * allow the block to have a configuration page
     *
     * @return boolean
     */
    public function has_config() {
        return false;
    }

    /**
     * allow more than one instance of the block on a page
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        //allow more than one instance on a page
        return false;
    }

    /**
     * allow instances to have their own configuration
     *
     * @return boolean
     */
    function instance_allow_config() {
        //allow instances to have their own configuration
        return false;
    }

    /**
     * instance specialisations (must have instance allow config true)
     *
     */
    public function specialization() {
    }

    /**
     * displays instance configuration form
     *
     * @return boolean
     */
    function instance_config_print() {
        if (!$this->instance_allow_config()) {
            return false;
        }

        global $CFG;

        $form = new block_integration_pointConfigForm(null, array($this->config));
        $form->display();

        return true;
    }

    /**
     * locations where block can be displayed
     *
     * @return array
     */
    public function applicable_formats() {
        return array('all'=>true);
    }

    /**
     * post install configurations
     *
     */
    public function after_install() {
    }

    /**
     * post delete configurations
     *
     */
    public function before_delete() {
    }

}

/**
 * short description of block_integration_point
 *
 * [long description of block_integration_point]
 */
class block_integration_pointConfigForm extends moodleform {
    /**
     * items in the form
     */
    function definition() {
        $mform = &$this->_form;
    }
}
?>
