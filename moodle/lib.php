<?php
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

define('RLIP_DIRLOCATION', $CFG->dirroot . '/blocks/rlip');

/**
 * all IP import plugins must extend this class
 */
abstract class elis_import {
    protected $headers;

    protected $log_filer;


    //each of the import functions return an object containing
    //$header which gives the field name for each field
    //$records an array of arrays of each record's fields values
    public abstract function import_user($file);
    public abstract function import_enrolment($file);
    public abstract function import_course($file);
    
//    public abstract function import_coursecategories($file = null);

    /**
     * sets the log file for outputing the import report
     * @global object $CURMAN
     * @param string $logfile name of the log file
     */
    public function __construct($logfile=null) {
        global $CFG;
        
        $this->log_filer = new log_filer($CFG->block_rlip_logfilelocation, $logfile);
    }

    /**
     * calls import_user, import_enrolment, iimport_course and calls the associated methods to
     * import the records
     * @param string $file name and location of the file to import
     * @param string $type type of records being imported
     */
    public function import_records($file, $type) {
        $retval = false;
        $context = get_context_instance(CONTEXT_SYSTEM);

        if(has_capability('block/rlip:config', $context)) {
            try {
                is_file($file) OR throwException("file $file not found");

                $method = "import_$type";
                method_exists($this, $method) OR throwException("unimplemented import $type");
                $data = $this->$method($file);  //calls import_<import type> on the import file

                $method = "get_$type";
                method_exists($this,$method) OR throwException("unimplemented get $type");
                $records = $this->$method($data);      //gets all the records in a proper form to insert

                $this->process($records, $type);

                $retval = true;
                
                if(!empty($file) && is_file($file)) {
//                    unlink($file);
                }
            } catch(Exception $e) {
                $this->log_filer->add_error($e->getMessage());
            }

            $this->log_filer->output_log();
        }

        return $retval;
    }

    /**
     * gets user records and send them to processing
     * @param array $data user records to be placed in the database
     */
    private function get_user($data) {
        return $this->get(new user_import(), $data);
    }

    /**
     * gets user records and send them to processing
     * @param array $data enrolment records
     */
    private function get_enrolment($data) {
        $si = new student_import();
        $properties = $si->get_properties_map();

        in_array($properties['context'], $columns) OR throwException('header must contain a context field');

        return $this->get($si, $data);
    }

    /**
     * get course records and send them to processing
     * @param  array $data course records
     */
    private function get_course($data) {
        return $this->get(new course_import(), $data);
    }

    private function get($import, $data) {
        $columns = $data->header;
        $records = $data->records;

        $properties = $import->get_properties_map();

        in_array($properties['execute'], $columns) OR throwException('header must contain an action field');

        $missing_fields = $import->get_missing_fields($columns);
        if(!empty($missing_fields)) {
            $missing = implode(', ', $missing_fields);

            throwException("missing required fields $missing");
        }

        $items = $import->get_items($records);

        return $items;
    }

    /**
     * does some checking of enrolment records then calls apropriate action on each record
     * to enrol/unenrol
     * @global object $CURMAN
     * @param array $records records to upload to db
     * @param int $num number of columns each record should have
     */
    public function enrolment_handler($records, $num) {
        global $CURMAN;

        $properties = student_import::get_properties_map();

        foreach($records as $r) {
            if(count($r) === $num) {
                $context = current(explode('_', $r[$properties['context']], 2));
                $method = "handle_{$context}_{$r[$properties['execute']]}";
                $this->$method($r);
            } else if(count($r) < $num) {
                $this->log_filer->add_error_record("not enough fields");
            } else if(count($r) > $num) {
                $this->log_filer->add_error_record("too many fields");
            }
        }
    }

    public function process($records, $type) {
        foreach($records as $record) {
            $obj = (object)$record;

            try {
                if(empty($obj->execute)) {

                    throwException('missing action');
                } else {
                    $method = "{$type}_$obj->execute";
                    $this->$method($obj);
                }
            } catch(Exception $e) {
                $this->log_filer->add_error_record($e->getMessage());
            }
        }
    }

    /**
     * logs an error if a non-existant method is called
     * eg if given a bad action or record type
     * @param string $name name of method
     * @param array $arguments
     */
    public function __call($name, $arguments) {
        $this->log_filer->add_error("invalid action $name");
    }

    /**
     * since "course" records can be for a course, track, curriculum, or cmclass
     * we have to do some processing before the records are inserted
     * @global object $CURMAN
     * @param array $records
     * @param int $num_columns
     */
    private function course_handler($records, $num_columns) {
        global $CURMAN;
        
        foreach($records as $r) {
            try {
                if(count($r) === $num_columns) {
                    $method = "handle_{$r['context']}_{$r['action']}";
                    $this->$method($r);
                } else if(count($r) < $num_columns) {
                    throwException('not enough fields');
                } else if(count($r) > $num_columns) {
                    throwException('too many fields');
                }
            } catch(Exception $e) {
                $this->log_filer->add_error_record($e->getMessage());
            }
        }
    }

    /**
     * creates a course given a new course record
     * @global object $CURMAN
     * @param array $r course record
     */
    private function handle_course_create($r) {
        global $CURMAN;

        $properties = course_import::get_properties_map();

        if(!empty($r[$properties['assignment']])) {
            $curriculum = curriculum::get_by_idnumber($r[$properties['assignment']]);
            if(empty($curriculum)) {
                $r[$properties['assignment']] = '';
                $this->log_filer->add_warning("curriculum {$r[$properties['assignment']]} not found");
            }
        }

        if(!empty($r[$properties['link']])) {
            $mcourseid = $CURMAN->db->get_field('course', 'id', 'shortname', $r[$properties['link']]);
            if(empty($mcourseid)) {
                $r[$properties['link']] = '';
                $this->log_filer->add_warning("course with shortname {$r[$properties['link']]} not found");
            }
        }

        if(!empty($r[$properties['environment']])) {
            $environment = environment::get_by_idnumber($r[$properties['environment']]);

            if(!empty($environment)) {
                $r['environmentid'] = $environment->id;
            } else {
                $this->log_filer->add_warning("invalid environment name {$r[$properties['environment']]}");
            }
        }
        
        $ci = new course_import();
        $course = $ci->get_item($r);

        $this->create($course);
    }

    /**
     * creates curriculum given a curriculum record
     * @param array $r curriculum record
     */
    private function handle_curr_create($r) {
        $cui = new curriculum_import();
        $curr = $cui->get_item($r);

        $this->create($curr);
    }

    /**
     * creates a track given a track record
     * @param array $r track record
     */
    private function handle_track_create($r) {
        $ti = new track_import();
        $properties = $ti->get_properties_map();
        
        if(!empty($properties['assignment']) && !empty($r[$properties['assignment']])) {      //assignment column can not be renamed
            $curriculum = curriculum::get_by_idnumber($r[$properties['assignment']]);

            if(!empty($curriculum)) {
                $r['curid'] = $curriculum->id;
                $track = $ti->get_item($r);

                $this->create($track);
            } else {
                $this->log_filer->add_error('associated curriculum does not exist');
            }
        } else {
            $this->log_filer->add_error_record('no associated curriculum declared');
        }
    }

    /**
     * creates a class given a class record
     * @global object $CURMAN
     * @param array $r class record
     */
    private function handle_class_create($r) {
        global $CURMAN;

        $cmi = new cmclass_import();
        $properties = $cmi->get_properties_map();

        if(!empty($r[$properties['assignment']])) {
            $course = course::get_by_idnumber($r[$properties['assignment']]);

            if(!empty($course)) {
                if(!empty($r[$properties['link']])) {
                    if(strcmp($r[$properties['link']], 'auto') === 0) {
                        $has_template = $CURMAN->db->get_field('crlm_coursetemplate', 'id', 'courseid', $course->id);

                        if(!empty($has_template)) {
                            $r['autocreate'] = true;
                        } else {
                            $this->log_filer->add_warning('associated course does not have a template moodle class');
                        }
                    } else {
                        //FIXME: dbcall that should be made into the api
                        $mcourseid = $CURMAN->db->get_field('course', 'id', 'shortname', $r['link']);

                        if(!empty($mcourseid)) {
                            $r['moodlecourseid'] = $mcourseid;
                        } else {
                            $this->log_filer->add_warning("invalid moodle course name {$r[$properties['link']]}");
                        }
                    }
                }

                if(!empty($r[$properties['track']])) {
                    $track = track::get_by_idnumber($r[$properties['track']]);

                    if(!empty($track)) {
                        $r[$properties['track']] = $trak->id;
                    } else {
                        $this->log_filer->add_warning("invalid trak idnumber {$r[$properties['track']]}");
                    }
                }

                if(!empty($r[$properties['environment']])) {
                    $environment = environment::get_by_idnumber($r[$properties['environment']]);

                    if(!empty($environment)) {
                        $r['environmentid'] = $environment->id;
                    } else {
                        $this->log_filer->add_warning("invalid environment name {$r[$properties['environment']]}");
                    }
                }

                $course = $cmi->get_item($r);

                $this->create($course);
            } else {
                $this->log_filer->add_error("associated course {$r[$properties['assignment']]} not found");
            }
        } else {
            $this->log_filer->add_error_record('no associated course declared');
        }
    }

    /**
     * updates an elis course
     * 
     * @global object $CURMAN
     * @param array $r 
     */
    public function handle_course_update($r) {
        global $CURMAN;

        $properties = course_import::get_properties_map();

        !empty($r[$properties['idnumber']]) OR throwException('missing idnumber');
        $course = course::get_by_idnumber($r[$properties['idnumber']]);

        !empty($course) OR throwException('no course with that idnumber');

        if(!empty($r[$properties['assignment']])) {
            $curriculum = curriculum::get_by_idnumber($r[$properties['assignment']]);

            if(!empty($curriculum)) {
                $course->curriculum = $curriculum->id;
            } else {
                $this->log_filer->add_warning("curriculum {$r[$properties['assignment']]} not found");
            }
        }

        if(!empty($r[$properties['link']])) {
            $mcourseid = $CURMAN->db->get_field('course', 'id', 'shortname', $r[$properties['link']]);

            if(!empty($mcourseid)) {
                $course->location = $mcourseid;
                $course->templateclass = 'moodlecourseurl';
            } else {
                $this->log_filer->add_warning("course with shortname {$r[$properties['link']]} not found");
            }
        }

        if(!empty($r[$properties['environment']])) {
            $environment = environment::get_by_idnumber($r[$properties['environment']]);

            if(!empty($environment)) {
                $course->environmentid = $environment->id;
                $course->environment = $environment;
            } else {
                $this->log_filer->add_warning("invalid environment name {$r[$properties['environment']]}");
            }
        }

        foreach($course->properties as $p=>$null) {
            if(!empty($properties[$p])) {
                if(!empty($r[$properties[$p]])) {
                    $course->$p = $r[$properties[$p]];
                }
            }
        }

        if($course->update()) {
            $this->log_filer->add_success("updated $course->idnumber");
        } else {
            throwException("failed to update $course->idnumber");
        }
    }

    public function handle_curr_update($r) {
        $properties = course_import::get_properties_map();

        !empty($r[$properties['idnumber']]) OR throwException('missing idnumber');

        $curr = curriculum::get_by_idnumber($r[$properties['idnumber']]);

        !empty($curr) OR throwException("no curriculum with that {$r[$properties['idnumber']]}");

        foreach($curr->properties as $p=>$null) {
            if(!empty($properties[$p])) {
                if(!empty($r[$properties[$p]])) {
                    $curr->$p = $r[$properties[$p]];
                }
            }
        }

        if($curr->update()) {
            $this->log_filer->add_success("updated $curr->idnumber");
        } else {
            throwException("failed to update $curr->idnumber");
        }
    }

    public function handle_class_update($r) {
        global $CURMAN;

        $properties = cmclass_import::get_properties_map();
                    //get the class that we are going to update
        !empty($r[$properties['idnumber']]) OR throwException('missing idnumber');
        $class = cmclass::get_by_idnumber($r[$properties['idnumber']]);

        !empty($class) OR throwException("no class with idnumber {$r[$properties['idnumber']]}");

                //get the course this class is associated with
        !empty($r[$properties['assignment']]) OR throwException('no associated course declared');
        $course = course::get_by_idnumber($r[$properties['assignment']]);

        !empty($course) OR throwException("associated course {$r[$properties['assignment']]} not found");

        if(empty($class->autocreate)) {
            $class->autocreate = false;
        }

        if(!empty($r[$properties['link']])) {
            if(strcmp($r[$properties['link']], 'auto') === 0) {
                $has_template = $CURMAN->db->get_field('crlm_coursetemplate', 'id', 'courseid', $course->id);

                if(!empty($has_template)) {
                    $class->autocreate = true;
                } else {
                    $this->log_filer->add_warning('associated course does not have a template moodle class');
                }
            } else {
                //FIXME: dbcall that should be made into the api
                $mcourseid = $CURMAN->db->get_field('course', 'id', 'shortname', $r['link']);

                if(!empty($mcourseid)) {
                    $class->moodlecourseid = $mcourseid;
                } else {
                    $this->log_filer->add_warning("invalid moodle course name {$r[$properties['link']]}");
                }
            }
        }

        if(!empty($r[$properties['track']])) {
            $track = track::get_by_idnumber($r[$properties['track']]);

            if(!empty($track)) {
                $class->track = $trak->id;
            } else {
                $this->log_filer->add_warning("invalid trak idnumber {$r[$properties['track']]}");
            }
        }

        if(!empty($r[$properties['environment']])) {
            $environment = environment::get_by_idnumber($r[$properties['environment']]);

            if(!empty($environment)) {
                $class->environmentid = $environment->id;
            } else {
                $this->log_filer->add_warning("invalid environment name {$r[$properties['environment']]}");
            }
        }

        foreach($class->properties as $p=>$null) {
            if(!empty($properties[$p])) {
                if(!empty($r[$properties[$p]])) {
                    $class->$p = $r[$properties[$p]];
                }
            }
        }

        if($class->update()) {
            $this->log_filer->add_success("updated $class->idnumber");
        } else {
            throwException("failed to update $class->idnumber");
        }
    }

    public function handle_track_update($r) {
        $properties = track_import::get_properties_map();

        !empty($properties['idnumber']) OR throwException('missing idnumber');

        $track = track::get_by_idnumber($r[$properties['idnumber']]);

        !empty($track) OR throwException("no track with idnumber {$r[$properties['idnumber']]}");

        !empty($r[$properties['assignment']]) OR throwException('no associated curriculum declared');
        $curriculum = curriculum::get_by_idnumber($r[$properties['assignment']]);

        !empty($curriculum) OR throwException('associated curriculum does not exist');
        $track->curid = $curriculum->id;

        foreach($track->properties as $p=>$null) {
            if(!empty($properties[$p])) {
                if(!empty($r[$properties[$p]])) {
                    $track->$p = $r[$properties[$p]];
                }
            }
        }

        if($track->update()) {
            $this->log_filer->add_success("updated $track->idnumber");
        } else {
            throwException("failed to update $track->idnumber");
        }
    }

    /**
     * delete a class record
     * @param array $r record to be deleted from the db
     */
    public function handle_class_delete($r) {
    	$this->handle_delete($r, 'cmclass');
    }

    /**
     * delete a track record
     * @param array $r record to be deleted from the db
     */
    public function handle_track_delete($r) {
    	$this->handle_delete($r, 'track');
    }

    /**
     * delete a course record
     * @param array $r record to be deleted from the db
     */
    public function handle_course_delete($r) {
    	$this->handle_delete($r, 'course');
    }

    /**
     * delete a curriculum record
     * @param array $r record to be deleted from the db
     */
    public function handle_curr_delete($r) {
    	$this->handle_delete($r, 'curriculum');
    }

    /**
     * delete any of course, curriculum, track, class records
     * @param array $record record to be deleted
     * @param string $type type of record to be deleted
     */
    public function handle_delete($record, $type) {
    	$context = new $type();
        $context = $context->get_by_idnumber($record['idnumber']);      //idnumber column can not be renamed

        if(!empty($context)) {
        	$name = $context->to_string();
        	if($context->delete()) {
        		$this->log_filer->add_success("deleted $name");
        	} else {
        		$this->log_filer->add_error_record("failed to delete $name");
        	}
        } else {
        	$this->log_filer->add_error_record("missing record with idnumber {$record['idnumber']}");
        }
    }

    /**
     * add a user entry
     * @param object $user user object
     */
    public function user_add($user) {
        if (!record_exists('user', 'username', $user->username)) {
            /// Add a new user
            $user->password   = hash_internal_user_password($user->password);
            $user->timemodified   = time();
            $user->id = insert_record('user', $user);
            $this->log_filer->add_success("user {$user->username} added");
        } else {
            throwException("add failed user $user->username already exists");
        }
    }

    /**
     * updates a given user record
     * @param object $user user to be updated
     */
    public function user_update($user) {
        if(record_exists('user', 'username', $user->username)) {
            /// Update an existing user
            $user->password   = hash_internal_user_password($user->password);
            $user->timemodified   = time();
            update_record('user', $user);
            $this->log_filer->add_success("user {$user->username} added");
        } else {
            throwException("update failed user $user->username not found");
        }
    }

    /**
     * delete given user
     * @param object $user user to be deleted
     */
    public function user_disable($user) {
        $userid = get_record('user', 'username', $user->username, 'deleted', '0');

        if(!empty($userid)) {
            delete_user($userid);
        } else {
            throwException("delete failed user $user->username not found");
        }

    }

    /**
     * used to support alternate spelling of enrol when retrieving an action
     * @param array $item class enrolment record
     * @see handle_class_enroll
     */
    public function handle_class_enrol($item) {
    	$this->handle_class_enroll($item);
    }

    /**
     * enroll students in a class or track based on the return value from student_import
     * @param array $item student record to enrol
     */
    public function handle_class_enroll($item) {
        $si = new student_import();
        $record = $si->get_item($item);
        if(!empty($record->item)) {
            $item = $record->item;

            if($item->has_required_fields() === true) {
                if($item->duplicate_check() === false) {
                    if($item->add()) {
                        $this->log_filer->add_success("{$item->to_string()} added");
                    } else {
                        $this->log_filer->add_error_record("{$item->to_string()} to database");
                    }
                } else {
                    $this->log_filer->add_error_record("{$item->to_string()} already exists");
                }
            } else {
                $required = $item->get_missing_required_fields();
                $required = implode(', ', $required);

                $this->log_filer->add_error_record("missing required fields $required");
            }
        } else {
            $this->log_filer->add_error_record('unable to get record');
        }
    }

    /**
     * used for alternate spelling of unenroll
     * @see handle_class_unenroll
     */
    public function handle_class_unenrol($item) {
    	$this->handle_class_unenroll($item);
    }

    /**
     * unenroll a student from a class
     * @param array $item record of student to enrol
     */
    public function handle_class_unenroll($item) {
        $properties = student_import::get_properties_map();

        $temp= user::get_by_idnumber($item[$properties['user_idnumber']]);

        if(!empty($temp->id)) {
            $userid = $temp->id;

            $context = explode('_', $item['context'], 2);
            next($context);
            $idnumber = current($context);
            $temp = cmclass::get_by_idnumber($idnumber);

            if(!empty($temp->id)) {
                $classid = $temp->id;
                $cmclass = student::get_userclass($userid, $classid);

                if(!empty($cmclass)) {
                    if($cmclass->has_required_fields() === true) {
                        if($cmclass->duplicate_check() === true) {
                            if($cmclass->delete()) {
                                $this->log_filer->add_success("{$cmclass->to_string()} removed");
                            } else {
                                $this->log_filer->add_error_record("inserting {$cmclass->to_string()} to database");
                            }
                        } else {
                            $this->log_filer->add_error_record("{$cmclass->to_string()} does not exist");
                        }
                    } else {
                        $required = $cmclass->get_missing_required_fields();
                        $required = implode(', ', $required);

                        $this->log_filer->add_error_record("missing required fields $required");
                    }
                } else {
                    $this->log_filer->add_error_record("no enrolment for student {$item[$properties['user_idnumber']]} in class {$idnumber}");
                }
            } else {
                $this->log_filer->add_error_record("class $idnumber not found");
            }
        } else {
            $this->log_filer->add_error_record("user {$item[$properties['user_idnumber']]} not found");
        }
    }

    /**
     * @see handle_track_enroll
     */
    public function handle_track_enrol($item) {
        $this->handle_track_enroll($item);
    }

    /**
     * enroles users in a track
     * @see handle_class_enroll
     */
    public function handle_track_enroll($item) {
        $this->handle_class_enroll($item);
    }

    /**
     * unenroll a user from a track
     * @param array $item user track record
     */
    public function handle_track_unenrol($item) {
        if(!empty($item)) {
            $properties = student_import::get_properties_map();
            $temp = user::get_by_idnumber($item[$properties['user_idnumber']]);

            if(!empty($temp->id)) {
                $userid = $temp->id;
                $context = explode('_', $item[$properties['context']], 2);
                next($context);
                $idnumber = current($context);
                $temp = cmclass::get_by_idnumber($idnumber);

                if(!empty($temp->id)) {
                    $classid = $temp->id;
                    $track = usertrack::get_usertrack($userid, $classid);

                    if(!empty($track)) {
                        if($track->has_required_fields() === true) {
                            if($track->duplicate_check() === false) {
                                if($track->add()) {
                                    $this->log_filer->add_success("{$track->to_string()} added");
                                } else {
                                    $this->log_filer->add_error_record("{$track->to_string()} to database");
                                }
                            } else {
                                $this->log_filer->add_error_record("{$track->to_string()} already exists");
                            }
                        } else {
                            $required = $track->get_missing_required_fields();
                            $required = implode(', ', $required);

                            $this->log_filer->add_error_record("missing required fields $required");
                        }
                    } else {
                        $this->log_filer->add_error_record("no enrolment for student {$item[$properties['user_idnumber']]} in track {$idnumber}");
                    }
                } else {
                    $this->log_filer->add_error_record("track $idnumber not found");
                }
            } else {
                $this->log_filer->add_error_record("user {$item[$properties['user_idnumber']]} not found");
            }
        }
    }

    /**
     * @see handle_track_unenrol
     */
    public function handle_track_unenroll($item) {
        $this->handle_track_unenrol($item);
    }

    public function handle_user_enroll($item) {
        $this->handle_user_enrol($item);
    }

    public function handle_user_enrol($item) {
        $properties = student_import::get_properties_map();

        $record_context = explode('_', $item[$properties['context']], 2);
        next($record_context);
        $idnumber = current($record_context);
        $temp = user::get_by_idnumber($idnumber);

        if(!empty($temp)) {
            $muserid = cm_get_moodleuserid($temp->id);

            if(!empty($muserid)) {
                $context = get_context_instance(CONTEXT_USER, $muserid);

                if(!empty($context)) {
                    $parent_recordid = $item[$properties['user_idnumber']];
                    $temp = user::get_by_idnumber($parent_recordid);

                    if(!empty($temp)) {
                        $parentid  = cm_get_moodleuserid($temp->id);

                        if(!empty($parentid)) {
                //        $assignableroles = get_assignable_roles($context, 'name', ROLENAME_BOTH);
                            $roleid = get_field('role', 'id', 'shortname', $item[$properties['role']]);

                            if(!empty($roleid)) {
                                $starttime = empty($item[$properties['enrolmenttime']])? 0: $item[$properties['enrolmenttime']];
                                $endtime = empty($item[$properties['completetime']])? 0: $item[$properties['completetime']];
                                
                                if(role_assign($roleid, $parentid, 0, $context->id, $starttime, $endtime)) {
                                    $this->log_filer->add_success("$parent_recordid made {$item[$properties['role']]} of  $idnumber");
                                } else {
                                    $this->log_filer->add_error_record("can not assign role to user");
                                }
                            } else {
                                $this->log_filer->add_error_record("invalid role short name {$item[$properties['role']]}");
                            }
                        } else {
                            $this->log_filer->add_error_record("$parent_recordid not associated with a moodle user");
                        }
                    } else {
                        $this->log_filer->add_error_record("invalid idnumber $parent_recordid");
                    }
                } else {
                    $this->log_filer->add_error_record("userid not associated with a moodle user");
                }
            } else {
                $this->log_filer->add_error_record("$idnumber not associated with a moodle user");
            }
        } else {
            $this->log_filer->add_error_record("invalid idnumber $idnumber");
        }
    }

    public function handle_user_unenroll($item) {
        $this->handle_user_unenrol($item);
    }

    public function handle_user_unenrol($item) {
        $properties = student_import::get_properties_map();

        $record_context = explode('_', $item[$properties['context']], 2);
        next($record_context);
        $idnumber = current($record_context);
        $temp = user::get_by_idnumber($idnumber);

        if(!empty($temp)) {
            $muserid = cm_get_moodleuserid($temp->id);

            if(!empty($muserid)) {
                $context = get_context_instance(CONTEXT_USER, $muserid);

                if(!empty($context)) {
                    $parent_recordid = $item[$properties['user_idnumber']];
                    $temp = user::get_by_idnumber($parent_recordid);

                    if(!empty($temp)) {
                        $parentid  = cm_get_moodleuserid($temp->id);

                        if(!empty($parentid)) {
                //        $assignableroles = get_assignable_roles($context, 'name', ROLENAME_BOTH);
                            $roleid = get_field('role', 'id', 'shortname', $item[$properties['role']]);

                            if(!empty($roleid)) {
                                $starttime = empty($item[$properties['enrolmenttime']])? 0: $item[$properties['enrolmenttime']];
                                $endtime = empty($item[$properties['completetime']])? 0: $item[$properties['completetime']];

                                if(role_unassign($roleid, $parentid, 0, $context->id, $starttime, $endtime)) {
                                    $this->log_filer->add_success("$parent_recordid removed from {$item[$properties['role']]} role of  $idnumber");
                                } else {
                                    $this->log_filer->add_error_record("can not assign role to user");
                                }
                            } else {
                                $this->log_filer->add_error_record("invalid role short name {$item[$properties['role']]}");
                            }
                        } else {
                            $this->log_filer->add_error_record("$parent_recordid not associated with a moodle user");
                        }
                    } else {
                        $this->log_filer->add_error_record("invalid idnumber $parent_recordid");
                    }
                } else {
                    $this->log_filer->add_error_record("userid not associated with a moodle user");
                }
            } else {
                $this->log_filer->add_error_record("$idnumber not associated with a moodle user");
            }
        } else {
            $this->log_filer->add_error_record("invalid idnumber $idnumber");
        }
    }

    /**
     * creates a track, course, curriculum, or class in the cm system
     * @param object $record contains an item object that will be added to the cm system
     */
    public function create($record) {
    	$item = $record->item;
        if($item->has_required_fields()) {
            if($item->duplicate_check() === false) {
                if($item->add()) {
                    $this->log_filer->add_success("added {$item->to_string()}");
                } else {
                    $this->log_filer->add_error_record("creating item {$item->to_string()}");
                }
            } else {
                $this->log_filer->add_error_record("record with idnumber $item->idnumber already exists");
            }
        } else {
            $required = $item->get_missing_required_fields();
            $this->log_filer->add_error_record("missing required fields $required");
        }
    }
}

/**
 * used to log messages to a file
 */
class log_filer {
    private $file = null;
    private $endl = "\n"; //new line delimiter
    private $warning = '';
    private $logs = array();
    private $count = 1; //holds the current record being logged to the file
    private $filename = '';

    /**
     * opens a file to append to with the given file name in the given file location
     * @param string $file location where log file is to be put
     * @param string $filename name of log file
     */
    function __construct($file, $filename) {
        if(!empty($file) && is_dir(addslashes($file))) {
            $this->filename = addslashes($file) . '/' . $filename . '.log';
            $this->file = fopen($this->filename, 'a');
        }
    }

    /**
     * print a string to the file with a new line at the end
     * @param string $line what to print
     */
    function lfprintln($line = '') {
        $this->lfprint($line . $this->endl);
    }

    /**
     * prints a string to the file
     * @param string $str what to print
     */
    function lfprint($str = '') {
        $this->logs[] = $str;
    }

    /**
     * ues the count to display what record contained the error
     * @param string $line prints an error message to the file for a particular record
     */
    function add_error_record($line='error') {
        $this->lfprintln("error with record #$this->count: $line $this->warning");
        $this->warning = '';
        $this->count++;
    }

    /**
     * adds an error message to the log file
     * @param string $line error message
     */
    function add_error($line='error') {
        $this->lfprintln("error: $line $this->warning");
        $this->warning = '';
    }

    /**
     * adds indication of successfully used the record
     * @param string $line success message
     */
    function add_success($line='success') {
        $this->lfprintln("success with record #$this->count: $line $this->warning");
        $this->warning = '';
        $this->count++;
    }

    /**
     * adds a warning to the log fil for the current record
     * @param string $line warning message
     */
    function add_warning($line='warning') {
        if(empty($this->warning)) {
            $this->warning = ' WARNING ' . $line;
        } else {
            $this->warning .= ', ' . $line;
        }
    }

    /**
     * prints all the messages to the log file
     * @global object $CURMAN
     * @global object $USER
     * @param object $file name of the file to log to
     */
    function output_log($file=null) {
        global $CFG, $USER;

        if(empty($file)) {
            $file = $this->file;
        }

        if(!empty($file)) {
            $message = '';
            foreach($this->logs as $log) {
                fwrite($file, $log);
                $message .= $log . "\n";
            }

            $idnumbers = explode(',', $CFG->block_rlip_emailnotification);

            $subject = 'integration point log';

            foreach($idnumbers as $idnum) {
                if(!empty($idnum)) {
                    $cmuser = get_record('user', 'idnumber', $idnum, 'deleted', '0');   //have to assume that idnumbers are unique

                    if(!empty($cmuser)) {
                        email_to_user($cmuser, $USER, $subject, $message);
                    }
                }
            }
        }
    }

    /**
     * close the file when this object loses focus, may not be needed but there
     * as a precaucion
     */
    function __destruct() {
        if(!empty($this->file)) {
            fclose($this->file);
        }
    }
}

/**
 *
 */
class user_import extends import {
    protected $context = 'user';
    protected $required = array('username',
                                'password',
                                'firstname',
                                'lastname',
                                'email',
                                'city',
                                'country');
    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    protected function get_fields() {
        $retval = array('idnumber',
                        'auth',
                        'username',
                        'password',
                        'email',
                        'firstname',
                        'lastname',
                        'mi',
                        'city',
                        'country',
                        'maildigest',
                        'autosubscribe',
                        'trackforums',
                        'timezone',
                        'language',
                        'theme',
                        'screen_reader',
                        'description',
                        'id_number',
                        'institution',
                        'department');

        $retval = array_combine($retval, $retval);

        $retval['execute'] = 'action';

        $custom_fields = get_records('user_info_field');
        foreach($custom_fields as $cf) {
            $retval[$cf->shortname] = $cf->shortname;
        }

        return $retval;
    }
}

/**
 *
 */
class student_import extends import {
    protected $context = 'student';
    protected $required = array('roleid',
                                'userid',
                                'contextid');
    /**
     *
     * @param <type> $record
     * @return <type>
     */
    public function get_item($record) {
        return null;
    }

    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    protected function get_fields() {
        $retval = array('username',
                        'userrole',
                        'useridnumber',
                        'course_idnumber',
                        'starttime',
                        'endtime');

        $retval = array_combine($retval, $retval);
        $retval['execute'] = 'action';

        return $retval;
    }
}

/**
 *
 */
class course_import extends import {
    protected $context = 'course';
    protected $required = array('category',
                                'fullname',
                                'shortname');
    /**
     *
     * @global <type> $CURMAN
     * @param <type> $record
     * @return <type>
     */
    public function get_item($record) {
        global $CURMAN;
        
        $properties_map = $this->get_properties_map();
        $item_record = array();
        
        
        return $temp;
    }

    /**
     *
     * @return array    user fields
     */
    protected function get_fields() {
        $retval = array('category',
                'format',
                'fullname',
                'guest',
                'idnumber',
                'lang',
                'maxbytes',
                'metacourse',
                'newsitems',
                'notifystudents',
                'numsections',
                'password',
                'shortname',
                'showgrades',
                'showreports',
                'sortorder',
                'startdate',
                'summary',
                'timecreated',
                'topic0',
                'visible',
                'link');

        $retval = array_combine($retval, $retval);

        $retval['execute'] = 'action';

        return $retval;
    }
}

abstract class import {
    protected $context;

    protected abstract function get_fields();

    protected function get_item($record) {
        $retval = array();
        $properties = $this->get_properties_map();

        foreach($properties as $key=>$p) {
            if(!empty($record[$p])) {
                $retval[$key] = $record[$p];
            }
        }

        return $retval;
    }

    /**
     *
     * @param <type> $columns
     * @return <type>
     */
    public function check_required_columns($columns) {
        $map = $this->get_properties_map();
        
        foreach($this->required as $r) {
            if(empty($columns[$map[$r]])) {
                return false;
            }
        }

        //decide and check wich properties must be present to process an item
        return true;
    }

    /**
     *
     * @param <type> $columns
     * @return <type>
     */
    public function get_missing_fields($columns) {
        $retval = array();
        
        $map = $this->get_properties_map();

        foreach($this->required as $r) {
            if(!in_array($map[$r], $columns)) {
                $retval[] = $map[$r];
            }
        }

        return $retval;
    }

    /**
     *
     * @param <type> $records
     * @return <type>
     */
    public function get_items($records) {
        $retval = array();

        foreach($records as $rec) {
            $retval[] = $this->get_item($rec);
        }

        return $retval;
    }
    
    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    public function get_properties_map() {
        $retval = $this->get_fields();

        $properties_map = get_records('block_rlip_fieldmap', 'context', $this->context);

        if(!empty($properties_map)) {
            foreach($properties_map as $pm) {
                $retval[$pm->fieldname] = $pm->fieldmap;
            }
        }

        return $retval;
    }

    /**
     *
     * @global object $CURMAN
     * @param string $key
     * @param string $value
     * @return object
     */
    public function set_property_map($key, $value) {
        $map = $this->get_properties_map();

        if(!record_exists('block_rlip_fieldmap', 'context', $this->context, 'fieldname', $key)) {
            if(!empty($map[$key]) && strcmp($map[$key], $value) !== 0) {
                $dataobject->context = $this->context;
                $dataobject->fieldname = $key;
                $dataobject->fieldmap = $value;

                return insert_record('block_rlip_fieldmap', $dataobject);
            } else {
                //invalid key value
                return false;
            }
        } else {
            return set_field('block_rlip_fieldmap', 'fieldmap', $value, 'context', $this->context, 'fieldname', $key);
        }
    }
}

function throwException($message = null, $code = null) {
    throw new Exception($message, $code);
}
?>
