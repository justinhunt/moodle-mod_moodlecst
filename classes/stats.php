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
 * Stats or use in Moodle CST
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodlecst;

defined('MOODLE_INTERNAL') || die();



/**
 * Stats for mod_moodlecst
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stats{
    //population based standard dev
    function std_dev_pop($arr)
    {
        $arr_size = count($arr);
        $mu = array_sum ($arr) / $arr_size;
        $ans = 0;
        foreach ($arr as $elem) {
            $ans += pow(($elem - $mu), 2);
        }

        return sqrt($ans / $arr_size);
    }

// Function to calculate square of value - mean
    function sd_square($x, $mean) { return pow($x - $mean,2); }

// sample based standard dev
    function std_dev_sample($array) {
        // square root of sum of squares devided by N-1
        return sqrt(array_sum(array_map([$this, 'sd_square'], $array, array_fill(0,count($array), (array_sum($array) / count($array)) ) ) ) / (count($array)-1) );
    }

    //standard error from dev
    function std_error($std_dev, $count){
        return $std_dev / sqrt($count);
    }

}
