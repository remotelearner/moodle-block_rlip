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
 * Original Moodle-only import 
 */
class rlip_importplugin_version1 extends rlip_importplugin_base {
    //required field definition
    static $import_fields_user_create = array('username',
                                              'password',
                                              'firstname',
                                              'lastname',
                                              'email',
                                              'city',
                                              'country');
    static $import_fields_user_add = array('username',
                                           'password',
                                           'firstname',
                                           'lastname',
                                           'email',
                                           'city',
                                           'country');
    static $import_fields_user_update = array(array('username',
                                                    'email',
                                                    'idnumber'));
    static $import_fields_user_delete = array(array('username',
                                                    'email',
                                                    'idnumber'));
    static $import_fields_user_disable = array(array('username',
                                                     'email',
                                                     'idnumber'));

    static $import_fields_course_create = array('shortname',
                                                'fullname',
                                                'category');
    static $import_fields_course_update = array('shortname');
    static $import_fields_course_delete = array('shortname');

    static $import_fields_enrolment_create = array(array('username',
                                                         'email',
                                                         'idnumber'),
                                                   'context',
                                                   'instance',
                                                   'role');
    static $import_fields_enrolment_add = array(array('username',
                                                      'email',
                                                      'idnumber'),
                                                'context',
                                                'instance',
                                                'role');
    static $import_fields_enrolment_delete = array(array('username',
                                                        'email',
                                                        'idnumber'),
                                                  'context',
                                                  'instance',
                                                  'role');

    /**
     * Hook run after a file header is read
     *
     * @param string $entity The type of entity
     * @param array $header The header record
     */
    function header_read_hook($entity, $header) {
        global $DB;

        if ($entity !== 'user') {
            return;
        }

        $this->fields = array();

        foreach ($header as $column) {
            if (strpos($column, 'profile_field_') === 0) {
                $shortname = substr($column, strlen('profile_field_'));
                $this->fields[$shortname] = $DB->get_record('user_info_field', array('shortname' => $shortname)); 
            }
        }
    }

    /**
     * Checks a field's data is one of the specified values
     * @todo: consider moving this because it's fairly generalized
     *
     * @param object $record The record containing the data to validate
     * @param string $property The field / property to check
     * @param array $list The valid possible values
     */
    function validate_fixed_list($record, $property, $list) {
        //note: do not worry about missing fields here
        if (isset($record->$property)) {
            return in_array($record->$property, $list);
        }

        return true;
    }

    /**
     * Converts a date in MMM/DD/YYYY format
     * to a unix timestamp
     * @todo: consider further generalizing / moving to base class
     *
     * @param string $date Date in MMM/DD/YYYY format
     * @return mixed The unix timestamp, or false if date is
     *               not in the right format
     */
    function parse_date($date) {
        //make sure there are three parts
        $parts = explode('/', $date);
        if (count($parts) != 3) {
            return false;
        }

        //make sure the month is valid
        $month = $parts[0];
        $day = $parts[1];
        $year = $parts[2];
        $months = array('jan', 'feb', 'mar', 'apr',
                        'may', 'jun', 'jul', 'aug',
                        'sep', 'oct', 'nov', 'dec');
        $pos = array_search(strtolower($month), $months);
        if ($pos === false) {
            //invalid month
            return false;
        }

        //make sure the combination of date components is valid
        $month = $pos + 1;
        $day = (int)$day;
        $year = (int)$year;
        if (!checkdate($month, $day, $year)) {
            //invalid combination of month, day and year
            return false;
        }

        //return unix timestamp
        return mktime(0, 0, 0, $month, $day, $year);
    }

    /**
     * Remove invalid fields from a user record
     * @todo: consider generalizing this
     *
     * @param object $record The user record
     * @return object The user record with the invalid fields removed
     */
    function remove_invalid_user_fields($record) {
        $allowed_fields = array('entity', 'action', 'username', 'auth',
                                'password', 'firstname', 'lastname', 'email',
                                'maildigest', 'autosubscribe', 'trackforums',
                                'screenreader', 'city', 'country', 'timezone',
                                'theme', 'lang', 'description', 'idnumber',
                                'institution', 'department');
        foreach ($record as $key => $value) {
            if (!in_array($key, $allowed_fields) && strpos($key, 'profile_field_') !== 0) {
                unset($record->$key);
            }
        }

        return $record;
    }

    /**
     * Check the lengths of fields from a user record
     * @todo: consider generalizing
     *
     * @param object $record The user record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_user_field_lengths($record) {
        $lengths = array('firstname' => 100,
                         'lastname' => 100,
                         'email' => 100,
                         'city' => 120,
                         'idnumber' => 255,
                         'institution' => 40,
                         'department' => 30);

        foreach ($lengths as $field => $length) {
            //note: do not worry about missing fields here
            if (isset($record->$field)) {
                if (strlen($record->$field) > $length) {
                    return false;
                }
            }
        }

        //no problems found
        return true;
    }

    /**
     * Delegate processing of an import line for entity type "user"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     *
     * @return boolean true on success, otherwise false
     */
    function user_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "user_{$action}";
        return $this->$method($record);
    }

    /**
     * Validates that core user fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_core_user_data($action, $record) {
        global $CFG;

        //make sure auth plugin refers to a valid plugin
        $auths = get_plugin_list('auth');
        if (!$this->validate_fixed_list($record, 'auth', array_keys($auths))) {
            return false;
        }

        //make sure password satisfies the site password policy
        if (isset($record->password)) {
            $errmsg = '';
            if (!check_password_policy($record->password, $errmsg)) {
                return false;
            }
        }

        //make sure email is in user@domain.ext format
        if ($action == 'create') {
            if (!validate_email($record->email)) {
                return false;
            }
        }

        //make sure maildigest is one of the available values
        if (!$this->validate_fixed_list($record, 'maildigest', array(0, 1, 2))) {
            return false;
        }

        //make sure autosubscribe is one of the available values
        if (!$this->validate_fixed_list($record, 'autosubscribe', array(0, 1))) {
            return false;
        }

        //make sure trackforums can only be set if feature is enabled
        if (isset($record->trackforums)) {
            if (empty($CFG->forum_trackreadposts)) {
                return false;
            }
        }

        //make sure trackforums is one of the available values
        if (!$this->validate_fixed_list($record, 'trackforums', array(0, 1))) {
            return false;
        }

        //make sure screenreader is one of the available values
        if (!$this->validate_fixed_list($record, 'screenreader', array(0, 1))) {
            return false;
        }

        //make sure country refers to a valid country code
        $countries = get_string_manager()->get_list_of_countries();
        if (!$this->validate_fixed_list($record, 'country', array_keys($countries))) {
            return false;
        }

        //make sure timezone can only be set if feature is enabled
        if (isset($record->timezone)) {
            if ($CFG->forcetimezone != 99 && $record->timezone != $CFG->forcetimezone) {
                return false;
            }
        }

        //make sure timezone refers to a valid timezone offset
        $timezones = get_list_of_timezones();
        if (!$this->validate_fixed_list($record, 'timezone', array_keys($timezones))) {
            return false;
        }

        //make sure theme can only be set if feature is enabled
        if (isset($record->theme)) {
            if (empty($CFG->allowuserthemes)) {
                return false;
            }
        }

        //make sure theme refers to a valid theme
        $themes = get_list_of_themes();
        if (!$this->validate_fixed_list($record, 'theme', array_keys($themes))) {
            return false;
        }

        //make sure lang refers to a valid language
        $languages = get_string_manager()->get_list_of_translations();
        if (!$this->validate_fixed_list($record, 'lang', array_keys($languages))) {
            return false;
        }

        return true;
    }

    /**
     * Validates user profile field data and performs any required
     * data transformation in-place
     *
     * @param object $record The import record
     *
     * @return boolean true if the record validates, otherwise false
     */
    function validate_user_profile_data($record) {
        //go through each profile field in the header
        foreach ($this->fields as $shortname => $field) {
            $key = 'profile_field_'.$shortname;
            $data = $record->$key;

            //perform type-specific validation and transformation
            if ($field->datatype == 'checkbox') {
                if ($data != 0 && $data != 1) {
                    return false;
                }
            } else if ($field->datatype == 'menu') {
                $options = explode("\n", $field->param1);
                if (!in_array($data, $options)) {
                    return false;
                }
            } else if ($field->datatype == 'datetime') {
                $value = $this->parse_date($data);
                if ($value === false) {
                    return false;
                }

                $record->$key = $value;
            }
        }

        return true;
    }

    /**
     * Create a user
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function user_create($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');

        //remove invalid fields
        $record = $this->remove_invalid_user_fields($record);

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_user_data('create', $record)) {
            return false;
        }

        //profile field validation
        if (!$this->validate_user_profile_data($record)) {
            return false;
        }

        //uniqueness checks
        if ($DB->record_exists('user', array('username' => $record->username,
                                             'mnethostid'=> $CFG->mnet_localhost_id))) {
            return false;
        }

        if ($DB->record_exists('user', array('email' => $record->email))) {
            return false;
        }

        if (isset($record->idnumber)) {
            if ($DB->record_exists('user', array('idnumber' => $record->idnumber))) {
                return false;
            }
        }

        //final data sanitization
        if (!isset($record->description)) {
            $record->description = '';
        }

        if (!isset($record->lang)) {
            $record->lang = $CFG->lang;
        }

        $record->descriptionformat = FORMAT_HTML;

        $record->mnethostid = $CFG->mnet_localhost_id;

        //write to the database
        $record->id = user_create_user($record);
        profile_save_data($record);

        return true;
    }

    /**
     * Add a user
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function user_add($record) {
        //note: this is only here due to legacy 1.9 weirdness
        return $this->user_create($record);
    }

    /**
     * Update a user
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function user_update($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');
        require_once($CFG->dirroot.'/user/profile/lib.php');

        //remove invalid fields
        $record = $this->remove_invalid_user_fields($record);

        //field length checking
        $lengthcheck = $this->check_user_field_lengths($record);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_user_data('update', $record)) {
            return false;
        }

        //profile field validation
        if (!$this->validate_user_profile_data($record)) {
            return false;
        }

        //find existing user record
        $params = array();
        if (isset($record->username)) {
            $params['username'] = $record->username;
        }
        if (isset($record->email)) {
            $params['email'] = $record->email;
        }
        if (isset($record->idnumber)) {
            $params['idnumber'] = $record->idnumber;
        }

        $record->id = $DB->get_field('user', 'id', $params);
        if (empty($record->id)) {
            return false;
        }

        //write to the database

        //taken from user_update_user
        // hash the password
        if (isset($record->password)) {
            $record->password = hash_internal_user_password($record->password);
        }

        $record->timemodified = time();
        $DB->update_record('user', $record);

        // trigger user_updated event on the full database user row
        $updateduser = $DB->get_record('user', array('id' => $record->id));
        events_trigger('user_updated', $updateduser);

        profile_save_data($record);

        return true;
    }

    /**
     * Delete a user
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function user_delete($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/user/lib.php');

        //find existing user record
        $params = array();
        if (isset($record->username)) {
            $params['username'] = $record->username;
        }
        if (isset($record->email)) {
            $params['email'] = $record->email;
        }
        if (isset($record->idnumber)) {
            $params['idnumber'] = $record->idnumber;
        }

        //make the appropriate changes
        if ($user = $DB->get_record('user', $params)) {
            user_delete_user($user);
            return true;
        }        

        return false;
    }

    /**
     * Create a user
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function user_disable($record) {
        //note: this is only here due to legacy 1.9 weirdness
        return $this->user_delete($record);
    }

    /**
     * Delegate processing of an import line for entity type "course"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     *
     * @return boolean true on success, otherwise false
     */
    function course_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "course_{$action}";
        return $this->$method($record);
    }

    /**
     * Remove invalid fields from a course record
     * @todo: consider generalizing this
     *
     * @param object $record The course record
     * @return object The course record with the invalid fields removed
     */
    function remove_invalid_course_fields($record) {
        $allowed_fields = array('entity', 'action','shortname', 'fullname',
                                'idnumber', 'summary', 'format', 'numsections',
                                'startdate', 'newsitems', 'showgrades', 'showreports',
                                'maxbytes', 'guest', 'password', 'visible',
                                'lang', 'category', 'link');
        foreach ($record as $key => $value) {
            if (!in_array($key, $allowed_fields)) {
                unset($record->$key);
            }
        }
        
        return $record;
    }

    /**
     * Check the lengths of fields from a course record
     * @todo: consider generalizing
     *
     * @param object $record The course record
     * @return boolean True if field lengths are ok, otherwise false
     */
    function check_course_field_lengths($record) {
        $lengths = array('fullname' => 254,
                         'shortname' => 100,
                         'idnumber' => 100);

        foreach ($lengths as $field => $length) {
            //note: do not worry about missing fields here
            if (isset($record->$field)) {
                if (strlen($record->$field) > $length) {
                    return false;
                }
            }
        }

        //no problems found
        return true;
    }

    /**
     * Intelligently splits a category specification into a list of categories
     *
     * @param string $category_string  The category specification string, using
     *                                 \\\\ to represent \, \\/ to represent /,
     *                                 and / as a category separator
     * @return array An array with one entry per category, containing the
     *               unescaped category names
     */
    function get_category_path($category_string) {
        //in-progress method result
        $result = array();

        //used to build up the current token before splitting
        $current_token = '';

        //tracks which token we are currently looking at
        $current_token_num = 0;

        for ($i = 0; $i < strlen($category_string); $i++) {
            //initialize the entry if necessary
            if (!isset($result[$current_token_num])) {
                $result[$current_token_num] = '';
            }

            //get the ith character from the category string
            $current_token .= substr($category_string, $i, 1);

            if(strpos($current_token, '\\\\') === strlen($current_token) - strlen('\\\\')) {
                //backslash character

                //append the result
                $result[$current_token_num] .= substr($current_token, 0, strlen($current_token) - strlen('\\\\')) . '\\';
                //reset the token
                $current_token = '';
            } else if(strpos($current_token, '\\/') === strlen($current_token) - strlen('\\/')) {
                //forward slash character

                //append the result
                $result[$current_token_num] .= substr($current_token, 0, strlen($current_token) - strlen('\\/')) . '/';
                //reset the token so that the / is not accidentally counted as a category separator
                $current_token = '';
            } else if(strpos($current_token, '/') === strlen($current_token) - strlen('/')) {
                //category separator

                //append the result
                $result[$current_token_num] .= substr($current_token, 0, strlen($current_token) - strlen('/'));
                //reset the token
                $current_token = '';
                //move on to the next token
                $current_token_num++;
            }
        }

        //append leftovers after the last slash

        //initialize the entry if necessary
        if (!isset($result[$current_token_num])) {
            $result[$current_token_num] = '';
        }

        $result[$current_token_num] .= $current_token;

        return $result;
    }

    /**
     * Map the specified category to a record id
     *
     * @param string $category_string The category specification string, using
     *                                \\\\ to represent \, \\/ to represent /,
     *                                and / as a category separator
     * @return mixed Returns false on error, or the integer category id otherwise
     */
    function get_category_id($category_string) {
        global $DB;

        $parentids = array();

        //check for a leading / for the case where an absolute path is specified 
        if (strpos($category_string, '/') === 0) {
            $category_string = substr($category_string, 1);
            $parentids[] = 0;
        }

        //split the category string into a list of categories
        $path = $this->get_category_path($category_string);

        foreach ($path as $categoryname) {
            //look for categories with the correct name
            $select = "name = ?";
            $params = array($categoryname);

            if (!empty($parentids)) {
                //only allow categories that also are children of categories
                //found in the last iteration of the specified path
                list($parentselect, $parentparams) = $DB->get_in_or_equal($parentids);
                $select = "{$select} AND parent {$parentselect}";
                $params = array_merge($params, $parentparams);
            }

            //find matching records
            if ($records = $DB->get_recordset_select('course_categories', $select, $params)) {
                if (!$records->valid()) {
                    //none found, so try see if the id was specified
                    if (is_numeric($category_string)) {
                        if ($DB->record_exists('course_categories', array('id' => $category_string))) {
                            return $category_string;
                        }
                    }

                    $parent = 0;
                    if (count($parentids) == 1) {
                        //we have a specific parent to create a child for
                        $parent = $parentids[0];
                    } else if (count($parentids) > 0) {
                        //ambiguous parent, so we can't continue
                        return false;
                    }

                    //create a new category
                    $newcategory = new stdClass;
                    $newcategory->name = $categoryname;
                    $newcategory->parent = $parent; 
                    $newcategory->id = $DB->insert_record('course_categories', $newcategory);

                    //set "parent ids" to the new category id
                    $parentids = array($newcategory->id);
                } else {
                    //set "parent ids" to the current result set for our next iteration
                    $parentids = array();
    
                    foreach ($records as $record) {
                        $parentids[] = $record->id;
                    }
                }
            }
        }

        if (count($parentids) == 1) {
            //found our category
            return $parentids[0];
        } else {
            //path refers to multiple potential categories
            return false;
        }
    }

    /**
     * Validates that core course fields are set to valid values, if they are set
     * on the import record
     *
     * @param string $action One of 'create' or 'update'
     * @param object $record The import record
     *
     * @return boolean true if the record validates correctly, otherwise false
     */
    function validate_core_course_data($action, $record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //make sure format refers to a valid course format
        if (isset($record->format)) {
            $courseformats = get_plugin_list('format');

            if (!$this->validate_fixed_list($record, 'format', array_keys($courseformats))) {
                return false;
            }
        }

        //make sure numsections is an integer between 0 and the configured max
        if (isset($record->numsections)) {
            $maxsections = (int)get_config('moodlecourse', 'maxsections');

            if ((int)$record->numsections != $record->numsections) {
                //not an integer
                return false;
            }

            $record->numsections = (int)$record->numsections;
            if ($record->numsections < 0 || $record->numsections > $maxsections) {
                //not between 0 and 10
                return false;
            }
        }

        //make sure startdate is a valid date
        if (isset($record->startdate)) {
            $value = $this->parse_date($record->startdate);
            if ($value === false) {
                return false;
            }

            //use the unix timestamp
            $record->startdate = $value;
        }

        //make sure newsitems is an integer between 0 and 10
        $options = range(0, 10);
        if (!$this->validate_fixed_list($record, 'newsitems', $options)) {
            return false;
        }

        //make sure showgrades is one of the available values 
        if (!$this->validate_fixed_list($record, 'showgrades', array(0, 1))) {
            return false;
        }

        //make sure showreports is one of the available values
        if (!$this->validate_fixed_list($record, 'showreports', array(0, 1))) {
            return false;
        }

        //make sure maxbytes is one of the available values
        if (isset($record->maxbytes)) {
            $choices = get_max_upload_sizes($CFG->maxbytes);
            if (!$this->validate_fixed_list($record, 'maxbytes', array_keys($choices))) {
                return false;
            }
        }

        //make sure guest is one of the available values
        if (!$this->validate_fixed_list($record, 'guest', array(0, 1))) {
            return false;
        }

        //make sure visible is one of the available values
        if (!$this->validate_fixed_list($record, 'visible', array(0, 1))) {
            return false;
        }

        //make sure lang refers to a valid language or the default value
        $languages = get_string_manager()->get_list_of_translations();
        $language_codes = array_merge(array(''), array_keys($languages));
        if (!$this->validate_fixed_list($record, 'lang', $language_codes)) {
            return false;
        }

        if ($action == 'create') {
            //make sure "guest" settings are consistent for new course
            if (empty($record->guest) && !empty($record->password)) {
                return false;
            }

            //make sure we don't have a course "link" (template) that refers to
            //an invalid course shortname
            if (isset($record->link)) {
                if (!$DB->record_exists('course', array('shortname' => $record->link))) {
                    return false;
                }
            }
        }

        if ($action == 'update') {
            //make sure "guest" settings are consistent for new course

            if (isset($record->guest) || isset($record->password)) {
                //a "guest" setting is used, validate that the guest enrolment
                //plugin is enabled for the current course
                if ($courseid = $DB->get_field('course', 'id', array('shortname' => $record->shortname))) {
                    if (!$DB->record_exists('enrol', array('courseid' => $courseid,
                                                            'enrol' => 'guest'))) {
                       return false;
                    }
                }
            }

            if (!empty($record->password)) {
                //make sure a password can only be set if guest access is enabled
                if ($courseid = $DB->get_field('course', 'id', array('shortname' => $record->shortname))) {

                    if (isset($record->guest) && empty($record->guest)) {
                        //guest access specifically disabled, which isn't
                        //consistent with providing a password
                        return false;
                    } else if (!isset($record->guest)) {
                        $params = array('courseid' => $courseid,
                                        'enrol' => 'guest',
                                        'status' => ENROL_INSTANCE_ENABLED);
                        if (!$DB->record_exists('enrol', $params)) {
                            //guest access disabled in the database
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Create a course
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function course_create($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');

        //remove invalid fields
        $record = $this->remove_invalid_course_fields($record);

        //field length checking
        $lengthcheck = $this->check_course_field_lengths($record);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_course_data('create', $record)) {
            return false;
        }

        //validate and set up the category
        $categoryid = $this->get_category_id($record->category);
        if ($categoryid === false) {
            return false;
        }

        $record->category = $categoryid;

        //uniqueness check
        if ($DB->record_exists('course', array('shortname' => $record->shortname))) {
            return false;
        }

        //final data sanitization
        if (isset($record->guest)) {
            $record->enrol_guest_status_0 = ENROL_INSTANCE_ENABLED;
            if (!isset($record->enrol_guest_password_0)) {
                $record->enrol_guest_password_0 = NULL;
            }
        }
        if (isset($record->password)) {
            $record->enrol_guest_password_0 = $record->password;
        }

        //write to the database
        if (isset($record->link)) {
            //creating from template
            require_once($CFG->dirroot.'/elis/core/lib/setup.php');
            require_once(elis::lib('rollover/lib.php'));
            $courseid = $DB->get_field('course', 'id', array('shortname' => $record->link));

            //perform the content rollover
            $record->id = course_rollover($courseid);
            //update appropriate fields, such as shortname
            update_course($record);
        } else {
            //creating directly (not from template)
            create_course($record);
        }

        return true;
    }

    /**
     * Update a course
     * @todo: consider factoring this some more once other actions exist
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function course_update($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/course/lib.php');
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //remove invalid fields
        $record = $this->remove_invalid_course_fields($record);

        //field length checking
        $lengthcheck = $this->check_course_field_lengths($record);
        if (!$lengthcheck) {
            return false;
        }

        //data checking
        if (!$this->validate_core_course_data('update', $record)) {
            return false;
        }

        //validate and set up the category
        if (isset($record->category)) {
            $categoryid = $this->get_category_id($record->category);
            if ($categoryid === false) {
                return false;
            }
    
            $record->category = $categoryid;
        }

        $record->id = $DB->get_field('course', 'id', array('shortname' => $record->shortname));
        if (empty($record->id)) {
            return false;
        }

        update_course($record);

        //special work for "guest" settings

        if (isset($record->guest) && empty($record->guest)) {
            //todo: add more error checking
            if ($enrol = $DB->get_record('enrol', array('courseid' => $record->id,
                                                        'enrol' => 'guest'))) {
                //disable the plugin for the current course
                $enrol->status = ENROL_INSTANCE_DISABLED;
                $DB->update_record('enrol', $enrol);
            }
        }

        if (!empty($record->guest)) {
            //todo: add more error checking
            if ($enrol = $DB->get_record('enrol', array('courseid' => $record->id,
                                                        'enrol' => 'guest'))) {
                //enable the plugin for the current course
                $enrol->status = ENROL_INSTANCE_ENABLED;
                if (isset($record->password)) {
                    //password specified, so set it
                    $enrol->password = $record->password;
                }
                $DB->update_record('enrol', $enrol);
            }
        }

        return true;
    }

    /**
     * Delete a course
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function course_delete($record) {
        global $DB;

        if ($courseid = $DB->get_field('course', 'id', array('shortname' => $record->shortname))) {
            delete_course($courseid, false);
            fix_course_sortorder();
            return true;
        }

        return false;
    }

    /**
     * Delegate processing of an import line for entity type "enrolment"
     *
     * @param object $record One record of import data
     * @param string $action The action to perform, or use data's action if
     *                       not supplied
     *
     * @return boolean true on success, otherwise false
     */
    function enrolment_action($record, $action = '') {
        if ($action === '') {
            $action = $record->action;
        }

        $method = "enrolment_{$action}";
        return $this->$method($record);
    }

    /**
     * Create an enrolment
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function enrolment_create($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //data checking
        if (!$roleid = $DB->get_field('role', 'id', array('shortname' => $record->role))) {
            return false;
        }

        //find existing user record
        $params = array();
        if (isset($record->username)) {
            $params['username'] = $record->username;
        }
        if (isset($record->email)) {
            $params['email'] = $record->email;
        }
        if (isset($record->idnumber)) {
            $params['idnumber'] = $record->idnumber;
        }
        if (!$userid = $DB->get_field('user', 'id', $params)) {
            return false;
        }

        //track which context level the assignment is taking place at
        $contextlevel = null;

        if ($record->context == 'course') {
            //find existing course
            if (!$courseid = $DB->get_field('course', 'id', array('shortname' => $record->instance))) {
                //invalid shortname
                return false;
            }

            //obtain the course context instance
            $contextlevel = CONTEXT_COURSE;
            $context = get_context_instance($contextlevel, $courseid);
        } else if ($record->context == 'system') {
            //obtain the system context instance
            $contextlevel = CONTEXT_SYSTEM;
            $context = get_context_instance($contextlevel);
        } else if ($record->context == 'coursecat') {
            //make sure category name is not ambiguous
            $count = $DB->count_records('course_categories', array('name' => $record->instance));
            if ($count > 1) {
                //ambiguous category name
                return false;
            }

            //find existing course category
            if (!$categoryid = $DB->get_field('course_categories', 'id', array('name' => $record->instance))) {
                //invalid name
                return false;
            }

            //obtain the course category context instance
            $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
            $contextlevel = CONTEXT_COURSECAT;
        } else if ($record->context == 'user') {
            //find existing user category
            if (!$targetuserid = $DB->get_field('user', 'id', array('username' => $record->instance))) {
                //invalid username
                return false;
            }

            //obtain the user context instance
            $context = get_context_instance(CONTEXT_USER, $targetuserid);
            $contextlevel = CONTEXT_USER;
        } else {
            //currently only supporting course, system, user and category
            //context levels
            return false;
        }

        //make sure the role is assignable at the course context level
        if (!$DB->record_exists('role_context_levels', array('roleid' => $roleid,
                                                             'contextlevel' => $contextlevel))) {
            return false;
        }

        //note: this seems redundant but will be useful for error messages later
        $params = array('roleid' => $roleid,
                        'contextid' => $context->id,
                        'userid' => $userid,
                        'component' => '',
                        'itemid' => 0);
        $role_assignment_exists = $DB->record_exists('role_assignments', $params);

        //track the group and grouping specified
        $groupid = 0;
        $groupingid = 0;

        //duplicate group / grouping name checks and name validity checking
        if ($record->context == 'course' && isset($record->group)) {
            $count = $DB->count_records('groups', array('name' => $record->group,
                                                        'courseid' => $courseid));
            if ($count > 1) {
                //ambiguous
                return false;
            } else if ($count == 0 && empty($CFG->bogus_rlip_creategroups)) {
                //todo: use proper setting
                //does not exist and not creating
                return false;
            } else {
                //exact group exists
                $groupid = groups_get_group_by_name($courseid, $record->group);
            }

            if (isset($record->grouping)) {
                $count = $DB->count_records('groupings', array('name' => $record->grouping,
                                                               'courseid' => $courseid));
                if ($count > 1) {
                    //ambiguous
                    return false;
                } else if ($count == 0 && empty($CFG->bogus_rlip_creategroups)) {
                    //todo: use proper setting
                    //does not exist and not creating
                    return false;
                } else {
                    //exact grouping exists
                    $groupingid = groups_get_grouping_by_name($courseid, $record->grouping);
                }
            }
        }

        //todo: add some sort of configuration for this?
        if ($record->context == 'course' && $record->role == 'student') {
            //set enrolment start time to the course start date
            $timestart = $DB->get_field('course', 'startdate', array('id' => $courseid));

            if ($role_assignment_exists) {
                //role assignment already exists, so just enrol the user
                enrol_try_internal_enrol($courseid, $userid, null, $timestart);
            } else if (!is_enrolled($context, $userid)) {
                //role assignment does not exist, so enrol and assign role
                enrol_try_internal_enrol($courseid, $userid, $roleid, $timestart);
            } else {
                return false;
            }
        } else {
            if ($role_assignment_exists) {
                //role assignment already exists, so this action serves no purpose
                return false;
            }

            role_assign($roleid, $userid, $context->id);
        }

        if ($record->context == 'course' && isset($record->group)) {
            //process specified group
            require_once($CFG->dirroot.'/lib/grouplib.php');
            require_once($CFG->dirroot.'/group/lib.php');

            if ($groupid == 0) {
                //need to create the group
                $data = new stdClass;
                $data->courseid = $courseid;
                $data->name = $record->group;
    
                $groupid = groups_create_group($data);
            }

            if (groups_is_member($groupid, $userid)) {
                //error handling
            } else {
                //try to assign the user to the group
                if (!groups_add_member($groupid, $userid)) {
                    //error handling - already a member
                }
            }

            if (isset($record->grouping)) {
                //process the specified grouping

                if ($groupingid == 0) {
                    //need to create the grouping
                    $data = new stdClass;
                    $data->courseid = $courseid;
                    $data->name = $record->grouping;
    
                    $groupingid = groups_create_grouping($data);
                }

                //assign the group to the grouping
                if ($DB->record_exists('groupings_groups', array('groupingid' => $groupingid,
                                                                 'groupid' => $groupid))) {
                    //error handling
                } else {
                    groups_assign_grouping($groupingid, $groupid);
                }
            }
        }

        return true;
    }

    /**
     * Add an enrolment
     *
     * @param object $record One record of import data
     * @return boolean true on success, otherwise false
     */
    function enrolment_add($record) {
        //note: this is only here due to legacy 1.9 weirdness
        return $this->enrolment_create($record);
    }

    function enrolment_delete($record) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/lib/enrollib.php');

        //data checking
        if (!$roleid = $DB->get_field('role', 'id', array('shortname' => $record->role))) {
            return false;
        }

        //find existing user record
        $params = array();
        if (isset($record->username)) {
            $params['username'] = $record->username;
        }
        if (isset($record->email)) {
            $params['email'] = $record->email;
        }
        if (isset($record->idnumber)) {
            $params['idnumber'] = $record->idnumber;
        }
        if (!$userid = $DB->get_field('user', 'id', $params)) {
            return false;
        }

        //track the context instance
        $context = null;
        //track whether an enrolment exists
        $enrolment_exists = false;

        if ($record->context == 'course') {
            //find existing course
            if (!$courseid = $DB->get_field('course', 'id', array('shortname' => $record->instance))) {
                return false;
            }
    
            //obtain the course context instance
            $context = get_context_instance(CONTEXT_COURSE, $courseid);

            $enrolment_exists = is_enrolled($context, $userid);
        } else if ($record->context == 'system') {
            //obtain the course context instance
            $context = get_context_instance(CONTEXT_SYSTEM);
        } else if ($record->context == 'coursecat') {
            $count = $DB->count_records('course_categories', array('name' => $record->instance));
            if ($count > 1) {
                //ambiguous
                return false;
            }

            //find existing category
            if (!$categoryid = $DB->get_field('course_categories', 'id', array('name' => $record->instance))) {
                return false;
            }

            //obtain the category context instance
            $context = get_context_instance(CONTEXT_COURSECAT, $categoryid);
        } else if ($record->context == 'user') {
            //find existing user
            if (!$userid = $DB->get_field('user', 'id', array('username' => $record->instance))) {
                return false;
            }
    
            //obtain the user context instance
            $context = get_context_instance(CONTEXT_USER, $userid);
        } else {
            //currently only supporting course, system, user and category
            //context levels
            return false;
        }

        //determine whether the role assignment and enrolment records exist
        $role_assignment_exists = $DB->record_exists('role_assignments', array('roleid' => $roleid,
                                                                               'contextid' => $context->id,
                                                                               'userid' => $userid));

        //todo: add some sort of configuration for this?
        if (!$role_assignment_exists && (!$enrolment_exists || $record->role != 'student')) {
            //nothing to delete
            return false;
        }

        if ($role_assignment_exists) {
            //unassign role
            role_unassign($roleid, $userid, $context->id);
        }

        //todo: add some sort of configuration for this?
        if ($enrolment_exists && $record->role == 'student') {
            //remove enrolment
            if ($instance = $DB->get_record('enrol', array('enrol' => 'manual',
                                                           'courseid' => $courseid))) {
                $plugin = enrol_get_plugin('manual');
                $plugin->unenrol_user($instance, $userid);
            }
        }

        return true;
    }
}