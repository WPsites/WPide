<?php
/*

Plugin Name: WPide
Plugin URI: https://github.com/WPsites/WPide
Description: Replace the default WordPress code editor for plugins and themes. Adding syntax highlighting, autocomplete of WordPress functions + PHP, line numbers, auto backup of files before editing.
Version: 1.0.6
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

		
	add_filter("plugin_row_meta", 'wpide_dev_links', 10, 2);

	function wpide_dev_links($links, $file) {
	    static $this_plugin;
	 
	    if (!$this_plugin) {
		$this_plugin = plugin_basename(__FILE__);
	    }
	 
	    // check to make sure we are on the correct plugin
	   if ($file === $this_plugin) {
		// the anchor tag and href to the URL we want. For a "Settings" link, this needs to be the url of your settings page
		$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/plugins.php?page=install-required-plugins" style="font-weight:bold;">Download and install V2 Development version</a>';
		// add the link to the list
		array_push($links, $settings_link);
	   }
	    return $links;
	}






/**
 * Include the TGM_Plugin_Activation class.
 */
require_once dirname( __FILE__ ) . '/tgm-plugin-activation/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'wpide_run_dev' );
/**
 * Register the required plugins for this theme.
 *
 * In this example, we register two plugins - one included with the TGMPA library
 * and one from the .org repo.
 *
 * The variable passed to tgmpa_register_plugins() should be an array of plugin
 * arrays.
 *
 * This function is hooked into tgmpa_init, which is fired within the
 * TGM_Plugin_Activation class constructor.
 */
function wpide_run_dev() {

	/**
	 * Array of plugin arrays. Required keys are name and slug.
	 * If the source is NOT from the .org repo, then source is also required.
	 */
	$plugins = array(

		// This is an example of how to include a plugin from the WordPress Plugin Repository
		array(
			'name' 		=> 'WPide V2 Dev',
			'slug' 		=> 'WPideV2',
			'version' 	=> '2.0', 
			'required' 	=> false,
			'external_url' 	=> 'https://github.com/WPsites/WPide/tree/v2dev',
			'source'    => 'https://github.com/WPsites/WPide/zipball/v2dev',
		),

	);

	// Change this to your theme text domain, used for internationalising strings
	$theme_text_domain = 'tgmpa';

	/**
	 * Array of configuration settings. Amend each line as needed.
	 * If you want the default strings to be available under your own theme domain,
	 * leave the strings uncommented.
	 * Some of the strings are added into a sprintf, so see the comments at the
	 * end of each line for what each argument will be.
	 */
	$config = array(
		'domain'       		=> $theme_text_domain,         	// Text domain - likely want to be the same as your theme.
		'default_path' 		=> '',                         	// Default absolute path to pre-packaged plugins
		'parent_menu_slug' 	=> 'plugins.php', 				// Default parent menu slug
		'parent_url_slug' 	=> 'plugins.php', 				// Default parent URL slug
		'menu'         		=> 'install-required-plugins', 	// Menu slug
		'has_notices'      	=> true,                       	// Show admin notices or not
		'is_automatic'    	=> true,					   	// Automatically activate plugins after installation or not
		'message' 			=> '',							// Message to output right before the plugins table
		'strings'      		=> array(
			'page_title'                       			=> __( 'Install Suggested Plugins', $theme_text_domain ),
			'menu_title'                       			=> __( 'Install Plugins', $theme_text_domain ),
			'installing'                       			=> __( 'Installing Plugin: %s', $theme_text_domain ), // %1$s = plugin name
			'oops'                             			=> __( 'Something went wrong with the plugin API.', $theme_text_domain ),
			'notice_can_install_required'     			=> _n_noop( 'This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.' ), // %1$s = plugin name(s)
			'notice_can_install_recommended'			=> _n_noop( 'The V2 Development branch of WPide is available for testing - With new features such as multi tab editing.<br />Word of warning: this is the cutting edge development version which could contain bugs so use at your own risk! ', 'WPide recommends the following plugins: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_install'  					=> _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.' ), // %1$s = plugin name(s)
			'notice_can_activate_required'    			=> _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.' ), // %1$s = plugin name(s)
			'notice_can_activate_recommended'			=> _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_activate' 					=> _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.' ), // %1$s = plugin name(s)
			'notice_ask_to_update' 						=> _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.' ), // %1$s = plugin name(s)
			'notice_cannot_update' 						=> _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.' ), // %1$s = plugin name(s)
			'install_link' 					  			=> _n_noop( 'Begin installing the development version of WPide', 'Begin installing plugins' ),
			'activate_link' 				  			=> _n_noop( 'Activate WPide V2 Dev', 'Activate installed plugins' ),
			'return'                           			=> __( 'Return to Suggested Plugins Installer', $theme_text_domain ),
			'plugin_activated'                 			=> __( 'Plugin activated successfully.', $theme_text_domain ),
			'complete' 									=> __( 'All plugins installed and activated successfully. %s', $theme_text_domain ) // %1$s = dashboard link
		)
	);

	tgmpa( $plugins, $config );

}
?>
