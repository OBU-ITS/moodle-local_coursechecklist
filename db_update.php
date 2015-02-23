<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * db updates acting on the local_regcourseapproval table
 *
 * @package    coursechecklist
 * @category   local
 * @copyright  2015, Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



/** Insert a record of a checklist having been checked for a given (single) course
 * 
 * @param type $cid  the course id
 * @param type $user_email the email of the invitee
 * @param type $email_approver the id of the course approver (usually the leader)
 */

function insert_checklist_update($courseid, $userid) {
   
    global $DB;
    
    $record = new stdClass();
    $record->courseid       = $courseid;
    $record->userid         = $userid;
    $record->date           = time();
    
    $DB->insert_record('local_coursechecklist', $record, false);      
    
}




/**
 * Build a list of courses meeting moduleleader checklist criteria - copies block/moduleleader functionality
 * 
 * @global type $CFG
 * @global type $DB
 * @param type $totalcount
 * @param type $leader_id
 * @return type
 */

function get_pending_courses(&$totalcount, $leader_id) {
    global $CFG, $DB;

    $mindays = get_config('block_moduleleader', 'mindays');
    $maxdays = get_config('block_moduleleader', 'maxdays');
    
    if ($mindays == null) { $mindays = 0; }
    if ($maxdays == null) { $maxdays = 90; }
    
    $sql = "SELECT date_format(FROM_UNIXTIME(c.startdate), '%D %M %Y') as start, c.*,
            (SELECT COUNT(*) FROM mdl_course_modules cm WHERE cm.course = c.id) AS Modules, 
            TIMESTAMPDIFF(DAY,curdate(),FROM_UNIXTIME(c.startdate)) as days
            FROM mdl_course AS c
            JOIN mdl_context AS ctx ON c.id = ctx.instanceid AND ctx.contextlevel = 50 
            JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
            JOIN mdl_role AS r ON ra.roleid = r.id
            JOIN mdl_user AS u ON u.id = ra.userid
            WHERE u.id = $leader_id
            and r.archetype = 'editingteacher'
            and TIMESTAMPDIFF(DAY,curdate(),FROM_UNIXTIME(c.startdate)) > $mindays
            and TIMESTAMPDIFF(DAY,curdate(),FROM_UNIXTIME(c.startdate)) < $maxdays
            GROUP BY c.id";
    
    $courses = array();
    $c = 0; // counts how many visible courses we've seen

    $params = array();
   
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach($rs as $course) {

        // context_instance_preload($course);
        context_helper::preload_from_record($course);

        $coursecontext = context_course::instance($course->id);
        // if ($course->visible || has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
            $c++;
            
            $courses[$course->id] = $course;
           
        // }
    }
    $rs->close();

    // our caller expects 2 bits of data - our return
    // array, and an updated $totalcount
    $totalcount = $c;
    return $courses;
}


/** 
 * Check to see if any of the courses in a list have been checklisted and return yes/no
 * 
 * @global type $CFG
 * @global type $DB
 * @param string $course_in
 */

function count_checklist_for_courses($course_include) {
    
    global $DB;
    
    $c = 0;
    
    $sql = "SELECT count(*) as count from mdl_local_coursechecklist where courseid in $course_include ";
        
    $params = array();
 
    $rs = $DB->get_recordset_sql($sql, $params);
    // just one
    foreach($rs as $row) {
        $c = $row->count;
    }
    $rs->close();        
    
    // echo $sql;
    
    return $c > 0;
        
    
}

















