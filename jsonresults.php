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
 * Reports for moodlecst
 *
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

header("Access-Control-Allow-Origin: *");
$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$userid = optional_param('userid', 0, PARAM_INT); // user id
$sesskey = optional_param('sesskey', 0, PARAM_TEXT); //session key 
$results= optional_param('results', '', PARAM_RAW); // data baby yeah


if ($id) {
    $cm         = get_coursemodule_from_id(MOD_MOODLECST_MODNAME, $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record(MOD_MOODLECST_TABLE, array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record(MOD_MOODLECST_TABLE, array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance(MOD_MOODLECST_TABLE, $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

//can't require login for this page. nodejs app and moodle cant share cookies . hmmmmmmmmm
//require_sesskey();
//require_login($course, false, $cm);

if($results){
 $results = json_decode($results);
}

//require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);
/*
//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){
	add_to_log($course->id, MOD_MOODLECST_MODNAME, 'reports', "reports.php?id={$cm->id}", $moduleinstance->name, $cm->id);
}else{
	// Trigger module viewed event.
	$event = \mod_moodlecst\event\course_module_viewed::create(array(
	   'objectid' => $moduleinstance->id,
	   'context' => $modulecontext
	));
	$event->add_record_snapshot('course_modules', $cm);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot(MOD_MOODLECST_MODNAME, $moduleinstance);
	$event->trigger();
} 
*/


/// Set up the page header
/*
$PAGE->set_url(MOD_MOODLECST_URL . '/processresult.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');
*/
//Get an admin settings 
$config = get_config(MOD_MOODLECST_FRANKY);
//get a holder for success/fails of DB updates
$dbresults=array();

//add an attempts object
$attemptdata = new stdClass;
$attemptdata->course =$course->id;
$attemptdata->userid =$USER->id;
$attemptdata->moodlecstid =$cm->instance;
$attemptdata->mode =$moduleinstance->mode;
$attemptdata->status =0;
$attemptdata->sessionscore =0;
$attemptdata->timecreated =time();
$attemptdata->timemodified =0;
$attemptid = $DB->insert_record(MOD_MOODLECST_ATTEMPTTABLE,$attemptdata);

//prepare our update object for adding summmary from items to attempt
$update_data = new stdClass();
$update_data->id=$attemptid;
$update_data->partnerid=0;
$update_data->totaltime=0;
$update_data->sessionscore=0;

//add all our item to DB and build return data.
foreach($results as $result){
	$itemdata = new stdClass;
	$itemdata->course =$course->id;
	$itemdata->moodlecstid =$cm->instance;
	$itemdata->attemptid =$attemptid;
	$itemprops = get_object_vars($result);
	foreach($itemprops as $key=>$value){
		$itemdata->{$key}=$value;
	}
	$itemdata->timecreated =time();
	$itemdata->timemodified =0;
	
	$dbresult = new stdClass;
	$dbresult->id=$DB->insert_record(MOD_MOODLECST_ATTEMPTITEMTABLE,$itemdata);
	if($dbresult->id){
		$dbresult->error='';
		$dbresult->success=true;
		//add info to attempt table update
		$update_data->partnerid = $itemdata->partnerid;
		$update_data->userid = $itemdata->userid;
		if($itemdata->correct){$update_data->sessionscore++;}
		$update_data->totaltime+=$itemdata->duration;
	}else{
		$dbresult->id=0;
		$dbresult->error='erroring inserting response item.';
		$dbresult->success=false;
	}
	$dbresults[] = $dbresult;
}

//update attempt table
$DB->update_record(MOD_MOODLECST_ATTEMPTTABLE,$update_data);

//return JSON to cst app
$jsonrenderer = $PAGE->get_renderer(MOD_MOODLECST_FRANKY,'json');
//header("Access-Control-Allow-Origin: *");
echo $jsonrenderer->render_results_json($dbresults);
