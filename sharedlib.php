<?php
/**
 * Returns true if site is using ELIS/CM.
 * Returns false if not ELIS, or if site has been configured to use non-ELIS IP.
 *
 * @param boolean $ignoreoverride Ignores the override ELIS IP setting. Used if
 * we need to know if ELIS is installed regardless of the config setting (like on
 * the settings page).
 */
function is_elis($ignoreoverride=false) {
    global $CFG;

    if (!$ignoreoverride && !empty($CFG->block_rlip_overrideelisip)) {
        return false;
    }

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