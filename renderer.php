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
require_once('slidepair/slidepairrenderer.php');

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_moodlecst
 * @copyright moodlecst
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodlecst_renderer extends plugin_renderer_base {
		  /**
     * Returns the header for the module
     *
     * @param mod $instance
     * @param string $currenttab current tab that is shown.
     * @param int    $item id of the anything that needs to be displayed.
     * @param string $extrapagetitle String to append to the page title.
     * @return string
     */
    public function header($moduleinstance, $cm, $currenttab = '', $itemid = null, $extrapagetitle = null) {
        global $CFG;

        $activityname = format_string($moduleinstance->name, true, $moduleinstance->course);
        if (empty($extrapagetitle)) {
            $title = $this->page->course->shortname.": ".$activityname;
        } else {
            $title = $this->page->course->shortname.": ".$activityname.": ".$extrapagetitle;
        }

        // Build the buttons
        $context = context_module::instance($cm->id);

    /// Header setup
        $this->page->set_title($title);
        $this->page->set_heading($this->page->course->fullname);
        $output = $this->output->header();

        if (has_capability('mod/moodlecst:manage', $context)) {
         //   $output .= $this->output->heading_with_help($activityname, 'overview', MOD_MOODLECST_LANG);

            if (!empty($currenttab)) {
                ob_start();
                include($CFG->dirroot.'/mod/moodlecst/tabs.php');
                $output .= ob_get_contents();
                ob_end_clean();
            }
        } else {
            $output .= $this->output->heading($activityname);
        }
	

        return $output;
    }
	
	public function show_node_server_button($cm,$action,$caption){
		//convert formdata to array
		$formdata = array();
		$formdata['id']=$cm->id;
		$formdata['action']=$action;
		$thebutton = new single_button(
			new moodle_url(MOD_MOODLECST_URL . '/nodeserver.php',$formdata), 
			$caption, 'get');

		return html_writer::div( $this->render($thebutton),MOD_MOODLECST_CLASS  . '_actionbuttons');
	}
	
	public function show_server_log($logdata){
		$ta = html_writer::tag('textarea', s($logdata),
            array('readonly' => 'readonly', 'wrap' => 'virtual', 'rows' => '20', 'cols' => '100'));

		return html_writer::div( $ta,MOD_MOODLECST_CLASS  . '_nodeserverlog');
	}
	
	/**
     * Return HTML to display limited header
     */
      public function notabsheader(){
      	return $this->output->header();
      }


	public function fetch_newsessionlink($cm, $isteacher,$caption,$moodlecst) {
		global $CFG,$USER;
		$activityid = $cm->id;
		$sesskey = $USER->sesskey;
		$userid = $USER->id;
		$partnermode = $moodlecst->partnermode==MOD_MOODLECST_PARTNERMODEAUTO ? 'auto' : 'manual';
		$mode = $moodlecst->mode==MOD_MOODLECST_MODETEACHERSTUDENT ? 'teacherstudent' : 'studentstudent';
		$urlparams = array('sesskey'=>$sesskey,'activityid'=>$activityid,'userid'=>$userid,'sessionid'=>1,'mode'=>$mode,'partnermode'=>$partnermode);
		switch($moodlecst->mode){
			case MOD_MOODLECST_MODESTUDENTSTUDENT:
				//the client gets confused of no set set, so by default 
				//we go in as student, until its changed
				$urlparams['seat'] = 'student';
				break;
			case MOD_MOODLECST_MODETEACHERSTUDENT:
			default:
				if($isteacher){
					$urlparams['seat'] = 'teacher';
					$urlparams['raterid'] = $userid;			
				}else{
					$urlparams['seat'] = 'student';
				}
		}
		$config = get_config(MOD_MOODLECST_FRANKY);
		$link = new moodle_url($config->nodejsurl . ':' . $config->nodejswebport,$urlparams);
		//$ret =  html_writer::link($link, get_string('gotocst',MOD_MOODLECST_LANG));
		$button = html_writer::tag('button',$caption, array('class'=>'btn btn-large btn-primary ' . MOD_MOODLECST_CLASS . '_actionbutton'));
		$popupparams = array('height'=>800,'width'=>1050);
		$popupaction = new popup_action('click', $link,'popup',$popupparams);
		$popupbutton =  $this->output->action_link($link, $button,$popupaction);
		$ret= html_writer::div($popupbutton ,MOD_MOODLECST_CLASS . '_buttoncontainer');
		return $ret;
    }
	  
	public function show_student_newsessionlink($cm,$caption,$moodlecst){
        return $this->fetch_newsessionlink($cm,false,$caption,$moodlecst);
    }
	
	public function show_teacher_newsessionlink($cm,$caption,$moodlecst){
        return $this->fetch_newsessionlink($cm,true,$caption,$moodlecst);
    }
	

    /**
     *
     */
    public function show_something($showtext) {
		$ret = $this->output->box_start();
		$ret .= $this->output->heading($showtext, 4, 'main');
		$ret .= $this->output->box_end();
        return $ret;
    }

	 /**
     *
     */
	public function show_intro($moodlecst,$cm){
		$ret = "";
		if (trim(strip_tags($moodlecst->intro))) {
			echo $this->output->box_start('mod_introbox');
			echo format_module_intro('moodlecst', $moodlecst, $cm->id);
			echo $this->output->box_end();
		}
	}
  
}

class mod_moodlecst_report_renderer extends plugin_renderer_base {


	public function render_reportmenu($moduleinstance,$cm, $reports) {
		$reportbuttons = array();
		foreach($reports as $report){
			$button = new single_button(
				new moodle_url(MOD_MOODLECST_URL . '/reports.php',array('report'=>$report,'id'=>$cm->id,'n'=>$moduleinstance->id)), 
				get_string($report .'report',MOD_MOODLECST_LANG), 'get');
			$reportbuttons[] = $this->render($button);
		}

		$ret = html_writer::div(implode('<br />',$reportbuttons) ,MOD_MOODLECST_CLASS  . '_listbuttons');

		return $ret;
	}

	public function render_delete_allattempts($cm){
		$deleteallbutton = new single_button(
				new moodle_url(MOD_MOODLECST_URL . '/manageattempts.php',array('id'=>$cm->id,'action'=>'confirmdeleteall')), 
				get_string('deleteallattempts',MOD_MOODLECST_LANG), 'get');
		$ret =  html_writer::div( $this->render($deleteallbutton) ,MOD_MOODLECST_CLASS  . '_actionbuttons');
		return $ret;
	}

	public function render_reporttitle_html($course,$username) {
		$ret = $this->output->heading(format_string($course->fullname),2);
		$ret .= $this->output->heading(get_string('reporttitle',MOD_MOODLECST_LANG,$username),3);
		return $ret;
	}

	public function render_empty_section_html($sectiontitle) {
		global $CFG;
		return $this->output->heading(get_string('nodataavailable',MOD_MOODLECST_LANG),3);
	}
	
	public function render_exportbuttons_html($cm,$formdata,$showreport){
		//convert formdata to array
		$formdata = (array) $formdata;
		$formdata['id']=$cm->id;
		$formdata['report']=$showreport;
		/*
		$formdata['format']='pdf';
		$pdf = new single_button(
			new moodle_url(MOD_MOODLECST_URL . '/reports.php',$formdata),
			get_string('exportpdf',MOD_MOODLECST_LANG), 'get');
		*/
		$formdata['format']='csv';
		$excel = new single_button(
			new moodle_url(MOD_MOODLECST_URL . '/reports.php',$formdata), 
			get_string('exportexcel',MOD_MOODLECST_LANG), 'get');

		return html_writer::div( $this->render($excel),MOD_MOODLECST_CLASS  . '_actionbuttons');
	}
	

	
	public function render_section_csv($sectiontitle, $report, $head, $rows, $fields) {

        // Use the sectiontitle as the file name. Clean it and change any non-filename characters to '_'.
        $name = clean_param($sectiontitle, PARAM_FILE);
        $name = preg_replace("/[^A-Z0-9]+/i", "_", trim($name));
		$quote = '"';
		$delim= ",";//"\t";
		$newline = "\r\n";

		header("Content-Disposition: attachment; filename=$name.csv");
		header("Content-Type: text/comma-separated-values");

		//echo header
		$heading="";	
		foreach($head as $headfield){
			$heading .= $quote . $headfield . $quote . $delim ;
		}
		echo $heading. $newline;
		
		//echo data rows
        foreach ($rows as $row) {
			$datarow = "";
			foreach($fields as $field){
				$datarow .= $quote . $row->{$field} . $quote . $delim ;
			}
			 echo $datarow . $newline;
		}
        exit();
        break;
	}

	public function render_section_html($sectiontitle, $report, $head, $rows, $fields) {
		global $CFG;
		if(empty($rows)){
			return $this->render_empty_section_html($sectiontitle);
		}
		
		//set up our table and head attributes
		$tableattributes = array('class'=>'generaltable '. MOD_MOODLECST_CLASS .'_table');
		$headrow_attributes = array('class'=>MOD_MOODLECST_CLASS . '_headrow');
		
		$htmltable = new html_table();
		$htmltable->attributes = $tableattributes;
		
		
		$htr = new html_table_row();
		$htr->attributes = $headrow_attributes;
		foreach($head as $headcell){
			$htr->cells[]=new html_table_cell($headcell);
		}
		$htmltable->data[]=$htr;
		
		foreach($rows as $row){
			$htr = new html_table_row();
			//set up descrption cell
			$cells = array();
			foreach($fields as $field){
				$cell = new html_table_cell($row->{$field});
				$cell->attributes= array('class'=>MOD_MOODLECST_CLASS . '_cell_' . $report . '_' . $field);
				$htr->cells[] = $cell;
			}

			$htmltable->data[]=$htr;
		}
		$html = $this->output->heading($sectiontitle, 4);
		$html .= html_writer::table($htmltable);
		return $html;
		
	}
	
	function show_reports_footer($moduleinstance,$cm,$formdata,$showreport){
		// print's a popup link to your custom page
		$link = new moodle_url(MOD_MOODLECST_URL . '/reports.php',array('report'=>'menu','id'=>$cm->id,'n'=>$moduleinstance->id));
		$ret =  html_writer::link($link, get_string('returntoreports',MOD_MOODLECST_LANG));
		$ret .= $this->render_exportbuttons_html($cm,$formdata,$showreport);
		return $ret;
	}

}

/**
 * A custom renderer class that outputs JSON representation for CST
 *
 * @package mod_moodlecst
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodlecst_json_renderer extends plugin_renderer_base {
 const INSTRUCTIONSID=-1;
 const CONSENTID=-2;
 const SESSIONSID=-3;
 const MYSEATID=-4;
 const PARTNERCONFIRMID=-5;

	/**
	 * Return JSON that nodejs client is expecting regarding quiz
	 * @param lesson $lesson
	 * @return string
	 */
	 public function render_results_json($results){
		$result = new stdClass;
		$status ='success';
		$progress = $results;
		$result->status=$status;
		$result->progess=$progress;
		return json_encode($result);
	 }
	 
	 /**
	 * Return JSON that nodejs client is expecting regarding quiz
	 * @param lesson $lesson
	 * @return string
	 */
	 public function render_testproperties_json($moodlecst){
		$properties = new stdClass;
		switch($moodlecst->timetarget){
			
			case MOD_MOODLECST_TIMETARGET_SHOW:
				$properties->timetarget='show';
				break;
			case MOD_MOODLECST_TIMETARGET_FORCE:
				$properties->timetarget='force';
				break;
			case MOD_MOODLECST_TIMETARGET_IGNORE:
			default:
				$properties->timetarget='ignore';
		}
		return json_encode($properties);
	 }

	 /**
	 * Return HTML to display add first page links
	 * @param lesson $lesson
	 * @return string
	 */
	 public function render_sessions_json($title,$context,$items,$moodlecst) {
		$sessions = new stdClass;
		$tasks = array();
		//fetch item ids
		foreach($items as $item){
				$tasks[] =  $this->fetch_item_id($item->type, $item);
		}
		
		//loop through labels
		$sessionlabels = array('1','2','3','4');
		foreach($sessionlabels as $label){
			$taskset =$tasks;
			//shuffling is evil
			//because the pairs might get different randomized.
			//To Be Figured out
			//shuffle($taskset);
			switch($label){
				case '1':
				case '3':
					sort($taskset);
					break;
				default:
					rsort($taskset);
			}
			
			//truncate it to the session size
			if($moodlecst->sessionsize > 0 && $moodlecst->selectsession && sizeof($taskset)>$moodlecst->sessionsize){
				$taskset = array_slice($taskset,0,$moodlecst->sessionsize);
			}
			
			//resort on 3 and 4 so all 4 sets are not the same 
			//this is just temporary we will do something better later
			switch($label){
				case '3':
					rsort($taskset);
					break;
				case '4':
					sort($taskset);
			}
			
			//prepend instructions and consent form 
			if($moodlecst->mode==MOD_MOODLECST_MODETEACHERSTUDENT){
				array_unshift($taskset,self::CONSENTID);
				array_unshift($taskset,self::INSTRUCTIONSID);
			}else{
				array_unshift($taskset,self::INSTRUCTIONSID);
			}
			
			//prepend session selection screen
			if($moodlecst->selectsession){
				array_unshift($taskset,self::SESSIONSID);
			}
			
			//Prepend Role Selection screen
			//this is only in student student mode, when auto making partners
			//otherwise there is the option on the setup screen or we show buttons to teachers
			if($moodlecst->mode == MOD_MOODLECST_MODESTUDENTSTUDENT){
				array_unshift($taskset,self::MYSEATID);
			}
			
			//partner confirmation
			array_unshift($taskset,self::PARTNERCONFIRMID);
			
			$sessions->{$label}=$taskset;
		}	
		return json_encode($sessions);
	 }
	
	/**
	 * Return user details (name + picurl) in json
	 * @param type (mydetails or partnerdetails
	 * @param object $user the user db etry whose details we want
	 * @return string
	 */
	 public function render_userdetails_json($type,$page, $user=false){
		$ret = new stdClass;
		$ret->type = $type;
		
		//if no user, return empty data
		if(!$user){
			$ret->userName = 'unknown';
			$ret->userPic = '';
		//if user, fetch name and pic url
		}else{		
			//username
			$ret->userName = strip_tags(fullname($user));
			
			//userpic
			$up =new user_picture($user);
			$up->size=100;
			$picurl = $up->get_url($page);
			$ret->userPic =strip_tags($picurl->__toString());
		}
		
		//return data
		return json_encode($ret);
	 
	 }
	
	
	/**
	 * Return HTML to display add first page links
	 * @param lesson $lesson
	 * @return string
	 */
	 public function render_tasks_json($title,$context,$items,$moodlecst) {
		$config  = get_config(MOD_MOODLECST_FRANKY);
		
		//build the test object
		$test = new stdClass;
		$test->id = 1;
		$test->title = $title;
	
		$instructionitem= new stdClass();
		$instructionitem->id = self::INSTRUCTIONSID;
		$instructionitem->type =MOD_MOODLECST_SLIDEPAIR_TYPE_INSTRUCTIONS;
		$instructionitem->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION} = $config->generalinstructions_teacher;
		$instructionitem->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '1'} = $config->generalinstructions_student;
		array_unshift($items,$instructionitem);
		
		$consentitem= new stdClass();
		$consentitem->id = self::CONSENTID;
		$consentitem->type =MOD_MOODLECST_SLIDEPAIR_TYPE_CONSENT;
		$consentitem->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION}= $config->consent;
		array_unshift($items,$consentitem);
		
		$sessionsitem = new stdClass();
		$sessionsitem->id = self::SESSIONSID;
		$sessionsitem->type =MOD_MOODLECST_SLIDEPAIR_TYPE_CHOICE;
		//prompt
		$sessionsitem->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION} = 'Choose the Session:';
		//variable
		$sessionsitem->{MOD_MOODLECST_SLIDEPAIR_AUDIOFNAME}='sessionId';
		//variable values
		$sessionsitem->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '1'} = '1';
		$sessionsitem->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '2'} = '2';
		$sessionsitem->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '3'} = '3';
		$sessionsitem->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '4'} = '4';
		array_unshift($items,$sessionsitem);
		
		$myseatitem = new stdClass();
		$myseatitem->id = self::MYSEATID;
		$myseatitem->type =MOD_MOODLECST_SLIDEPAIR_TYPE_CHOICE;
		//prompt
		$myseatitem->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION} = 'Choose your Role:';
		//variable
		$myseatitem->{MOD_MOODLECST_SLIDEPAIR_AUDIOFNAME}='action:doSetSeat';
		//variable values
		$myseatitem->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '1'} = 'teacher';
		$myseatitem->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '2'} = 'student';
		array_unshift($items,$myseatitem);
		
		$partnerconfirmitem= new stdClass();
		$partnerconfirmitem->id = self::PARTNERCONFIRMID;
		$partnerconfirmitem->type =MOD_MOODLECST_SLIDEPAIR_TYPE_PARTNERCONFIRM;
		$partnerconfirmitem->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION}= '';
		array_unshift($items,$partnerconfirmitem);
		
		
		
		//process our tasks
		$tasks = array();
		foreach($items as $item){
			$tasks[] = $this->render_slidepair($context,$item);
		}
		$test->tasks = $tasks;
		
		//build our return object
		$ret = new stdClass;
		$ret->test = $test;
		
		return json_encode($ret);
	 }
	 
	 public function fetch_item_id($type, $item){
		$return ='unknown';
		switch($item->type){
			case MOD_MOODLECST_SLIDEPAIR_TYPE_PICTURECHOICE:
				//$return = 'picture_' . $item->id;
				$return  = $item->id;
				break;
			case MOD_MOODLECST_SLIDEPAIR_TYPE_TEXTCHOICE:
				//$return = 'listen_' . $item->id;
				$return  = $item->id;
				break;
			case MOD_MOODLECST_SLIDEPAIR_TYPE_TABOO:
				//$return = 'taboo_' . $item->id;
				$return  = $item->id;
				break;
			case MOD_MOODLECST_SLIDEPAIR_TYPE_TRANSLATE:
				//$return = 'taboo_' . $item->id;
				$return  = $item->id;
				break;
			default:
				$return  = $item->id;
		}
		return $return;
	 }

	/**
	 * Return HTML to display add first page links
	 * @param lesson $lesson
	 * @return string
	 */
	 public function render_slidepair($context,$item) {
		$theitem = new stdClass;
		$theitem->id = $this->fetch_item_id($item->type, $item);
		if(!isset($item->timetarget)){
			$item->timetarget = 0;
		}
		$theitem->timetarget=$item->timetarget;

		switch($item->type){
			case MOD_MOODLECST_SLIDEPAIR_TYPE_PICTURECHOICE:
				$theitem->type='Productive';
				$theitem->subType='picture';
				$theitem->content=$this->fetch_media_url($context,MOD_MOODLECST_SLIDEPAIR_PICTUREQUESTION_FILEAREA,$item);
				$answers = array();
				for($x=1;$x<MOD_MOODLECST_SLIDEPAIR_MAXANSWERS+1;$x++){
					$theanswer= new stdClass;
					$theanswer->id = $x;
					$theanswer->img = $this->fetch_media_url($context,MOD_MOODLECST_SLIDEPAIR_PICTUREANSWER_FILEAREA . $x,$item);
					$theanswer->correct = ($x==$item->{MOD_MOODLECST_SLIDEPAIR_CORRECTANSWER});
					$answers[] = $theanswer;
				}
				$theitem->answers = $answers;
				break;
				
			case MOD_MOODLECST_SLIDEPAIR_TYPE_CHOICE:
				$theitem->type='Productive';
				$theitem->subType='choice';
				$theitem->heading=$item->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION};
				$theitem->variable=$item->{MOD_MOODLECST_SLIDEPAIR_AUDIOFNAME};
				$answers = array();
				for($x=1;$x<MOD_MOODLECST_SLIDEPAIR_MAXANSWERS+1;$x++){
					if(!property_exists($item,MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . $x)){
						break;
					}
					$theanswer= new stdClass;
					$theanswer->id = $x;
					$theanswer->text = $item->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . $x};
					$theanswer->correct = true;
					$answers[] = $theanswer;
				}
				$theitem->answers = $answers;
				break;
			
			case MOD_MOODLECST_SLIDEPAIR_TYPE_TEXTCHOICE:
				$theitem->type='Productive';
				$theitem->subType='listen';
				$theitem->content=$this->fetch_media_url($context,MOD_MOODLECST_SLIDEPAIR_AUDIOQUESTION_FILEAREA,$item);
				$answers = array();
				for($x=1;$x<MOD_MOODLECST_SLIDEPAIR_MAXANSWERS+1;$x++){
					$theanswer= new stdClass;
					$theanswer->id = $x;
					$theanswer->text = $item->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . $x};
					$theanswer->correct = ($x==$item->{MOD_MOODLECST_SLIDEPAIR_CORRECTANSWER});
					$answers[] = $theanswer;
				}
				$theitem->answers = $answers;
				break;
				
			case MOD_MOODLECST_SLIDEPAIR_TYPE_TABOO:
				$theitem->type='Productive';
				$theitem->subType='taboo';
				$theitem->content=$item->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION};
				$answers = array();
				$theanswer= new stdClass;
				$theanswer->id = 1;
				$theanswer->text = 'Done';
				$answers[] = $theanswer;
				$theitem->answers = $answers;
				break;
				
			case MOD_MOODLECST_SLIDEPAIR_TYPE_TRANSLATE:
				$theitem->type='Receptive';
				$theitem->subType='translate';
				$theitem->content=array('source'=>$item->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION},'target'=>$item->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '1'});
				$answers = array();
				$theanswer= new stdClass;
				$theanswer->id = 1;
				$theanswer->text = $item->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '1'};
				$theanswer->correct = 1;
				$answers[] = $theanswer;
				$theitem->answers = $answers;
				break;
				
			case MOD_MOODLECST_SLIDEPAIR_TYPE_AUDIOCHOICE:
				break;
				
			case MOD_MOODLECST_SLIDEPAIR_TYPE_CONSENT:
				$theitem->type='Productive';
				$theitem->subType='consent';
				$theitem->content=$item->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION};
				$theitem->answers='waiting for consent';
				break;
				
			case MOD_MOODLECST_SLIDEPAIR_TYPE_PARTNERCONFIRM:
				$theitem->type='Productive';
				$theitem->subType='partnerconfirm';
				$theitem->content='';
				$theitem->answers='';
				break;
				
			case MOD_MOODLECST_SLIDEPAIR_TYPE_INSTRUCTIONS:
				$theitem->type='Productive';
				$theitem->subType='instructions';
				$theitem->content=$item->{MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION};
				$theitem->answers=$item->{MOD_MOODLECST_SLIDEPAIR_TEXTANSWER . '1'};
				break;
				
			case MOD_MOODLECST_SLIDEPAIR_TYPE_WHOWHO:
				$theitem->type='Productive';
				$theitem->subType='whowho';
				$theitem->content='';
				$theitem->answers='';
				break;
				
		}
		return $theitem;
	 }
	 
	 function fetch_media_url($context,$filearea,$item){
			//get question audio div (not so easy)			
			$fs = get_file_storage();
			$files = $fs->get_area_files($context->id, 'mod_moodlecst',$filearea,$item->id);
			foreach ($files as $file) {
				$filename = $file->get_filename();
				if($filename=='.'){continue;}
				$filepath = '/';
				$mediaurl = moodle_url::make_pluginfile_url($context->id,'mod_moodlecst',
						$filearea, $item->id,
						$filepath, $filename);
				return $mediaurl->__toString();
				
			}
			//We always take the first file and if we have none, thats not good.
			return "$context->id pp $filearea pp $item->id";
	 }


}


