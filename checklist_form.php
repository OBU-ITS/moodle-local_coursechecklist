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
 * Pre-release course best practice checklist, with tracking whether viewed
 *
 * @package    coursechecklist
 * @category   local
 * @copyright  2014 Oxford Brookes University {@link http://www.brookes.ac.uk/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("db_update.php");
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir . '/coursecatlib.php');
 
// Additional 16/5/2014 following feedback from RF/AB - now also display the course list as displayed in the 'Moduleleader' block
// Master switch of whether to (re)show this course list - this uses much code copied from block/moduleleader, along with block/moduleleader language and settings
// NB this copying of functionality should be resolved if this is going to be used long term - just done this way for demo/test purposes.
$show_course_list = get_config('coursechecklist', 'showcourselist');

$submitted    = optional_param('submitted', '', PARAM_RAW);  // form submitted
$courseids     = optional_param('courseids', '', PARAM_RAW);  // comma separated list of all the course IDs to which this checklist will be applied
$acceptchecklist = optional_param('acceptchecklist', '', PARAM_RAW); // checklist accepted yes/no

$site = get_site();

$urlparams = array();
foreach (array('courseids') as $param) {
    if (!empty($$param)) {
        $urlparams[$param] = $$param;
    }
}


$PAGE->set_url('/local/coursechecklist/checklist_form.php', $urlparams);

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

if ($CFG->forcelogin) {
    require_login();
}

// If form not submitted, just display the checklist
// If form submitted, validate (very simply) and store date and course id(s) involved in db table, then display appropriate messaging

if (empty($submitted)) {
    
    // $PAGE->navbar->add($strcourses, new moodle_url('/course/index.php'));
    $PAGE->navbar->add("Moodle Checklist");
    $PAGE->set_title($site->fullname);
    $PAGE->set_heading($site->fullname);
    
    echo $OUTPUT->header();
    echo $OUTPUT->box_start();
    echo "<br />";
    
    if ($show_course_list) {
        $count = 0;
        $courses = get_pending_courses($count, $USER->id);
        $course_list = print_new_course_list($courses);
        echo $course_list;    
        echo "<br />";
    }

    echo "<h3>" . get_string('pagetitle', 'local_coursechecklist') . "</h3>";
    echo "<br />";
    echo print_course_checklist($courseids);
    echo "<br />";
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    
    exit;
}

else {
    
    $message = "";
    
    if ($acceptchecklist == "yes") {
    
        // Here we need to update the database
        // Then show some appropriate messaging
        
        $message = get_string('acknowledge_yes', 'local_coursechecklist');
        
        // Do the database update - do this one at a time for each course involved (which while not super efficient, does keep the records separate)
        // Only do this if have appropriate permissions (for each course)
        
        foreach (explode(",", $courseids) as $this_course_id) {
            
            // Super inefficently fetch each individual course, but this isn't going to be running often by definition
            // This is only belt and braces to avoid URL firkling since to be on this page, must ordinarily have been invited

            $params = array('id' => $this_course_id);
            $course = $DB->get_record('course', $params, '*', MUST_EXIST);
            
            // preload_course_contexts($course->id);
            context_helper::preload_course($course->id);
            $context = context_course::instance($course->id, MUST_EXIST);

            // Check for correct permissions
            require_login();
            require_capability('moodle/course:update', $context);            
            
            insert_checklist_update($course->id, $USER->id);

        }
        
    }

    else {
        $message = get_string('acknowledge_no', 'local_coursechecklist');  
    }
    
    $PAGE->navbar->add("Moodle Checklist");
    $PAGE->set_title($site->fullname);
    $PAGE->set_heading($site->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->box_start();
    echo "<br />";
    echo "<h3>" . get_string('pagetitle', 'local_coursechecklist') . "</h3>";
    echo "<br />";
    echo $message;
    echo "<br />";
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    
    exit;
    
}


/** Print the checklist as a form which can be submitted
 * 
 * @global type $CFG
 * @return string
 */

function print_course_checklist($courseids) {
    global $CFG, $OUTPUT, $DB;
    static $count = 0;

    $count++;
    $output = "";
    $pid = 'coursechecklist';
    
    // echo $OUTPUT->heading(get_string('title', 'local_coursechecklist'));

    $output .= '<form id="'.$pid.'" action="'.$CFG->wwwroot.'/local/coursechecklist/checklist_form.php" method="get">';
    $output .= '<fieldset class="coursesearchbox invisiblefieldset">';
    $output .= "<div>";
    
    $output .= '<input type="hidden" name="submitted" id="submitted" value="submitted" />';
    $output .= '<input type="hidden" name="courseids" id="courseids" value="' . $courseids. '" />';
    
    // Now insert the content of the page - this will be a site page which can sit on home page normally (OK?)
    // Display code for it lifted from mod/page/view.php
    // page ID to use is defined in the settings (see settings.php)

    $id = get_config('coursechecklist', 'page_id');
   
    if (!$cm = get_coursemodule_from_id('page', $id)) {
        print_error('invalidcoursemodule');
    }
    $page = $DB->get_record('page', array('id'=>$cm->instance), '*', MUST_EXIST);
    
    $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

    require_course_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/page:view', $context);    
    
    $content = file_rewrite_pluginfile_urls($page->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $page->revision);
    $formatoptions = new stdClass;
    $formatoptions->noclean = true;
    $formatoptions->overflowdiv = true;
    $formatoptions->context = $context;
    $content = format_text($content, $page->contentformat, $formatoptions);
        
    $output .= $OUTPUT->box($content, "generalbox center clearfix");

    $output .= '<br /><br />';
    $output .= get_string('checklist_header', 'local_coursechecklist');
    $output .= '<input type="radio" name="acceptchecklist" value="yes" />' . get_string('accept_checklist', 'local_coursechecklist');
    $output .= '<br /><br />';
    $output .= '<input type="radio" name="acceptchecklist" value="no" checked="checked" />' . get_string('decline_checklist', 'local_coursechecklist');
    $output .= '<br /><br />';
    $output .= get_string('release_instructions', 'local_coursechecklist');
    $output .= '<br /><br />';
    
    $output .= '<input type="submit" value="'.get_string('submit', 'local_coursechecklist').'" />';
    
    $output .= "</div>";
    
    $output .= '</fieldset></form>';

    return $output;
    
}



/** Build and display the list of courses to be shown here - cf. similar/same functionality in block/moduleleader
 * 
 * @global type $CFG, $USER
 * @return string
 */

function print_new_course_list($courseids) {

    global $CFG, $USER; 
    
    $content = "";
    
    $courses = array();
    $count = 0;
    
    // Get the master category list (so we can exclude the ones that won't be relevant eg non-PIP)
    // I hope this is cached (core Moodle) and isn't recalculated every time (but see https://tracker.moodle.org/browse/MDL-40276 )
    $displaylist =  coursecat::make_categories_list();
    // $parentlist = coursecat::get_parents();    
      
    $courses = get_pending_courses($count, $USER->id);
 
    $new_course_list = "";
    $empty_course_list = "";
    $new_courses = array(); // track the IDs of new courses so we can check them against the DB/checklist
    
    foreach ($courses as $course) {
        
        // Here we make some decisions about what to include, what not to include, and where
        // viz:
        // - exclude non-PIP linked (in the category) - or as set by settings
        // - include not visible in list of due to make live (checklist link)
        // - include little or no content (modules < x) in list of rollovers to do
        
        // So first - exclude the category we want to exclude
        $exclude = get_config('block_moduleleader', 'excludetext');
        if ($exclude && !(strpos($displaylist[$course->category], $exclude) >= 0)) {
            continue;
        }
        // If not visible include in list to be made visible (and show checklist, somewhere)
        if (!($course->visible)) {
            
            $new_courses[] = $course->id;
            
            $new_course_list .= print_course_link($course);
           
            //if the start date is today or in the past, show visual alert (red text)
            if ($course->days <= get_config('block_moduleleader', 'alertdays')) {
                $new_course_list .= " (starting " . "<span style='font-weight:bold'>" . $course->start . "</span>)";
            }
            else {
                $new_course_list .= " (starting " . $course->start . ")";  
            }
            
            $new_course_list .= "<br>";              
        }
                
        // If no content, (modules less than 5, say, allowing for forum, guide etc) include in list to be rolled over to
        if ($course->modules < get_config('block_moduleleader', 'alertmodules')) {
            $empty_course_list .= print_rollover_link($course);
            $empty_course_list .= " (starting " . $course->start . ")";
            $empty_course_list .= "<br>";   
        }
       
    }    

    if ($new_course_list) {
        
        $content .= "<h3>" . get_string('partoneheader', 'local_coursechecklist') . "</h3>";
        $content .= get_string('partonetext', 'local_coursechecklist') . "<br />";
        
        // no need to show checklist link here - this IS the checklist
        $checklist_link = "";
        $new_course_list = "<br><b>" . get_string('requirerelease', 'block_moduleleader') . "</b><br>" . $checklist_link . $new_course_list;
        $content .= $new_course_list;   
    }
    if ($empty_course_list) {
        $content .= "<br><b>" . get_string('requirecontent', 'block_moduleleader') . "</b><br>" . $empty_course_list;   
    }
    
    $content .= "<br /><br />";
    $content .= "<h3>" . get_string('parttwoheader', 'local_coursechecklist') . "</h3>";
    $content .= get_string('parttwotext', 'local_coursechecklist') . "</br></br>";

    
    // However if there's nothing to show, then show nothing
    if (!$new_course_list && !$empty_course_list) {
        $content = "";
    }
    
    return $content;
    
}
  

/** Print standard course link
 * 
 * @param type $course
 * @return type
 */

function print_course_link($course) {
    
    // Alternative approach would be to link direct to edit page, such as /course/edit.php?id=?
    
    $linkhref = new moodle_url('/course/view.php', array('id'=>$course->id));
    $coursename = get_course_display_name_for_list($course);
    $linktext = format_string($coursename);
    $linkparams = array('title'=>get_string('entercourse'));
    
    if ($course->days <= get_config('block_moduleleader', 'alertdays')) {
        $linkparams['style'] = 'color:red';
    }
    
    /* // They are all not visible here so don't dim
    if (empty($course->visible)) {
        $linkparams['class'] = 'dimmed';
    }
    */
    
    return html_writer::link($linkhref, $linktext, $linkparams);    
}

/** Print link to course rollover
 * 
 * @param int $course course id
 * @return string the link
 */

function print_rollover_link($course) {
    
    $linkhref = new moodle_url('/local/manualrollover/manualrollover.php', array('id'=>$course->id, 'rtype'=>'to'));
    $coursename = get_course_display_name_for_list($course);
    $linktext = format_string($coursename);
    $linkparams = array('title'=>get_string('entercourse'));
    
    if ($course->days <= get_config('block_moduleleader', 'alertdays')) {
        $linkparams['style'] = 'color:red';
    }
    
    return html_writer::link($linkhref, $linktext, $linkparams);    
}


/** Checks to see if ANY courses in list have been checked against the checklist.
 *  If ANY have NOT, then the link will be displayed
 * 
 * @param array $new_course_list
 * @return int
 */

function check_course_checklist($new_course_list) {
       
    $course_include = "(";
    
    // build an sql list include of the IDs
    foreach ($new_course_list as $course) {
        if ($course_include != "(") {
            $course_include .= ",";
        }
        $course_include .= $course;
          
    }
    $course_include .= ")";
    
    return count_checklist_for_courses($course_include);
  
}

