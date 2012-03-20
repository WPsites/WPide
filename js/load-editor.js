var autocompleting = false;
var autocompletelength = 2;
var editor = '';

var saved_editor_sessions = [];
var saved_undo_manager = [];
var last_added_editor_session = 0;
var current_editor_session = 0;

var EditSession = require('ace/edit_session').EditSession; 
var UndoManager = require('ace/undomanager').UndoManager; 

function onSessionChange(e)  {
	//don't continue with autocomplete if /n entered
	try {
		if ( e.data.text.charCodeAt(0) === 10 ){
			return;
		}
	}catch(error){}

    //don't continue with autocomplete if backspace entered
    try {
            if ( e.data.text.charCodeAt(0) === 8 ){
                    alert('backspace');
                    return;
            }
    }catch(error){}


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
	if (text.length < 3) return;
	
	//we don't want to autocomplete the <?php tag
	if (text == 'php'){
		range.start.column = range.start.column - 1;
		var text4 = editor.getSession().doc.getTextRange(range);
		if (text4 == "?php") return;
	}


	//create the dropdown for autocomplete
	var sel = editor.getSelection();
	var session = editor.getSession();
	var lead = sel.getSelectionLead();

	var pos = editor.renderer.textToScreenCoordinates(lead.row, lead.column);
	var ac; // #ac is auto complete html select element



	if( document.getElementById('ac') ){
		ac=document.getElementById('ac');

        //editor clicks should hide the autocomplete dropdown
        editor.container.addEventListener('click', function(e){
               ac.style.display='none';
        }, false);
	
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
function wpide_set_file_contents(file, callback_func){
	"use strict";
    
	//ajax call to get file contents we are about to edit
	var data = { action: 'wpide_get_file', filename: file, _wpnonce: jQuery('#_wpnonce').val(), _wp_http_referer: jQuery('#_wp_http_referer').val() };

	jQuery.post(ajaxurl, data, function(response) { 
		var the_path = file.replace(/^.*[\\\/]/, ''); 
		var the_id = "wpide_tab_" + last_added_editor_session;
        
		jQuery("#wpide_toolbar_tabs").append('<span id="'+the_id+'" sessionrel="'+last_added_editor_session+'"  title="  '+file+' " rel="'+file+'" class="wpide_tab">'+ the_path +'</a> <a class="close_tab" href="#">x</a> ');		
			
		saved_editor_sessions[last_added_editor_session] = new EditSession(response);//set saved session
		saved_editor_sessions[last_added_editor_session].on('change', onSessionChange);
		saved_undo_manager[last_added_editor_session] = new UndoManager(editor.getSession().getUndoManager());//new undo manager for this session
		
		last_added_editor_session++; //increment session counter
			
		//add click event for the new tab. 
        //We are actually clearing the click event and adding it again for all tab elements, it's the only way I could get the click handler listening on all dynamically added tabs
		jQuery(".wpide_tab").off('click').on("click", function(event){
			event.preventDefault();
			console.log( jQuery(this).attr('rel') + " opened");
			jQuery('input[name=filename]').val( jQuery(this).attr('rel') );
            
			//save current editor into session
			//get old editor out of session and apply to editor
			var clicksesh = jQuery(this).attr('sessionrel'); //editor id number
			saved_editor_sessions[ clicksesh ].setUndoManager(saved_undo_manager[ clicksesh ]);
			editor.setSession( saved_editor_sessions[ clicksesh ] );
            
            //set this tab as active
            jQuery(".wpide_tab").removeClass('active');
            jQuery(this).addClass('active');
		
			var currentFilename = jQuery(this).attr('rel');
			var mode;

            //set the editor mode based on file name
			if (/\.css$/.test(currentFilename)) {
				mode = require("ace/mode/css").Mode;
			}
            else if (/\.less$/.test(currentFilename)) {
				mode = require("ace/mode/css").Mode;
			}
			else if (/\.js$/.test(currentFilename)) {
				mode = require("ace/mode/javascript").Mode;
			}
			else {
				mode = require("ace/mode/php").Mode; //default to php	
			}
			editor.getSession().setMode(new mode());
			
			editor.getSession().on('change', onSessionChange);
			editor.resize(); 
			editor.focus(); 
			//make a note of current editor
			current_editor_session = clicksesh;
		
		});
        
		//add click event for tab close. 
		//We are actually clearing the click event and adding it again for all tab elements, it's the only way I could get the click handler listening on all dynamically added tabs
		jQuery(".close_tab").off('click').on("click", function(event){
		event.preventDefault();
		var clicksesh = jQuery(this).parent().attr('sessionrel');
		var activeFallback;
            
            //if the currently selected tab is being removed then remember to make the first tab active
            if ( jQuery("#wpide_tab_"+clicksesh).hasClass('active') ) {
                activeFallback = true;
            }else{
                activeFallback = false;
            }
            
            //remove tab
            jQuery(this).parent().remove();
            
            //clear session and undo
    	   saved_undo_manager[clicksesh] = undefined;
		   saved_editor_sessions[clicksesh] = undefined;
           console.log(saved_editor_sessions);
           
           //Clear the active editor if all tabs closed or activate first tab if required since the active tab may have been deleted
           if (jQuery(".wpide_tab").length == 0){
               editor.getSession().setValue( "" );
           }else if ( activeFallback ){
               jQuery(".wpide_tab")[0].click();
           }
   
		});
		
    		jQuery("#"+the_id).click();
	
	if (callback_func != null) {
		callback_func(response);
	}
	
	});
	
	
}

function saveDocument() {
	//ajax call to save the file and generate a backup if needed
	var data = { action: 'wpide_save_file', filename: jQuery('input[name=filename]').val(),  _wpnonce: jQuery('#_wpnonce').val(), _wp_http_referer: jQuery('#_wp_http_referer').val(), content: editor.getSession().getValue() };
	jQuery.post(ajaxurl, data, function(response) { 
		if (response === 'success') {
			jQuery("#wpide_message").html('<span>File saved.</span>');
			jQuery("#wpide_message").show();
			jQuery("#wpide_message").fadeOut(5000); 
		} else {
			alert("error: " + response);
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

	//use editors php mode
	var phpMode = require("ace/mode/php").Mode;
	editor.getSession().setMode(new phpMode());
	
	//START AUTOCOMPLETE
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
	function selectACitem () {
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
		exec: selectACitem
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
