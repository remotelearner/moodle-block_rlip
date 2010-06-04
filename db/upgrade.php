<?php


function xmldb_block_rlip_upgrade($oldversion = 0) {
    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2010051703) {
        if(empty($CFG->block_rlip_impuser_filename)) {
            set_config('block_rlip_impuser_filename', 'user.csv');
        }
        if(empty($CFG->block_rlip_impuser_filetype)) {
            set_config('block_rlip_impuser_filetype', 'csv');
        }

        if(empty($CFG->block_rlip_impcourse_filename)) {
            set_config('block_rlip_impcourse_filename', 'enroll.csv');
        }
        if(empty($CFG->block_rlip_impcourse_filetype)) {
            set_config('block_rlip_impcourse_filetype', 'csv');
        }

        if(empty($CFG->block_rlip_impenrolment_filename)) {
        set_config('block_rlip_impenrolment_filename', 'course.csv');
        }
        if(empty($CFG->block_rlip_impenrolment_filetype)) {
            set_config('block_rlip_impenrolment_filetype', 'csv');
        }
    }

    return $result;
}
?>
