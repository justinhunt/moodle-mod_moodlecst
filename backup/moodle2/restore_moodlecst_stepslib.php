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
 * @package   mod_moodlecst
 * @copyright 2014 Justin Hunt poodllsupport@gmail.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 require_once($CFG->dirroot . '/mod/moodlecst/lib.php');
 require_once($CFG->dirroot . '/mod/moodlecst/slidepair/slidepairlib.php');

/**
 * Define all the restore steps that will be used by the restore_moodlecst_activity_task
 */

/**
 * Structure step to restore one moodlecst activity
 */
class restore_moodlecst_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // are we including userinfo?

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - non-user data
        ////////////////////////////////////////////////////////////////////////

        // root element describing moodlecst instance
        $oneactivity = new restore_path_element(MOD_MOODLECST_MODNAME, '/activity/moodlecst');
        $paths[] = $oneactivity;
		
		//slidepairs
		$slidepairs = new restore_path_element(MOD_MOODLECST_SLIDEPAIR_TABLE,
                                            '/activity/moodlecst/slidepairs/slidepair');
		$paths[] = $slidepairs;

		

        // End here if no-user data has been selected
        if (!$userinfo) {
            return $this->prepare_activity_structure($paths);
        }

        ////////////////////////////////////////////////////////////////////////
        // XML interesting paths - user data
        ////////////////////////////////////////////////////////////////////////
		//attempts
		 $attempts= new restore_path_element(MOD_MOODLECST_USERTABLE,
                                            '/activity/moodlecst/attempts/attempt');
		$paths[] = $attempts;
		 
		 //items
		 $attemptitems= new restore_path_element(MOD_MOODLECST_USERTABLE,
                                            '/activity/moodlecst/attempts/attempt/items/item');
		$paths[] = $attemptitems;


        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_moodlecst($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timecreated = $this->apply_date_offset($data->timecreated);


        // insert the activity record
        $newitemid = $DB->insert_record(MOD_MOODLECST_TABLE, $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

	protected function process_moodlecst_slidepairs($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		
        $data->{MOD_MOODLECST_MODNAME} = $this->get_new_parentid(MOD_MOODLECST_MODNAME);
        $newslidepairid = $DB->insert_record(MOD_MOODLECST_SLIDEPAIR_TABLE, $data);
       $this->set_mapping(MOD_MOODLECST_SLIDEPAIR_TABLE, $oldid, $newslidepairid, true); // Mapping with files
  }
  
  protected function process_moodlecst_sessions($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		
        $data->{MOD_MOODLECST_MODNAME} = $this->get_new_parentid(MOD_MOODLECST_MODNAME);
        $newsessionid = $DB->insert_record(MOD_MOODLECST_SESSION_TABLE, $data);
       $this->set_mapping(MOD_MOODLECST_SESSION_TABLE, $oldid, $newsessionid, true); // Mapping with files ..dont need this
  }
	
	protected function process_moodlecst_attempts($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		
        $data->{MOD_MOODLECST_MODNAME . 'id'} = $this->get_new_parentid(MOD_MOODLECST_MODNAME);
        $newattemptid = $DB->insert_record(MOD_MOODLECST_ATTEMPTTABLE, $data);
       $this->set_mapping(MOD_MOODLECST_ATTEMPTTABLE, $oldid, $newattemptid, false); // Mapping without files
    }
	
	protected function process_moodlecst_items($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->timecreated = $this->apply_date_offset($data->timecreated);

		$data->{'moodlecstid'} = $this->get_new_parentid(MOD_MOODLECST_MODNAME);
        $data->{'attemptid'} = $this->get_new_parentid(MOD_MOODLECST_ATTEMPTTABLE);
        $newitemid = $DB->insert_record(MOD_MOODLECST_ATTEMPTITEMTABLE, $data);
       $this->set_mapping(MOD_MOODLECST_ATTEMPTITEMTABLE, $oldid, $newitemid, false); // Mapping without files
    }
	
    protected function after_execute() {

        // Add module related files, no need to match by itemname (just internally handled context)
        $this->add_related_files(MOD_MOODLECST_FRANKY, 'intro', null);

		//do question areas
		$this->add_related_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION_FILEAREA, MOD_MOODLECST_SLIDEPAIR_TABLE);
		$this->add_related_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_AUDIOQUESTION_FILEAREA, MOD_MOODLECST_SLIDEPAIR_TABLE);
		$this->add_related_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_PICTUREQUESTION_FILEAREA, MOD_MOODLECST_SLIDEPAIR_TABLE);

		//do answer areas 
		for($i=1;$i<=MOD_MOODLECST_SLIDEPAIR_MAXANSWERS;$i++){
			$this->add_related_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_TEXTANSWER_FILEAREA.$i, MOD_MOODLECST_SLIDEPAIR_TABLE);
			$this->add_related_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_AUDIOANSWER_FILEAREA.$i, MOD_MOODLECST_SLIDEPAIR_TABLE);
			$this->add_related_files(MOD_MOODLECST_FRANKY, MOD_MOODLECST_SLIDEPAIR_PICTUREANSWER_FILEAREA.$i, MOD_MOODLECST_SLIDEPAIR_TABLE);

		}
    }
}
