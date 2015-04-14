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
 * Action for adding/editing a CRUD. 
 * replace i) MOD_CRUDMODULE eg MOD_CST, then ii) CRUDMODULE eg cst, then iii) CRUD eg slidepair, then iv) create a capability 
 *
 * @package mod_CRUDMODULE
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once("../../../config.php");
require_once($CFG->dirroot.'/mod/CRUDMODULE/lib.php');
require_once($CFG->dirroot.'/mod/CRUDMODULE/CRUD/CRUDforms.php');
require_once($CFG->dirroot.'/mod/CRUDMODULE/CRUD/CRUDlocallib.php');

global $USER,$DB;

// first get the nfo passed in to set up the page
$itemid = optional_param('itemid',0 ,PARAM_INT);
$id     = required_param('id', PARAM_INT);         // Course Module ID
$type  = optional_param('type', MOD_CRUDMODULE_CRUD_NONE, PARAM_INT);
$action = optional_param('action','edit',PARAM_TEXT);

// get the objects we need
$cm = get_coursemodule_from_id('CRUDMODULE', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$CRUDMODULE = $DB->get_record('CRUDMODULE', array('id' => $cm->instance), '*', MUST_EXIST);

//make sure we are logged in and can see this form
require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/CRUDMODULE:itemedit', $context);

//set up the page object
$PAGE->set_url('/mod/CRUDMODULE/CRUD/manageCRUDs.php', array('itemid'=>$itemid, 'id'=>$id, 'type'=>$type));
$PAGE->set_title(format_string($CRUDMODULE->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_pagelayout('course');

//are we in new or edit mode?
if ($itemid) {
    $item = $DB->get_record(MOD_CRUDMODULE_CRUD_TABLE, array('id'=>$itemid,'CRUDMODULE' => $cm->instance), '*', MUST_EXIST);
	if(!$item){
		print_error('could not find item of id:' . $itemid);
	}
    $type = $item->type;
    $edit = true;
} else {
    $edit = false;
}

//we always head back to the CRUDMODULE items page
$redirecturl = new moodle_url('/mod/CRUDMODULE/CRUD/CRUDs.php', array('id'=>$cm->id));

	//handle delete actions
    if($action == 'confirmdelete'){
		$CRUD_renderer = $PAGE->get_renderer('mod_CRUDMODULE');
		$renderer = $PAGE->get_renderer('mod_CRUDMODULE','CRUD');
		echo $renderer->header($CRUDMODULE, $cm, 'CRUDs', null, get_string('confirmitemdeletetitle', 'CRUDMODULE'));
		echo $CRUD_renderer->confirm(get_string("confirmitemdelete","CRUDMODULE",$item->name), 
			new moodle_url('/mod/CRUDMODULE/CRUD/manageCRUDs.php', array('action'=>'delete','id'=>$cm->id,'itemid'=>$itemid)), 
			$redirecturl);
		echo $renderer->footer();
		return;

	/////// Delete item NOW////////
    }elseif ($action == 'delete'){
    	require_sesskey();
		$success = mod_CRUDMODULE_CRUD_delete_item($CRUDMODULE,$itemid,$context);
        redirect($redirecturl);
	
    }



//get filechooser and html editor options
$editoroptions = mod_CRUDMODULE_CRUD_fetch_editor_options($course, $context);
$audiofilemanageroptions = mod_CRUDMODULE_fetch_filemanager_options($course,1);


//get the mform for our item
switch($type){
	case MOD_CRUDMODULE_CRUD_TYPE_TEXTCHOICE:
		$mform = new CRUDMODULE_add_item_form_textchoice(null,
			array('editoroptions'=>$editoroptions, 
			'audiofilemanageroptions'=>$audiofilemanageroptions)
		);
		break;
	case MOD_CRUDMODULE_CRUD_TYPE_AUDIOCHOICE:
		$mform = new CRUDMODULE_add_item_form_audiochoice(null,
			array('editoroptions'=>$editoroptions, 
			'audiofilemanageroptions'=>$audiofilemanageroptions)
		);
		break;
	case MOD_CRUDMODULE_CRUD_NONE:
	default:
		print_error('No item type specifified');

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
        $theitem->CRUDMODULE = $CRUDMODULE->id;
        $theitem->id = $data->itemid;
		$theitem->visible = $data->visible;
		$theitem->order = $data->order;
		$theitem->type = $data->type;
		$theitem->shuffleanswers = $data->shuffleanswers;
		$theitem->correctanswer = $data->correctanswer;
		$theitem->name = $data->name;
		$theitem->modifiedby=$USER->id;
		$theitem->timemodified=time();
		
		//first insert a new item if we need to
		//that will give us a itemid, we need that for saving files
		if(!$edit){
			
			$theitem->{MOD_CRUDMODULE_CRUD_TEXTQUESTION} = '';
			$theitem->{MOD_CRUDMODULE_CRUD_TEXTQUESTION.'format'} = 0;
			$theitem->timecreated=time();			
			$theitem->createdby=$USER->id;
			switch($data->type){
				case MOD_CRUDMODULE_CRUD_TYPE_TEXTCHOICE:
					for($i=1;$i<=MOD_CRUDMODULE_CRUD_MAXANSWERS;$i++){
						$theitem->{MOD_CRUDMODULE_CRUD_TEXTANSWER . $i}='';
						$theitem->{MOD_CRUDMODULE_CRUD_TEXTANSWER . $i . 'format'}=0;
					}
				
			}
			
			if (!$theitem->id = $DB->insert_record(MOD_CRUDMODULE_CRUD_TABLE,$theitem)){
					error("Could not insert CRUDMODULE item!");
					redirect($redirecturl);
			}
		}			
		
		//handle all the files
		//save the item text editor files (common to all types)
		$data = file_postupdate_standard_editor( $data, MOD_CRUDMODULE_CRUD_TEXTQUESTION, $editoroptions, $context,
								'mod_CRUDMODULE', MOD_CRUDMODULE_CRUD_TEXTQUESTION_FILEAREA, $theitem->id);
		$theitem->{MOD_CRUDMODULE_CRUD_TEXTQUESTION} = $data->{MOD_CRUDMODULE_CRUD_TEXTQUESTION} ;
		$theitem->{MOD_CRUDMODULE_CRUD_TEXTQUESTION.'format'} = $data->{MOD_CRUDMODULE_CRUD_TEXTQUESTION.'format'} ;
		
		//save item audio files
		file_save_draft_area_files($data->{MOD_CRUDMODULE_CRUD_AUDIOQUESTION}, $context->id, 'mod_CRUDMODULE', MOD_CRUDMODULE_CRUD_AUDIOQUESTION_FILEAREA,
			   $theitem->id, $audiofilemanageroptions);
					
		//do things dependant on type
		switch($data->type){
			case MOD_CRUDMODULE_CRUD_TYPE_TEXTCHOICE:
				
				// Save answertext/files data
				$answercount=0;
				for($i=1;$i<=MOD_CRUDMODULE_CRUD_MAXANSWERS;$i++){
					//saving files from text editor
					$data = file_postupdate_standard_editor( $data, MOD_CRUDMODULE_CRUD_TEXTANSWER . $i, $editoroptions, $context,
                                        'mod_CRUDMODULE', MOD_CRUDMODULE_CRUD_TEXTANSWER_FILEAREA.$i, $theitem->id);
					$theitem->{MOD_CRUDMODULE_CRUD_TEXTANSWER . $i} = $data->{MOD_CRUDMODULE_CRUD_TEXTANSWER . $i} ;
					$theitem->{MOD_CRUDMODULE_CRUD_TEXTANSWER . $i .'format'} = $data->{MOD_CRUDMODULE_CRUD_TEXTANSWER . $i .'format'};	
					if(trim($theitem->{MOD_CRUDMODULE_CRUD_TEXTANSWER . $i}) !=''){
						$answercount=$i;
					}
				}
				
				//save answer layout data
				$theitem->{MOD_CRUDMODULE_CRUD_ANSWERSINROW}=$data->{MOD_CRUDMODULE_CRUD_ANSWERSINROW};
				$theitem->{MOD_CRUDMODULE_CRUD_ANSWERWIDTH}=$data->{MOD_CRUDMODULE_CRUD_ANSWERWIDTH};
				$theitem->answercount=$answercount;
				break;
				
			case MOD_CRUDMODULE_CRUD_TYPE_AUDIOCHOICE:
				// Save answer data
				for($i=1;$i<=MOD_CRUDMODULE_CRUD_MAXANSWERS;$i++){
					file_save_draft_area_files($data->{MOD_CRUDMODULE_CRUD_AUDIOANSWER . $i}, $context->id, 'mod_CRUDMODULE', MOD_CRUDMODULE_CRUD_AUDIOANSWER_FILEAREA . $i,
					   $theitem->id, $audiofilemanageroptions);
				}
				
				//save answer layout data. We ignore this here
				$theitem->{MOD_CRUDMODULE_CRUD_ANSWERSINROW}=0;
				$theitem->{MOD_CRUDMODULE_CRUD_ANSWERWIDTH}=0;
				//its hard to tell from here how many audio files were added. 
				$theitem->answercount=MOD_CRUDMODULE_CRUD_MAXANSWERS;
				//$theitem->answercount=$answercount;			
				break;
										
			default:
				break;
		
		}

		
		//now update the db once we have saved files and stuff
		if (!$DB->update_record(MOD_CRUDMODULE_CRUD_TABLE,$theitem)){
				print_error("Could not update CRUDMODULE item!");
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
	$data->visible = 1;
	$data->type=$type;
}
		
	//init our item, we move the id fields around a little 
    $data->id = $cm->id;
    $data = file_prepare_standard_editor($data, MOD_CRUDMODULE_CRUD_TEXTQUESTION, $editoroptions, $context, 'mod_CRUDMODULE', 
		MOD_CRUDMODULE_CRUD_TEXTQUESTION_FILEAREA,  $data->itemid);	
		
	//prepare audio file areas
	$draftitemid = file_get_submitted_draft_itemid(MOD_CRUDMODULE_CRUD_AUDIOQUESTION);
	file_prepare_draft_area($draftitemid, $context->id, 'mod_CRUDMODULE', MOD_CRUDMODULE_CRUD_AUDIOQUESTION_FILEAREA, $data->itemid,
						$audiofilemanageroptions);
	$data->{MOD_CRUDMODULE_CRUD_AUDIOQUESTION} = $draftitemid;
	
	//Set up the item type specific parts of the form data
	switch($type){
		case MOD_CRUDMODULE_CRUD_TYPE_TEXTCHOICE:			
			//prepare answer areas
			for($i=1;$i<=MOD_CRUDMODULE_CRUD_MAXANSWERS;$i++){
				//text editor
				$data = file_prepare_standard_editor($data, MOD_CRUDMODULE_CRUD_TEXTANSWER . $i, $editoroptions, $context, 'mod_CRUDMODULE', MOD_CRUDMODULE_CRUD_TEXTANSWER_FILEAREA . $i,  $data->itemid);
			}
			
			break;
		case MOD_CRUDMODULE_CRUD_TYPE_AUDIOCHOICE:
			
			//prepare answer areas
			for($i=1;$i<=MOD_CRUDMODULE_CRUD_MAXANSWERS;$i++){
				//audio editor
				$draftitemid = file_get_submitted_draft_itemid(MOD_CRUDMODULE_CRUD_AUDIOANSWER . $i);
				file_prepare_draft_area($draftitemid, $context->id, 'mod_CRUDMODULE', MOD_CRUDMODULE_CRUD_AUDIOANSWER_FILEAREA . $i, $data->itemid,
									$audiofilemanageroptions);
				$data->{MOD_CRUDMODULE_CRUD_AUDIOANSWER . $i} = $draftitemid;
			
			}
			
			break;
		default:
	}
    $mform->set_data($data);
    $PAGE->navbar->add(get_string('edit'), new moodle_url('/mod/CRUDMODULE/CRUD/CRUDs.php', array('id'=>$id)));
    $PAGE->navbar->add(get_string('editingitem', 'CRUDMODULE', get_string($mform->typestring, 'CRUDMODULE')));
	$renderer = $PAGE->get_renderer('mod_CRUDMODULE');
	$mode='CRUDs';
	echo $renderer->header($CRUDMODULE, $cm,$mode, null, get_string('edit', 'CRUDMODULE'));
	$mform->display();
	echo $renderer->footer();