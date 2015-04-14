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

require_once($CFG->dirroot .'/mod/CRUDMODULE/CRUD/CRUDforms.php');

//require_once($CFG->dirroot.'/mod/CRUDMODULE/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base.
 *
 * @package mod_CRUDMODULE
 * @copyright COPYRIGHTNOTICE
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_CRUDMODULE_CRUD_renderer extends plugin_renderer_base {

 /**
 * Return HTML to display add first page links
 * @param lesson $lesson
 * @return string
 */
 public function add_edit_page_links($CRUDMODULE) {
		global $CFG;
        $itemid = 0;

        $output = $this->output->heading(get_string("whatdonow", "CRUDMODULE"), 3);
        $links = array();

        $addtextchoiceitemurl = new moodle_url('/mod/CRUDMODULE/CRUD/manageCRUDs.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_CRUDMODULE_CRUD_TYPE_TEXTCHOICE));
        $links[] = html_writer::link($addtextchoiceitemurl, get_string('addtextchoiceitem', 'CRUDMODULE'));
        
        $addaudiochoiceitemurl = new moodle_url('/mod/CRUDMODULE/CRUD/manageCRUDs.php',
			array('id'=>$this->page->cm->id, 'itemid'=>$itemid, 'type'=>MOD_CRUDMODULE_CRUD_TYPE_AUDIOCHOICE));
        $links[] = html_writer::link($addaudiochoiceitemurl, get_string('addaudiochoiceitem', 'CRUDMODULE'));
		
		
        return $this->output->box($output.'<p>'.implode('</p><p>', $links).'</p>', 'generalbox firstpageoptions');
    }
	
	/**
	 * Return the html table of homeworks for a group  / course
	 * @param array homework objects
	 * @param integer $courseid
	 * @return string html of table
	 */
	function show_items_list($items,$CRUDMODULE,$cm){
	
		if(!$items){
			return $this->output->heading(get_string('noitems','CRUDMODULE'), 3, 'main');
		}
	
		$table = new html_table();
		$table->id = 'mod_CRUDMODULE_qpanel';
		$table->head = array(
			get_string('itemname', 'CRUDMODULE'),
			get_string('itemtype', 'CRUDMODULE'),
			get_string('actions', 'CRUDMODULE')
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
				case MOD_CRUDMODULE_CRUD_TYPE_TEXTCHOICE:
					$itemtype = get_string('textchoice','CRUDMODULE');
					break;
				case MOD_CRUDMODULE_CRUD_TYPE_AUDIOCHOICE:
					$itemtype = get_string('audiochoice','CRUDMODULE');
					break;
				default:
			} 
			$itemtypecell = new html_table_cell($itemtype);
		
			$actionurl = '/mod/CRUDMODULE/CRUD/manageCRUDs.php';
			$editurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id));
			$editlink = html_writer::link($editurl, get_string('edititem', 'CRUDMODULE'));
			$editcell = new html_table_cell($editlink);
			
			//$previewurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id, 'action'=>'previewitem'));
			//$previewlink = html_writer::link($previewurl, get_string('previewitem', 'CRUDMODULE'));
			$previewlink = $this->fetch_preview_link($item->id,$CRUDMODULE->id);
			$previewcell = new html_table_cell($previewlink);
		
			$deleteurl = new moodle_url($actionurl, array('id'=>$cm->id,'itemid'=>$item->id,'action'=>'confirmdelete'));
			$deletelink = html_writer::link($deleteurl, get_string('deleteitem', 'CRUDMODULE'));
			$deletecell = new html_table_cell($deletelink);

			$row->cells = array(
				$itemnamecell, $itemtypecell, $editcell, $previewcell, $deletecell
			);
			$table->data[] = $row;
		}

		return html_writer::table($table);

	}

}