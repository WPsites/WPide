<?php
/*
Plugin Name: WPide V2 Dev
Plugin URI: https://github.com/WPsites/WPide
Description: WordPress code editor for plugins and themes. Adding syntax highlighting, autocomplete of WordPress functions + PHP, line numbers, auto backup of files before editing, tabbed editor.
Version: 2.0
Author: Simon Dunton
Author URI: http://www.wpsites.co.uk
*/



class WPide2

{

	public $site_url, $plugin_url;
	
	function __construct() {
	
		//add WPide to the menu
		add_action( 'admin_menu',  array( &$this, 'add_my_menu_page' ) );
		
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
            
			if( count($files) > 2 ) { /* The 2 accounts for . and .. */
				echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
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
				echo "</ul>";	
			}
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
		if (!$wp_filesystem->is_dir($new_file_info['dirname'])) $wp_filesystem->mkdir($new_file_info['dirname'], 0775);
		
		//do backup
		$wp_filesystem->copy( $file_name, $backup_path );
        
		//save file
		if( $wp_filesystem->put_contents( $file_name, stripslashes($_POST['content'])) ) echo "success";
		die(); // this is required to return a proper result
	}
	
	public function add_my_menu_page() {
		//add_menu_page("wpide", "wpide","edit_themes", "wpidesettings", array( &$this, 'my_menu_page') );
		add_menu_page('wpide', 'wpide', 'edit_themes', "wpide", array( &$this, 'my_menu_page' ));
	}
	
	public function my_menu_page() {
		if ( !current_user_can('edit_themes') )
		wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
		
		?>
		<script>

			jQuery(document).ready( function($) {
				$('#wpide_file_browser').fileTree({ script: ajaxurl }, function(parent, file) {
	
				    if ( $(".wpide_tab[rel='"+file+"']").length > 0) { 
					$(".wpide_tab[sessionrel='"+ $(".wpide_tab[rel='"+file+"']").attr("sessionrel") +"']").click();//focus the already open tab
				    }else{
					
					var image_patern =new RegExp("(\.jpg|\.gif|\.png|\.bmp)$");
					if ( image_patern.test(file) ){
						alert("Image editing is not currently available. It's a planned feature using http://pixlr.com/");
					}else{
						$(parent).addClass('wait');
						 
						wpide_set_file_contents(file, function(){
								
								//once file loaded remove the wait class/indicator
								$(parent).removeClass('wait');
								
							});
						
						$('#filename').val(file);
					}
					 
				    }
				    
				});
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
						<div id="publishing-action"> <img src="/wp-admin/images/wpspin_light.gif" class="ajax-loading" id="ajax-loading" alt="">
						  <input name="save" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="Update">
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
