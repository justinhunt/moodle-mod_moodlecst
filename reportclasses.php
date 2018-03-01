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
	
	public function fetch_formatted_milliseconds($milliseconds){
			
			//return empty string if the timestamps are not both present.
			if(!$milliseconds){return '';}
			$time = time();
			
			return $this->fetch_time_difference($time, $time + ($milliseconds/1000));
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
	protected $fields = array('id','username','partnername','sessionscore','totaltime','timecreated', 'delete');	
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
									'itemid'=>$record->id));
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
						$ret= $this->fetch_formatted_milliseconds($record->totaltime);
						break;
						
				case 'timecreated':
						$ret = date("Y-m-d H:i:s",$record->timecreated);
					break;
					

				case 'delete':
					if($withlinks){
						$actionurl = '/mod/moodlecst/manageattempts.php';
						$deleteurl = new moodle_url($actionurl, array('id'=>$record->cmid,'attemptid'=>$record->id,'action'=>'confirmdelete'));
						$ret = html_writer::link($deleteurl, get_string('deleteattempt', 'moodlecst'));
					}else{
						$ret="";
					}
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
		
		
		foreach($alldata as $adata){
			$adata->cmid = $formdata->cmid;
		}
		
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
class mod_moodlecst_allabilities_report extends  mod_moodlecst_base_report {

    protected $report="allabilities";
    protected $fields = array('id','username','partnername','sessionscore','ability','totaltime','timecreated', 'delete');
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
                            'itemid'=>$record->id));
                    $ret = html_writer::link($oneattempturl,$ret);
                }
                break;

            case 'username':
                $theuser = $this->fetch_cache('user',$record->userid);
                $ret=fullname($theuser);
                break;

            case 'ability':
                $ret=$record->ability;
                if($withlinks){
                    $showabilitycalcurl = new moodle_url('/mod/moodlecst/reports.php',
                        array('n'=>$record->moodlecstid,
                            'report'=>'showabilitycalc',
                            'itemid'=>$record->id));
                    $ret = html_writer::link($showabilitycalcurl,$ret);
                }
                break;

            case 'partnername':
                $theuser = $this->fetch_cache('user',$record->partnerid);
                $ret=fullname($theuser);
                break;
            case 'totaltime':
                $ret= $this->fetch_formatted_milliseconds($record->totaltime);
                break;

            case 'timecreated':
                $ret = date("Y-m-d H:i:s",$record->timecreated);
                break;


            case 'delete':
                if($withlinks){
                    $actionurl = '/mod/moodlecst/manageattempts.php';
                    $deleteurl = new moodle_url($actionurl, array('id'=>$record->cmid,'attemptid'=>$record->id,'action'=>'confirmdelete'));
                    $ret = html_writer::link($deleteurl, get_string('deleteattempt', 'moodlecst'));
                }else{
                    $ret="";
                }
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

        return get_string('allabilitiesheading',MOD_MOODLECST_LANG);

    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data
        $this->headingdata = new stdClass();

        $emptydata = array();
        $alldata = $DB->get_records(MOD_MOODLECST_ATTEMPTTABLE,array('course'=>$moduleinstance->course,'moodlecstid'=>$moduleinstance->id,'ucatenabled'=>1));


        foreach($alldata as $adata){
            $adata->cmid = $formdata->cmid;
        }

        if($alldata){
            $this->rawdata= $alldata;
        }else{
            $this->rawdata= $emptydata;
        }
        return true;
    }
}

/*
* Latet Ability Report : this is faulty. hard to show slidepairids, each user different
*
*
*/
class mod_moodlecst_latestabilitysummary_report extends mod_moodlecst_base_report
{

    protected $report = "latestabilitysummary";
    protected $fields = array();//this is set in process raw data
    protected $headingdata = null;
    protected $qcache = array();
    protected $ucache = array();

    public function fetch_head()
    {
        $head = array();
        foreach ($this->fields as $field) {
            if (strpos($field, 'item_correct_') === 0) {
                $itemid = str_replace('item_correct_', '', $field);
                $slidepair = $this->fetch_cache('moodlecst_slidepairs', $itemid);
                if ($slidepair) {
                    $head[] = $slidepair->name . ':correct';
                } else {
                    $head[] = 'item:correct';
                }
            } elseif (strpos($field, 'item_duration_') === 0) {
                $itemid = str_replace('item_duration_', '', $field);
                $slidepair = $this->fetch_cache('moodlecst_slidepairs', $itemid);
                if ($slidepair) {
                    $head[] = $slidepair->name . ':time';
                } else {
                    $head[] = 'item:duration';
                }
            } else {
                $head[] = get_string($field, MOD_MOODLECST_LANG);
            }
        }
        return $head;
    }

    public function fetch_formatted_field($field, $record, $withlinks)
    {
        global $DB;
        switch ($field) {

            case 'username':
                $theuser = $this->fetch_cache('user', $record->username);
                $ret = fullname($theuser);
                break;

            case 'moodlecst':
                $themoodlecst = $this->fetch_cache('moodlecst', $record->moodlecst);
                $ret = $themoodlecst->name;
                break;

            default:
                //put logic here if need to format item correct or time
                if (strpos($field, 'item_correct_') === 0) {
                    //do something
                } elseif (strpos($field, 'item_duration_') === 0) {
                    //do something
                }

                if (property_exists($record, $field)) {
                    $ret = $record->{$field};
                } else {
                    $ret = '';
                }
        }
        return $ret;
    }
    public function fetch_formatted_heading(){
        return get_string('latestabilitysummary',MOD_MOODLECST_LANG,$this->headingdata->name );
    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data for report header, add moodle cst name
        $this->headingdata = new stdClass();
        $this->headingdata = $this->fetch_cache('moodlecst',$moduleinstance->id);

        $emptydata = array();


        $itemarray= $DB->get_fieldset_select(MOD_MOODLECST_ATTEMPTITEMTABLE,
            'slidepairid', 'moodlecstid = ?',array($moduleinstance->id));
        $items = array_unique($itemarray);

        //print_r($items);

        $sql ='SELECT *, MAX(attemptid) as maxattemptid FROM {' . MOD_MOODLECST_ATTEMPTITEMTABLE . '} ';
        $sql .= 'WHERE moodlecstid =? AND slidepairid IN ('. implode(',',$items) .') GROUP BY userid,slidepairid';

        //echo $sql;
        //die;

        $itemsbyuser = $DB->get_records_sql($sql,array($moduleinstance->id));

        //update the fields since each run of the report may have diff fields in it
        $this->fields = array('username');
        foreach($items as $item){
            $this->fields[]='item_correct_' . $item;
            $this->fields[]='item_duration_' . $item;
        }

        //sometimes we get a userid of 0 ... this is odd
        //how does that happen. Anyway default is -1 which means the first
        //pass of data processing will detect a new user data set
        $currentuserid=-1;

        $rawdatarow = false;
        foreach($itemsbyuser as $useritem){
            //data is a series of rows each of a diff slidepair grouped by user
            //so we group data till the user changes, then we stash it
            if($useritem->userid!=$currentuserid){
                if($rawdatarow){
                    $this->rawdata[]= $rawdatarow;
                }
                $currentuserid = $useritem->userid;
                $rawdatarow= new stdClass;
                $rawdatarow->username=$useritem->userid;
                $rawdatarow->moodlecst=$moduleinstance->id;
                foreach($items as $item){
                    $rawdatarow->{'item_correct_' . $item}='-';
                    $rawdatarow->{'item_duration_' . $item}='-';
                }
            }
            //stash the slide pair data
            $rawdatarow->{'item_correct_' . $useritem->slidepairid}=$useritem->correct;
            $rawdatarow->{'item_duration_' . $useritem->slidepairid}=$useritem->duration;
        }
        if($rawdatarow){
            $this->rawdata[]= $rawdatarow;
        }

        if(!$rawdatarow){
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
class mod_moodlecst_latestattemptsummary_report extends mod_moodlecst_base_report {
	
	protected $report="latestattemptsummary";
	protected $fields = array();//this is set in process raw data	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_head(){
		$head=array();
		foreach($this->fields as $field){
			if(strpos($field,'item_correct_')===0){
				$itemid = str_replace('item_correct_','',$field);
				$slidepair =$this->fetch_cache('moodlecst_slidepairs',$itemid);
				if($slidepair){
					$head[]=$slidepair->name . ':correct' ;
				}else{
					$head[]='item:correct';
				}	
			}elseif(strpos($field,'item_duration_')===0){
				$itemid = str_replace('item_duration_','',$field);
				$slidepair =$this->fetch_cache('moodlecst_slidepairs',$itemid);
				if($slidepair){
					$head[]=$slidepair->name . ':time' ;
				}else{
					$head[]='item:duration';
				}
			}else{
				$head[]=get_string($field,MOD_MOODLECST_LANG);
			}
		}
		return $head;
	}
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){

				case 'username':
						$theuser = $this->fetch_cache('user',$record->username);
						$ret=fullname($theuser);
					break;
					
				case 'moodlecst':
						$themoodlecst = $this->fetch_cache('moodlecst',$record->moodlecst);
						$ret=$themoodlecst->name;
					break;
			
				default:
					//put logic here if need to format item correct or time
					if(strpos($field,'item_correct_')===0){
						//do something
					}elseif(strpos($field,'item_duration_')===0){
						//do something
					}
				
					if(property_exists($record,$field)){
						$ret=$record->{$field};
					}else{
						$ret = '';
					}
			}
			return $ret;
	}
	
	public function fetch_formatted_heading(){
		return get_string('latestattemptsummary',MOD_MOODLECST_LANG,$this->headingdata->name );
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data for report header, add moodle cst name
		$this->headingdata = new stdClass();
		$this->headingdata = $this->fetch_cache('moodlecst',$moduleinstance->id);
		
		$emptydata = array();
		
		$itemarray= $DB->get_fieldset_select(MOD_MOODLECST_ATTEMPTITEMTABLE,
		'slidepairid', 'moodlecstid = ?',array($moduleinstance->id));
		$items = array_unique($itemarray);
		
		//print_r($items);
				
		$sql ='SELECT *, MAX(attemptid) as maxattemptid FROM {' . MOD_MOODLECST_ATTEMPTITEMTABLE . '} ';
		$sql .= 'WHERE moodlecstid =? AND slidepairid IN ('. implode(',',$items) .') GROUP BY userid,slidepairid';
		
		//echo $sql;
		//die;
		
		$itemsbyuser = $DB->get_records_sql($sql,array($moduleinstance->id));

		//update the fields since each run of the report may have diff fields in it
		$this->fields = array('username');	
		foreach($items as $item){
			$this->fields[]='item_correct_' . $item;
			$this->fields[]='item_duration_' . $item;
		}
		
		//sometimes we get a userid of 0 ... this is odd
		//how does that happen. Anyway default is -1 which means the first
		//pass of data processing will detect a new user data set
		$currentuserid=-1;
		
		$rawdatarow = false;
		foreach($itemsbyuser as $useritem){
			//data is a series of rows each of a diff slidepair grouped by user
			//so we group data till the user changes, then we stash it
			if($useritem->userid!=$currentuserid){
				if($rawdatarow){
					$this->rawdata[]= $rawdatarow;
				}
				$currentuserid = $useritem->userid;
				$rawdatarow= new stdClass;
				$rawdatarow->username=$useritem->userid;
				$rawdatarow->moodlecst=$moduleinstance->id;
				foreach($items as $item){
					$rawdatarow->{'item_correct_' . $item}='-';
					$rawdatarow->{'item_duration_' . $item}='-';
				}
			}
			//stash the slide pair data
			$rawdatarow->{'item_correct_' . $useritem->slidepairid}=$useritem->correct;
			$rawdatarow->{'item_duration_' . $useritem->slidepairid}=$useritem->duration;
		}
		if($rawdatarow){
			$this->rawdata[]= $rawdatarow;
		}
		
		if(!$rawdatarow){
			$this->rawdata= $emptydata;
		}
		return true;
	}
}







/*
* One Attempt Report
*
*
*/
class mod_moodlecst_oneattempt_report extends  mod_moodlecst_base_report {
	
	protected $report="oneattempt";
	protected $fields = array('id','slidepairname','answer','difficulty','correct','points','totaltime','timecreated');
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						if($withlinks && false){
							$oneattempturl = new moodle_url('/mod/moodlecst/reports.php', 
									array('n'=>$record->moodlecstid,
									'report'=>'oneattempt',
									'itemid'=>$record->id));
								$ret = html_writer::link($oneattempturl,$ret);
						}
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
						$ret= $this->fetch_formatted_milliseconds($record->duration);
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

/*
* All Slidepairs Report
*
*
*/
class mod_moodlecst_allslidepairs_report extends  mod_moodlecst_base_report {
	
	protected $report="allslidepairs";
	protected $fields = array('id','slidepairname','count','avgcorrect','avgtotaltime');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->slidepairid;
						if($withlinks){
							$oneslidepairurl = new moodle_url('/mod/moodlecst/reports.php', 
									array('n'=>$record->moodlecstid,
									'report'=>'oneslidepair',
									'itemid'=>$record->slidepairid));
								$ret = html_writer::link($oneslidepairurl,$ret);
						}
						break;
						break;
				
				case 'slidepairname':
						$theslidepair = $this->fetch_cache(MOD_MOODLECST_SLIDEPAIR_TABLE,$record->slidepairid);
						$ret=$theslidepair->name;
					break;
					
				case 'count':
						$ret=$record->cntslidepairid;
					break;
				
				case 'avgcorrect':
						$ret= round($record->avgcorrect,2);
						break;				
					
				case 'avgtotaltime':
						$ret= $this->fetch_formatted_milliseconds(round($record->avgtotaltime));
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
		return get_string('allslidepairsheading',MOD_MOODLECST_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata= $DB->get_records_sql('SELECT slidepairid,moodlecstid,COUNT(slidepairid) AS cntslidepairid, AVG(correct) AS avgcorrect,AVG(duration) AS avgtotaltime FROM {'.	MOD_MOODLECST_ATTEMPTITEMTABLE.'} WHERE moodlecstid=:moodlecstid GROUP BY slidepairid',array('moodlecstid'=>$moduleinstance->id));

		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

/*
* One Attempt Report
*
*
*/
class mod_moodlecst_showabilitycalc_report extends  mod_moodlecst_base_report {

    protected $report="showabilitycalc";
    protected $fields = array('id','slidepairname','answer','difficulty','correct','ability','sd','se');
    protected $headingdata = null;
    protected $qcache=array();
    protected $ucache=array();

    public function fetch_formatted_field($field,$record,$withlinks){
        global $DB;
        switch($field){
            case 'id':
                $ret = $record->id;
                if($withlinks && false){
                    $oneattempturl = new moodle_url('/mod/moodlecst/reports.php',
                        array('n'=>$record->moodlecstid,
                            'report'=>'oneattempt',
                            'itemid'=>$record->id));
                    $ret = html_writer::link($oneattempturl,$ret);
                }
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

            case 'ability':
                $ret= round($record->ability,2);
                break;

            case 'sd':
                $ret = round($record->sd,2);
                break;

            case 'se':
                $ret = round($record->se,2);
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
        return get_string('showabilitycalcheading',MOD_MOODLECST_LANG);

    }

    public function process_raw_data($formdata,$moduleinstance){
        global $DB;

        //heading data
        $this->headingdata = new stdClass();

        $emptydata = array();
        $attemptitemdata = $DB->get_records(MOD_MOODLECST_ATTEMPTITEMTABLE,array('attemptid'=>$formdata->attemptid,'course'=>$moduleinstance->course,'moodlecstid'=>$moduleinstance->id));

        //process attempt data to get abilty log
       if($attemptitemdata) {

           //build answers for processanswer (could just pass in alldata ...but to keep it comprehensible..)
           foreach ($attemptitemdata as $attemptitem) {
               $answer = new stdClass();
               $answer->slidepairid = $attemptitem->slidepairid;
               $answer->answerid = $attemptitem->answerid;
               $answers[] = $answer;
               $slidepairids[] = $attemptitem->slidepairid;
           }
           $items = $DB->get_records_select(MOD_MOODLECST_SLIDEPAIR_TABLE,
               'id IN (' . implode(',', $slidepairids) . ')', array());

           $itemlogs = \mod_moodlecst\ucat::process_answer($items, $answers, $moduleinstance->estimatemethod, true);
           foreach ($attemptitemdata as $attemptitem) {
               $attemptitem->ability = $itemlogs[$attemptitem->slidepairid]->ability;
               $attemptitem->se = $itemlogs[$attemptitem->slidepairid]->se;
               $attemptitem->sd = $itemlogs[$attemptitem->slidepairid]->sd;
           }
       }

       //return results
        if($attemptitemdata){
            $this->rawdata= $attemptitemdata;
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
class mod_moodlecst_oneslidepair_report extends  mod_moodlecst_base_report {
	
	protected $report="oneslidepair";
	protected $fields = array('id','username','answer','correct','totaltime','timecreated');	
	protected $headingdata = null;
	protected $qcache=array();
	protected $ucache=array();
	
	public function fetch_formatted_field($field,$record,$withlinks){
				global $DB;
			switch($field){
				case 'id':
						$ret = $record->id;
						break;
				
				case 'username':
						$user = $this->fetch_cache('user',$record->userid);
						$ret=fullname($user);
					break;
		
					
				case 'correct':
						$theuser = $this->fetch_cache('user',$record->partnerid);
						$ret=$record->correct ? get_string('yes') : get_string('no');
					break;
				
				case 'answer':
						$ret=$record->answerid;
					break;
				
				case 'totaltime':
						$ret= $this->fetch_formatted_milliseconds($record->duration);
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
		return get_string('oneslidepairheading',MOD_MOODLECST_LANG);
		
	}
	
	public function process_raw_data($formdata,$moduleinstance){
		global $DB;
		
		//heading data
		$this->headingdata = new stdClass();
		
		$emptydata = array();
		$alldata = $DB->get_records(MOD_MOODLECST_ATTEMPTITEMTABLE,array('slidepairid'=>$formdata->slidepairitemid,'course'=>$moduleinstance->course,'moodlecstid'=>$moduleinstance->id));
		if($alldata){
			$this->rawdata= $alldata;
		}else{
			$this->rawdata= $emptydata;
		}
		return true;
	}
}

