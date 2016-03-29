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
 * The mod_page course module viewed event.
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodlecst\task;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/moodlecst/lib.php');

/**
 * The mod_moodlecst course module viewed event class.
 *
 * @package    mod_moodlecst
 * @since      Moodle 2.7
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodlecst_scheduled extends \core\task\scheduled_task {    
		
	public function get_name() {
        // Shown in admin screens
        return get_string('moodlecsttask', MOD_MOODLECST_LANG);
    }
	
	 /**
     *  Run all the tasks
     */
	 public function execute(){
		$trace = new \text_progress_trace();
        $moodlecst->mod_moodlecst_dotask($trace);
	}

}

