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


defined('MOODLE_INTERNAL') || die();
define('MOD_MOODLECST_SESSION_TYPE_NONE',0);
define('MOD_MOODLECST_SESSION_TYPE_NORMAL',1);
define('MOD_MOODLECST_SESSION_TABLE','moodlecst_sessions');
define('MOD_MOODLECST_SESSION_ITEM_TABLE','moodlecst_sessionitem');
define('MOD_MOODLECST_SESSION_SELECT','mod_moodlecst_session_select');
define('MOD_MOODLECST_SESSION_CHOSEN','mod_moodlecst_session_chosen');
define('MOD_MOODLECST_SESSION_UNCHOSEN','mod_moodlecst_session_unchosen');
define('MOD_MOODLECST_SESSION_UPDATEFIELD','slidepairkeys');
define('MOD_MOODLECST_SESSION_LISTSIZE',10);


require_once($CFG->dirroot.'/mod/moodlecst/slidepair/slidepairlib.php');

//get session items
function mod_moodlecst_get_session_items($moodlecstid){
	global $DB;
	$usesession = $DB->get_record(MOD_MOODLECST_SESSION_TABLE,
			array('moodlecst'=>$moodlecstid, 'active'=>1),'*', IGNORE_MULTIPLE); 
	if($usesession){
		$slidepair_SQL_IN =mod_moodlecst_create_sql_in($usesession->slidepairkeys);
		$items = $DB->get_records_select(MOD_MOODLECST_SLIDEPAIR_TABLE, 
			'slidepairkey IN (' . $slidepair_SQL_IN   . ')' ,array(),'moodlecst, slidepairkey ASC');
	}else{
		$items = $DB->get_records(MOD_MOODLECST_SLIDEPAIR_TABLE,array('moodlecst'=>$moodlecstid),'name ASC');
	}
	return $items;
}
