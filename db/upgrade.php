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
    
    if ($result && $oldversion < 2010120900) {
        //add a new table for storing the mapping of fields in the export
        //as opposed to the existing import mapping table
        $table = new XMLDBTable('block_rlip_export_fieldmap');
        
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->addFieldInfo('context', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->addFieldInfo('fieldname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->addFieldInfo('fieldmap', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->addFieldInfo('fieldorder', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL);
        
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        
        $table->addIndexInfo("fieldorder_ix", XMLDB_INDEX_UNIQUE, array('fieldorder'));
        
        $result = $result && create_table($table); 
    }

    return $result;
}
?>
