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

require_once($CFG->dirroot .'/mod/moodlecst/slidepair/slidepairforms.php');

//require_once($CFG->dirroot.'/mod/moodlecst/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_moodlecst
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodlecst_slidepair_renderer extends plugin_renderer_base {

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

		$addtextchoiceitemurl = new moodle_url('/mod/moodlecst/slidepair/manageslidepairs.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_MOODLECST_SLIDEPAIR_TYPE_TEXTCHOICE));
        $links[] = html_writer::link($addtextchoiceitemurl, get_string('addtextchoiceitem', 'moodlecst'));
		
        $addpicturechoiceitemurl = new moodle_url('/mod/moodlecst/slidepair/manageslidepairs.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_MOODLECST_SLIDEPAIR_TYPE_PICTURECHOICE));
        $links[] = html_writer::link($addpicturechoiceitemurl, get_string('addpicturechoiceitem', 'moodlecst'));
        
        $addaudiochoiceitemurl = new moodle_url('/mod/moodlecst/slidepair/manageslidepairs.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_MOODLECST_SLIDEPAIR_TYPE_AUDIOCHOICE));
        $links[] = html_writer::link($addaudiochoiceitemurl, get_string('addaudiochoiceitem', 'moodlecst'));
		
		$addtabooitemurl = new moodle_url('/mod/moodlecst/slidepair/manageslidepairs.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_MOODLECST_SLIDEPAIR_TYPE_TABOO));
        $links[] = html_writer::link($addtabooitemurl, get_string('addtabooitem', 'moodlecst'));
		
		
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
			return $this->output->heading(get_string('noitems','moodlecst'), 3, 'main');
		}
	
		$table = new html_table();
		$table->id = 'MOD_MOODLECST_qpanel';
		$table->head = array(
			get_string('itemname', 'moodlecst'),
			get_string('itemtype', 'moodlecst'),
			get_string('actions', 'moodlecst')
		);
		$table->headspan = array(1,1,2);
		$table->colclasses = array(
			'itemname', 'itemtitle', 'edit','delete'
		);

		//sort by start date
		core_collator::asort_objects_by_property($items,'timecreated',core_collator::SORT_NUMERIC);

		//loop through the homoworks and add to table
		foreach ($items as $item) {
			$row = new html_table_row();
		
		
			$itemnamecell = new html_table_cell($item->name);	
			switch($item->type){
				case MOD_MOODLECST_SLIDEPAIR_TYPE_PICTURECHOICE:
					$itemtype = get_string('picturechoice','moodlecst');
					break;
				case MOD_MOODLECST_SLIDEPAIR_TYPE_AUDIOCHOICE:
					$itemtype = get_string('audiochoice','moodlecst');
					break;
				case MOD_MOODLECST_SLIDEPAIR_TYPE_TABOO:
					$itemtype = get_string('taboo','moodlecst');
					break;
				case MOD_MOODLECST_SLIDEPAIR_TYPE_TEXTCHOICE:
					$itemtype = get_string('textchoice','moodlecst');
					break;
				default:
			} 
			$itemtypecell = new html_table_cell($itemtype);
		
			$actionurl = '/mod/moodlecst/slidepair/manageslidepairs.php';
			$editurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id));
			$editlink = html_writer::link($editurl, get_string('edititem', 'moodlecst'));
			$editcell = new html_table_cell($editlink);
			
			//$previewlink = $this->fetch_preview_link($item->id,$moodlecst->id);
			//$previewcell = new html_table_cell($previewlink);
		
			$deleteurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id,'action'=>'confirmdelete'));
			$deletelink = html_writer::link($deleteurl, get_string('deleteitem', 'moodlecst'));
			$deletecell = new html_table_cell($deletelink);

			$row->cells = array(
				$itemnamecell, $itemtypecell, $editcell, $deletecell
			);
			$table->data[] = $row;
		}

		return html_writer::table($table);

	}
}