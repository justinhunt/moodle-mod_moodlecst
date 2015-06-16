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

require_once($CFG->dirroot .'/mod/moodlecst/session/sessionforms.php');

//require_once($CFG->dirroot.'/mod/moodlecst/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_moodlecst
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodlecst_session_renderer extends plugin_renderer_base {

 /**
 * Return HTML to display add first page links
 * @param lesson $lesson
 * @return string
 */
 public function add_edit_page_links($moodlecst) {
		global $CFG;
        $itemid = 0;

        $output = $this->output->heading(get_string("whatdonow", "moodlecst"), 3);
        $links = array();

        $addnormalsessionurl = new moodle_url('/mod/moodlecst/session/managesessions.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_MOODLECST_SESSION_TYPE_NORMAL));
        $links[] = html_writer::link($addnormalsessionurl, get_string('addnormalsession', 'moodlecst'));
        

        return $this->output->box($output.'<p>'.implode('</p><p>', $links).'</p>', 'generalbox firstpageoptions');
    }
	
	/**
	 * Return the html table of homeworks for a group  / course
	 * @param array homework objects
	 * @param integer $courseid
	 * @return string html of table
	 */
	function show_items_list($items,$moodlecst,$cm){
	
		if(!$items){
			return $this->output->heading(get_string('nosessions','moodlecst'), 3, 'main');
		}
	
		$table = new html_table();
		$table->id = 'mod_moodlecst_qpanel';
		$table->head = array(
			get_string('itemname', 'moodlecst'),
			get_string('itemtype', 'moodlecst'),
			get_string('actions', 'moodlecst')
		);
		$table->headspan = array(1,1,3);
		$table->colclasses = array(
			'itemname', 'itemtitle', 'edit','preview','delete'
		);

		//sort by start date
		core_collator::asort_objects_by_property($items,'timecreated',core_collator::SORT_NUMERIC);

		//loop through the homoworks and add to table
		foreach ($items as $item) {
			$row = new html_table_row();
		
		
			$itemnamecell = new html_table_cell($item->name);	
			switch($item->type){
				case MOD_MOODLECST_SESSION_TYPE_NORMAL:
					$itemtype = get_string('normal','moodlecst');
					break;
				default:
			} 
			$itemtypecell = new html_table_cell($itemtype);
		
			$actionurl = '/mod/moodlecst/session/managesessions.php';
			$editurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id));
			$editlink = html_writer::link($editurl, get_string('edititem', 'moodlecst'));
			$editcell = new html_table_cell($editlink);
			
			//$previewurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id, 'action'=>'previewitem'));
			//$previewlink = html_writer::link($previewurl, get_string('previewitem', 'moodlecst'));
			$previewlink = $this->fetch_preview_link($item->id,$moodlecst->id);
			$previewcell = new html_table_cell($previewlink);
		
			$deleteurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id,'action'=>'confirmdelete'));
			$deletelink = html_writer::link($deleteurl, get_string('deleteitem', 'moodlecst'));
			$deletecell = new html_table_cell($deletelink);

			$row->cells = array(
				$itemnamecell, $itemtypecell, $editcell, $previewcell, $deletecell
			);
			$table->data[] = $row;
		}

		return html_writer::table($table);

	}
	
		public function fetch_chooser($chosen,$unchosen){
		//select lists
		$config= get_config('moodlecst');
		//$listheight=$config->listheight;
		$listheight = 10;
		if(!$listheight){$listheight=MOD_MOODLECST_SESSION_LISTSIZE;}
		 $listboxopts = array('class'=>MOD_MOODLECST_SESSION_SELECT, 'size'=>$listheight,'multiple'=>true);
		 $chosenbox =	html_writer::select($chosen,MOD_MOODLECST_SESSION_CHOSEN,'',false,$listboxopts);
		 $unchosenbox =	html_writer::select($unchosen,MOD_MOODLECST_SESSION_UNCHOSEN,'',false,$listboxopts);

		 
		 //buttons
		 $choosebutton = html_writer::tag('button',get_string('choose','moodlecst'),  
					array('type'=>'button','class'=>'mod_moodlecst_session_button yui3-button',
					'id'=>'mod_moodlecst_session_choosebutton','onclick'=>'M.mod_moodlecst_session.choose()'));
		$unchoosebutton = html_writer::tag('button',get_string('unchoose','moodlecst'),  
					array('type'=>'button','class'=>'mod_moodlecst_session_button yui3-button',
					'id'=>'mod_moodlecst_session_unchoosebutton','onclick'=>'M.mod_moodlecst_session.unchoose()'));
		$buttonbox = html_writer::tag('div', $choosebutton . '<br/>' . $unchoosebutton, array('class'=>'mod_moodlecst_session_buttoncontainer','id'=>'mod_moodlecst_session_buttoncontainer'));
		 
		 //filters
		 $chosenfilter = html_writer::tag('input','',  
					array('type'=>'text','class'=>'mod_moodlecst_session_text',
					'id'=>'mod_moodlecst_session_chosenfilter','onkeyup'=>'M.mod_moodlecst_session.filter_chosen()'));
		 $unchosenfilter = html_writer::tag('input','',  
					array('type'=>'text','class'=>'mod_moodlecst_session_text',
					'id'=>'mod_moodlecst_session_unchosenfilter','onkeyup'=>'M.mod_moodlecst_session.filter_unchosen()'));
		
		//the field to update for form submission
		$chosenkeys = array_keys($chosen);
		$usekeys='';
		if(!empty($chosenkeys)){
			$usekeys = implode(',',$chosenkeys);
		}
		
		//choose component container
		$htmltable = new html_table();
		$htmltable->attributes = array('class'=>'generaltable mod_moodlecst_session_choosertable');
		
		//heading row
		$htr = new html_table_row();
		$htr->cells[] = get_string('chosenlabel','moodlecst');
		$htr->cells[] = '';
		$htr->cells[] = get_string('unchosenlabel','moodlecst');
		$htmltable->data[]=$htr;
		
		
		//chooser components
		$listcellattributes = array('class'=>'listcontainer');
		$buttoncellattributes = array('class'=>'buttoncontainer');
		
		$ftr = new html_table_row();
		$cell = new html_table_cell($chosenbox . '<br/>' . $chosenfilter);
		$cell->attributes =$listcellattributes;
		$ftr->cells[] = $cell;
		$cell = new html_table_cell($buttonbox);
		$cell->attributes =$buttoncellattributes;
		$ftr->cells[] = $cell;
		$cell = new html_table_cell($unchosenbox . '<br/>' . $unchosenfilter);
		$cell->attributes =$listcellattributes;
		$ftr->cells[] = $cell;
		$htmltable->data[]=$ftr;
		$chooser = html_writer::table($htmltable);
		
		return $chooser;
	}

}