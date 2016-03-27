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
 * Provides the JSON return
 *
 * @package mod_moodlecst
 * @copyright  2014 Justin Hunt  {@link http://poodll.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/moodlecst/lib.php');
require_once($CFG->dirroot.'/mod/moodlecst/slidepair/slidepairlib.php');

$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('moodlecst', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
//$moodlecst = new moodlecst($DB->get_record('moodlecst', array('id' => $cm->instance), '*', MUST_EXIST));
$moodlecst = $DB->get_record('moodlecst', array('id' => $cm->instance), '*', MUST_EXIST);

header("Access-Control-Allow-Origin: *");

//can't require login for this page. nodejs app and moodle cant share cookies . hmmmmmmmmm
//require_sesskey();
//require_login($course, false, $cm);

//get the items in the currently active session 
$items = mod_moodlecst_get_session_items($moodlecst->id);

$modulecontext = context_module::instance($cm->id);
$PAGE->set_context($modulecontext);
$jsonrenderer = $PAGE->get_renderer('mod_moodlecst','json');
echo $jsonrenderer->render_sessions_json('The CST Test',$modulecontext,$items,$moodlecst);
