<?php
/*
 Plugin Name: WPide
Plugin URI: https://github.com/WPsites/WPide
Description: WordPress code editor with auto completion of both WordPress and PHP functions with reference, syntax highlighting, line numbers, tabbed editing, automatic backup.
Version: 2.0.2
Author: Simon Dunton
Author URI: http://www.wpsites.co.uk
*/



class WPide2

{

	public $site_url, $plugin_url;
	
	function __construct() {
	
		//add WPide to the menu
		add_action( 'admin_menu',  array( &$this, 'add_my_menu_page' ) );
		
		//hook for processing incoming image saves
		if ( isset($_GET['wpide_save_image']) ){
			
			//force local file method for testing - you could force other methods 'direct', 'ssh', 'ftpext' or 'ftpsockets'
			define('FS_METHOD', 'direct');
			
			add_action('admin_init', array($this, 'wpide_save_image'));
			
		}
		
		//only include this plugin if on theme editor, plugin editor or an ajax call
		if ( $_SERVER['PHP_SELF'] === '/wp-admin/admin-ajax.php' ||
			$_GET['page'] === 'wpide' ){
                
			//force local file method for testing - you could force other methods 'direct', 'ssh', 'ftpext' or 'ftpsockets'
			define('FS_METHOD', 'direct'); 

			// Uncomment any of these calls to add the functionality that you need.
			//add_action('admin_head', 'WPide2::add_admin_head');
			add_action('admin_init', 'WPide2::add_admin_js');
			add_action('admin_init', 'WPide2::add_admin_styles');
			
			//setup jqueryFiletree list callback
			add_action('wp_ajax_jqueryFileTree', 'WPide2::jqueryFileTree_get_list');
			//setup ajax function to get file contents for editing 
			add_action('wp_ajax_wpide_get_file', 'WPide2::wpide_get_file' );
			//setup ajax function to save file contents and do automatic backup if needed
			add_action('wp_ajax_wpide_save_file', 'WPide2::wpide_save_file' );
			//setup ajax function to create new item (folder, file etc)
			add_action('wp_ajax_wpide_create_new', 'WPide2::wpide_create_new' );
			
			//setup ajax function to create new item (folder, file etc)
			add_action('wp_ajax_wpide_image_edit_key', 'WPide2::wpide_image_edit_key' );
			
			
			
		
		}
		

		

		
		$WPide->site_url = get_bloginfo('url');
		

	}



	public static function add_admin_head()
	{
    
	}






	public static function add_admin_js(){
		
	    $plugin_path =  plugin_dir_url( __FILE__ );
		    //include file tree
		    wp_enqueue_script('jquery-file-tree', plugins_url("jqueryFileTree.js", __FILE__ ) );
		    //include ace
		    wp_enqueue_script('ace', plugins_url("ace-0.2.0/src/ace.js", __FILE__ ) );
		    //include ace modes for css, javascript & php
		    wp_enqueue_script('ace-mode-css', $plugin_path . 'ace-0.2.0/src/mode-css.js');
		    wp_enqueue_script('ace-mode-javascript', $plugin_path . 'ace-0.2.0/src/mode-javascript.js');
		    wp_enqueue_script('ace-mode-php', $plugin_path . 'ace-0.2.0/src/mode-php.js');
		    //include ace theme
		    wp_enqueue_script('ace-theme', plugins_url("ace-0.2.0/src/theme-dawn.js", __FILE__ ) );//monokai is nice
		    // wordpress-completion tags
		    wp_enqueue_script('wpide-wordpress-completion', plugins_url("js/autocomplete.wordpress.js", __FILE__ ) );
		    // php-completion tags
		    wp_enqueue_script('wpide-php-completion', plugins_url("js/autocomplete.php.js", __FILE__ ) );
		    // load editor
		    wp_enqueue_script('wpide-load-editor', plugins_url("js/load-editor.js", __FILE__ ) );
		    // load autocomplete dropdown 
		    wp_enqueue_script('wpide-dd', plugins_url("js/jquery.dd.js", __FILE__ ) );
		    
		     // load jquery ui  
		    wp_enqueue_script('jquery-ui', plugins_url("js/jquery-ui-1.8.20.custom.min.js", __FILE__ ), array('jquery'),  '1.8.20');
		    
		   
    
    
	}
    
	public static function add_admin_styles(){

		//main wpide styles
		 wp_register_style( 'wpide_style', plugins_url('wpide.css', __FILE__) );
		 wp_enqueue_style( 'wpide_style' );
		 //filetree styles
		 wp_register_style( 'wpide_filetree_style', plugins_url('jqueryFileTree.css', __FILE__) );
		 wp_enqueue_style( 'wpide_filetree_style' );
		 //autocomplete dropdown styles
		 wp_register_style( 'wpide_dd_style', plugins_url('dd.css', __FILE__) );
		 wp_enqueue_style( 'wpide_dd_style' );
		 
		 //jquery ui styles
		 wp_register_style( 'wpide_jqueryui_style', plugins_url('css/flick/jquery-ui-1.8.20.custom.css', __FILE__) );
		 wp_enqueue_style( 'wpide_jqueryui_style' );
	
		
	}
    
	
	
	public static function jqueryFileTree_get_list() {
		//check the user has the permissions
		check_admin_referer('plugin-name-action_wpidenonce'); 
		if ( !current_user_can('edit_themes') )
			wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
		
		//setup wp_filesystem api
		global $wp_filesystem;
		if ( ! WP_Filesystem($creds) ) 
		    return false;
        
		$_POST['dir'] = urldecode($_POST['dir']);
		$root = WP_CONTENT_DIR;
		
		if( $wp_filesystem->exists($root . $_POST['dir']) ) {
			//$files = scandir($root . $_POST['dir']);
			//print_r($files);
			$files = $wp_filesystem->dirlist($root . $_POST['dir']);
			//print_r($files);
            
			echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
			if( count($files) > 0 ) { 
				
				// All dirs
				foreach( $files as $file => $file_info ) {
					if( $file != '.' && $file != '..' && $file_info['type']=='d' ) {
						echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "/\">" . htmlentities($file) . "</a></li>";
					}
				}
				// All files
				foreach( $files as $file => $file_info ) {
					if( $file != '.' && $file != '..' &&  $file_info['type']!='d') {
						$ext = preg_replace('/^.*\./', '', $file);
						echo "<li class=\"file ext_$ext\"><a href=\"#\" rel=\"" . htmlentities($_POST['dir'] . $file) . "\">" . htmlentities($file) . "</a></li>";
					}
				}
			}
			//output toolbar for creating new file, folder etc
			echo "<li class=\"create_new\"><a class='new_directory' title='Create a new directory here.' href=\"#\" rel=\"{type: 'directory', path: '" . htmlentities($_POST['dir']) . "'}\"></a> <a class='new_file' title='Create a new file here.' href=\"#\" rel=\"{type: 'file', path: '" . htmlentities($_POST['dir']) . "'}\"></a><br style='clear:both;' /></li>";
			echo "</ul>";	
		}
	
		die(); // this is required to return a proper result
	}

	
	public static function wpide_get_file() {
		//check the user has the permissions
		check_admin_referer('plugin-name-action_wpidenonce'); 
		if ( !current_user_can('edit_themes') )
			wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
		
		//setup wp_filesystem api
		global $wp_filesystem;
		if ( ! WP_Filesystem($creds) ) 
		    return false;
        
         
		$root = WP_CONTENT_DIR;
		$file_name = $root . stripslashes($_POST['filename']);
		echo $wp_filesystem->get_contents($file_name);
		die(); // this is required to return a proper result
	}
	
	
	
	public static function wpide_image_edit_key() {
		
		//check the user has the permissions
		check_admin_referer('plugin-name-action_wpidenonce'); 
		if ( !current_user_can('edit_themes') )
			wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
		
		//create a nonce based on the image path
		echo wp_create_nonce( 'wpide_image_edit' . $_POST['file'] );
		
	}
	
	public static function wpide_create_new() {
		//check the user has the permissions
		check_admin_referer('plugin-name-action_wpidenonce'); 
		if ( !current_user_can('edit_themes') )
			wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
		
		//setup wp_filesystem api
		global $wp_filesystem;
		if ( ! WP_Filesystem($creds) ) 
		    return false;
		
		$root = WP_CONTENT_DIR;
        
		//check all required vars are passed
		if (strlen($_POST['path'])>0 && strlen($_POST['type'])>0 && strlen($_POST['file'])>0){
			
			
			$filename = sanitize_file_name( $_POST['file'] );
			$path = $_POST['path'];
			
			if ($_POST['type'] == "directory"){
				
				$write_result = $wp_filesystem->mkdir($root . $path . $filename, FS_CHMOD_DIR);
				
				if ($write_result){
					die("1"); //created
				}else{
					echo "Problem creating directory" . $root . $path . $filename;
				}
				
			}else if ($_POST['type'] == "file"){
				
				$write_result = $wp_filesystem->put_contents(
					$root . $path . $filename,
					' ',
					FS_CHMOD_FILE // predefined mode settings for WP files
				);
				
				if ($write_result){
					die("1"); //created
				}else{
					echo "Problem creating file " . $root . $path . $filename;
				}
				
			}
			
			
			//print_r($_POST);
			
				
		}
		echo "0";
		die(); // this is required to return a proper result
	}
	
	public static function wpide_save_file() {
		//check the user has the permissions
		check_admin_referer('plugin-name-action_wpidenonce'); 
		if ( !current_user_can('edit_themes') )
		wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
        
		//setup wp_filesystem api
		global $wp_filesystem;
		if ( ! WP_Filesystem($creds) ) 
		    echo "Cannot initialise the WP file system API";
		
		//save a copy of the file and create a backup just in case
		$root = WP_CONTENT_DIR;
		$file_name = $root . stripslashes($_POST['filename']);
		
		//set backup filename
		$backup_path =  ABSPATH .'wp-content/plugins/' . basename(dirname(__FILE__)) .'/backups/' . str_replace( str_replace('\\', "/", ABSPATH), '', $file_name) .'.'.date("YmdH");
		//create backup directory if not there
		$new_file_info = pathinfo($backup_path);
		if (!$wp_filesystem->is_dir($new_file_info['dirname'])) wp_mkdir_p( $new_file_info['dirname'] ); //should use the filesytem api here but there isn't a comparable command right now
		
		//do backup
		$wp_filesystem->copy( $file_name, $backup_path );
        
		//save file
		if( $wp_filesystem->put_contents( $file_name, stripslashes($_POST['content'])) ) {
			$result = "success";
		}
		
		die($result); // this is required to return a proper result
	}
	
	public static function wpide_save_image() {
		
			$filennonce = split("::", $_POST["opt"]); //file::nonce
			
			//check the user has a valid nonce
			//we are checking two variations of the nonce, one as-is and another that we have removed a trailing zero from
			//this is to get around some sort of bug where a nonce generated on another page has a trailing zero and a nonce generated/checked here doesn't have the zero
			if (! wp_verify_nonce( $filennonce[1], 'wpide_image_edit' . $filennonce[0]) &&
			    ! wp_verify_nonce( rtrim($filennonce[1], "0") , 'wpide_image_edit' . $filennonce[0])) {
				die('Security check'); //die because both checks failed
			}
			//check the user has the permissions
			if ( !current_user_can('edit_themes') )
			wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
		
	
			$_POST['content'] = base64_decode($_POST["data"]); //image content
			$_POST['filename'] = $filennonce[0]; //filename
			
			//setup wp_filesystem api
			global $wp_filesystem;
			
			if ( ! WP_Filesystem($creds) ) 
			    echo "Cannot initialise the WP file system API";
			
			//save a copy of the file and create a backup just in case
			$root = WP_CONTENT_DIR;
			$file_name = $root . stripslashes($_POST['filename']);
			
			//set backup filename
			$backup_path =  ABSPATH .'wp-content/plugins/' . basename(dirname(__FILE__)) .'/backups/' . str_replace( str_replace('\\', "/", ABSPATH), '', $file_name) .'.'.date("YmdH");
			//create backup directory if not there
			$new_file_info = pathinfo($backup_path);
			if (!$wp_filesystem->is_dir($new_file_info['dirname'])) wp_mkdir_p( $new_file_info['dirname'] ); //should use the filesytem api here but there isn't a comparable command right now
			
			//do backup
			$wp_filesystem->move( $file_name, $backup_path );
			
		
			//save file
			if( $wp_filesystem->put_contents( $file_name, $_POST['content']) ) {
				$result = "success";
			}
			
			if ($result == "success"){
				wp_die('<p>'.__('<strong>Image saved.</strong> <br />You may <a href="JavaScript:window.close();">close this window / tab</a>.').'</p>');
			}else{
				wp_die('<p>'.__('<strong>Problem saving image.</strong> <br /><a href="JavaScript:window.close();">Close this window / tab</a> and try editing the image again.').'</p>');
			}
			//print_r($_POST);
	
		
		//return;
	}
	
	
	public function add_my_menu_page() {
		//add_menu_page("wpide", "wpide","edit_themes", "wpidesettings", array( &$this, 'my_menu_page') );
		add_menu_page('WPide', 'WPide', 'edit_themes', "wpide", array( &$this, 'my_menu_page' ));
	}
	
	public function my_menu_page() {
		if ( !current_user_can('edit_themes') )
		wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
		
		$app_url = get_bloginfo('url'); //need to make this https if we are currently looking on the site using https (even though https for admin might not be forced it can still cause issues)
		if (is_ssl()) $app_url = str_replace("http:", "https:", $app_url);
		
		?>
		<script>

			var wpide_app_path = "<?php echo plugin_dir_url( __FILE__ ); ?>";
			
			function the_filetree() {
				jQuery('#wpide_file_browser').fileTree({ script: ajaxurl }, function(parent, file) {
	
				    if ( jQuery(parent).hasClass("create_new") ){ //create new file/folder
					//to create a new item we need to know the name of it so show input
					
					var item = eval('('+file+')');
					
					//hide all inputs just incase one is selected
					jQuery(".new_item_inputs").hide();
					//show the input form for this
					jQuery("div.new_" + item.type).show();
					jQuery("div.new_" + item.type + " input[name='new_" + item.type + "']").focus();
					jQuery("div.new_" + item.type + " input[name='new_" + item.type + "']").attr("rel", file);
				
					
				    }else if ( jQuery(".wpide_tab[rel='"+file+"']").length > 0) {  //focus existing tab
					jQuery(".wpide_tab[sessionrel='"+ jQuery(".wpide_tab[rel='"+file+"']").attr("sessionrel") +"']").click();//focus the already open tab
				    }else{ //open file
					
					var image_patern =new RegExp("(\.jpg|\.gif|\.png|\.bmp)");
					if ( image_patern.test(file) ){
						//it's an image so open it for editing
						
						//using modal+iframe
						if ("lets not" == "use the modal for now"){
							
						 var NewDialog = jQuery('<div id="MenuDialog">\
							<iframe src="http://www.sumopaint.com/app/?key=ebcdaezjeojbfgih&target=<?php echo get_bloginfo('url') . "?action=wpide_image_save";?>&url=<?php echo get_bloginfo('url') . "/wp-content";?>' + file + '&title=Edit image&service=Save back to WPide" width="100%" height="600px"> </iframe>\
						    </div>');
						    NewDialog.dialog({
							modal: true,
							title: "title",
							show: 'clip',
							hide: 'clip',
							width:'800',
							height:'600'
						    });
    
						}else{ //open in new tab/window
							
							var data = { action: 'wpide_image_edit_key', file: file, _wpnonce: jQuery('#_wpnonce').val(), _wp_http_referer: jQuery('#_wp_http_referer').val() };
							var image_data = '';
							jQuery.ajaxSetup({async:false}); //we need to wait until we get the response before opening the window
							jQuery.post(ajaxurl, data, function(response) {
								
								//with the response (which is a nonce), build the json data to pass to the image editor. The edit key (nonce) is only valid to edit this image
								image_data = file+'::'+response;
								
							});
							
							
							window.open('http://www.sumopaint.com/app/?key=ebcdaezjeojbfgih&url=<?php echo $app_url. "/wp-content";?>' + file + '&opt=' + image_data + '&title=Edit image&service=Save back to WPide&target=<?php echo urlencode( $app_url . "/wp-admin/admin.php?wpide_save_image=yes" ) ;?>');
						
						}
				    
					}else{
						jQuery(parent).addClass('wait');
						 
						wpide_set_file_contents(file, function(){
								
								//once file loaded remove the wait class/indicator
								jQuery(parent).removeClass('wait');
								
							});
						
						jQuery('#filename').val(file);
					}
					 
				    }
				    
				});
			}
			
			jQuery(document).ready(function($) {
				// Handler for .ready() called.
				the_filetree() ;
				
				
			
				
				
				
			});
		</script>
		
		<?php
		$url = wp_nonce_url('admin.php?page=wpide','plugin-name-action_wpidenonce');
		if ( ! WP_Filesystem($creds) ) {
		    request_filesystem_credentials($url, '', true, false, null);
			return;
		}
		?>
		
		<div id="poststuff" class="metabox-holder has-right-sidebar">
		
			<div id="side-info-column" class="inner-sidebar">
				
				<div id="wpide_info"><div id="wpide_info_content"></div> </div>
		
				<div id="submitdiv" class="postbox "> 
				  <h3 class="hndle"><span>Files</span></h3>
				  <div class="inside"> 
					<div class="submitbox" id="submitpost"> 
					  <div id="minor-publishing"> 
					  </div>
					  <div id="major-publishing-actions"> 
						<div id="wpide_file_browser"></div>
						<br style="clear:both;" />
						<div class="new_file new_item_inputs">
							<label for="new_folder">File name</label><input class="has_data" name="new_file" type="text" rel="" value="" placeholder="Filename.ext" />
							<a href="#" id="wpide_create_new_file" class="button-primary">CREATE</a>
						</div>
						<div class="new_directory new_item_inputs">
							<label for="new_directory">Directory name</label><input class="has_data" name="new_directory" type="text" rel="" value="" placeholder="Filename.ext" />
							<a href="#" id="wpide_create_new_directory" class="button-primary">CREATE</a>
						</div>
						<div class="clear"></div>
					  </div>
					</div>
				  </div>
				</div>
				
				
			</div>
		
			<div id="post-body">			
				<div id="wpide_toolbar" class="quicktags-toolbar"> 
				  <div id="wpide_toolbar_tabs"> </div>
				  <div id="dialog_window_minimized_container"></div>
				</div>
							
				<div id="wpide_toolbar_buttons"> 
				  <div id="wpide_message" class="error highlight"></div>
				  <a href="#"></a> <a href="#"></a> </div>
							
							
				<div id='fancyeditordiv'></div>
				
				<form id="wpide_save_container" action="" method="get">
				   <a href="#" id="wpide_save" class="button-primary">SAVE 
				   FILE</a> 
				   <input type="hidden" id="filename" name="filename" value="" />
				       <?php
				       if ( function_exists('wp_nonce_field') )
					   wp_nonce_field('plugin-name-action_wpidenonce');
				       ?>
				 </form>
			</div>	
				
			
		
		</div>
			
		<?php
	}

}
add_action("init", create_function('', 'new WPide2();'));
?>
