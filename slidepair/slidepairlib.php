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

define('MOD_MOODLECST_SLIDEPAIR_NONE', 0);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_PICTURECHOICE', 1);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_AUDIOCHOICE', 2);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_TABOO', 3);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_TEXTCHOICE', 4);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_TRANSLATE', 5);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_INSTRUCTIONS', 6);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_CONSENT', 7);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_WHOWHO', 8);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_CHOICE', 9);
define('MOD_MOODLECST_SLIDEPAIR_TYPE_PARTNERCONFIRM', 10);
define('MOD_MOODLECST_SLIDEPAIR_TEXTCHOICE', 'textchoice');
define('MOD_MOODLECST_SLIDEPAIR_PICTURECHOICE', 'picturechoice');
define('MOD_MOODLECST_SLIDEPAIR_TRANSLATE', 'translate');
define('MOD_MOODLECST_SLIDEPAIR_AUDIOFNAME', 'itemaudiofname');
define('MOD_MOODLECST_SLIDEPAIR_AUDIOQUESTION', 'audioitem');
define('MOD_MOODLECST_SLIDEPAIR_AUDIOANSWER', 'audioanswer');
define('MOD_MOODLECST_SLIDEPAIR_AUDIOQUESTION_FILEAREA', 'audioitem');
define('MOD_MOODLECST_SLIDEPAIR_AUDIOANSWER_FILEAREA', 'audioanswer');
define('MOD_MOODLECST_SLIDEPAIR_PICTUREQUESTION', 'pictureitem');
define('MOD_MOODLECST_SLIDEPAIR_PICTUREANSWER', 'pictureanswer');
define('MOD_MOODLECST_SLIDEPAIR_TRANSLATEQUESTION', 'translateitem');
define('MOD_MOODLECST_SLIDEPAIR_TRANSLATEANSWER', 'translateanswer');
define('MOD_MOODLECST_SLIDEPAIR_PICTUREQUESTION_FILEAREA', 'pictureitem');
define('MOD_MOODLECST_SLIDEPAIR_PICTUREANSWER_FILEAREA', 'pictureanswer');
define('MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION', 'itemtext');
define('MOD_MOODLECST_SLIDEPAIR_TEXTANSWER', 'answertext');
define('MOD_MOODLECST_SLIDEPAIR_MAXDURATIONBOUNDARIES',5);
define('MOD_MOODLECST_SLIDEPAIR_DURATIONBOUNDARY', 'timebound');
define('MOD_MOODLECST_SLIDEPAIR_DIFFICULTY', 'difficulty');
define('MOD_MOODLECST_SLIDEPAIR_BOUNDARYGRADE', 'timegrade');
define('MOD_MOODLECST_SLIDEPAIR_TEXTQUESTION_FILEAREA', 'itemarea');
define('MOD_MOODLECST_SLIDEPAIR_TEXTANSWER_FILEAREA', 'answerarea');
define('MOD_MOODLECST_SLIDEPAIR_CORRECTANSWER','correctanswer');
define('MOD_MOODLECST_SLIDEPAIR_SHUFFLEANSWERS','shuffleanswers');
define('MOD_MOODLECST_SLIDEPAIR_ANSWERSINROW','answersinrow');
define('MOD_MOODLECST_SLIDEPAIR_ANSWERWIDTH','answerwidth');
define('MOD_MOODLECST_SLIDEPAIR_MAXANSWERS',4);
define('MOD_MOODLECST_SLIDEPAIR_TABLE','moodlecst_slidepairs');

function mod_moodlecst_create_slidepairkey(){
	global $CFG;
	$prefix = $CFG->wwwroot . '@';
	return uniqid($prefix, true); 
}

function mod_moodlecst_create_sql_in($csvlist){
			$temparray = explode(',',$csvlist);
			$sql_in = '""';
			foreach($temparray as $onekey){	
				if($sql_in == '""'){
					$sql_in ='';
				}else{
					$sql_in .=',';
				} 
				$sql_in .= '"' . $onekey . '"' ;
			}
			return $sql_in;
}

function mod_moodlecst_fetch_itemscore($slidepairid, $duration, $correct){
	global $CFG,$DB;
	$ret = 0;
	$sp = $DB->get_record(MOD_MOODLECST_SLIDEPAIR_TABLE,array('id'=>$slidepairid));
	if($sp){
		$lowestboundary = 10000000000; //any stupidly high number
		for($i=1;$i<=MOD_MOODLECST_SLIDEPAIR_MAXDURATIONBOUNDARIES;$i++){
			$durationboundary = $sp->{MOD_MOODLECST_SLIDEPAIR_DURATIONBOUNDARY . $i} * 1000;
			$durationscore = $sp->{MOD_MOODLECST_SLIDEPAIR_BOUNDARYGRADE  . $i};

			//if this is not a specified condition ... continue
			if(($durationscore + $durationboundary) == 0){continue;}


			if($duration >= $durationboundary 
				&& $duration < $lowestboundary 
			  ){
					$lowestboundary = $durationboundary;
					if($durationscore > 0){
						$ret = ($durationscore / 10);
					}else{
						$ret = 0;
					}//end of if usescore > 0
			}//end of if duration
		}//end of for
	}//end of if $sp
	return $ret;
}
