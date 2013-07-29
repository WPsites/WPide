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
var TokenIterator = require("ace/token_iterator").TokenIterator;

var oHandler;

function onSessionChange(e)  {
    
    //set the document as unsaved
    jQuery(".wpide_tab.active", "#wpide_toolbar").data( "unsaved", true);
    jQuery("#wpide_footer_message_unsaved").html("[ Document contains unsaved content &#9998; ]").show();
	
	if( editor.getSession().enable_autocomplete === false){
        return;   
	}
    
    //don't continue with autocomplete if /n entered
	try {
		if ( e.data.text.charCodeAt(0) === 10 ){
			return;
		}
	}catch(error){}

	try {
		if ( e.data.action == 'removeText' ){
			
		       if (autocompleting) {
				autocompletelength = (autocompletelength - 1) ;
			}else{
				return;
			}
		}
	}catch(error){}
	
	
	//get current cursor position
	range = editor.getSelectionRange();
	//take note of selection row to compare with search
	cursor_row = range.start.row;
	
	try{
	//quit autocomplete if we are writing a "string"
		var iterator = new TokenIterator(editor.getSession(), range.start.row, range.start.column);
		var current_token_type = iterator.getCurrentToken().type;
		if(current_token_type == "string" || current_token_type == "comment"){
			return;
		}
	}catch(error){}
	
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
		
		wpide_close_autocomplete();
		return;
	}
	
	autocompletelength = text.length;

	//create the dropdown for autocomplete
	var sel = editor.getSelection();
	var session = editor.getSession();
	var lead = sel.getSelectionLead();

	var pos = editor.renderer.textToScreenCoordinates(lead.row, lead.column);
	var ac = document.getElementById('ac'); // #ac is auto complete html select element

    

	if( typeof ac !== 'undefined' ){
		
        //add editor click listener
        //editor clicks should hide the autocomplete dropdown
        editor.container.addEventListener('click', function(e){
		
            wpide_close_autocomplete();
            
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
			option.setAttribute('title', wpide_app_path + 'images/wpac.png');//path to icon image or wpac.png
		
	
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
			option.setAttribute('title', wpide_app_path + 'images/phpac.png');//path to icon image or wpac.png

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
		wpide_close_autocomplete();
	} else {

		ac.selectedIndex=0;			
		autocompleting=true;
		oHandler = jQuery("#ac").msDropDown({visibleRows:10, rowHeight:20}).data("dd");
		
		jQuery("#ac_child").click(function(item){
			//get the link node and pass to select AC item function
            if (typeof item.srcElement != 'undefined'){
                var link_node = item.srcElement; //works on chrome
            }else{
                var link_node = item.target; //works on Firefox etc
            }
			
            selectACitem(link_node);
		});
		
		jQuery("#ac_child a").mouseover(function(item){
			//show the code in the info panel
            
            //get the link ID
            if (typeof item.srcElement != 'undefined'){
                var link_id = item.srcElement.id; //works on chrome
            }else{
                var link_id = item.target.id; //works on Firefox etc
            }
            
            if (link_id == '') return; //if the link doesn't have an id it's not valid so just stop
            
            
			//if this command item is enabled
			if (jQuery("#"+link_id).hasClass("enabled")){
				
				var selected_item_index = jQuery("#"+link_id).index();
				
				if (selected_item_index > -1){ //if select item is valid
					
					//set the selected menu item
					oHandler.selectedIndex(selected_item_index);
					//show command help panel for this command
					wpide_function_help();
					
				}
			}

		});
		
		
		jQuery("#ac_child").css("z-index", "9999");
		jQuery("#ac_child").css("background-color", "#ffffff");
		jQuery("#ac_msdd").css("z-index", "9999");
		jQuery("#ac_msdd").css("position", "absolute");
		jQuery("#ac_msdd").css("top", ac.style.top);
		jQuery("#ac_msdd").css("left", ac.style.left);
		
		//show command help panel for this command
		wpide_function_help();
		
	}

}

function token_test(){
	
	var iterator = new TokenIterator(editor.getSession(), range.start.row, range.start.column);
	var current_token_type = iterator.getCurrentToken().type;
	return iterator.getCurrentToken();
}

function wpide_close_autocomplete(){
	if (typeof document.getElementById('ac') != 'undefined') document.getElementById('ac').style.display='none';
	if (typeof oHandler != 'undefined') oHandler.close();
	
	autocompleting = false;
	
	//clear the text in the command help panel
	//jQuery("#wpide_info_content").html("");
}

function selectionChanged(e)  {
    var selected_text = editor.getSession().doc.getTextRange(editor.getSelectionRange());
    
    //check for hex colour match
    if ( selected_text.match('^#?([a-f]|[A-F]|[0-9]){3}(([a-f]|[A-F]|[0-9]){3})?$')  != null ){
        
        var therange = editor.getSelectionRange();
        therange.end.column = therange.start.column;
        therange.start.column = therange.start.column-1;

        // only show color assist if the character before the selection indicates a hex color (#)
	    if ( editor.getSession().doc.getTextRange( therange ) == "#" ){
            jQuery("#wpide_color_assist").show();
	    }
       
    }
}

function wpide_function_help() {
  //mouse over

	try
	{
		var selected_command_item = jQuery("#ac_child a.selected");
		
		
			key = selected_command_item.find("span.ddTitleText").text().replace("()","");
			
			//wordpress autocomplete
			if ( selected_command_item.find("img").attr("src").indexOf("wpac.png")  >= 0){
		
			  if (autocomplete_wordpress[key].desc != undefined){
				
				//compose the param info
				var param_text ="";
				for(i=0; i<autocomplete_wordpress[key].params.length; i++) {
					
					//wrap params in a span to highlight not required
					if (autocomplete_wordpress[key].params[i].required == "no"){
						param_text = param_text + "<span class='wpide_func_arg_notrequired'>" + autocomplete_wordpress[key].params[i]['param'] + "<em>optional</em></span><br /> <br />";
					}else{
						param_text = param_text + autocomplete_wordpress[key].params[i]['param'] + "<br /> <br />";
					}
					
				}
				//compose returns text
				if (autocomplete_wordpress[key].returns.length > 0){
					returns_text = "<br /><br /><strong>Returns:</strong> " + autocomplete_wordpress[key].returns;
				}else{
					returns_text = "";
				}
				
				
				//output command info
				jQuery("#wpide_info_content").html(
								"<strong class='wpide_func_highlight_black'>Function: </strong><strong class='wpide_func_highlight'>" + key  + "(</strong><br />" +
								   "<span class='wpide_func_desc'>" + autocomplete_wordpress[key].desc + "</span><br /><br /><em class='wpide_func_params'>" +
								   param_text + "</em>"+
								   "<strong class='wpide_func_highlight'>)</strong> " +
								   returns_text +
                                   "<p><a href='http://codex.wordpress.org/Function_Reference/" + key  + "' target='_blank'>See " + key  + "() in the WordPress codex</a></p>"
								   );
			  }
			  
			}
			
			//php autocomplete
			if ( selected_command_item.find("img").attr("src").indexOf("phpac.png") >= 0){
		
			  if (autocomplete_php[key].returns != undefined){
				
				//params text
				var param_text ="";
				for(i=0; i<autocomplete_php[key].params.length; i++) {
					
					//wrap params in a span to highlight not required
					if (autocomplete_php[key].params[i].required == "no"){
						param_text = param_text + "<span class='wpide_func_arg_notrequired'>" + autocomplete_php[key].params[i]['param'] + "<em>optional</em></span><br /> <br />";
					}else{
						param_text = param_text + autocomplete_php[key].params[i]['param'] + "<br /> <br />";
					}
					
				}
				//compose returns text
				if (autocomplete_php[key].returns.length > 0){
					returns_text = "<br /><br /><strong>Returns:</strong> " + autocomplete_php[key].returns;
				}else{
					returns_text = "";
				}
				
				jQuery("#wpide_info_content").html(
								"<strong class='wpide_func_highlight_black'>Function: </strong><strong class='wpide_func_highlight'>" + key + "(</strong><br />" +
								   autocomplete_php[key].desc + "<br /><br /><em class='wpide_func_params'>" +
								   param_text + "</em>" +
								   "<strong class='wpide_func_highlight'>)</strong>" +
								   returns_text +
                                   "<p><a href='http://php.net/manual/en/function." + key.replace(/_/g, "-")  + ".php' target='_blank'>See " + key  + "() in the PHP manual</a></p>"
								   );
				
			  }
			  
			}
		
		

	}
      catch(err)
	{
	//Handle errors here
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
            
            //turn autocomplete off initially, then enable as needed
            editor.getSession().enable_autocomplete = false;

            //set the editor mode based on file name
			if (/\.css$/.test(currentFilename)) {
				mode = require("ace/mode/css").Mode;
			}
            else if (/\.less$/.test(currentFilename)) {
				mode = require("ace/mode/less").Mode;
			}
			else if (/\.js$/.test(currentFilename)) {
				mode = require("ace/mode/javascript").Mode;
			}
			else {
				mode = require("ace/mode/php").Mode; //default to PHP
                
                //only enable session change / auto complete for PHP
                if (/\.php$/.test(currentFilename))
                    editor.getSession().enable_autocomplete = true;
			}
			editor.getSession().setMode(new mode());
			
            editor.getSession().on('change', onSessionChange);
            
            editor.getSession().selection.on('changeSelection', selectionChanged);
            
			editor.resize(); 
			editor.focus(); 
			//make a note of current editor
			current_editor_session = clicksesh;
            
            //hide/show the restore button if it's a php file and the restore url is set (i.e saved in this session)
            if ( /\.php$/i.test( currentFilename ) && jQuery(".wpide_tab.active", "#wpide_toolbar").data( "backup" ) != undefined ){
                jQuery("#wpide_toolbar_buttons .button.restore").show();
            }else{
                jQuery("#wpide_toolbar_buttons .button.restore").hide();
            }
            
            //show hide unsaved content message
            if (  jQuery(".wpide_tab.active", "#wpide_toolbar").data( "unsaved" ) ){
                jQuery("#wpide_footer_message_unsaved").html("[ Document contains unsaved content &#9998; ]").show();
            }else{
                jQuery("#wpide_footer_message_unsaved").hide();
            }
            
            //show last saved message if it's been saved
            if ( jQuery(".wpide_tab.active", "#wpide_toolbar").data( "lastsave" ) != undefined){
                jQuery("#wpide_footer_message_last_saved").html("<strong>Last saved: </strong>" + jQuery(".wpide_tab.active", "#wpide_toolbar").data( "lastsave" ) ).show();
            }else{
                jQuery("#wpide_footer_message_last_saved").hide();
            }
            
            //hide the message if we have a fresh tab
            jQuery("#wpide_message").hide();
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
               jQuery( "#" + jQuery(".wpide_tab")[0].id ).click();
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
        var regexchk=/\".*:::.*\"/;
        var saved_when = Date();
        
        if ( regexchk.test(response) ){
            //store the resulting backup file name just incase we need to restore later
            //temp note: you can then access the data like so  jQuery(".wpide_tab.active", "#wpide_toolbar").data( "backup" );
            user_nonce_addition = response.match(/:::(.*)\"$/)[1]; //need this to send with restore request
            jQuery(".wpide_tab.active", "#wpide_toolbar").data( "backup", response.replace(/(^\"|:::.*\"$)/g, "") );
            jQuery(".wpide_tab.active", "#wpide_toolbar").data( "lastsave",  saved_when );
            jQuery(".wpide_tab.active", "#wpide_toolbar").data( "unsaved", false);
            
            if ( /\.php$/i.test( data.filename ) )
                jQuery("#wpide_toolbar_buttons .button.restore").show();
                
            jQuery("#wpide_footer_message_last_saved").html("<strong>Last saved: </strong>" + saved_when).show();
            jQuery("#wpide_footer_message_unsaved").hide();
            
            jQuery("#wpide_message").html('<strong>File saved &#10004;</strong>')
    		.show()
            .delay(2000)
			.fadeOut(600);
        }else{
            alert("error: " + response);
        }
	});	
}

//enter/return command
function selectACitem (item) {
	if( document.getElementById('ac').style.display === 'block' && oHandler.visible() == 'block' ){
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
		
		//clean up the tag/command
		tag = tag.replace(")", ""); //remove end parenthesis
		
		//console.log(tag);
		editor.selection.setSelectionRange(sel);				
		editor.insert(tag);
		
		wpide_close_autocomplete();
	} else {
		editor.insert('\n');
	}
}
	
	
jQuery(document).ready(function($) {
	$("#wpide_save").click(saveDocument);

    // drag and drop colour picker image
    $("#wpide_color_assist").on('drop', function(e) {
        e.preventDefault();
        e.originalEvent.dataTransfer.items[0].getAsString(function(url){
                  
                $(".ImageColorPickerCanvas", $("#side-info-column") ).remove();
                $("img", $("#wpide_color_assist")).attr('src', url );
            
        });
    });
    
    $("#wpide_color_assist").on('dragover', function(e) {
        $(this).addClass("hover");
    }).on('dragleave', function(e) {
        $(this).removeClass("hover");
    });

	
	//add div for ace editor to latch on to
	$('#template').prepend("<div style='width:80%;height:500px;margin-right:0!important;' id='fancyeditordiv'></div>");
	//create the editor instance
	editor = ace.edit("fancyeditordiv");
    //turn off print margin
    editor.setPrintMarginColumn(false);
	//set the editor theme
	editor.setTheme("ace/theme/dawn"); 
	//get a copy of the initial file contents (the file being edited)
	//var intialData = $('#newcontent').val()
	var intialData = "Use the file manager to find a file you wish edit, click the file name to edit. \n\n";
    
    
    //startup info - usefull for debugging
        var data = { action: 'wpide_startup_check', _wpnonce: jQuery('#_wpnonce').val(), _wp_http_referer: jQuery('#_wp_http_referer').val() };
	
		jQuery.post(ajaxurl, data, function(response) {
            if (response == "-1"){
    			intialData = intialData + "Permission/security problem with ajax request. Refresh WPide and try again. \n\n";
			}else{
    		    intialData = intialData + response;
			}
            
            editor.getSession().setValue( intialData );
            
		});
    
	
	
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
				
				//show command help panel for this command
				wpide_function_help();
                //console.log("handler is visible");
				
			}else if( document.getElementById('ac').style.display === 'block'  ) {
				var select=document.getElementById('ac');
				if( select.selectedIndex === 0 ) {
					select.selectedIndex = select.options.length-1;
				} else {
					select.selectedIndex = select.selectedIndex-1;
				}
                 //console.log("ac is visible");
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
				
				//show command help panel for this command
				wpide_function_help();
				
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
	
	
	//click action for new directory/file submit link
	$("#wpide_create_new_directory, #wpide_create_new_file").click(function(e){
		e.preventDefault();
	
		var data_input = jQuery(this).parent().find("input.has_data");
		var item = eval('('+ data_input.attr("rel") +')');
		
		//item.path file|directory
		var data = { action: 'wpide_create_new', path: item.path, type: item.type, file: data_input.val(), _wpnonce: jQuery('#_wpnonce').val(), _wp_http_referer: jQuery('#_wp_http_referer').val() };
	
		jQuery.post(ajaxurl, data, function(response) {
			
			if (response == "1"){
				//remove the file/dir name from the text input
				data_input.val("");
				
				if ( jQuery("ul.jqueryFileTree a[rel='"+ item.path +"']").length == 0){
					
					//if no parent then we are adding something to the wp-content folder so regenerate the whole filetree
					the_filetree();
				
				}
				
				//click the parent once to hide 
				jQuery("ul.jqueryFileTree a[rel='"+ item.path +"']").click();
				
				//hide the parent input block
				data_input.parent().hide();
				
				//click the parent once again to show with new folder and focus on this area
				jQuery("ul.jqueryFileTree a[rel='"+ item.path +"']").click();
				jQuery("ul.jqueryFileTree a[rel='"+ item.path +"']").focus();
				
			}else if (response == "-1"){
				alert("Permission/security problem. Refresh WPide and try again.");
			}else{
				alert(response);
			}
			
			
		});
		
	});

});//end jquery load
