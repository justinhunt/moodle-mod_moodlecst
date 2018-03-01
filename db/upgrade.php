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
 * This file keeps track of upgrades to the moodlecst module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/moodlecst/slidepair/slidepairlib.php');

/**
 * Execute moodlecst upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_moodlecst_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // And upgrade begins here. For each one, you'll need one
    // block of code similar to the next one. Please, delete
    // this comment lines once this file start handling proper
    // upgrade code.

    // if ($oldversion < YYYYMMDD00) { //New version in version.php
    //
    // }

    // Lines below (this included)  MUST BE DELETED once you get the first version
    // of your module ready to be installed. They are here only
    // for demonstrative purposes and to show how the moodlecst
    // iself has been upgraded.

    // For each upgrade block, the file moodlecst/version.php
    // needs to be updated . Such change allows Moodle to know
    // that this file has to be processed.

    // To know more about how to write correct DB upgrade scripts it's
    // highly recommended to read information available at:
    //   http://docs.moodle.org/en/Development:XMLDB_Documentation
    // and to play with the XMLDB Editor (in the admin menu) and its
    // PHP generation posibilities.

    // First example, some fields were added to install.xml on 2007/04/01
    if ($oldversion < 2007040100) {

        // Define field course to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $field = new xmldb_field('course', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'id');

        // Add field course
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field intro to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'medium', null, null, null, null,'name');

        // Add field intro
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field introformat to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'intro');

        // Add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Once we reach this point, we can store the new version and consider the module
        // upgraded to the version 2007040100 so the next time this block is skipped
        upgrade_mod_savepoint(true, 2007040100, 'moodlecst');
    }

    // Second example, some hours later, the same day 2007/04/01
    // two more fields and one index were added to install.xml (note the micro increment
    // "01" in the last two digits of the version
    if ($oldversion < 2007040101) {

        // Define field timecreated to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'introformat');

        // Add field timecreated
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field timemodified to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0',
            'timecreated');

        // Add field timemodified
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index course (not unique) to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $index = new xmldb_index('courseindex', XMLDB_INDEX_NOTUNIQUE, array('course'));

        // Add index to course field
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Another save point reached
        upgrade_mod_savepoint(true, 2007040101, 'moodlecst');
    }

	
	//added partnermode 
    if ($oldversion < 2015051701) {

        // Define field partnermode to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $field = new xmldb_field('partnermode', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field partnermode
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		 upgrade_mod_savepoint(true, 2015051701, 'moodlecst');
	}
	
	//added sessionsise
    if ($oldversion < 2015051801) {

        // Define field sessionsize to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $field = new xmldb_field('sessionsize', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field partnermode
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		 upgrade_mod_savepoint(true, 2015051801, 'moodlecst');
	}
	//added selectsession 
    if ($oldversion < 2015052001) {

        // Define field sessionsize to be added to moodlecst
        $table = new xmldb_table('moodlecst');
        $field = new xmldb_field('selectsession', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Add field partnermode
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		 upgrade_mod_savepoint(true, 2015052001, 'moodlecst');
	}
	
	//added partnerid and totaltime
    if ($oldversion < 2015052901) {

        // Define field sessionsize to be added to moodlecst
        $table = new xmldb_table('moodlecst_attempt');
        

        // Add field partnerid
        $field = new xmldb_field('partnerid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		// Add field totaltime
		$field = new xmldb_field('totaltime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		 upgrade_mod_savepoint(true, 2015052901, 'moodlecst');
	}
	
	//added tags
    if ($oldversion < 2015053001) {

        // Define field sessionsize to be added to moodlecst
        $table = new xmldb_table('moodlecst_slidepairs');
        

        // Add field tags
        $field = new xmldb_field('tags', XMLDB_TYPE_TEXT, 'medium', null, null, null, null);
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

		 upgrade_mod_savepoint(true, 2015053001, 'moodlecst');
	}
	
	//added time target
    if ($oldversion < 2015053002) {

        // Define field sessionsize to be added to moodlecst
        $table = new xmldb_table('moodlecst_slidepairs');
        
        // Add field tags
        $field = new xmldb_field('timetarget', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
		
		// Define field sessionsize to be added to moodlecst
        $table = new xmldb_table('moodlecst');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

		 upgrade_mod_savepoint(true, 2015053002, 'moodlecst');
	}
	
	if($oldversion < 2016032603){
		
		// Get moodle cst table
        $table = new xmldb_table('moodlecst');
		
		//add grade field
		$field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Get moodle cst table
        $table = new xmldb_table('moodlecst_sessions');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('moodlecst', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'course');
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'moodlecst');
        $table->add_field('type', XMLDB_TYPE_INTEGER, '9', null, XMLDB_NOTNULL, null, null, 'name');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null, 'type');
        $table->add_field('displayorder', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, '0', 'active');
        $table->add_field('slidepairkeys', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null, 'displayorder');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'slidepairkeys');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '18', null, XMLDB_NOTNULL, null, null, 'timecreated');
		//add primary key
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        
         // Conditionally launch create table for quiz_sections.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

		// Get moodle cst table
        $table = new xmldb_table('moodlecst_slidepairs');        
        $newfields = array();
        $newfields[] = new xmldb_field('difficulty', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timebound1', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timegrade1', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timebound2', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timegrade2', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timebound3', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timegrade3', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timebound4', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timegrade4', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timebound5', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('timegrade5', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		$newfields[] = new xmldb_field('slidepairkey', XMLDB_TYPE_TEXT, null, null, null, null, null);
		foreach($newfields as $newfield){
			if (!$dbman->field_exists($table, $newfield)) {
				$dbman->add_field($table, $newfield);
			}
        }
        
        //add a slidepairkey to all records
        $records = $DB->get_records_select(MOD_MOODLECST_SLIDEPAIR_TABLE,
        	$DB->sql_compare_text('slidepairkey') ." = ''");
		foreach($records as $record){
			$DB->set_field(MOD_MOODLECST_SLIDEPAIR_TABLE,'slidepairkey',
				mod_moodlecst_create_slidepairkey(),array('id'=>$record->id));
		}
		
		//add max grade at the 0 boundary to each slidepair
		$DB->set_field(MOD_MOODLECST_SLIDEPAIR_TABLE,'timegrade1',
				100,array());
        
        upgrade_mod_savepoint(true, 2016032603, 'moodlecst');
	}
	
	if($oldversion < 2016092103){
		$records = $DB->get_records(MOD_MOODLECST_SLIDEPAIR_TABLE);
		foreach($records as $record){
			
			//update time grades
			if($record->timegrade1 > 0){
				$record->timegrade1 = $record->timegrade1 / 10;
			}
			if($record->timegrade2 > 0){
				$record->timegrade2 = $record->timegrade2 / 10;
			}
			if($record->timegrade3 > 0){
				$record->timegrade3 = $record->timegrade3 / 10;
			}
			if($record->timegrade4 > 0){
				$record->timegrade4 = $record->timegrade4 / 10;
			}
			if($record->timegrade5 > 0){
				$record->timegrade5 = $record->timegrade5 / 10;
			}
			
			//update time bounds
			$record->timebound1 = $record->timebound2;
			$record->timebound2 = $record->timebound3;
			$record->timebound3 = $record->timebound4;
			$record->timebound4 = $record->timebound5;
			$record->timebound5 = 0;

			//update record
			$DB->update_record(MOD_MOODLECST_SLIDEPAIR_TABLE,$record);								 
		}
		upgrade_mod_savepoint(true, 2016092103, 'moodlecst');	
	}
	
	if($oldversion < 2016092104){
	
	// Get moodle cst table
        $table = new xmldb_table('moodlecst_attemptitem');
		
		//add grade field
		$field = new xmldb_field('points', 
			XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
		if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
	
		upgrade_mod_savepoint(true, 2016092104, 'moodlecst');	
	}

	//adding UCAT mode
    if($oldversion < 2017022400){

        // Get moodle cst table
        $table = new xmldb_table('moodlecst');

        $newfields = array();
        $newfields[] = new xmldb_field('ucatenabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('ucatendcondition', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('ucatreqitems', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('ucatse', XMLDB_TYPE_NUMBER, '7', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('ucatlogitbias', XMLDB_TYPE_NUMBER, '7', null, XMLDB_NOTNULL, null, '0');

        foreach($newfields as $newfield){
            if (!$dbman->field_exists($table, $newfield)) {
                $dbman->add_field($table, $newfield);
            }
        }
        upgrade_mod_savepoint(true, 2017022400, 'moodlecst');
    }

    //adding some UCAT fields to the DB
    if($oldversion < 2017022800){

        // Add ucat info to attempt table
        $table = new xmldb_table('moodlecst_attempt');

        $newfields = array();
        $newfields[] = new xmldb_field('ucatenabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $newfields[] = new xmldb_field('ability', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        foreach($newfields as $newfield){
            if (!$dbman->field_exists($table, $newfield)) {
                $dbman->add_field($table, $newfield);
            }
        }

        //add ucat info to attempt item table
        $table = new xmldb_table('moodlecst_attemptitem');
        $newfield= new xmldb_field('difficulty', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $newfield)) {
            $dbman->add_field($table, $newfield);
        }


        upgrade_mod_savepoint(true, 2017022800, 'moodlecst');
    }

    //adding some UCAT fields to the DB
    if($oldversion < 2018022800){

        // Add ucat info to attempt table
        $table = new xmldb_table('moodlecst');

        $newfields = array();
        $newfields[] = new xmldb_field('estimatemethod', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');

        foreach($newfields as $newfield){
            if (!$dbman->field_exists($table, $newfield)) {
                $dbman->add_field($table, $newfield);
            }
        }
        upgrade_mod_savepoint(true, 2018022800, 'moodlecst');
    }

    return true;
}
