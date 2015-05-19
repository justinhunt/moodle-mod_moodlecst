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
 * Defines all the backup steps that will be used by {@link backup_moodlecst_activity_task}
 *
 * @package     mod_moodlecst
 * @category    backup
 * @copyright   moodlecst
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/moodlecst/lib.php');
 require_once($CFG->dirroot . '/mod/moodlecst/slidepair/slidepairlib.php');

/**
 * Defines the complete webquest structure for backup, with file and id annotations
 *
 */
class backup_moodlecst_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the structure of the moodlecst element inside the webquest.xml file
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // are we including userinfo?
        $userinfo = $this->get_setting_value('userinfo');

        ////////////////////////////////////////////////////////////////////////
        // XML nodes declaration - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing moodlecst instance
        $oneactivity = new backup_nested_element(MOD_MOODLECST_MODNAME, array('id'), array(
            'course','name','intro','introformat','someinstancesetting','gradeoptions','maxattempts','mingrade',
			'mode','partnermode','sessionsize','timecreated','timemodified'
			));
			
		// slidepair	
		$slidepairs = new backup_nested_element('slidepairs');
		$slidepair = new backup_nested_element('slidepair', array('id'),array(
			MOD_MOODLECST_MODNAME, 'name', 'type','visible','itemtext', 'itemformat','itemaudiofname', 'answertext1', 'answertext1format','answertext2', 'answertext2format','answertext3', 'answertext3format','answertext4', 'answertext4format',
		  'correctanswer','shuffleanswers','answercount','answersinrow','answerwidth','createdby','modifiedby','timecreated','timemodified'
		));
		
		//attempts
        $attempts = new backup_nested_element('attempts');
        $attempt = new backup_nested_element('attempt', array('id'),array(
			MOD_MOODLECST_MODNAME ."id","course","userid","status","mode","sessionscore","timecreated","timemodified"
		));
		
		//items
        $items = new backup_nested_element('items');
        $item = new backup_nested_element('item', array('id'),array(
			MOD_MOODLECST_MODNAME ."id","course","userid","attemptid","partnerid","slidepairid","sessionid","consent","correct","duration","timecreated","timemodified"
		));

		
		// Build the tree.
		$oneactivity->add_child($slidepairs);
		$slidepairs->add_child($slidepair);
        $oneactivity->add_child($attempts);
        $attempts->add_child($attempt);
		$oneactivity->add_child($items);
		$items->add_child($item);
		


        // Define sources.
        $oneactivity->set_source_table(MOD_MOODLECST_TABLE, array('id' => backup::VAR_ACTIVITYID));
		$slidepair->set_source_table(MOD_MOODLECST_SLIDEPAIR_TABLE,
                                        array(MOD_MOODLECST_MODNAME => backup::VAR_PARENTID));

        //sources if including user info
        if ($userinfo) {
			$attempt->set_source_table(MOD_MOODLECST_ATTEMPTTABLE,
											array(MOD_MOODLECST_MODNAME . 'id' => backup::VAR_PARENTID));
			$item->set_source_table(MOD_MOODLECST_ATTEMPTITEMTABLE,
											array('attemptid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $attempt->annotate_ids('user', 'userid');
        $item->annotate_ids('user', 'userid');


        // Define file annotations.
        // intro file area has 0 itemid.
        $oneactivity->annotate_files(MOD_MOODLECST_FRANKY, 'intro', null);
		
		//other file areas use moodlecstid
		$slidepair->annotate_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION_FILEAREA, 'id');
		$slidepair->annotate_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_PICTUREQUESTION_FILEAREA, 'id');
		$slidepair->annotate_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_AUDIOQUESTION_FILEAREA, 'id');
		for($i=1;$i<=MOD_MOODLECST_SLIDEPAIR_MAXANSWERS;$i++){
			$slidepair->annotate_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_TEXTANSWER_FILEAREA.$i, 'id');
			$slidepair->annotate_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_PICTUREANSWER_FILEAREA.$i, 'id');
			$slidepair->annotate_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_AUDIOANSWER_FILEAREA.$i, 'id');
		}

        // Return the root element (choice), wrapped into standard activity structure.
        return $this->prepare_activity_structure($oneactivity);
		

    }
}
