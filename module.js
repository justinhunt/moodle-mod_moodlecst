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
 * JavaScript library for the moodlecst module.
 *
 * @package    mod
 * @subpackage moodlecst
 * @copyright  moodlecst
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_moodlecst = M.mod_moodlecst || {};

M.mod_moodlecst.helper = {
	gY: null,


	 /**
     * @param Y the YUI object
     * @param opts an array of options
     */
    init: function(Y,opts) {
    	
    	M.mod_moodlecst.helper.gY = Y;
    	console.log(opts['someinstancesetting']);
    
    }
};

// Define the core_user namespace if it has not already been defined
M.mod_moodlecst_session = {
	gY: null,
	chosen: null,
	unchosen: null,
	chosendata: null,
	unchosendata: null,
	sortorder: null,
	updatefield: null,
	init: function(Y,opts){
		this.gY = Y;
		this.chosen =opts['chosen'];
		this.unchosen = opts['unchosen'];
		this.chosendata =opts['chosendata'];
		this.unchosendata = opts['unchosendata'];
		this.updatefield = opts['updatefield'];
		if(opts['sortorder']){
			this.sortorder=opts['sortorder'].split(',');
		};
		//kill moform formatting
		Y.one('.mform').removeClass('mform');
	},
	
	choose: function(){
		var from= this.unchosen;
		var to= this.chosen;
		this.move(from,to);
		this.do_sync_updatefield();
	},
	
	unchoose: function(){
		var from=this.chosen;
		var to= this.unchosen;		
		this.move(from,to);
		this.do_sync_updatefield();
	},
	do_filter: function(filterarray,filtertext){
		var filtered = {};
		filtertext=filtertext.toLowerCase();
		this.gY.each(filterarray,function(value,key){
			if(typeof value=='undefined'){return;}
			var yes = value.toLowerCase().indexOf(filtertext) > -1;
			if(yes){
				filtered[key]=value;
			}
		});
		return filtered;
	},
	do_sync_listbox: function(listboxname,newdata){
		var listbox = this.get_listbox(listboxname);
		listbox.all('option').each(function(){
			this.remove()
		});
		this.gY.each(newdata,function(value,key){
			listbox.append('<option name="' + key + '" value="' + key + '">'+ value +'</option>');
		});
	},
	do_sync_updatefield: function(){
		var updatefield =  this.gY.one("input[name='" + this.updatefield + "']");
		var chosenkeys = Object.keys(this.chosendata)
		var usevalue = '';
		if(chosenkeys.length > 0){
			usevalue = chosenkeys.join();
		}
		updatefield.set('value',usevalue);
	},
	filter_chosen: function(){
		var filtertext = this.gY.one('#mod_moodlecst_session_chosenfilter').get('value');
		var filtered = this.do_filter(this.chosendata,filtertext);
		this.do_sync_listbox(this.chosen,filtered);
		//console.log(filtered);
	},
	filter_unchosen: function(){
		var filtertext = this.gY.one('#mod_moodlecst_session_unchosenfilter').get('value');
		var filtered = this.do_filter(this.unchosendata,filtertext);
		this.do_sync_listbox(this.unchosen,filtered);
		//console.log(filtered);
	},
	get_listbox: function(listboxname){
		return  this.gY.one("select[name='" + listboxname + "']");
	},
	move: function (listfrom, listto) {
	
		var listboxfrom = this.get_listbox(listfrom);
		var listboxto = this.get_listbox(listto);
		//setup the data arrays to be edited
		if(listfrom==this.chosen){
			var fromdata = this.chosendata;
			var todata = this.unchosendata;
		}else{
			var fromdata = this.unchosendata;
			var todata = this.chosendata;
		}
		listboxfrom.all('option:checked').each(function(){
			//modify data arrays
			todata[this.get('value')]=this.get('text');
			delete fromdata[this.get('value')];
		});
		this.refreshListbox(this.chosen,this.chosendata);
		this.refreshListbox(this.unchosen,this.unchosendata);
		
	},
	refreshListbox: function(listbox,listboxdata){
		var thelistbox = this.get_listbox(listbox);
		thelistbox.all('option').remove();
		this.gY.Array.each(this.sortorder,function(value){		
				if(listboxdata.hasOwnProperty(value)){
					//console.log('key:' + value);
					//console.log('value:' + listboxdata[value]);
					thelistbox.append('<option name="' + value + '" value="' + value + '">'+ listboxdata[value] +'</option>');
				}
			}
		);
	}
}
