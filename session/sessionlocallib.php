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
 * Internal library of functions for module moodlecst
 *
 * All the moodlecst specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_moodlecst
 * @copyright  COPYRIGHTNOTICE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

 
   
   function mod_moodlecst_session_delete_item($moodlecst, $itemid, $context) {
		global $DB;
		$ret = false;
		
		//Delete session
        if (!$DB->delete_records(MOD_MOODLECST_SESSION_TABLE, array('id'=>$itemid))){
            print_error("Could not delete session");
			return $ret;
        }

		//remove items from MOD_MOODLECST_SESSION_ITEM_TABLE
		if (!$DB->delete_records(MOD_MOODLECST_SESSION_ITEM_TABLE, array('sessionid'=>$itemid))){
            print_error("Could not delete session items");
			return $ret;
        }
		
		$ret = true;
		return $ret;
   } 
   
   
  
	function mod_moodlecst_session_fetch_editor_options($course, $modulecontext){
		$maxfiles=99;
		$maxbytes=$course->maxbytes;
		return  array('trusttext'=>true, 'subdirs'=>true, 'maxfiles'=>$maxfiles,
							  'maxbytes'=>$maxbytes, 'context'=>$modulecontext);
	}

	function mod_moodlecst_session_fetch_filemanager_options($course, $maxfiles=1){
		$maxbytes=$course->maxbytes;
		return array('subdirs'=>true, 'maxfiles'=>$maxfiles,'maxbytes'=>$maxbytes,'accepted_types' => array('audio'));
	}



