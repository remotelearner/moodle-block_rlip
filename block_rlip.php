<?php //$Id: Exp $
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2010 Remote-Learner.net Inc (http://www.remote-learner.net)
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

/**
 * user, course, enrollment import block for moodle and elis
 */

require_once($CFG->dirroot.'/lib/weblib.php');
require_once($CFG->dirroot . '/lib/formslib.php');

require_once($CFG->dirroot . '/blocks/rlip/sharedlib.php');
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
        $this->version = 2010120900;
        $this->release = '1.9.3.1';
        $this->cron = 5 * MINSECS;

        $this->log_filer = null;
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

        if(!block_rlip_is_elis() && has_capability('block/rlip:config', $context)) {
            $this->content->text = '<a href="' . $CFG->wwwroot . '/blocks/rlip/moodle/displaypage.php' . '">' . get_string('ip_link', 'block_rlip') . '</a>';
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

        set_config('block_rlip_impcourse_filename', 'course.csv');
        set_config('block_rlip_impcourse_filetype', 'csv');

        set_config('block_rlip_impenrolment_filename', 'enroll.csv');
        set_config('block_rlip_impenrolment_filetype', 'csv');
    }

    /**
     * Executes the block's recurring cron tasks.
     *
     * @param bool $manual   Set to true if the cron method is being from manually from the Moodle UI.
     * @param bool $override Override the disabled cron check (used with the CLI cron script).
     * @return bool Always returns true, even if execution fails.
     */
    function cron($manual = false, $override = false) {
        global $CFG;

        // Check if
        if (!empty($CFG->block_rlip_nocron) && !$override) {
            mtrace(get_string('crondisabled', 'block_rlip'));
            return true;
        }

        $timenow = time();

        if(block_rlip_is_elis()) {
            require_once('ElisExport.class.php');

            $export = new ElisExport();
        } else {
            require_once('MoodleExport.class.php');

            $export = new MoodleExport();
        }

        /*
         * Export
         */
        $last_export = 0;
        if(!empty($CFG->block_rlip_last_export_cron)) {
            $last_export = $CFG->block_rlip_last_export_cron;
        }

        $export_period = 0;
        if(!empty($CFG->block_rlip_exportperiod)) {
            $export_period = block_rlip_time_string_to_seconds($CFG->block_rlip_exportperiod);
        } else {
            $export_period = block_rlip_time_string_to_seconds('1d');
        }

        if($timenow >= ($last_export + $export_period) && !empty($CFG->block_rlip_exportfilelocation)) {
            $export->cron($manual, $last_export);
            set_config('block_rlip_last_export_cron', $timenow);
        }

        /*
         * Import
         */
        $last_import = 0;
        if(!empty($CFG->block_rlip_last_import_cron)) {
            $last_import = $CFG->block_rlip_last_import_cron;
        }

        $import_period = 0;
        if(!empty($CFG->block_rlip_importperiod)) {
            $import_period = block_rlip_time_string_to_seconds($CFG->block_rlip_importperiod);
        } else {
            $import_period = block_rlip_time_string_to_seconds('30m');
        }

        if($timenow >= ($last_import + $import_period)) {
            include_once(RLIP_DIRLOCATION . '/lib/dataimport.php');
            set_config('block_rlip_last_import_cron', $timenow);
        }

        if (defined('FULLME') && FULLME == 'cron') {
            mtrace('done');
        }

        return true;
    }
}

?>
