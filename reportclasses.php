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
 *  Report Classes.
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Classes for Reports 
 *
 *	The important functions are:
*  process_raw_data : turns log data for one thing (e.g question attempt) into one row
 * fetch_formatted_fields: uses data prepared in process_raw_data to make each field in fields full of formatted data
 * The allusers report is the simplest example 
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class mod_moodlecst_base_report {

    protected $report="";
    protected $head=array();
	protected $rawdata=null;
    protected $fields = array();
	protected $dbcache=array();
	
	
	abstract function process_raw_data($formdata,$moduleinstance);
	abstract function fetch_formatted_heading();
	
	public function fetch_fields(){
		return $this->fields;
	}
	public function fetch_head(){
		$head=array();
		foreach($this->fields as $field){
			$head[]=get_string($field,MOD_MOODLECST_LANG);
		}
		return $head;
	}
	public function fetch_name(){
		return $this->report;
	}

	public function truncate($string, $maxlength){
		if(strlen($string)>$maxlength){
			$string=substr($string,0,$maxlength - 2) . '..';
		}
		return $string;
	}

	public function fetch_cache($table,$rowid){
		global $DB;
		if(!array_key_exists($table,$this->dbcache)){
			$this->dbcache[$table]=array();
		}
		if(!array_key_exists($rowid,$this->dbcache[$table])){
			$this->dbcache[$table][$rowid]=$DB->get_record($table,array('id'=>$rowid));
		}
		return $this->dbcache[$table][$rowid];
	}

	public function fetch_formatted_time($seconds){
			
			//return empty string if the timestamps are not both present.
			if(!$seconds){return '';}
			$time = time();
			
			return $this->fetch_time_difference($time, $time + $seconds);
	}
	
	public function fetch_time_difference($starttimestamp,$endtimestamp){
			
			//return empty string if the timestamps are not both present.
			if(!$starttimestamp || !$endtimestamp){return '';}
			
			$s = $date = new DateTime();
			$s->setTimestamp($starttimestamp);
						
			$e =$date = new DateTime();
			$e->setTimestamp($endtimestamp);
						
			$diff = $e->diff($s);
			$ret = $diff->format("%H:%I:%S");
			return $ret;
	}
	
	public function fetch_formatted_rows($withlinks=true){
		$records = $this->rawdata;
		$fields = $this->fields;
		$returndata = array();
		foreach($records as $record){
			$data = new stdClass();
			foreach($fields as $field){
				$data->{$field}=$this->fetch_formatted_field($field,$record,$withlinks);
			}//end of for each field
			$returndata[]=$data;
		}//end of for each record
		return $returndata;
	}
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'timecreated':
					$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
				case 'userid':
					$u = $this->fetch_cache('user',$record->userid);
					$ret =fullname($u);
					break;
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
}

/*
* Basic Report
*
*
*/
class mod_moodlecst_basic_report extends  mod_moodlecst_base_report {
	
	protected $report="basic";
	protected $fields = array('id','name','timecreated');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						break;
				
				case 'name':
						$ret = $record->name;
					break;
				
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
					
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		//$ec = $this->fetch_cache(MOD_MOODLECST_TABLE,$record->englishcentralid);
		return get_string('basicheading',MOD_MOODLECST_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_MOODLECST_TABLE,array());
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

/*
* All Attempts Report
*
*
*/
class mod_moodlecst_allattempts_report extends  mod_moodlecst_base_report {
	
	protected $report="allattempts";
	protected $fields = array('id','username','partnername','sessionscore','totaltime','timecreated');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						if($withlinks){
							$oneattempturl = new moodle_url('/mod/moodlecst/reports.php', 
									array('n'=>$record->moodlecstid,
									'report'=>'oneattempt',
									'attemptid'=>$record->id));
								$ret = html_writer::link($oneattempturl,$ret);
						}
						break;
				
				case 'username':
						$theuser = $this->fetch_cache('user',$record->userid);
						$ret=fullname($theuser);
					break;
					
				case 'partnername':
						$theuser = $this->fetch_cache('user',$record->partnerid);
						$ret=fullname($theuser);
					break;
				case 'totaltime':
						$ret= $this->fetch_formatted_time($record->totaltime);
						break;
						
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
					
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		//$ec = $this->fetch_cache(MOD_MOODLECST_TABLE,$record->englishcentralid);
		return get_string('allattemptsheading',MOD_MOODLECST_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_MOODLECST_ATTEMPTTABLE,array('course'=>$moduleinstance->course,'moodlecstid'=>$moduleinstance->id));
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

/*
* All Attempts Report
*
*
*/
class mod_moodlecst_oneattempt_report extends  mod_moodlecst_base_report {
	
	protected $report="oneattempt";
	protected $fields = array('id','slidepairname','answer','correct','totaltime','timecreated');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						break;
				
				case 'slidepairname':
						$theslidepair = $this->fetch_cache(MOD_MOODLECST_SLIDEPAIR_TABLE,$record->slidepairid);
						$ret=$theslidepair->name;
					break;
					
				case 'correct':
						$theuser = $this->fetch_cache('user',$record->partnerid);
						$ret=$record->correct ? get_string('yes') : get_string('no');
					break;
				
				case 'answer':
						$ret=$record->answerid;
					break;
				
				case 'totaltime':
						$ret= $this->fetch_formatted_time($record->duration);
						break;
						
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
					
				default:
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		$record = $this->headingdata;
		$ret='';
		if(!$record){return $ret;}
		//$ec = $this->fetch_cache(MOD_MOODLECST_TABLE,$record->englishcentralid);
		return get_string('oneattemptheading',MOD_MOODLECST_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_MOODLECST_ATTEMPTITEMTABLE,array('attemptid'=>$formdata->attemptid,'course'=>$moduleinstance->course,'moodlecstid'=>$moduleinstance->id));
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}
