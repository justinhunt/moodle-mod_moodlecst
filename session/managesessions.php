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
 * Action for adding/editing a session. 
 * replace i) MOD_SESSIONMODULE eg MOD_CST, then ii) SESSIONMODULE eg cst, then iii) SESSION eg slidepair, then iv) create a capability 
 *
 * @package mod_moodlecst
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../../config.php");
require_once($CFG->dirroot.'/mod/moodlecst/lib.php');
require_once($CFG->dirroot.'/mod/moodlecst/session/sessionforms.php');
require_once($CFG->dirroot.'/mod/moodlecst/session/sessionlocallib.php');

global $USER,$DB;

// first get the nfo passed in to set up the page
$itemid = optional_param('itemid',0 ,PARAM_INT);
$id     = required_param('id', PARAM_INT);         // Course Module ID
$type  = optional_param('type', MOD_MOODLECST_SESSION_TYPE_NONE, PARAM_INT);
$action = optional_param('action','edit',PARAM_TEXT);

// get the objects we need
$cm = get_coursemodule_from_id('moodlecst', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$moodlecst = $DB->get_record('moodlecst', array('id' => $cm->instance), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/moodlecst:itemedit', $context);

//set up the page object
$PAGE->set_url('/mod/moodlecst/moodlecst/session/managesessions.php', array('itemid'=>$itemid, 'id'=>$id, 'type'=>$type));
$PAGE->set_title(format_string($moodlecst->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//are we in new or edit mode?
if ($itemid) {
    $item = $DB->get_record(MOD_MOODLECST_SESSION_TABLE, array('id'=>$itemid,'moodlecst' => $cm->instance), '*', MUST_EXIST);
	if(!$item){
		print_error('could not find item of id:' . $itemid);
	}
    $type = $item->type;
    $edit = true;
} else {
    $edit = false;
}


//get renderers
$renderer = $PAGE->get_renderer('mod_moodlecst');
$session_renderer = $PAGE->get_renderer('mod_moodlecst','session');

//we always head back to the moodlecst items page
$redirecturl = new moodle_url('/mod/moodlecst/session/sessions.php', array('id'=>$cm->id));

	//handle delete actions
    if($action == 'confirmdelete'){
		echo $renderer->header($moodlecst, $cm, 'session', null, get_string('confirmitemdeletetitle', 'moodlecst'));
		echo $session_renderer->confirm(get_string("confirmitemdelete","moodlecst",$item->name), 
			new moodle_url('/mod/moodlecst/session/managesessions.php', array('action'=>'delete','id'=>$cm->id,'itemid'=>$itemid)), 
			$redirecturl);
		echo $renderer->footer();
		return;

	/////// Delete item NOW////////
    }elseif ($action == 'delete'){
    	require_sesskey();
		$success = mod_moodlecst_session_delete_item($moodlecst,$itemid,$context);
        redirect($redirecturl);
	
    }

//get the mform for our item
switch($type){
	case MOD_MOODLECST_SESSION_TYPE_NORMAL:
		//need to get CHOSENDATA and UNCHOSEN data , ie have to set it
		$chosendata=array();
		$unchosendata=array();
		$sortorderarray = array();
		$chooser = $session_renderer->fetch_chooser($chosendata,$unchosendata);
		$mform = new moodlecst_session_standard_form(null,array($chooser));
		break;

	default:
		print_error('No item type specified');

}

//if the cancel button was pressed, we are out of here
if ($mform->is_cancelled()) {
    redirect($redirecturl);
    exit;
}

//if we have data, then our job here is to save it and return to the quiz edit page
if ($data = $mform->get_data()) {
		require_sesskey();
		
		$theitem = new stdClass;
        $theitem->moodlecst = $moodlecst->id;
        $theitem->id = $data->itemid;
        $theitem->course = $data->courseid;
		$theitem->sessionid= $data->sessionid;
		$theitem->order = $data->order;
		$theitem->modifiedby=$USER->id;
		$theitem->timemodified=time();
		
		//first insert a new item if we need to
		//that will give us a itemid, we need that for saving files
		if(!$edit){

			$theitem->timecreated=time();				
			if (!$theitem->id = $DB->insert_record(MOD_MOODLECST_SESSION_TABLE,$theitem)){
					error("Could not insert  item!");
					redirect($redirecturl);
			}
		}			

		
		//now update the db once we have saved files and stuff
		if (!$DB->update_record(MOD_MOODLECST_SESSION_TABLE,$theitem)){
				print_error("Could not update item!");
				redirect($redirecturl);
		}

		
		//go back to edit quiz page
		redirect($redirecturl);
}

//if  we got here, there was no cancel, and no form data, so we are showing the form
//if edit mode load up the item into a data object
if ($edit) {
	$data = $item;		
	$data->itemid = $item->id;
}else{
	$data=new stdClass;
	$data->itemid = null;
	$data->type=$type;
}
		

	//Set up the item type specific parts of the form data
	switch($type){
		case MOD_MOODLECST_SESSION_TYPE_NORMAL:			
			//get our javascript all ready to go
			$jsmodule = array(
				'name'     => 'mod_moodlecst',
				'fullpath' => '/mod/moodlecst/module.js',
				'requires' => array('io','json','button','array-extras')
			);
			$opts =Array();
			$opts['chosen'] =MOD_MOODLECST_SESSION_CHOSEN;
			$opts['unchosen'] =MOD_MOODLECST_SESSION_UNCHOSEN;
			$opts['updatefield'] =MOD_MOODLECST_SESSION_UPDATEFIELD;
			$opts['chosendata'] =$chosendata;
			$opts['unchosendata'] =$unchosendata;
			$opts['sortorder']=implode(',',$sortorderarray);
			$PAGE->requires->js_init_call('M.mod_moodlecst_session.init', array($opts),false,$jsmodule);
			break;
		default:
	}
    $mform->set_data($data);
    $PAGE->navbar->add(get_string('edit'), new moodle_url('/mod/moodlecst/session/sessions.php', array('id'=>$id)));
    $PAGE->navbar->add(get_string('editingitem', 'moodlecst', get_string($mform->typestring, 'moodlecst')));
	$renderer = $PAGE->get_renderer('mod_moodlecst');
	$mode='sessions';
	echo $renderer->header($moodlecst, $cm,$mode, null, get_string('edit', 'moodlecst'));
	$mform->display();
	echo $renderer->footer();