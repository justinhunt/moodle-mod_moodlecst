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
 * The main moodlecst configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/moodlecst/lib.php');

/**
 * Module instance settings form
 */
class mod_moodlecst_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
    	global $CFG;

        $mform = $this->_form;

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('moodlecstname', MOD_MOODLECST_LANG), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'moodlecstname', MOD_MOODLECST_LANG);

        // Adding the standard "intro" and "introformat" fields
        if($CFG->version < 2015051100){
        	$this->add_intro_editor();
        }else{
        	$this->standard_intro_elements();
		}
		
		//mode options
        $modeoptions = array(MOD_MOODLECST_MODETEACHERSTUDENT => get_string('teacherstudent',MOD_MOODLECST_LANG),
                            MOD_MOODLECST_MODESTUDENTSTUDENT => get_string('studentstudent', MOD_MOODLECST_LANG));
        $mform->addElement('select', 'mode', get_string('modeoptions', MOD_MOODLECST_LANG), $modeoptions);
		
		//Partner Mode
		$partnermodeoptions = array(MOD_MOODLECST_PARTNERMODEMANUAL => get_string('manualpartners', MOD_MOODLECST_LANG),
								MOD_MOODLECST_PARTNERMODEAUTO => get_string('autopartners',MOD_MOODLECST_LANG)
								);
        $mform->addElement('select', 'partnermode', get_string('partnermode', MOD_MOODLECST_LANG), $partnermodeoptions);
		
		
		//attempts
		/*
        $attemptoptions = array(0 => get_string('unlimited', MOD_MOODLECST_LANG),
                            1 => '1',2 => '2',3 => '3',4 => '4',5 => '5',);
        $mform->addElement('select', 'maxattempts', get_string('maxattempts', MOD_MOODLECST_LANG), $attemptoptions);
        */
		$mform->addElement('hidden', 'maxattempts');
        $mform->setType('maxattempts', PARAM_INT);
		$mform->setDefault('maxattempts', 0);
		
		//to use sessions or not
		 $mform->addElement('selectyesno', 'selectsession', get_string('selectsession',MOD_MOODLECST_LANG));
		 $mform->setType('selectsession', PARAM_INT);
		 $mform->setDefault('selectsession', 0);
		
		//sessionsize
		 $mform->addElement('text', 'sessionsize', get_string('sessionsize', MOD_MOODLECST_LANG), array('size'=>'32'));
		 $mform->setType('sessionsize', PARAM_INT);
		 $mform->setDefault('sessionsize', 0);
		 //$mform->disabledIf('sessionsize','selectsession','eq',0);
		 
		//time target options
        $timetargetoptions = array(MOD_MOODLECST_TIMETARGET_IGNORE => get_string('timetargetignore',MOD_MOODLECST_LANG),
                            MOD_MOODLECST_TIMETARGET_SHOW => get_string('timetargetshow', MOD_MOODLECST_LANG),
                            MOD_MOODLECST_TIMETARGET_FORCE => get_string('timetargetforce', MOD_MOODLECST_LANG));
        $mform->addElement('select', 'timetarget', get_string('timetarget', MOD_MOODLECST_LANG), $timetargetoptions);
		$mform->setDefault('timetarget', MOD_MOODLECST_TIMETARGET_IGNORE);
		 
		
		
		// Grade.
        $this->standard_grading_coursemodule_elements();
		
		
        //grade options
        $gradeoptions = array(MOD_MOODLECST_GRADEHIGHEST => get_string('gradehighest',MOD_MOODLECST_LANG),
                            MOD_MOODLECST_GRADELOWEST => get_string('gradelowest', MOD_MOODLECST_LANG),
                            MOD_MOODLECST_GRADELATEST => get_string('gradelatest', MOD_MOODLECST_LANG),
                            MOD_MOODLECST_GRADEAVERAGE => get_string('gradeaverage', MOD_MOODLECST_LANG),
							MOD_MOODLECST_GRADENONE => get_string('gradenone', MOD_MOODLECST_LANG));
        $mform->addElement('select', 'gradeoptions', get_string('gradeoptions', MOD_MOODLECST_LANG), $gradeoptions);

        //-------------------------------------------------------------------------------
        // Adding the "UCAT" fieldset, where all the ucatmode settings are showed
        $mform->addElement('header', 'ucatelements', get_string('ucatelements', MOD_MOODLECST_LANG));

        $mform->addElement('selectyesno', 'ucatenabled', get_string('enableucat',MOD_MOODLECST_LANG ));

        $opts_estimatemethod = array(
            \mod_moodlecst\ucat::ESTIMATE_SIMPLE => get_string('estimatesimple', MOD_MOODLECST_LANG),
            \mod_moodlecst\ucat::ESTIMATE_COMPLEX => get_string('estimatecomplex', MOD_MOODLECST_LANG),
        );
        $mform->addElement('select', 'estimatemethod', get_string('estimatemethod', MOD_MOODLECST_LANG), $opts_estimatemethod);
        $mform->setDefault('estimatemethod', \mod_moodlecst\ucat::ESTIMATE_COMPLEX);

        $opts_endcondition = array(
            \mod_moodlecst\ucat::ENDCOND_ALL => get_string('allitems', MOD_MOODLECST_LANG),
            \mod_moodlecst\ucat::ENDCOND_NUMQUEST => get_string('byreqitems', MOD_MOODLECST_LANG),
            \mod_moodlecst\ucat::ENDCOND_SE => get_string('byse', MOD_MOODLECST_LANG),
            \mod_moodlecst\ucat::ENDCOND_NUMQUESTANDSE => get_string('byreqitemsandse', MOD_MOODLECST_LANG)
        );
        $mform->addElement('select', 'ucatendcondition', get_string('endcondition', MOD_MOODLECST_LANG), $opts_endcondition);

        $mform->addElement('text', 'ucatreqitems', get_string('reqitems', MOD_MOODLECST_LANG), array('size' => 4));
        $mform->setType('ucatreqitems', PARAM_INT);
        $mform->setDefault('ucatreqitems', 1);

        $mform->addElement('text', 'ucatse', get_string('se', MOD_MOODLECST_LANG), array('size' => 8));
        $mform->setType('ucatse', PARAM_FLOAT);
        $mform->setDefault('ucatse', 0);

        $mform->addElement('hidden', 'ucatlogitbias');
        $mform->setType('ucatlogitbias', PARAM_FLOAT);
        $mform->setDefault('ucatlogitbias', 0);


        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }
	
	
    /**
     * This adds completion rules
	 * The values here are just dummies. They don't work in this project until you implement some sort of grading
	 * See lib.php moodlecst_get_completion_state()
     */
	 function add_completion_rules() {
		$mform =& $this->_form;  
		$config = get_config(MOD_MOODLECST_FRANKY);
    
		//timer options
        //Add a place to set a mimumum time after which the activity is recorded complete
       $mform->addElement('static', 'mingradedetails', '',get_string('mingradedetails', MOD_MOODLECST_LANG));
       $options= array(0=>get_string('none'),20=>'20%',30=>'30%',40=>'40%',50=>'50%',60=>'60%',70=>'70%',80=>'80%',90=>'90%',100=>'40%');
       $mform->addElement('select', 'mingrade', get_string('mingrade', MOD_MOODLECST_LANG), $options);	   
	   
		return array('mingrade');
	}
	
	function completion_rule_enabled($data) {
		return ($data['mingrade']>0);
	}
}
