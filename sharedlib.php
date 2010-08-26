<?php

function is_elis() {
    global $CFG;

    if (!file_exists($CFG->dirroot.'/curriculum/config.php')) {
        // return early!
        return false;
    }
    if (!record_exists('block', 'name', 'curr_admin')) {
        return false;
    }
    return true;
}

?>