<?php
/*

Plugin Name: WPide
Plugin URI: https://github.com/WPsites/WPide
Description: Replace the default WordPress code editor for plugins and themes. Adding syntax highlighting, autocomplete of WordPress functions + PHP, line numbers, auto backup of files before editing.
Version: 1.0.4
Author: Simon Dunton
Author URI: http://www.wpsites.co.uk

*/



class WPide

{


	function __construct() {

		// Uncomment any of these calls to add the functionality that you need.
		add_action('admin_head', 'WPide::add_admin_head');
		add_action('admin_init', 'WPide::add_admin_js');

		//setup ajax function to save a backup
		add_action('wp_ajax_ace_backup_call', 'WPide::ace_backup_call');

	}


    public static function add_admin_head()
    {

    ?>

      <style type="text/css">
	#quicktags, #post-status-info, #editor-toolbar, #newcontent, .ace_print_margin { display: none; }
    #fancyeditordiv {
	  position: relative;
	  width: 500px;
	  height: 400px;
	}
	#template div{margin-right:0 !important;}
    </style>

    <?php

    }

    public static function add_admin_js()
    {
        $plugin_path =  get_bloginfo('url').'/wp-content/plugins/' . basename(dirname(__FILE__)) .'/';
		//include ace
        wp_enqueue_script('ace', $plugin_path . 'ace-0.2.0/src/ace.js');
		//include ace modes for css, javascript & php
		wp_enqueue_script('ace-mode-css', $plugin_path . 'ace-0.2.0/src/mode-css.js');
		wp_enqueue_script('ace-mode-javascript', $plugin_path . 'ace-0.2.0/src/mode-javascript.js');
        wp_enqueue_script('ace-mode-php', $plugin_path . 'ace-0.2.0/src/mode-php.js');
		//include ace theme
		wp_enqueue_script('ace-theme', $plugin_path . 'ace-0.2.0/src/theme-dawn.js');//monokai is nice
		// html tags for completion
		wp_enqueue_script('wpide-editor-completion', $plugin_path . 'js/html-tags.js');
		// load & prepare editor
		wp_enqueue_script('wpide-editor-load', $plugin_path . 'js/load-editor.js');
    }


	public static function ace_backup_call() {

		$backup_path =  get_bloginfo('url').'/wp-content/plugins/' . basename(dirname(__FILE__)) .'/backups/';
		$file_name = $_POST['filename'];
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

}

//only include this plugin if on theme or plugin editors (Or Multisite network equivalents) or an ajax call
$is_ms = '';
if ( is_multisite() ) 
	$is_ms = 'network/';

if ( $_SERVER['PHP_SELF'] === '/wp-admin/' . $is_ms . 'plugin-editor.php' || 
		$_SERVER['PHP_SELF'] === '/wp-admin/' . $is_ms . 'theme-editor.php' ||
			$_SERVER['PHP_SELF'] === '/wp-admin/admin-ajax.php' ){

	add_action( 'init', create_function( '', 'new WPide();' ) );
}
?>