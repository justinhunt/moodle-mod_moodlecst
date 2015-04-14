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
 * Provides the interface for overall managing of items
 *
 * @package mod_CRUDMODULE
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../../config.php');
require_once($CFG->dirroot.'/mod/CRUD/lib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('CRUDMODULE', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//$CRUDMODULE = new CRUDMODULE($DB->get_record('CRUDMODULE', array('id' => $cm->instance), '*', MUST_EXIST));
$CRUDMODULE = $DB->get_record('CRUDMODULE', array('id' => $cm->instance), '*', MUST_EXIST);
$items = $DB->get_records(MOD_CRUDMODULE_CRUD_TABLE,array('CRUDMODULE'=>$CRUDMODULE->id));

//set mode for tabs later on
$mode='CRUDs';
//Set page url before require login, so we know where to return to, if bumped off to login
$PAGE->set_url('/mod/CRUDMODULE/CRUD/CRUDs.php', array('id'=>$cm->id,'mode'=>$mode));
require_login($course, false, $cm);

//get module context
$context = context_module::instance($cm->id);

//set up renderer and nav
$renderer = $PAGE->get_renderer('mod_CRUDMODULE');
$CRUD_renderer = $PAGE->get_renderer('mod_CRUDMODULE','CRUD');
$PAGE->navbar->add(get_string('CRUDs','CRUDMODULE'));
echo $renderer->header($CRUDMODULE, $cm, $mode, null, get_string('CRUDs', 'CRUDMODULE'));


    // Need view permission to be here
    require_capability('mod/CRUDMODULE:itemview', $context);
    
    //show edit links if can edit
    if(has_capability('mod/CRUDMODULE:itemedit', $context)){
    	echo $CRUD_renderer->add_edit_page_links($CRUDMODULE);
    }



if($items){
	echo $CRUD_renderer->show_items_list($items,$CRUDMODULE,$cm);
}
echo $renderer->footer();
