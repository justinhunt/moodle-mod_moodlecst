<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// This file is part of Moodle - http://moodle.org/                      //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//                                                                       //
// Moodle is free software: you can redistribute it and/or modify        //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation, either version 3 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// Moodle is distributed in the hope that it will be useful,             //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details.                          //
//                                                                       //
// You should have received a copy of the GNU General Public License     //
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.       //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

/**
 * Forms for moodlecst Activity
 *
 * @package    mod_moodlecst
 * @author     Justin Hunt <poodllsupport@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  (C) 1999 onwards Justin Hunt  http://poodll.com
 */

require_once($CFG->libdir . '/formslib.php');
//require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/moodlecst/lib.php');

/**
 * Abstract class that item type's inherit from.
 *
 * This is the abstract class that add item type forms must extend.
 *
 * @abstract
 * @copyright  2014 Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class moodlecst_session_add_item_form_base extends moodleform {

    /**
     * This is used to identify this itemtype.
     * @var string
     */
    public $type;

    /**
     * The simple string that describes the item type e.g. audioitem, textitem
     * @var string
     */
    public $typestring;


	
    /**
     * True if this is a standard item of false if it does something special.
     * items are standard items
     * @var bool
     */
    protected $standard = true;

    /**
     * Each item type can and should override this to add any custom elements to
     * the basic form that they want
     */
    public function custom_definition() {}

    /**
     * Used to determine if this is a standard item or a special item
     * @return bool
     */
    public final function is_standard() {
        return (bool)$this->standard;
    }

    /**
     * Add the required basic elements to the form.
     *
     * This method adds the basic elements to the form including title and contents
     * and then calls custom_definition();
     */
    public final function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'typeheading', get_string('createaitem', 'moodlecst', get_string($this->typestring, 'moodlecst')));

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);
        
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

		$mform->addElement('hidden', 'type');
		$mform->setType('type', PARAM_INT);
			
		$mform->addElement('hidden', 'displayorder');
		$mform->setType('displayorder', PARAM_INT);

		$mform->addElement('text', 'name', get_string('sessiontitle', 'moodlecst'), array('size'=>70));
		$mform->setType('name', PARAM_TEXT);
		$mform->addRule('name', get_string('required'), 'required', null, 'client');
	
		$mform->addElement('selectyesno', 'active', get_string('active','moodlecst'));
		
        $this->custom_definition();
		
		//add the action buttons
        $this->add_action_buttons(get_string('cancel'), get_string('saveitem', 'moodlecst'));

    }


 /**
     * Adds slidepair chooser
     *
     * @param string rendered slidepair chooser
     * @return void
     */
    protected final function add_slidepairchooser($renderedchooser) {
        $this->_form->addElement('hidden', MOD_MOODLECST_SESSION_UPDATEFIELD);
        $this->_form->setType(MOD_MOODLECST_SESSION_UPDATEFIELD, PARAM_TEXT);
		$this->_form->addElement('static', 'activitychooser',null, $renderedchooser);
    }


    /**
     * A function that gets called upon init of this object by the calling script.
     *
     * This can be used to process an immediate action if required. Currently it
     * is only used in special cases by non-standard item types.
     *
     * @return bool
     */
    public function construction_override($itemid,  $moodlecst) {
        return true;
    }
}

//this is the standard form for creating a session of slidepairs
class moodlecst_session_standard_form extends moodlecst_session_add_item_form_base {

    public $type = 'slidepairchooser';
    public $typestring = 'slidepairchooser';

    public function custom_definition() {
    	list($renderedchooser) = $this->_customdata;
    	$this->add_slidepairchooser($renderedchooser);
    }
}

