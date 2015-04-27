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
 * Prints a particular instance of moodlecst
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');


$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // moodlecst instance ID - it should be named as the first character of the module
$action = optional_param('action', nodejshelper::NODE_ACTION_NONE, PARAM_INT);  // node js action

if ($id) {
    $cm         = get_coursemodule_from_id('moodlecst', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance  = $DB->get_record('moodlecst', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $moduleinstance  = $DB->get_record('moodlecst', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('moodlecst', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}


/// Set up the page url, so we have somewhere to return to, in the event of a logout
$PAGE->set_url('/mod/moodlecst/nodeserver.php', array('id' => $cm->id,'action'=>$action));


/// Page setup
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);
require_capability('mod/moodlecst:preview',$modulecontext);

//finish up page setup
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->set_pagelayout('course');

//Diverge logging logic at Moodle 2.7
if($CFG->version<2014051200){
	add_to_log($course->id, 'moodlecst', 'view', "view.php?id={$cm->id}", $moduleinstance->name, $cm->id);
}else{
	// Trigger module viewed event.
	$event = \mod_moodlecst\event\course_module_viewed::create(array(
	   'objectid' => $moduleinstance->id,
	   'context' => $modulecontext
	));
	$event->add_record_snapshot('course_modules', $cm);
	$event->add_record_snapshot('course', $course);
	$event->add_record_snapshot('moodlecst', $moduleinstance);
	$event->trigger();
} 

//get our renderer
$renderer = $PAGE->get_renderer('mod_moodlecst');

$running = nodejshelper::is_server_running();

$config = get_config('mod_moodlecst');
$node_app_path = $config->nodejsapppath;
if(strpos($node_app_path,'/')!==0){
	$node_app_path = $CFG->dirroot . '/mod/moodlecst/' . $node_app_path;
}


switch ($action){
	case nodejshelper::NODE_ACTION_START:
		if($running){
			echo $renderer->heading(get_string('nodeserveralreadyrunning', MOD_MOODLECST_LANG),3);
		}else{
			 nodejshelper::start_server($node_app_path);
		}
		break;
	case nodejshelper::NODE_ACTION_STOP:
		if(!$running){
			echo $renderer->heading(get_string('nodeservernotalreadyrunning', MOD_MOODLECST_LANG),3);
		}else{
			 nodejshelper::stop_server();
		}
	break;
	
	case nodejshelper::NODE_ACTION_FORCERESTART:
			 nodejshelper::force_restart_server($node_app_path);
	break;
}


echo $renderer->header($moduleinstance, $cm, 'nodeserver', null, get_string('nodeserver', MOD_MOODLECST_LANG));
echo $renderer->heading($moduleinstance->name,2);


if(nodejshelper::is_server_running()){
	echo $renderer->heading(get_string('nodeserverrunning', MOD_MOODLECST_LANG),4);
	echo $renderer->show_node_server_button($cm,nodejshelper::NODE_ACTION_STOP,get_string('nodeserverstop', MOD_MOODLECST_LANG));
}else{
	echo $renderer->heading(get_string('nodeservernotrunning', MOD_MOODLECST_LANG),4);
	echo $renderer->show_node_server_button($cm,nodejshelper::NODE_ACTION_START,get_string('nodeserverstart', MOD_MOODLECST_LANG));
}

//Show the log
$logdata = nodejshelper::fetch_log();
echo $renderer->show_server_log($logdata);

//show refresh
echo $renderer->show_node_server_button($cm,nodejshelper::NODE_ACTION_NONE,get_string('refresh'));

//show force restart
echo $renderer->show_node_server_button($cm,nodejshelper::NODE_ACTION_FORCERESTART,get_string('nodeserverforcerestart', MOD_MOODLECST_LANG));

// Finish the page
echo $renderer->footer();
