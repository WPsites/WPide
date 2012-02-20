<?php
/*

Plugin Name: WPide
Plugin URI: https://github.com/WPsites/WPide
Description: WordPress code editor for plugins and themes. Adding syntax highlighting, autocomplete of WordPress functions + PHP, line numbers, auto backup of files before editing, tabbed editor.
Version: 2.0
Author: Simon Dunton
Author URI: http://www.wpsites.co.uk

*/



class WPide

{

	public $site_url, $plugin_url;
	
	function __construct() {
	
		//add WPide to the menu
		add_action( 'admin_menu',  array( &$this, 'add_my_menu_page' ) );
		
		//only include this plugin if on theme editor, plugin editor or an ajax call
		if ( $_SERVER['PHP_SELF'] === '/wp-admin/plugin-editor.php' || 
				$_SERVER['PHP_SELF'] === '/wp-admin/theme-editor.php' ||
					$_SERVER['PHP_SELF'] === '/wp-admin/admin-ajax.php' ||
					$_GET['page'] === 'wpide' ){
		
					// Uncomment any of these calls to add the functionality that you need.
					add_action('admin_head', 'WPide::add_admin_head');
					add_action('admin_init', 'WPide::add_admin_js');
					//add_action('admin_head', 'WPide::add_admin_styles');
					
					//setup ajax function to save a backup
					add_action('wp_ajax_ace_backup_call', 'WPide::ace_backup_call');
					//setup ajax function to get file contents for editing 
					add_action('wp_ajax_wpide_get_file', 'WPide::wpide_get_file' );
					//setup ajax function to save file contents
					add_action('wp_ajax_wpide_save_file', 'WPide::wpide_save_file' );
		
		}
		
		$WPide->site_url = get_bloginfo('url');
		

	}



    public static function add_admin_head()
    {

    ?>
	<link rel='stylesheet' href='<?php echo plugins_url("jqueryFileTree.css", __FILE__ );?>' type='text/css' media='all' />
		
      <style type="text/css">
	#quicktags, #post-status-info, #editor-toolbar, #newcontent, .ace_print_margin { display: none; }
    #fancyeditordiv {
	  position: relative;
	  width: 70%;
	  height: 400px;
	}
	#template div{margin-right:0 !important;}
	
	#wpide_toolbar{
		width:75%;
		height:30px;
		text-align:right;
		padding-top:20px;
		position:relative;
	}
	#wpide_toolbar form{
		position: absolute;
		left: 104%;
	}
	#wpide_toolbar_tabs{
		width:75%;
	}
	
	#wpide_toolbar .wpide_tab {
		height: 18px;
		font: 13px/18px Arial,Helvetica,sans-serif normal;
		margin-top: -2px;
		margin-right: 2px;
		padding: 6px;
		float: left;
		cursor: pointer;
		border-width: 1px;
		border-style: solid;
		-webkit-border-top-right-radius: 3px;
		-webkit-border-top-left-radius: 3px;
		border-top-right-radius: 3px;
		border-top-left-radius: 3px;
		background-color: #E8E8E8;
		border-color: #DFDFDF #DFDFDF #CCC;
		color: #21759B;
		text-decoration: none;
		font-style: italic;
	}
	
	#wpide_file_browser{
		margin-right:2%;
		text-align:left;
	}
	
	.toplevel_page_wpide #submitdiv{
		 width:22%;
		 float:right;
	 }
	 .toplevel_page_wpide #submitdiv h3.hndle{
	 	font-family: Georgia,"Times New Roman";
		font-size: 15px;
		font-weight: bold;
		padding: 7px 10px;
		margin: 0;
		line-height: 1;
	 }
	 
	 #wpide_toolbar_buttons {
	 	position:relative;
	 	min-height:30px;
		width:75%;
		float:left;
		text-align:left;
		overflow:hidden;
		border-bottom: 1px solid #CCC;
		background-color: #E9E9E9;
		background-image: -ms-linear-gradient(bottom,#ddd,#e9e9e9);
		background-image: -moz-linear-gradient(bottom,#ddd,#e9e9e9);
		background-image: -o-linear-gradient(bottom,#ddd,#e9e9e9);
		background-image: -webkit-linear-gradient(bottom,#ddd,#e9e9e9);
		background-image: linear-gradient(bottom,#ddd,#e9e9e9);
		
		-webkit-border-top-right-radius: 6px;
		-webkit-border-top-left-radius: 0px;
		border-top-right-radius: 6px;
		border-top-left-radius: 0px;
		}
		
		#wpide_toolbar_buttons a{
			display:block;
			float:left;
			margin:6px;
		}
		
		#wpwrap div.ace_gutter{
			background-color:#f4f4f4;
			color:#aaa;
		}
	
    </style>

    <?php

    }




















    public static function add_admin_js()
    {
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
		// php-completion tags
		wp_enqueue_script('wpide-php-completion', plugins_url("js/php-completion.js", __FILE__ ) );
		// load editor
		wp_enqueue_script('wpide-load-editor', plugins_url("js/load-editor.js", __FILE__ ) );
    }


	public static function ace_backup_call() {

		$backup_path =  $WPide->site_url .'/wp-content/plugins/' . basename(dirname(__FILE__)) .'/backups/';
		$file_name = stripslashes($_POST['filename']);
		$edit_type = $_POST['edittype'];

		if ($edit_type==='theme'){
				$theme_root = get_theme_root();
				$short_path = str_replace($theme_root, '', $file_name);

				$new_file_path_daily = WP_PLUGIN_DIR.'/wpide/backups/themes'.$short_path.'.'.date("Ymd");
				$new_file_path_hourly = WP_PLUGIN_DIR.'/wpide/backups/themes'.$short_path.'.'.date("YmdH");

				$new_file_info = pathinfo($new_file_path_daily);

				if (!is_dir($new_file_info['dirname'])) mkdir($new_file_info['dirname'], 0777, true); //make directory if not exist

				//check for todays backup if non existant then create
				if (!file_exists($new_file_path_daily)){
					$backup_result = copy($file_name, $new_file_path_daily); //make a copy of the file

				//check for a backup this hour if doesn't exist then create
				}else if(!file_exists($new_file_path_hourly)){
                    $backup_result = copy($file_name, $new_file_path_hourly); //make a copy of the file
				}
				
				

				//do no further backups since one intial backup for today and an hourly one is plenty!

		}else if ($edit_type==='plugin'){

				$plugin_root = WP_PLUGIN_DIR;
				$short_path = str_replace($plugin_root, '', $file_name);

				$new_file_path_daily = WP_PLUGIN_DIR.'/wpide/backups/plugins/'.$short_path.'.'.date("Ymd");
				$new_file_path_hourly = WP_PLUGIN_DIR.'/wpide/backups/plugins/'.$short_path.'.'.date("YmdH");

				$new_file_info = pathinfo($new_file_path_daily);

				if (!is_dir($new_file_info['dirname'])) mkdir($new_file_info['dirname'], 0777, true); //make directory if not exist

				//check for todays backup if non existant then create
				if (!file_exists($new_file_path_daily)){
					$backup_result = copy($plugin_root.'/'.$file_name, $new_file_path_daily); //make a copy of the file
						
				//check for a backup this hour if doesn't exist then create
				}else if(!file_exists($new_file_path_hourly)){
					$backup_result = copy($plugin_root.'/'.$file_name, $new_file_path_hourly); //make a copy of the file

				}

				//do no further backups since one intial backup for today and an hourly one is plenty!

		}

		if ($backup_result){
			echo "success";
		}

		//echo "final debug info : " . WP_PLUGIN_DIR.'/wpide/backups/'.$short_path.'.backup';
		die(); // this is required to return a proper result

	}
	
	public static function wpide_get_file() {

		$file_name = stripslashes($_POST['filename']);
		echo file_get_contents($file_name);
		die(); // this is required to return a proper result
	}
	
	public static function wpide_save_file() {

		$file_name = stripslashes($_POST['filename']);
		
		//set backup filename
		$backup_path =  ABSPATH .'wp-content/plugins/' . basename(dirname(__FILE__)) .'/backups/' . str_replace( str_replace('\\', "/", ABSPATH), '', $file_name) .'.'.date("YmdH");
		//create backup directory if not there
		$new_file_info = pathinfo($backup_path);
		if (!is_dir($new_file_info['dirname'])) mkdir($new_file_info['dirname'], 0777, true); //make directory if not exist
		//do backup
		file_put_contents($backup_path,  file_get_contents($file_name) );
		
		if( file_put_contents($file_name, stripslashes($_POST['content'])  ) ) echo "success";
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
			$('#wpide_file_browser').fileTree({ root: '<?php echo str_replace('\\', "/", WP_CONTENT_DIR);?>/', script: '<?php echo plugins_url("jqueryFileTree.php", __FILE__ );?>' }, function(file) {
				wpide_set_file_contents(file);
				$('#filename').val(file);
			});
		});
		</script>
		
		
			
<div id="wpide_toolbar" class="quicktags-toolbar"> 
  <div id="wpide_toolbar_tabs"> </div>
  <form action="" method="get">
    <a href="#" id="wpide_save" class="button-primary" style="margin-right:25px;">SAVE 
    FILE</a> 
    <input type="hidden" id="filename" name="filename" value="" />
  </form>
</div>
			
<div id="wpide_toolbar_buttons"> 
  <div id="wpide_message" class="error highlight" style="display:none;width: 97%;position: absolute;top: 1px;left: 2px;text-align:left;margin:0;padding:5px;"></div>
  <a href="#"></a> <a href="#"></a> </div>
			
			
<div style='width:75%;height:650px;margin-right:0!important;float:left;' id='fancyeditordiv'></div>	
			
			
			<div id="submitdiv" class="postbox "> 
			  <div class="handlediv" title="Click to toggle"><br>
			  </div>
			  <h3 class="hndle"><span>Files</span></h3>
			  <div class="inside"> 
				<div class="submitbox" id="submitpost"> 
				  <div id="minor-publishing"> 
				  </div>
				  <div id="major-publishing-actions"> 
					<div id="wpide_file_browser"></div>
					<br style="clear:both;" />
					<div id="publishing-action"> <img src="/wp-admin/images/wpspin_light.gif" class="ajax-loading" id="ajax-loading" alt="" style="visibility: hidden; "> 
					  <input name="save" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="Update">
					</div>
					<div class="clear"></div>
				  </div>
				</div>
			  </div>
			</div>


			
		<?php
	}

}


add_action("init", create_function('', 'new WPide();'));
?>
