<?php
function is_elis() {
    global $CFG;
    return file_exists($CFG->dirroot . '/curriculum/config.php') && record_exists('block', 'name', 'curr_admin');
}

?>
