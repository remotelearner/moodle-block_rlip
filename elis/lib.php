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

require_once (CURMAN_DIRLOCATION . '/lib/user.class.php');

define('logfile', 'logfile.log');

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
                $this->$method($data);      //processes the records and inserts them in the database
                $retval = true;
                
                if(!empty($file) && is_file($file)) {
                    unlink($file);
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
        $columns = $data->header;
        $records = $data->records;

        $ui = new user_import();
        $properties = $ui->get_properties_map();

        in_array($properties['execute'], $columns) OR throwException('header must contain an action field');

        if(!$ui->check_required_columns($columns)) {
            $missing_fields = $ui->get_missing_fields($columns);
            $missing = implode(', ', $missing_fields);

            throwException("missing required fields $missing");
        }

        $this->user_handler($records, count($columns));
    }

    /**
     * gets user records and send them to processing
     * @param array $data enrolment records 
     */
    private function get_enrolment($data) {
        $columns = $data->header;
        $records = $data->records;

        $si = new student_import();
        $properties = student_import::get_properties_map();

        in_array($properties['execute'], $columns) OR throwException('header must contain an action field');
        in_array($properties['context'], $columns) OR throwException('header must contain a context field');

        if(!$si->check_required_columns($columns)) {
            $missing_fields = $si->get_missing_fields($columns);

            $missing = implode(', ', $missing_fields);
            throwException("missing required fields $missing");
        }

        $this->enrolment_handler($records, count($columns));
    }

    /**
     * get course records and send them to processing
     * @param  array $data course records
     */
    private function get_course($data) {
        $columns = $data->header;
        $records = $data->records;

        $cmi = new cmclass_import();
        $ti = new track_import();
        $ci = new course_import();
        $cui = new curriculum_import();

        in_array('action', $columns) OR throwException('header must contain an action field');          //action and context fields can not be renamed
        in_array('context', $columns) OR throwException('header must contain a context field');
        
        if(!$ci->check_required_columns($columns) ||
            !$cmi->check_required_columns($columns) ||
            !$ti->check_required_columns($columns) ||
            !$cui->check_required_columns($columns)) {

            $missing_fields = array_merge($ci->get_missing_fields($columns),
                $cmi->get_missing_fields($columns),
                $ti->get_missing_fields($columns),
                $cui->get_missing_fields($columns));

            $missing_fields = array_unique($missing_fields);

            $missing = implode(', ', $missing_fields);
            
            throwException("missing required fields $missing");
        }

        $this->course_handler($records, count($columns));
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

    public function user_handler($records, $num) {
        $user_imp = new user_import();
        $users = $user_imp->get_items($records);

        foreach($users as $ui) {
            if(!empty($ui->action)) {
                if(!empty($ui->item)) {
                    $method = "user_{$ui->action}";
                    $this->$method($ui->item);
                } else {
                    $this->log_filer->add_error_record();
                }
            } else {
                $this->log_filer->add_error_record('action required');
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
        global $CURMAN;

        if($user->has_required_fields() === true) {
            if($user->duplicate_check() === false) {
                if($user->add()) {
                    $this->log_filer->add_success("user {$user->to_string()} added");

                    if(!empty($user->theme)) {
                        $CURMAN->db->set_field('user', 'theme', $user->theme, 'id', cm_get_moodleuserid($user->id));
                    }

                    if(!empty($user->auth)) {
                        $CURMAN->db->set_field('user', 'auth', $user->auth, 'id', cm_get_moodleuserid($user->id));
                    }
                } else {
                    $this->log_filer->add_error_record("user {$user->to_string()} to database");
                }
            } else {
                $this->log_filer->add_error_record("{$user->to_string()} record already exists");
            }
        } else {
            $required = $user->get_missing_required_fields();
            $required = implode(', ', $required);

            $this->log_filer->add_error_record("missing required fields $required");
        }
    }

    /**
     * delete given user
     * @param object $user user to be deleted
     */
    public function user_disable($user) {
        if(!empty($user->idnumber)) {
            $old_user = $user;
            $user = user::get_by_idnumber($user->idnumber); //save to different variable to extra properties checking

            if(!empty($user)) {
                if(strcmp($old_user->username, $user->username) !== 0) {
                    $this->log_filer->add_warning("user name does not match");
                }

                if($user->delete()) {
                    $this->log_filer->add_success("user {$user->to_string()} successfully disabled");
                } else {
                    $this->log_filer->add_error_record("updating user {$user->to_string()}");
                }
            } else {
                $this->log_filer->add_error_record("no user with idnumber {$old_user->idnumber}");
            }
        } else {
            $this->log_filer->add_error_record("missing idnumber field in record");
        }
    }

    /**
     * updates a given user record
     * @param object $user user to be updated
     */
    public function user_update($user) {
        global $CURMAN;

        if(!empty($user->idnumber)) {
            $old_user = $user;
            $user = user::get_by_idnumber($user->idnumber);
            
            if(!empty($user)) {
                if(!empty($user)) {
                    foreach($old_user->properties as $key=>$null) {
                        if(!empty($old_user->$key)) {
                            $user->$key = $old_user->$key;
                        }
                    }

                    if($user->has_required_fields() === true) {
                        if($user->update()) {
                            if(!empty($old_user->theme)) {
                                $CURMAN->db->set_field('user', 'theme', $old_user->theme, 'id', cm_get_moodleuserid($user->id));
                            }

                            if(!empty($old_user->auth)) {
                                $CURMAN->db->set_field('user', 'auth', $old_user->auth, 'id', cm_get_moodleuserid($user->id));
                            }
                            
                            $this->log_filer->add_success("user {$user->to_string()} successfully updated");
                        } else {
                            $this->log_filer->add_error_record("failed to update user {$user->to_string()}");
                        }
                    } else {
                        $required = $user->get_missing_required_fields();
                        $required = implode(', ', $required);

                        $this->log_filer->add_error_record("failed to update, missing required fields $required");
                    }
                } else {
                $this->log_filer->add_error_record("user with idnumber $old_user->idnumber not found");
                }
            } else {
                $this->log_filer->add_error_record("failed to update, no user with idnumber {$old_user->idnumber}");
            }
        } else {
            $this->log_filer->add_error_record("failed to update, missing idnumber field");
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
                $cmuser = user::get_by_idnumber(trim($idnum));
                
                if(!empty($cmuser)) {
                    $user = cm_get_moodleuser($cmuser->id);

                    if(!empty($user)) {
                        email_to_user($user, $USER, $subject, $message);
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
    protected $data_object = 'user';

    /**
     *
     * @param <type> $records
     * @return <type>
     */
    public function get_items($records) {
        global $USER;
        
        $properties_map = $this->get_properties_map();
        $user = new user();
        $retval = array();

        foreach($records as $rec) {
            $user_record = array();
            
            foreach($user->properties as $p=>$null) {
                if(!empty($properties_map[$p])) {
                    if(!empty($rec[$properties_map[$p]])) {
                        $user_record[$p] = $rec[$properties_map[$p]];
                    }
                }
            }

            if(!empty($user_record['password'])) {
                $user_record['password'] = hash_internal_user_password($user_record['password']);
            }

            $temp = new object();

            $temp->action = empty($rec[$properties_map['execute']])? '': $rec[$properties_map['execute']];

            if(!empty($user_record)) {
                if(!empty($user_record[$properties_map['country']])) {
                    if(!in_array($user_record[$properties_map['country']], get_list_of_countries())) {
                        $user_record[$properties_map['country']] = $USER->country;
                    }
                }

                $temp->item = new user($user_record);

                if(!empty($rec[$properties_map['theme']])) {
                    $temp->item->theme = $rec[$properties_map['theme']];
                }

                if(!empty($rec[$properties_map['auth']])) {
                    $temp->item->auth = $rec[$properties_map['auth']];
                }

                $custom_fields = field::get_for_context_level('user');

                if(!empty($custom_fields)) {
                    foreach($custom_fields as $cf) {
                        if(!empty($rec[$properties_map[$cf->shortname]])) {
                            $property = 'field_' . $cf->shortname;
                            $temp->item->$property = $rec[$properties_map[$cf->shortname]];
                        }
                    }
                }
            } else {
                $temp->item = null;
            }

            $retval[] = $temp;
        }

        return $retval;
    }

    public function get_item($record) {
    }

    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    public function get_properties_map() {
        global $CURMAN;

        $u = new user();
        $properties = array_keys($u->properties);

        $map = array_combine($properties, $properties);
        unset($map['id']);
        unset($map['timecreated']);
        unset($map['timeapproved']);
        unset($map['timemodified']);

        $map['execute'] = 'action';
        $map['theme'] = 'theme';
        $map['auth'] = 'auth';
        
        $custom_fields = field::get_for_context_level('user');

        if(!empty($custom_fields)) {
            foreach($custom_fields as $cf) {
                $map[$cf->shortname] = $cf->shortname;
            }
        }

        $properties_map = $CURMAN->db->get_records('crlm_field_map', 'context', 'user');

        if(!empty($properties_map)) {
            foreach($properties_map as $pm) {
                $map[$pm->elis_field] = $pm->data_field;
            }
        }

        return $map;
    }
}

/**
 *
 */
class student_import extends import {
    protected $data_object = 'student';

    public function __get($name) {
        if(empty($this->properties)) {
            $this->properties = $this->get_properties_map();
            
            if(strcmp($name, 'properties') === 0) {
                return $this->properties;
            }
        }

        if(!empty($this->properties[$name])) {
            return $this->properties[$name];
        }

        return null;
    }

    /**
     *
     * @param <type> $record
     * @return <type>
     */
    public function get_item($record) {
        $item = new student();                                  //dynamic student class
        $retval = array();

        foreach($item->properties as $p=>$type) {
            if($this->$p !== null && !empty($record[$this->$p])) {
                if(strcmp($p, 'completetime') === 0 || strcmp($p, 'enrolmenttime') === 0 || strcmp($p, 'endtime') === 0) {
                    $item_record[$p] = strtotime($record[$this->$p]);
                } else {
                    $item_record[$p] = $record[$this->$p];
                }
            }
        }

        if($this->user_idnumber !== null && !empty($record[$this->user_idnumber])) {
            $user = user::get_by_idnumber($record[$this->user_idnumber]);

            if(!empty($user)) {
                $item_record['userid'] = $user->id;
            }
        }

                //no error checking since it would of been done earlier in the process
        if($this->context !== null && !empty($record[$this->context])) {
            $context = explode('_', $record[$this->context], 2);

            $location = current($context);
            next($context);
            $id = current($context);
        }

        $temp = new object();

        $temp->action = empty($record['action'])? '': $record['action'];

        $temp->item = null;

        if(!empty($record[$this->role]) && strcmp($record[$this->role], 'instructor') === 0) {
            $cmclass = cmclass::get_by_idnumber($id);

            if(!empty($cmclass)) {
                $item_record['classid'] = $cmclass->id;
                $temp->item = new instructor($item_record);
            }
        }else {
            if(strcmp($location, 'class') === 0) {
                $cmclass = cmclass::get_by_idnumber($id);
                
                if(!empty($cmclass)) {
                    $item_record['classid'] = $cmclass->id;
                    $temp->item = new student($item_record);            //dynamic student class
                }
            } else {
                $track = track::get_by_idnumber($id);

                if(!empty($track)) {
                    $item_record['trackid'] = $track->id;
                    $temp->item = new usertrack($item_record);
                }
            }
        }

        return $temp;
    }

    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    public function get_properties_map() {
        global $CURMAN;

        $item = new student();                         //dynamic student class
        $properties = array_keys($item->properties);

        $map = array_combine($properties, $properties);
        unset($map['id']);
        unset($map['classid']);
        unset($map['userid']);
        unset($map['idnumber']);
        
        $map['user_idnumber'] = 'user_idnumber';
        $map['role'] = 'role';
        $map['context'] = 'context';
        $map['execute'] = 'action';

        $properties_map = $CURMAN->db->get_records('crlm_field_map', 'context', 'student');

        if(!empty($properties_map)) {
            foreach($properties_map as $pm) {
                $map[$pm->elis_field] = $pm->data_field;
            }
        }

        return $map;
    }

    /**
     *
     * @param <type> $columns
     * @return <type>
     */
    public function check_required_columns($columns) {
        $map = $this->get_properties_map();
        $u = new student();             //reaplace student with something more dynamic
        $columnkeys = array_flip($columns);

        foreach($u->_required as $r) {
            if(!empty($map[$r]) && empty($columnkeys[$map[$r]])) {
                return false;
            }
        }

        if(empty($map['context']) || empty($columnkeys[$map['context']])) {
            return false;
        }

        if(empty($map['user_idnumber']) || empty($columnkeys[$map['user_idnumber']])) {
            return false;
        }
        
        return true;
    }
}

/**
 *
 */
class course_import extends import {
    protected $data_object = 'course';

    /**
     *
     * @global <type> $CURMAN
     * @param <type> $record
     * @return <type>
     */
    public function get_item($record) {
        global $CURMAN;
        
        $properties_map = $this->get_properties_map();
        $item = new course();
        $item_record = array();
        
        foreach($item->properties as $p=>$null) {
            if(!empty($properties_map[$p])) {
                if(!empty($record[$properties_map[$p]])) {
                    $item_record[$p] = $record[$properties_map[$p]];
                }
            }
        }


        if(!empty($record['assignment'])) {
            $curriculum = curriculum::get_by_idnumber($record['assignment']);

            $item_record['curriculum'] = array();
            $item_record['curriculum'][] = $curriculum->id;
        }

        if(!empty($record['link'])) {
            $mcourseid = $CURMAN->db->get_field('course', 'id', 'shortname', $record['link']);

            $item_record['location'] = $mcourseid;
            $item_record['templateclass'] = 'moodlecourseurl';
        }

        if(!empty($record['environmentid'])) {
            $item_record['environmentid'] = $record['environmentid'];
        }

        $temp = new object();

        $temp->action = empty($record['action'])? '': $record['action'];
        $item->set_from_data((object)$item_record);
        $temp->item = $item;

        return $temp;
    }

    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    public function get_properties_map() {
        global $CURMAN;

        $u = new course();
        $properties = array_keys($u->properties);

        $map = array_combine($properties, $properties);
        unset($map['id']);
        unset($map['timecreated']);
        unset($map['timemodified']);
        unset($map['environmentid']);
        $map['environment'] = 'environment';
        $map['assignment'] = 'assignment';
        $map['link'] = 'link';
        
        $properties_map = $CURMAN->db->get_records('crlm_field_map', 'context', 'course');

        if(!empty($properties_map)) {
            foreach($properties_map as $pm) {
                $map[$pm->elis_field] = $pm->data_field;
            }
        }

        return $map;
    }
}

/**
 *
 */
class cmclass_import extends import {
    protected $data_object = 'cmclass';

    /**
     *
     * @param <type> $record
     * @return <type>
     */
    public function get_item($record) {
        $properties_map = $this->get_properties_map();
        $item = new cmclass();

        $item_record = array();
        foreach($item->properties as $p=>$null) {
            if(!empty($properties_map[$p])) {
                if(!empty($record[$properties_map[$p]])) {
                    $item_record[$p] = $record[$properties_map[$p]];
                }
            }
        }

        if(!empty($record[$properties_map['autocreate']])) {
            $item_record['moodleCourses']['autocreate'] = $record['autocreate'];
        }

        if(!empty($record[$properties_map['moodlecourseid']])) {
            $item_record['moodleCourses']['moodlecourseid'] = $record['moodlecourseid'];
        }

        if(!empty($record['environmentid'])) {
            $item_record['environmentid'] = $record['environmentid'];
        }

        $course = course::get_by_idnumber($record[$properties_map['assignment']]);
        $item_record['courseid'] = $course->id;

        $temp = new object();

        $temp->action = empty($record['action'])? '': $record['action'];
        $item->set_from_data((object)$item_record);
        $temp->item = $item;

        return $temp;
    }
    
    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    public function get_properties_map() {
        global $CURMAN;

        $u = new cmclass();
        $properties = array_keys($u->properties);

        $map = array_combine($properties, $properties);
        unset($map['id']);
        unset($map['timecreated']);
        unset($map['timeapproved']);
        unset($map['timemodified']);
        unset($map['courseid']);
        unset($map['environmentid']);
        $map['environment'] = 'environment';
        $map['assignment'] = 'assignment';
        $map['link'] = 'link';
        $map['track'] = 'track';
        $map['moodlecourseid'] = 'moodlecourseid';
        $map['autocreate'] = 'autocreate';
        
        $properties_map = $CURMAN->db->get_records('crlm_field_map', 'context', 'class');

        if(!empty($properties_map)) {
            foreach($properties_map as $pm) {
                $map[$pm->elis_field] = $pm->data_field;
            }
        }

        return $map;
    }
}

/**
 *
 */
class curriculum_import extends import {
    protected $data_object = 'curriculum';

    /**
     *
     * @param <type> $record
     * @return <type>
     */
    public function get_item($record) {
        $properties_map = $this->get_properties_map();
        $item = new curriculum();

        $item_record = array();
        foreach($item->properties as $p=>$null) {
            if(!empty($properties_map[$p])) {
                if(!empty($record[$properties_map[$p]])) {
                    $item_record[$p] = $record[$properties_map[$p]];
                }
            }
        }

        $temp = new object();

        $temp->action = empty($record['action'])? '': $record['action'];
        $temp->item = new curriculum($item_record);

        return $temp;
    }

    /**
     *
     * @global <type> $CURMAN
     * @return <type>
     */
    public function get_properties_map() {
        global $CURMAN;

        $u = new curriculum();
        $properties = array_keys($u->properties);

        $map = array_combine($properties, $properties);
        unset($map['id']);
        unset($map['timecreated']);
        unset($map['timeapproved']);
        unset($map['timemodified']);
        unset($map['iscustom']);
        
        $properties_map = $CURMAN->db->get_records('crlm_field_map', 'context', 'curriculum');

        if(!empty($properties_map)) {
            foreach($properties_map as $pm) {
                $map[$pm->elis_field] = $pm->data_field;
            }
        }

        return $map;
    }
}

/**
 *
 */
class track_import extends import {
    protected $data_object = 'track';

    /**
     *
     * @param <type> $record
     * @return <type>
     */
    public function get_item($record) {
        $properties_map = $this->get_properties_map();
        $item = new track();
        $item_record = array();
        
        foreach($item->properties as $p=>$null) {
            if(!empty($properties_map[$p])) {
                if(!empty($record[$properties_map[$p]])) {
                    $item_record[$p] = $record[$properties_map[$p]];
                }
            }
        }

        $curriculum = curriculum::get_by_idnumber($record[$properties_map['assignment']]);

        $item_record['curid'] = $curriculum->id;

        $temp = new object();

        $temp->action = empty($record['action'])? '': $record['action'];
        $item->set_from_data((object)$item_record);
        $temp->item = $item;

        return $temp;
    }

    /**
     * used for mapping user defined fields to elis properties
     * 
     * @global <type> $CURMAN
     * @return <type>
     */
    public function get_properties_map() {
        global $CURMAN;

        $u = new track();
        $properties = array_keys($u->properties);

        $map = array_combine($properties, $properties);
        unset($map['id']);
        unset($map['timecreated']);
        unset($map['timeapproved']);
        unset($map['timemodified']);
        unset($map['curid']);
        unset($map['defaulttrack']);
        
        $map['assignment'] = 'assignment';
        $map['autocreate'] = 'autocreate';

        $properties_map = $CURMAN->db->get_records('crlm_field_map', 'context', 'track');

        if(!empty($properties_map)) {
            foreach($properties_map as $pm) {
                $map[$pm->elis_field] = $pm->data_field;
            }
        }

        return $map;
    }
}

abstract class import {
    protected $data_object;

    public abstract function get_properties_map();
    public abstract function get_item($record);

    /**
     *
     * @param <type> $columns
     * @return <type>
     */
    public function check_required_columns($columns) {
        $map = $this->get_properties_map();
        $temp = $this->data_object;
        $u = new $temp();
        $columnkeys = array_flip($columns);

        foreach($u->_required as $r) {
            if(!empty($map[$r]) && empty($columnkeys[$map[$r]])) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     * @param <type> $columns
     * @return <type>
     */
    public function get_missing_fields($columns) {
        $map = $this->get_properties_map();
        $item = new $this->data_object();
        $columnkeys = array_flip($columns);

        $retval = array();

        foreach($item->_required as $r) {
            if(!empty($map[$r]) && empty($columnkeys[$map[$r]])) {
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
     * @global object $CURMAN
     * @param string $key
     * @param string $value
     * @return object
     */
    public function set_property_map($key, $value) {
        global $CURMAN;

        $map = self::get_properties_map();

        if(!$CURMAN->db->get_record('crlm_field_map', 'context', 'user', 'elis_field', $key)) {
            if(!empty($map[$key]) && strcmp($map[$key], $value) !== 0) {
                $dataobject->context = 'user';
                $dataobject->elis_field = $key;
                $dataobject->data_field = $value;

                return $CURMAN->db->insert_record('crlm_field_map', $dataobject);
            } else {
                //invalid key value
                return false;
            }
        } else {
            return $CURMAN->db->set_field('crlm_field_map', 'data_field', $value, 'context', 'user', 'elis_field', $key);
        }
    }
}

function throwException($message = null, $code = null) {
    throw new Exception($message, $code);
}
?>
