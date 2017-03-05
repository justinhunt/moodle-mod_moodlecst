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

        $currentability = 0;
        $abilitydata = self::process_answer($items, $responsedata, $currentability);
       // error_log(print_r($abilitydata,true));

        $letsfinish = self::check_ending_condition($moodlecst,$responsedata, $abilitydata);
        if ($letsfinish) {
            $next_task_id=0;
            error_log("finishing");
        }else{
            $next_task_id = self::get_next_item($moodlecst, $items, $responsedata, $abilitydata);
            error_log("next item:" . $next_task_id);
        }
        $ret->next_task_id=$next_task_id;
        return $ret;

        /*


        //roughly incremental method
        foreach($questiondata as $taskid){
            if($taskid >$currenttaskid){
                $ret->next_task_id=$taskid;
                break;
            }
        }
       // $ret->next_task_id=3;//$questiondata[0];
       // error_log(print_r($questiondata,true));
       error_log(print_r($responsedata,true));
        return $ret;
        */
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
                if ($abilitydata->se < $moodlecst->ucatse) {
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


    //this is where we set the students ability and the margin flag (abilright) and standard error
    //its calculated each time by what we now know about their ability
    //there is a problem here because the currentability is not kept recorded and always starts at 0.
    //The original algorithm started with the most recent known ability
    public static function process_answer($items,$answers,$currentability) {
        global $DB;

        $ret = new \stdClass();
        $ret->se=0;
        $ret->ability=50; //set this to zero if diff range is -200 - 200
        $ret->abilright=0;

        $pexp = 0;
        $pvar = 0;
        $presult = 0;

        //reset answer indexes
        $answers = array_values($answers);


        //NB does answers include consent etc??
        for ($p = 0; $p < count($answers); $p++) {
            $answer = $answers[$p];
            $item = $items[$answer->slidepairid];

            $success = 1 / (1 + exp(($item->difficulty) - $currentability));
            $pexp += $success;
            $pvar += $success * (1 - $success);

            //old value
            //$presult += $attempt->get_mark();
            //need to set correct value, it will be empty currently
            $correct = $answer->answerid == $item->correctanswer;
           // error_log('ANSWER::' . print_r($answer,true));
            $presult += mod_moodlecst_fetch_itemscore($answer->slidepairid,
                $answer->duration,
                $correct);
        }
        if ($pvar != 0) {
            $ret->se = sqrt(1 / $pvar);
        }
        if ($pvar < 1) {
            $pvar = 1;
        }

        $ret->ability += ($presult - $pexp) / $pvar;
        $ret->abilright = $ret->ability + (1 / $pvar);
        return $ret;

    }

    public static function get_next_item($moodlecst,$items, $answers, $abilitydata) {

        global $DB;
        /*
        //Adding questions to pool of questions already in session
        // so add a where to check if we already have taken a question

        $where = '';
        if ($this->questions) {
            $where = ' AND q.id NOT IN ('.implode(',', $this->questions).')';
        }

        //This UCAT mod appends functions via existing questionbank
        //here we check the q categories that have been flagged to include
        $qcats = implode(',', question_categorylist($this->ucat->questioncategory));

        //we collect all the question ids/difficulties for questions flagged in categories flagged, that we do not already have
        $qdiffs = $DB->get_records_sql('
                SELECT q.id, uq.difficulty
                    FROM {question} q
                        LEFT JOIN {ucat_questions} uq ON q.id = uq.questionid
                    WHERE q.category in ('.$qcats.')'.$where
        );
*/


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

        $accuracy = 0.7;
        $qselect = 0;

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

        //It seems to always choose a higher difficulty level, and this probably works because the ability is downgraded
        //on a wrong answer
/*
        $this->questions[] = $items[$qselect]->id;
        $this->session->questions = serialize($this->questions);
        $this->update();
        $this->currentquestion = $items[$qselect]->id;
        $quba = question_engine::load_questions_usage_by_activity($this->questionsusage);
        $questions = question_load_questions(array($this->currentquestion));
        $qobj = question_bank::make_question(reset($questions));
        $slot = $quba->add_question($qobj);
        $quba->start_question($slot);
        question_engine::save_questions_usage_by_activity($quba);
        $this->session->slot = $slot;
        $this->session->status = self::STATUS_ASKED;
        $this->update();
  */
     //   error_log('QSELECT::::' . $qselect);
        return $items[$qselect]->id;
    }

}
