var autocompleting = false;
var autocompletelength = 2;
var editor = '';

var saved_editor_sessions = [];
var saved_undo_manager = [];
var last_added_editor_session = 0;
var current_editor_session = 0;

var EditSession = require('ace/edit_session').EditSession; 
var UndoManager = require('ace/undomanager').UndoManager; 

//are we editing a theme or plugin?
var aceedittype;
if (/wp\-admin\/plugin-editor\.php/.test(document.URL)) {
	aceedittype = 'plugin';
}
else if (/wp\-admin\/theme-editor\.php/.test(document.URL)) {
	aceedittype = 'theme';
}
else {
	aceedittype = 'theme';
}	

function onSessionChange(e)  {
	//don't continue with autocomplete if /n entered
	try {
		if ( e.data.text.charCodeAt(0) === 10 ){
			return;
		}
	}catch(e){}

	//get cursor/selection
	var range = editor.getSelectionRange();
	
	//do we need to extend the length of the autocomplete string
	if (autocompleting) {
		autocompletelength = autocompletelength + 1;
	} else {
		autocompletelength = 2;
	}

	//modify the cursor/selection data we have to get text from the editor to check for matching function/method
	//set start column
	range.start.column = range.start.column - autocompletelength;
	//no column lower than 1 thanks
	if (range.start.column < 1) {
		range.start.column = 0;
	}
	//set end column
	range.end.column = range.end.column + 1;
	//get the editor text based on that range
	var text = editor.getSession().doc.getTextRange(range);

	//dont show if no text passed
	$quit_onchange = false;
	try {
		if (text==="") {
			ac.style.display='none';
		}
	} catch(e) { }//catch end
	// if string length less than 3 then quit this
	if (text.length < 3){
		return;
	}


	//create the dropdown for autocomplete
	var sel = editor.getSelection();
	var session = editor.getSession();
	var lead = sel.getSelectionLead();

	var pos = editor.renderer.textToScreenCoordinates(lead.row, lead.column);
	var ac;



	if( document.getElementById('ac') ){
		ac=document.getElementById('ac');

		//editor clicks should hide the autocomplete dropdown
		editor.container.addEventListener('click',function(e){
			ac.style.display='none';
		});

	} //end - create initial autocomplete dropdown and related actions


	//calulate the editor container offset
	var obj=editor.container;

	var curleft = 0;
	var curtop = 0;

	if (obj.offsetParent) {

		do {
			curleft += obj.offsetLeft;
			curtop += obj.offsetTop;
		} while (obj = obj.offsetParent);

	}						


	//position autocomplete
	ac.style.top=pos.pageY - curtop + 40 + "px";
	ac.style.left=pos.pageX - curleft + 20 + "px";
	ac.style.display='block';
	ac.style.background='white';


	//remove all options, starting a fresh list
	ac.options.length = 0;

	//loop through tags and check for a match
	var tag;
	for(i in html_tags) {
		if(!html_tags.hasOwnProperty(i) ){
			continue;
		}

		tag=html_tags[i];					
		if( text ){
			if( text !== tag.substr(0,text.length) ){
				continue;
			}
		}

		var option = document.createElement('option');
		option.text = tag;
		option.value = tag;

		try {
			ac.add(option, null); // standards compliant; doesn't work in IE
		}
		catch(ex) {
			ac.add(option); // IE only
		}

	}//end for



	//if the return list contains everything then don't display it
	if (html_tags.length === ac.options.length){
		ac.options.length = 0;
	}

	//check for matches
	if ( ac.length === 0 ) {
		ac.style.display='none';
		autocompleting=false;
	} else {
		ac.selectedIndex=0;			
		autocompleting=true;
	}

}

//open another file and add to editor
function wpide_set_file_contents(file){
	"use strict";
	//ajax call to get file contents we are about to edit
	var data = { action: 'wpide_get_file', filename: file };
	jQuery.post(ajaxurl, data, function(response) { 
		var the_path = file.replace(/^.*[\\\/]/, ''); 
		var the_id = "wpide_tab_" + last_added_editor_session;
		jQuery("#wpide_toolbar_tabs").append('<a href="#" id="'+the_id+'" sessionrel="'+last_added_editor_session+'"  title="  '+file+' " rel="'+file+'" class="wpide_tab">'+ the_path +'</a>');		
			
		saved_editor_sessions[last_added_editor_session] = new EditSession(response);//set saved session
		saved_editor_sessions[last_added_editor_session].on('change', onSessionChange);
		saved_undo_manager[last_added_editor_session] = new UndoManager(editor.getSession().getUndoManager());
		
		last_added_editor_session++; //increment session counter
			
			
		jQuery(".wpide_tab").off('click').on("click", function(event){
			event.preventDefault();
			console.log( jQuery(this).attr('rel') + " opened");
			jQuery('input[name=filename]').val( jQuery(this).attr('rel') );
			//save current editor into session
			//saved_editor_sessions[current_editor_session] = editor.getSession(); 
			//get old editor out of session and apply to editor
			var clicksesh = jQuery(this).attr('sessionrel');
			saved_editor_sessions[ clicksesh ].setUndoManager(saved_undo_manager[ clicksesh ]);
			editor.setSession( saved_editor_sessions[ clicksesh ] );
		
			//use editors php mode
			var currentFilename = jQuery(this).attr('rel');
			var mode;
			// var phpMode = require("ace/mode/php").Mode;
			// editor.getSession().setMode(new phpMode());
			if (/\.css$/.test(currentFilename)) {
				mode = require("ace/mode/css").Mode;
			}
			else if (/\.js$/.test(currentFilename)) {
				mode = require("ace/mode/javascript").Mode;
			}
			else {
				mode = require("ace/mode/php").Mode;	
			}
			editor.getSession().setMode(new mode());
			
			editor.getSession().on('change', onSessionChange);
			editor.resize(); 
			editor.focus(); 
			//make a not of current editor
			current_editor_session = clicksesh;
		
		});
		
		jQuery("#"+the_id).click();
		return response;
			
	});
}

function saveDocument() {
	//ajax call to generate a backup of this file we are about to edit
	var data = { action: 'wpide_save_file', filename: jQuery('input[name=filename]').val(), content: editor.getSession().getValue() };
	jQuery.post(ajaxurl, data, function(response) { 
		if (response === 'success') {
			jQuery("#wpide_message").html('<span>File saved.</span>');
			jQuery("#wpide_message").show();
			jQuery("#wpide_message").fadeOut(5000); 
		} else {
			alert(response);
		}
	});	
}

jQuery(document).ready(function($) {
	$("#wpide_save").click(saveDocument);

	
	//add div for ace editor to latch on to
	//$('#template').prepend("<div style='width:80%;height:500px;margin-right:0!important;' id='fancyeditordiv'></div>");
	//create the editor instance
	editor = ace.edit("fancyeditordiv");
	//set the editor theme
	editor.setTheme("ace/theme/dawn"); 
	//get a copy of the initial file contents (the file being edited)
	//var intialData = $('#newcontent').val()
	var intialData = "Use the file manager to find a file you wish edit, click the file name to edit. \n\n";
	editor.getSession().setValue( intialData );



	//ajax call to generate a backup of this file we are about to edit
	var data = { action: 'ace_backup_call', filename: $('input[name=file]').val(), edittype: aceedittype };
	jQuery.post(ajaxurl, data, function(response) { 
		if (response === 'success'){
			alert("A backup copy of this file has been generated.");
		}
	});

	//use editors php mode
	var phpMode = require("ace/mode/php").Mode;
	editor.getSession().setMode(new phpMode());


	$('#submit').click(function(event){
        var use_val = editor.getSession().getValue();
		$('textarea#newcontent').text( use_val ); //.html does some dodgy things with certain php files
	});
	

	//START WP AUTOCOMPLETE
	//create the autocomplete dropdown
	var ac = document.createElement('select');
	ac.id = 'ac';
	ac.name = 'ac';
	ac.style.position='absolute';
	ac.style.zIndex=100;
	ac.style.width='auto';
	ac.style.display='none';
	ac.style.height='auto';
	ac.size=10;
	editor.container.appendChild(ac);

	//hook onto any change in editor contents
	editor.getSession().on('change', onSessionChange);//end editor change event



	//START COMMANDS
	var canon = require('pilot/canon');

	//Key up command
	canon.addCommand({		
		name: "up",
		bindKey: {
			win: "Up",
			mac: "Up",
			sender: "editor"
		},			
		exec: function(env, args, request) {
			if( document.getElementById('ac').style.display === 'block'  ) {
				var select=document.getElementById('ac');
				if( select.selectedIndex === 0 ) {
					select.selectedIndex = select.options.length-1;
				} else {
					select.selectedIndex = select.selectedIndex-1;
				}
			} else {
				var range = editor.getSelectionRange();
				editor.clearSelection();
				editor.moveCursorTo(range.end.row - 1, range.end.column);
			}
		}
	});


	//key down command
	canon.addCommand({
		name: "down",
		bindKey: {
			win: "Down",
			mac: "Down",
			sender: "editor"
		},
		exec: function(env, args, request) {
			if ( document.getElementById('ac').style.display === 'block' ) {
				var select=document.getElementById('ac');
				if ( select.selectedIndex === select.options.length-1 ) {
					select.selectedIndex=0;
				} else {
					select.selectedIndex=select.selectedIndex+1;
				}
			} else {
				var range = editor.getSelectionRange();
				editor.clearSelection();
				editor.moveCursorTo(range.end.row +1, range.end.column);
			}
		}
	});

	
	//enter/return command
	function trythis () {
		if( document.getElementById('ac').style.display === 'block'  ){
			var ac_dropdwn = document.getElementById('ac');
			var tag = ac_dropdwn.options[ac_dropdwn.selectedIndex].value;
			var sel = editor.selection.getRange();
			var line = editor.getSession().getLine(sel.start.row);										
			sel.start.column = sel.start.column-(autocompletelength+1);
			editor.selection.setSelectionRange(sel);				
			editor.insert(tag);
			autocompleting = false;
			ac_dropdwn.style.display='none';
		} else {
			editor.insert('\n');
		}
	}
	
	canon.addCommand({
		name: "enter",
		bindKey: {
			win: "Return",
			mac: "Return",
			sender: "editor"
		},
		exec: trythis
	});

	// save command: 
	canon.addCommand({
		name: "save",
		bindKey: {
			win: "Ctrl-S",
			mac: "Command-S",
			sender: "editor"
		},
		exec: saveDocument
	});

	//END COMMANDS


});//end jquery load