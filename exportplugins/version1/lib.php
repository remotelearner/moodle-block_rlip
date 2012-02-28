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

/**
 * Helper class that is used for configuring the Version 1 format export
 */
class rlipexport_version1_config {
    //define the "move" directions - up or down
    const DIR_UP = 0;
    const DIR_DOWN = 1;

    /**
     * Remove a Moodle user profile field from the export configuration
     *
     * @param int $exportid The database record id of the mapping record
     */
    static function delete_field_from_export($exportid) {
        global $DB;

        //determine the current position in the list
        $order = $DB->get_field('block_rlip_version1_export', 'fieldorder', array('id' => $exportid));

        //remove the record
        $DB->delete_records('block_rlip_version1_export', array('id' => $exportid));

        //shift the records after the deleted record
        $sql = "UPDATE {block_rlip_version1_export}
                SET fieldorder = fieldorder - 1
                WHERE fieldorder > ?";
        $params = array($order);
        $DB->execute($sql, $params);
    }

    /**
     * Add a Moodle user profile field to the export configuration
     *
     * @param int $fieldid The database record id of the user profile field
     */
    static function add_field_to_export($fieldid) {
        global $DB;

        //set up our data record
        $record = new stdClass;
        $record->fieldid = $fieldid;

        //the header defaults to the field name
        $record->header = $DB->get_field('user_info_field', 'name', array('id' => $fieldid));

        //field order defaults to the end of the list
        $max_order = $DB->get_field('block_rlip_version1_export', 'MAX(fieldorder)', array());
        $record->fieldorder = $max_order + 1;

        //insert our data record
        $DB->insert_record('block_rlip_version1_export', $record);
    }

    /**
     * Move a field up or down in the order within the export configuration
     *
     * @param int $exportid The database record id of the mapping record
     * @param int $direction The direction in which the field is being moved -
     *                       one of DIR_UP or DIR_DOWN
     */
    static function move_field($exportid, $direction) {
        global $DB;

        //determine the current field order for the field being moved
        $params = array('id' => $exportid);
        $currentorder = $DB->get_field('block_rlip_version1_export', 'fieldorder', $params);

        //specific setup depending on the move direction
        if ($direction == self::DIR_UP) {
            $operator = 'MAX';
            $comparrison_symbol = '<';
        } else {
            $operator = 'MIN';
            $comparrison_symbol = '>';
        }

        //find the next field order value in the right direction that
        //corresponds to a user profile field that is not deleted
        $sql = "SELECT {$operator}(export.fieldorder)
                FROM {block_rlip_version1_export} export
                  WHERE EXISTS (
                    SELECT 'x'
                    FROM {user_info_field} field
                    WHERE export.fieldid = field.id
                  ) AND export.fieldorder {$comparrison_symbol} ?";
        $neworder = $DB->get_field_sql($sql, array($currentorder));

        //change the fieldorder on the record being moved
        $params = array('id' => $exportid);
        $DB->set_field('block_rlip_version1_export', 'fieldorder', $neworder, $params);

        //change the field that is "one away" to use the field order
        $select = "fieldorder = ? AND id != ?";
        $params = array($neworder, $exportid);
        $DB->set_field_select('block_rlip_version1_export', 'fieldorder', $currentorder, $select, $params);
    }

    /**
     * Set the header text for a field within the export configuration
     *
     * @param int $exportid The database record id of the mapping record
     * @param string $header The header text to set on the mapping record
     */
    static function update_field_header($exportid, $header) {
        global $DB;

        $record = new stdClass;
        $record->id = $exportid;
        $record->header = $header;

        $DB->update_record('block_rlip_version1_export', $record);
    }

    /**
     * Set the header text for a set of fields within the export configuration
     *
     * @param array $data A set of data representing field headers to be
     *                    updated
     */
    static function update_field_headers($data) {
        if ($data !== false) {
            foreach ($data as $key => $value) {
                if (strpos($key, 'header_') === 0) {
                    $recordid = substr($key, strlen('header_'));
                    self::update_field_header($recordid, $value); 
                }
            }
        }
    }

    /**
     * Specifies a recordset that provides a listing of configured export
     * fields, including the mapping id, field name, export header text and
     * field order
     *
     * @return object The appropriate recordset
     */
    static function get_configured_fields() {
        global $DB;

        $sql = "SELECT export.id,
                       field.name,
                       export.header,
                       export.fieldorder
                FROM {user_info_field} field
                JOIN {block_rlip_version1_export} export
                  ON field.id = export.fieldid
                ORDER BY export.fieldorder";

        return $DB->get_recordset_sql($sql);
    }

    /**
     * Specifies a recordset that provides a listing of Moodle user profile
     * fields that have not yet been included in the recordset, including their
     * record ids and names
     *
     * @return object The appropriate recordset
     */
    static function get_available_fields() {
        global $DB;

        $sql = "SELECT field.id, field.name
                FROM {user_info_category} category
                JOIN {user_info_field} field
                  ON category.id = field.categoryid
                WHERE NOT EXISTS (
                  SELECT 'x'
                  FROM {block_rlip_version1_export} export
                  WHERE field.id = export.fieldid
                )
                ORDER BY category.sortorder, field.sortorder";

        return $DB->get_recordset_sql($sql);
    }

    /**
     * Handles actions submitted by the form for configuring which profile
     * fields are part of the export, redirecting if necessary
     *
     * @param string $baseurl The base url to redirect to after an action takes
     *                        place
     */
    static function handle_field_action($baseurl) {
        //handle removal of a field from export
        $delete = optional_param('delete', 0, PARAM_INT);
        if ($delete != 0) {
            self::delete_field_from_export($delete);
            redirect($baseurl, '', 0);
        }

        //handle moving a field down the list
        $down = optional_param('down', 0, PARAM_INT);
        if ($down != 0) {
            self::move_field($down, rlipexport_version1_config::DIR_DOWN);
            redirect($baseurl, '', 0);
        }

        //handle moving a field up the list
        $up = optional_param('up', 0, PARAM_INT);
        if ($up != 0) {
            self::move_field($up, rlipexport_version1_config::DIR_UP);
            redirect($baseurl, '', 0);
        }

        //handle adding a field to the list
        $field = optional_param('field', 0, PARAM_INT);
        if ($field != 0) {
            self::add_field_to_export($field);
            redirect($baseurl, '', 0);
        }

        //handle field renaming
        $updatefields = optional_param('updatefields', '', PARAM_CLEAN);
        if ($updatefields !== '') {
            $data = data_submitted();
            self::update_field_headers($data);
            redirect($baseurl, '', 0);
        }
    }
}

/**
 * Performs page setup work needed on the page for configuring which profile
 * fields are part of the export
 *
 * @param string $baseurl The page's base url
 */
function rlipexport_version1_page_setup($baseurl) {
    global $PAGE, $SITE;

    //set up the basic page info
    $PAGE->set_url($baseurl);
    $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
    $displaystring = get_string('configuretitle', 'rlipexport_version1');
    $PAGE->set_title("$SITE->shortname: ".$displaystring);
    $PAGE->set_heading($SITE->fullname);

    //use the default admin layout
    $PAGE->set_pagelayout('admin');

    //add navigation items
    $PAGE->navbar->add(get_string('administrationsite'));
    $PAGE->navbar->add(get_string('plugins', 'admin'));
    $PAGE->navbar->add(get_string('blocks'));
    $PAGE->navbar->add(get_string('exportfields', 'rlipexport_version1'));
}

/**
 * Calculates the HTML code needed to show an icon wrapped in an anchor
 *
 * @param string $url The URL to link to
 * @param string $imageidentifier The short-hand image identifier (e.g. t/up)
 * @return string The HTML code including the anchor and the image
 */
function rlipexport_version1_linked_image($url, $imageidentifier) {
    global $OUTPUT;

    //get the full image tag
    $imageurl = $OUTPUT->pix_url($imageidentifier);
    $imagetag = html_writer::empty_tag('img', array('src' => $imageurl));

    //return the full anchor tag
    $attributes = array('href' => $url);
    return html_writer::tag('a', $imagetag, $attributes);
}