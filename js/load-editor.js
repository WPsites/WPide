var autocompleting = false;
var autocompletelength = 2;
var editor = '';

var saved_editor_sessions = [];
var saved_undo_manager = [];
var last_added_editor_session = 0;
var current_editor_session = 0;

var EditSession = require('ace/edit_session').EditSession; 
var UndoManager = require('ace/undomanager').UndoManager;
var Search = require("ace/search").Search;

var oHandler;

function onSessionChange(e)  {
	//don't continue with autocomplete if /n entered
	try {
		if ( e.data.text.charCodeAt(0) === 10 ){
			return;
		}
	}catch(error){}

	//get cursor/selection
	var range = editor.getSelectionRange();
	
	try {
		if ( e.data.action == 'removeText' ){
			
		       if (autocompleting) {
				//console.log(e.data);
				
				autocompletelength = (autocompletelength - 1) ;
				
			}
			//return;
		}
	}catch(error){}
	
	
	//get current cursor position
	range = editor.getSelectionRange();
	//take note of selection row to compare with search
	cursor_row = range.start.row;
	
	if (range.start.column > 0){
	
		//search for command text user has entered that we need to try match functions against
		var search = new Search().set({
			needle: "[\\n \.\)\(]",
			backwards: true,
			wrap: false,
			caseSensitive: false,
			wholeWord: false,
			regExp: true
		      });
		      //console.log(search.find(editor.getSession()));
		      
		range = search.find(editor.getSession());
		
		if (range) range.start.column++;
	
	}else{ //change this to look char position, if it's starting at 0 then do this
		
		range.start.column = 0;
	}
	
	if (! range || range.start.row < cursor_row ){
		//forse the autocomplete check on this row starting at column 0
		range = editor.getSelectionRange();
		range.start.column = 0;
	}

	
	//console.log("search result - start row " + range.start.row + "-" + range.end.row + ", column " + range.start.column+ "-" + range.end.column);
	//console.log(editor.getSelection().getRange());
	
	range.end.column = editor.getSession().getSelection().getCursor().column +1;//set end column as cursor pos
	
	//console.log("[ \.] based: " + editor.getSession().doc.getTextRange(range));
	
	//no column lower than 1 thanks
	if (range.start.column < 1) {
		range.start.column = 0;
	}
	
	//console.log("after row " + range.start.row + "-" + range.end.row + ", column " + range.start.column+ "-" + range.end.column);
	//get the editor text based on that range
	var text = editor.getSession().doc.getTextRange(range);
	$quit_onchange = false;
	
	//console.log(text);
	
	//console.log("Searching for text \""+text+"\" length: "+ text.length);
	if (text.length < 3){
		
		if (ac) ac.style.display='none';
		if (oHandler) oHandler.close();
		return;
	}
	
	autocompletelength = text.length;

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
		
		if (ac) ac.style.display='none';
		if (oHandler) oHandler.close();
	       
	       	autocompleting=false;
		autocompletelength = 2;
		
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
	ac.style.top= ((pos.pageY - curtop)+20) + "px";
	ac.style.left= ((pos.pageX - curleft)+10) + "px";
	ac.style.display='block';
	ac.style.background='white';


	//remove all options, starting a fresh list
	ac.options.length = 0;


	//loop through WP tags and check for a match
	if (autocomplete_wordpress){
		var tag;
		for(i in autocomplete_wordpress) {
			//if(!html_tags.hasOwnProperty(i) ){
			//	continue;
			//}
	
			tag= i;
			//see if the tag is a match
			if( text !== tag.substr(0,text.length) ){
				continue;
			}
			
			//add parentheses
			tag = tag + "()";
			
			var option = document.createElement('option');
			option.text = tag;
			option.value = tag;
			option.setAttribute('title', '/wp-content/plugins/WPide/images/wpac.png');//path to icon image or wpac.png
	
			try {
				ac.add(option, null); // standards compliant; doesn't work in IE
			}
			catch(ex) {
				ac.add(option); // IE only
			}
	
		}//end for
	}//end php autocomplete
	
	//loop through PHP tags and check for a match
	if (autocomplete_php){
		var tag;
		for(i in autocomplete_php) {
			//if(!html_tags.hasOwnProperty(i) ){
			//	continue;
			//}
	
			tag= i;
			//see if the tag is a match
			if( text !== tag.substr(0,text.length) ){
				continue;
			}
			
			//add parentheses
			tag = tag + "()";
			
			var option = document.createElement('option');
			option.text = tag;
			option.value = tag;
			option.setAttribute('title', '/wp-content/plugins/WPide/images/phpac.png');//path to icon image or wpac.png
	
			try {
				ac.add(option, null); // standards compliant; doesn't work in IE
			}
			catch(ex) {
				ac.add(option); // IE only
			}
	
		}//end for
	}//end php autocomplete
	

	//check for matches
	if ( ac.length === 0 ) {
		if (ac) ac.style.display='none';
		if (oHandler) oHandler.close();
		
		//console.log("set auto complete false due to ac.length==0");
	} else {

		ac.selectedIndex=0;			
		autocompleting=true;
		oHandler = jQuery("#ac").msDropDown({visibleRows:10, rowHeight:20}).data("dd");
		
		jQuery("#ac_child").click(function(item){
			//console.log(item.srcElement.textContent);
			selectACitem(item.srcElement.textContent);
		});
		
		
		jQuery("#ac_child").css("z-index", "9999");
		jQuery("#ac_child").css("background-color", "#ffffff");
		jQuery("#ac_msdd").css("z-index", "9999");
		jQuery("#ac_msdd").css("position", "absolute");
		jQuery("#ac_msdd").css("top", ac.style.top);
		jQuery("#ac_msdd").css("left", ac.style.left);
		
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
		
		//enable editor now we have a file open
		jQuery('#fancyeditordiv textarea').removeAttr("disabled");
        
		jQuery("#wpide_toolbar_tabs").append('<span id="'+the_id+'" sessionrel="'+last_added_editor_session+'"  title="  '+file+' " rel="'+file+'" class="wpide_tab">'+ the_path +'</a> <a class="close_tab" href="#">x</a> ');		
			
		saved_editor_sessions[last_added_editor_session] = new EditSession(response);//set saved session
		saved_editor_sessions[last_added_editor_session].on('change', onSessionChange);
		saved_undo_manager[last_added_editor_session] = new UndoManager(editor.getSession().getUndoManager());//new undo manager for this session
		
		last_added_editor_session++; //increment session counter
			
		//add click event for the new tab. 
        //We are actually clearing the click event and adding it again for all tab elements, it's the only way I could get the click handler listening on all dynamically added tabs
		jQuery(".wpide_tab").off('click').on("click", function(event){
			event.preventDefault();

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

//enter/return command
function selectACitem (item) {
	if( document.getElementById('ac').style.display === 'block'  ){
		var ac_dropdwn = document.getElementById('ac');
		var tag = ac_dropdwn.options[ac_dropdwn.selectedIndex].value;
		var sel = editor.selection.getRange();
		var line = editor.getSession().getLine(sel.start.row);										
		sel.start.column = sel.start.column - autocompletelength;
		
		if (item.length){
			tag = item; //get tag from new msdropdown passed as arg	
		}else{
			tag = jQuery("#ac_msdd a.selected").children("span.ddTitleText").text(); //get tag from new msdropdown
		}
		
		
		editor.selection.setSelectionRange(sel);				
		editor.insert(tag);
		autocompleting = false;
		ac_dropdwn.style.display='none';
		if (oHandler) oHandler.close();
	} else {
		editor.insert('\n');
	}
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
	
	//make initial editor read only
	$('#fancyeditordiv textarea').attr("disabled", "disabled");

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
	
	//Key up command
	editor.commands.addCommand({		
		name: "up",
		bindKey: {
			win: "Up",
			mac: "Up",
			sender: "editor"
		},			
		exec: function(env, args, request) {
			if (oHandler && oHandler.visible() === 'block'){
				oHandler.previous();
				
			}else if( document.getElementById('ac').style.display === 'block'  ) {
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
	editor.commands.addCommand({
		name: "down",
		bindKey: {
			win: "Down",
			mac: "Down",
			sender: "editor"
		},
		exec: function(env, args, request) {
		
			if (oHandler && oHandler.visible() === 'block'){
				oHandler.next();
				
			}else if ( document.getElementById('ac').style.display === 'block' ) {
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

	

	
	editor.commands.addCommand({
		name: "enter",
		bindKey: {
			win: "Return",
			mac: "Return",
			sender: "editor"
		},
		exec: selectACitem
	});

	// save command: 
	editor.commands.addCommand({
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
