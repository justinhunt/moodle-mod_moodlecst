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
 * Adaptive handler for moodlecst plugin
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodlecst;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/mod/moodlecst/lib.php');
require_once($CFG->dirroot .'/mod/moodlecst/slidepair/slidepairlib.php');


/**
 * Event observer for mod_moodlecst
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ucat{

    const ENDCOND_ALL           = 0;
    const ENDCOND_NUMQUEST      = 1;
    const ENDCOND_SE            = 2;
    const ENDCOND_NUMQUESTANDSE = 3;
    const DUMMY_SE = 100;
    const ESTIMATE_SIMPLE =0;
    const ESTIMATE_COMPLEX =1;

    /**
     * Implements adaptive feature by returning the Task ID of next question
     *
     * @param \core\event\course_deleted $event
     * @return bool true on success
     */
    public static function fetch_next( $moodlecst, $responsedata, $questiondata, $currenttaskid)
    {
        global $DB;

        $ret = new \stdClass();
        $ret->next_task_id=0;

        //error_log('RESPONSE::' . print_r($responsedata,true));


        $questioncsv = implode(',', $questiondata);
       // $slidepair_SQL_IN = mod_moodlecst_create_sql_in($questioncsv);
        $items = $DB->get_records_select(MOD_MOODLECST_SLIDEPAIR_TABLE,
            'id IN (' . $questioncsv. ')', array(), 'moodlecst, id ASC');


        $abilitydata = self::process_answer($items, $responsedata,$moodlecst->estimatemethod);
         error_log(print_r($abilitydata,true));

        $letsfinish = self::check_ending_condition($moodlecst,$responsedata, $abilitydata);
        if ($letsfinish) {
            $next_task_id=0;
         //   error_log("finishing");
        }else{
            $next_task_id = self::get_next_item($moodlecst, $items, $responsedata, $abilitydata);
          //  error_log("next item:" . $next_task_id);
        }
        $ret->next_task_id=$next_task_id;
        return $ret;


    }

    /**
     * @return bool
     */
    public static function check_ending_condition($moodlecst,$responsedata, $abilitydata) {
        global $DB;

        switch ($moodlecst->ucatendcondition) {
            case ucat::ENDCOND_NUMQUEST:
                if (count($responsedata) >= $moodlecst->ucatreqitems) {
                    return true;
                }
                return false;

            case ucat::ENDCOND_SE:
                if (count($responsedata) > 1 && ($abilitydata->se < $moodlecst->ucatse)) {
                    return true;
                }
                return false;

            case ucat::ENDCOND_NUMQUESTANDSE:
                if (count($responsedata) > $moodlecst->ucatreqitems && $abilitydata->se < $moodlecst->ucatse) {
                    return true;
                }
                return false;

            case ucat::ENDCOND_ALL:
                return false;
        }
    }



    public static function estimateAbilitySimple($oldability, $difficulty,$correct) {
        $newability=$oldability;
        if($correct){
            if($oldability < $difficulty){
                $newability = $difficulty;
            }
        }else{
            if($oldability > $difficulty){
                $newability = $difficulty;
            }
        }
        return $newability;
    }
    public static function fetchProbOfSuccess($oldability, $difficulty) {
        $prob_of_success = 1 / (1 + exp($difficulty - $oldability));
        return $prob_of_success;
    }

    public static function estimateAbilityComplex($oldability,$probsum, $correctsum) {
        $residual=$correctsum - $probsum;
        $newability=$oldability +$residual;
        return $newability;
    }


    //this is where we set the students ability and the margin flag (abilright) and standard error
    //its calculated each time by what we now know about their ability
    //there is a problem here because the currentability is not kept recorded and always starts at 0.
    //The original algorithm started with the most recent known ability
    public static function process_answer($items,$answers,$estimatemethod)
    {

        $ret = new \stdClass();
        $answercount = count($answers);

        //simple ability estimates
        $simple_current_ability = 50; //set this to zero if diff range is -200 - 200
        $simple_abilities = array();


        //complex ability estimates
        $complex_current_ability=50; //set this to zero if diff range is -200 - 200
        $probsum=0; //sum of the probability for success of each item based on immediate previous ability estimate
        $probvariancesum=0;//sum this calc ...$probOfSuccess * (1 - $probOfSuccess) .. some sort of margin for determining next item difficulty
        $correctsum=0; //sum of corret answers

        for ($p = 0; $p < $answercount; $p++) {
            $answer = $answers[$p];
            $item = $items[$answer->slidepairid];
            $correct = $answer->answerid == $item->correctanswer;

            //estimation method SIMPLE
            $simple_current_ability=self::estimateAbilitySimple($simple_current_ability,$item->difficulty,$correct);
            $simple_abilities[] = $simple_current_ability;


            //estimation COMPLEX
            $probOfSuccess=self::fetchProbOfSuccess($complex_current_ability,$item->difficulty);
            $probsum+=$probOfSuccess;
            $correctsum+=$correct;
            $probvariancesum+= $probOfSuccess * (1 - $probOfSuccess);
            $complex_current_ability=self::estimateAbilityComplex($complex_current_ability,$probsum,$correctsum);
          //  error_log($complex_current_ability . '@@' . $probsum. '@@' . $correctsum);
            $complex_abilities[]=$complex_current_ability;
        }

        //get stats functions
        $stats = new stats();

        if($answercount>1) {
            switch($estimatemethod){
                case UCAT::ESTIMATE_COMPLEX :
                    $sd = $stats->std_dev_sample($complex_abilities);
                    break;
                case UCAT::ESTIMATE_SIMPLE:
                default:
                    $sd = $stats->std_dev_sample($simple_abilities);
            }
            $se = $stats->std_error($sd, $answercount);
        }else{
            $se=0;
            $sd=0;
        }
       // error_log('SD:' . $sd . '@@' . 'SE:' . $se);

        if($estimatemethod == UCAT::ESTIMATE_COMPLEX ){
            $ret->se=$se;
            $ret->ability = $complex_current_ability;
            if($probvariancesum <1){$probvariancesum=1;}
            $ret->abilright = $complex_current_ability + (1 / $probvariancesum);
        }else{
            $ret->se=$se;
            $ret->ability = $simple_current_ability;
            $ret->abilright = $simple_current_ability+1;
        }

        return $ret;

    }

    public static function get_next_item($moodlecst,$items, $answers, $abilitydata) {


        //get an array of items not yet attempted
        foreach($answers as $answer){
            if(array_key_exists($answer->slidepairid,$items)){
                unset($items[$answer->slidepairid]);
            }

        }
        //reset the array index
        $items = array_values($items);
        //count the array
        $qcount = count($items);

        //if no questions we just return
        if ($qcount == 0) {
            return 0;
        }

        //set the bias: =WHAT?
        $bias = 0;
        if (!empty($moodlecst->ucatlogitbias)) {
            $bias = $moodlecst->ucatlogitbias;
        }

        //determine (sessionability + session abilright /2)
        //session ability is current ability level of curent user
        //n = a random number between 0 and qcount-1
        $n = mt_rand(0, $qcount - 1);
        $abilhalf = ($abilitydata->abilright + $abilitydata->ability) * 0.5;

        //loop the total number of questions(eg qcount =15, 15 times)
        //but start from n, so we will probably go over the qcount
        $qselect = 0;
        $qhold=0;
        for ($qq = $n + 1; $qq <= $qcount + $n; $qq++) {
            // a randomisation technique that ensures all questions are eligible
            // this results in q starting from somewhere in the question list
            // and looping upwards from that point, wrapping to arrive at the prior point
            if ($qq >= $qcount) {
                $q = $qq - $qcount;
            } else {
                $q = $qq;
            }
            //If the difficulty of q is unset, set it to 5 (why?)
            //else use the difficulty as set
            if (is_null($items[$q]->difficulty)) {
                $i = 5;
            } else {
                $i = $items[$q]->difficulty;
            }
            //if the difficulty is greater than the ability AND less than abilright
            //then select
            if ($i >= $abilitydata->ability + $bias && $i <= $abilitydata->abilright + $bias) {
                $qselect = $q;
                break;// $items[$qselect];
            }
            //if (not selected(first pass) OR diff closer than prev closest diff) then select current q and reset pre most diff
            if ($qselect == 0 || abs($i - $abilhalf) < $qhold) {
                $qselect = $q;
                $qhold = abs($i - $abilhalf);
            }
        }

        return $items[$qselect]->id;
    }

}
