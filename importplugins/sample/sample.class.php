<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2012 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @subpackage core
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 2008-2012 Remote Learner.net Inc http://www.remote-learner.net
 *
 */

require_once($CFG->dirroot.'/blocks/rlip/rlip_importplugin.class.php');

/**
 * Test plugin used to test a simple entity and action
 */
class rlip_importplugin_sample extends rlip_importplugin_base {
    //required field definition
    static $import_fields_sampleentity_sampleaction = array('samplefield');

    //tracks whether our specific action was called
    private $action_called = false;

    /**
     * Delegate processing of an import line for entity type "sampleentity"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     *
     * @return boolean true on success, otherwise false
     */
    function sampleentity_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "sampleentity_{$action}";
        return $this->$method($record);
    }

    /**
     * Test method for entity of "sampleentity" and action of "sampleaction"
     *
     * @param object $record One record of import data
     *
     * @return boolean true on success, otherwise false
     */
    function sampleentity_sampleaction($record) {
        //remember that this action was processed
        $this->action_called = true;

        return true;
    }

    /**
     * Specifies whether the entity's action method was called
     *
     * @return boolean true if it was called, otherwise false
     */
    function action_called() {
        return $this->action_called;
    }

    /**
     * Specifies the UI labels for the various import files supported by this
     * plugin
     *
     * @return array The string labels, in the order in which the
     *               associated [entity]_action methods are defined
     */
    function get_file_labels() {
        return array('Sample Entity');
    }

    /**
     * Specifies flag for indicating that this plugin is for testing only
     */
    function is_test_plugin() {
        //this plugin is for testing only
        return true;
    }
}