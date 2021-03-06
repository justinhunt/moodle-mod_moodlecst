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
 * Internal library of functions for module moodlecst
 *
 * All the moodlecst specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class nodejshelper{ 

const NODEPID = 'nodepid';
const NODELOG = 'nodeout';
const NODE_ACTION_NONE = 0;
const NODE_ACTION_START = 1;
const NODE_ACTION_STOP = 2;
const NODE_ACTION_FORCERESTART = 3;

	/**
	 * Start the Node JS server
	 *
	 */
public static function start_server($app_path) {
		$config = get_config(MOD_MOODLECST_FRANKY);
		$temppath = self::get_temp_path();
		$nodepidfilepath = $temppath . '/' . self::NODEPID;
		$nodelogfilepath = $temppath . '/' . self::NODELOG;
		
		if(file_exists($nodepidfilepath)){
			$node_pid = intval(file_get_contents($nodepidfilepath));
			if($node_pid > 0) {
				return false;
			}
		}
		$file = escapeshellarg($app_path);
		$node_pid = exec($config->nodejsexecpath . " $file >$nodelogfilepath 2>&1 & echo $!");
		$started= $node_pid > 0;
		file_put_contents($nodepidfilepath, $node_pid, LOCK_EX);
		return true;
	}
	
	/**
	 * Get the temp dir for nodepid and nodeout
	 *
	 */
	static function get_temp_path(){
		global $CFG;
		$config = get_config(MOD_MOODLECST_FRANKY);
		$temppath =  $config->nodejstemppath;
		if(strpos($temppath,'/')!==0){
			$temppath = $CFG->dirroot . '/mod/moodlecst/' . $temppath;
		}
		//if temp path does not exist, lets create it.
        if (!file_exists($temppath)) {
            mkdir($temppath, 0755, true);
        }

		return $temppath;
	}

	/**
	 * Start the Node JS server
	 *
	 */
public static function force_restart_server($app_path) {
		$temppath = self::get_temp_path();
		$nodepidfilepath = $temppath . '/' . self::NODEPID;
		$nodelogfilepath = $temppath . '/' . self::NODELOG;

		self::stop_server();
		file_put_contents($nodepidfilepath, '', LOCK_EX);
		file_put_contents($nodelogfilepath, '', LOCK_EX);
		self::start_server($app_path);
		return true;
	}

	/**
	 * Stop the Node JS server
	 *
	 */
public static function stop_server() {
		$temppath = self::get_temp_path();
		$nodepidfilepath = $temppath . '/' . self::NODEPID;
		$nodelogfilepath = $temppath . '/' . self::NODELOG;
		if(file_exists($nodepidfilepath)){
			$node_pid = intval(file_get_contents($nodepidfilepath));
			if($node_pid === 0) {
				return false;
			}
		}else{
			return false;
		}
		$ret = -1;
		passthru("kill $node_pid", $ret);
		$stopped =  $ret === 0;
		file_put_contents($nodepidfilepath, '', LOCK_EX);
		file_put_contents($nodelogfilepath, '', LOCK_EX);
		return $stopped;
	}
	
	/**
	 * Fetch Node JS server log
	 *
	 */
public static function fetch_log() {
		$temppath = self::get_temp_path();
		$nodelogfilepath = $temppath . '/' . self::NODELOG;
		if(file_exists($nodelogfilepath)){
			$node_log = file_get_contents($nodelogfilepath);
			return $node_log;
		}else{
			return '';
		}
	}

	/**
	 * Is the Node JS server running
	 *
	 */
 public static function is_server_running() {
 		$temppath = self::get_temp_path();
		$nodepidfilepath = $temppath . '/' . self::NODEPID;
		$nodelogfilepath = $temppath . '/' . self::NODELOG;
		if(file_exists($nodepidfilepath)){
			$node_pid = intval(file_get_contents($nodepidfilepath));
			return ($node_pid !== 0);
		}else{
			return false;
		}
	}

}