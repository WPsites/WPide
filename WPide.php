<?php
/*
Plugin Name: WPide
Plugin URI: https://github.com/WPsites/WPide
Description: WordPress code editor with auto completion of both WordPress and PHP functions with reference, syntax highlighting, line numbers, tabbed editing, automatic backup.
Version: 2.2
Author: Simon @ WPsites
Author URI: http://www.wpsites.co.uk
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


if ( !class_exists( 'wpide' ) ) :
class wpide

{

	public $site_url, $plugin_url;
    
    /**
	 * The main WPide loader (PHP4 compatable)
	 *
	 * @uses wpide::__construct() Setup the globals needed
	 */
	public function wpide() {
		$this->__construct();
	}
	
	function __construct() {
        
    	//add WPide to the menu
		add_action( 'admin_menu',  array( $this, 'add_my_menu_page' ) );
		
		//hook for processing incoming image saves
		if ( isset($_GET['wpide_save_image']) ){
			
			//force local file method for testing - you could force other methods 'direct', 'ssh', 'ftpext' or 'ftpsockets'
			$this->override_fs_method('direct');
			
			add_action('admin_init', array( $this, 'wpide_save_image') );
			
		}
		
          
		//only include this plugin if on theme editor, plugin editor or an ajax call
		if ( (isset($_GET['page']) && $_GET['page'] === 'wpide') ||
			 preg_match('#admin-ajax\.php$#', $_SERVER['PHP_SELF']) ){
                
                
			// force local file method until I've worked out how to implement the other methods
            // main problem being password wouldn't/isn't saved between requests
            // you could force other methods 'direct', 'ssh', 'ftpext' or 'ftpsockets'
			$this->override_fs_method('direct');

			// Uncomment any of these calls to add the functionality that you need.
			add_action('admin_init', array( $this, 'add_admin_js' ) );
			add_action('admin_init', array( $this, 'add_admin_styles' ) );
			
			//setup jqueryFiletree list callback
			add_action('wp_ajax_jqueryFileTree', array( $this, 'jqueryFileTree_get_list' ) );
			//setup ajax function to get file contents for editing 
			add_action('wp_ajax_wpide_get_file',  array( $this, 'wpide_get_file' ) );
			//setup ajax function to save file contents and do automatic backup if needed
			add_action('wp_ajax_wpide_save_file',  array( $this, 'wpide_save_file' ) );
			//setup ajax function to create new item (folder, file etc)
			add_action('wp_ajax_wpide_create_new', array( $this, 'wpide_create_new' ) );
            //setup ajax function to show local git repo changes
    		add_action('wp_ajax_wpide_show_changed_files', array( $this, 'show_changed_files' ) );
            
			
			//setup ajax function to create new item (folder, file etc)
			add_action('wp_ajax_wpide_image_edit_key', array( $this, 'wpide_image_edit_key' )  );
			
			//setup ajax function for startup to get some debug info, checking permissions etc
    		add_action('wp_ajax_wpide_startup_check', array( $this, 'wpide_startup_check' ) );
            
            //add a warning when navigating away from WPide
            //it has to go after WordPress scripts otherwise WP clears the binding
			add_action('admin_print_footer_scripts', array( $this, 'add_admin_nav_warning' ), 99 );
            
            // Add body class to collapse the wp sidebar nav
            add_filter('admin_body_class', array( $this, 'hide_wp_sidebar_nav' ), 11);
            
            //hide the update nag
            add_action('admin_menu', array( $this, 'hide_wp_update_nag' ));
            
		}
		

		

		
		$this->site_url = get_bloginfo('url');
		

	}


    public function override_fs_method($method = 'direct'){
        
        
        if ( defined('FS_METHOD') ){
            
            define('WPIDE_FS_METHOD_FORCED_ELSEWHERE', FS_METHOD); //make a note of the forced method
            
        }else{
            
            define('FS_METHOD', $method); //force direct
            
        }
        
    }
    

    public function hide_wp_sidebar_nav($classes) {
        
    	return  str_replace("auto-fold", "", $classes) . ' folded';
    }
    
    public function hide_wp_update_nag() {
        remove_action( 'admin_notices', 'update_nag', 3 );
    }

	public static function add_admin_nav_warning()
	{
        ?>
            <script type="text/javascript">
            
                jQuery(document).ready(function($) {
                    window.onbeforeunload = function() {
                      return 'You are attempting to navigate away from WPide. Make sure you have saved any changes made to your files otherwise they will be forgotten.' ;
                    }
                });
    
            </script>
        <?php
	}






	public static function add_admin_js(){
		
	    $plugin_path =  plugin_dir_url( __FILE__ );
		    //include file tree
		    wp_enqueue_script('jquery-file-tree', plugins_url("jqueryFileTree.js", __FILE__ ) );
		    //include ace
		    wp_enqueue_script('ace', plugins_url("ace-0.2.0/src/ace.js", __FILE__ ) );
		    //include ace modes for css, javascript & php
		    wp_enqueue_script('ace-mode-css', $plugin_path . 'ace-0.2.0/src/mode-css.js');
            wp_enqueue_script('ace-mode-less', $plugin_path . 'ace-0.2.0/src/mode-less.js');
		    wp_enqueue_script('ace-mode-javascript', $plugin_path . 'ace-0.2.0/src/mode-javascript.js');
		    wp_enqueue_script('ace-mode-php', $plugin_path . 'ace-0.2.0/src/mode-php.js');
		    //include ace theme
		    wp_enqueue_script('ace-theme', plugins_url("ace-0.2.0/src/theme-dawn.js", __FILE__ ) );//monokai is nice
		    // wordpress-completion tags
		    wp_enqueue_script('wpide-wordpress-completion', plugins_url("js/autocomplete/wordpress.js", __FILE__ ) );
		    // php-completion tags
		    wp_enqueue_script('wpide-php-completion', plugins_url("js/autocomplete/php.js", __FILE__ ) );
		    // load editor
		    wp_enqueue_script('wpide-load-editor', plugins_url("js/load-editor.js", __FILE__ ) );
		    // load autocomplete dropdown 
		    wp_enqueue_script('wpide-dd', plugins_url("js/jquery.dd.js", __FILE__ ) );
		    
		     // load jquery ui  
		    wp_enqueue_script('jquery-ui', plugins_url("js/jquery-ui-1.9.2.custom.min.js", __FILE__ ), array('jquery'),  '1.9.2');
	
		    // load color picker  
    	    wp_enqueue_script('ImageColorPicker', plugins_url("js/ImageColorPicker.js", __FILE__ ), array('jquery'),  '0.3');
		    
    
    
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
        $url = wp_nonce_url('admin.php?page=wpide','plugin-name-action_wpidenonce');
        $form_fields = null; // for now, but at some point the login info should be passed in here
        if (false === ($creds = request_filesystem_credentials($url, FS_METHOD, false, false, $form_fields) ) ) {
             // no credentials yet, just produced a form for the user to fill in
            return true; // stop the normal page form from displaying
        }
        
		if ( ! WP_Filesystem($creds) ) 
		    return false;
        
		$_POST['dir'] = urldecode($_POST['dir']);
        $root = apply_filters( 'wpide_filesystem_root', WP_CONTENT_DIR ); 
		
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
        $url = wp_nonce_url('admin.php?page=wpide','plugin-name-action_wpidenonce');
        $form_fields = null; // for now, but at some point the login info should be passed in here
        if (false === ($creds = request_filesystem_credentials($url, FS_METHOD, false, false, $form_fields) ) ) {
             // no credentials yet, just produced a form for the user to fill in
            return true; // stop the normal page form from displaying
        }
		if ( ! WP_Filesystem($creds) ) 
		    return false;
        
         
		$root = apply_filters( 'wpide_filesystem_root', WP_CONTENT_DIR ); 
		$file_name = $root . stripslashes($_POST['filename']);
		echo $wp_filesystem->get_contents($file_name);
		die(); // this is required to return a proper result
	}
    
    
    public static function show_changed_files() {
		//check the user has the permissions
		check_admin_referer('plugin-name-action_wpidenonce'); 
		if ( !current_user_can('edit_themes') )
			wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
		
        print_r($_POST);
        
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
        $url = wp_nonce_url('admin.php?page=wpide','plugin-name-action_wpidenonce');
        $form_fields = null; // for now, but at some point the login info should be passed in here
        if (false === ($creds = request_filesystem_credentials($url, FS_METHOD, false, false, $form_fields) ) ) {
            // no credentials yet, just produced a form for the user to fill in
            return true; // stop the normal page form from displaying
        }
		if ( ! WP_Filesystem($creds) ) 
		    return false;
		
	    $root = apply_filters( 'wpide_filesystem_root', WP_CONTENT_DIR ); 
        
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
				
                //write the file
				$write_result = $wp_filesystem->put_contents(
					$root . $path . $filename,
					'',
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
        
        $is_php = false;
        
        //check file syntax of PHP files by parsing the PHP
        if ( preg_match("#\.php$#i", $_POST['filename']) ){
            
            $is_php = true;
            
            require('PHP-Parser/lib/bootstrap.php');
            ini_set('xdebug.max_nesting_level', 2000);
            
            $code = stripslashes($_POST['content']);
            
            $parser = new PHPParser_Parser(new PHPParser_Lexer);
            
            try {
                $stmts = $parser->parse($code);
            } catch (PHPParser_Error $e) {
                echo 'Parse Error: ', $e->getMessage();
                die();
            }
        }

		//setup wp_filesystem api
		global $wp_filesystem;
        $url = wp_nonce_url('admin.php?page=wpide','plugin-name-action_wpidenonce');
        $form_fields = null; // for now, but at some point the login info should be passed in here
        if (false === ($creds = request_filesystem_credentials($url, FS_METHOD, false, false, $form_fields) ) ) {
            // no credentials yet, just produced a form for the user to fill in
            return true; // stop the normal page form from displaying
        }
		if ( ! WP_Filesystem($creds) ) 
		    echo "Cannot initialise the WP file system API";
		
		//save a copy of the file and create a backup just in case
		$root = apply_filters( 'wpide_filesystem_root', WP_CONTENT_DIR ); 
		$file_name = $root . stripslashes($_POST['filename']);
		
		//set backup filename
		$backup_path = 'backups' . preg_replace( "#\.php$#i", "_".date("Y-m-d-H").".php", $_POST['filename'] );
		$backup_path_full = plugin_dir_path(__FILE__) . $backup_path;
        //create backup directory if not there
		$new_file_info = pathinfo($backup_path_full);
		if (!$wp_filesystem->is_dir($new_file_info['dirname'])) wp_mkdir_p( $new_file_info['dirname'] ); //should use the filesytem api here but there isn't a comparable command right now
		

        
        if ($is_php){
            //create the backup file adding some php to the file to enable direct restore
            global $current_user;
            get_currentuserinfo();
            $user_md5 = md5( serialize($current_user) );
            
            $restore_php = '<?php /* start WPide restore code */
                                    if ($_POST["restorewpnonce"] === "'.  $user_md5.$_POST['_wpnonce'] .'"){
                                        if ( file_put_contents ( "'.$file_name.'" ,  preg_replace("#<\?php /\* start WPide(.*)end WPide restore code \*/ \?>#s", "", file_get_contents("'.$backup_path_full.'") )  ) ){
                                            echo "Your file has been restored, overwritting the recently edited file! \n\n The active editor still contains the broken or unwanted code. If you no longer need that content then close the tab and start fresh with the restored file.";
                                        }
                                    }else{
                                        echo "-1";
                                    }
                                    die();
                            /* end WPide restore code */ ?>';
            
            file_put_contents ( $backup_path_full ,  $restore_php . file_get_contents($file_name) );
            
        }else{
            //do normal backup
		    $wp_filesystem->copy( $file_name, $backup_path_full );
        }
        
		//save file
		if( $wp_filesystem->put_contents( $file_name, stripslashes($_POST['content'])) ) {
        	
            //lets create an extra long nonce to make it less crackable
            global $current_user;
            get_currentuserinfo();
            $user_md5 = md5( serialize($current_user) );
            
			$result = "\"". $backup_path . ":::" . $user_md5 ."\"";
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
			$url = wp_nonce_url('admin.php?page=wpide','plugin-name-action_wpidenonce');
            $form_fields = null; // for now, but at some point the login info should be passed in here
            if (false === ($creds = request_filesystem_credentials($url, FS_METHOD, false, false, $form_fields) ) ) {
                // no credentials yet, just produced a form for the user to fill in
                return true; // stop the normal page form from displaying
            }
			if ( ! WP_Filesystem($creds) ) 
			    echo "Cannot initialise the WP file system API";
			
			//save a copy of the file and create a backup just in case
			$root = apply_filters( 'wpide_filesystem_root', WP_CONTENT_DIR ); 
			$file_name = $root . stripslashes($_POST['filename']);
			
			//set backup filename
        	$backup_path = 'backups' . preg_replace( "#\.php$#i", "_".date("Y-m-d-H").".php", $_POST['filename'] );
    		$backup_path = plugin_dir_path(__FILE__) . $backup_path;
            
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
    
    
    public static function wpide_startup_check() {
        global $wp_filesystem, $wp_version;
        
        echo "\n\n\n\nWPIDE STARTUP CHECKS \n";
        echo "___________________ \n\n";
        
        //WordPress version
        if ($wp_version > 3){
            echo "WordPress version = " . $wp_version . "\n\n";
        }else{
            echo "WordPress version = " . $wp_version . " (which is too old to run WPide) \n\n";
        }
		
    	//check the user has the permissions
		check_admin_referer('plugin-name-action_wpidenonce'); 
		if ( !current_user_can('edit_themes') )
			wp_die('<p>'.__('You do not have sufficient permissions to edit templates for this site. SORRY').'</p>');
            
        if ( defined( 'WPIDE_FS_METHOD_FORCED_ELSEWHERE' ) ){
            echo "WordPress filesystem API has been forced to use the " . WPIDE_FS_METHOD_FORCED . " method by another plugin/WordPress. \n\n";
        }
		
		//setup wp_filesystem api
        $wpide_filesystem_before = $wp_filesystem;
        
        $url = wp_nonce_url('admin.php?page=wpide','plugin-name-action_wpidenonce');
        $form_fields = null; // for now, but at some point the login info should be passed in here
	ob_start();
        if (false === ($creds = request_filesystem_credentials($url, FS_METHOD, false, false, $form_fields) ) ) {
            // if we get here, then we don't have credentials yet,
            // but have just produced a form for the user to fill in,
            // so stop processing for now
            //return true; // stop the normal page form from displaying
        }
	ob_end_clean();
		if ( ! WP_Filesystem($creds) ) {
            
            echo "There has been a problem initialising the filesystem API \n\n";
            echo "Filesystem API before this plugin ran: \n\n" . print_r($wpide_filesystem_before, true);
            echo "Filesystem API now: \n\n" . print_r($wp_filesystem, true);
    	    
		}
        unset($wpide_filesystem_before);
        

		$root = apply_filters( 'wpide_filesystem_root', WP_CONTENT_DIR ); 
        if ( isset($wp_filesystem) ){
			
        //Running webservers user and group
        echo "Web server user/group = " . getenv('APACHE_RUN_USER') . ":" . getenv('APACHE_RUN_GROUP') . "\n";
        //wp-content user and group
        echo "wp-content owner/group = " . $wp_filesystem->owner( $root ) . ":" . $wp_filesystem->group( $root ) . "\n\n";
        
        
        //check we can list wp-content files
        if( $wp_filesystem->exists( $root ) ){
            
            $files = $wp_filesystem->dirlist( $root );
            if ( count($files) > 0){
                echo "wp-content folder exists and contains ". count($files) ." files \n";
            }else{
                echo "wp-content folder exists but we cannot read it's contents \n";
            }
        }
        
        // $wp_filesystem->owner() $wp_filesystem->group() $wp_filesystem->is_writable() $wp_filesystem->is_readable()
        echo "\nUsing the ".$wp_filesystem->method." method of the WP filesystem API\n";
        
        //wp-content editable?
        echo "The wp-content folder ". ( $wp_filesystem->is_readable( $root )==1 ? "IS":"IS NOT" ) ." readable and ". ( $wp_filesystem->is_writable( $root )==1 ? "IS":"IS NOT" ) ." writable by this method \n";
        
        
        //plugins folder editable
        echo "The wp-content/plugins folder ". ( $wp_filesystem->is_readable( $root."/plugins" )==1 ? "IS":"IS NOT" ) ." readable and ". ( $wp_filesystem->is_writable( $root."/plugins" )==1 ? "IS":"IS NOT" ) ." writable by this method \n";
     

        //themes folder editable
        echo "The wp-content/themes folder ". ( $wp_filesystem->is_readable( $root."/themes" )==1 ? "IS":"IS NOT" ) ." readable and ". ( $wp_filesystem->is_writable( $root."/themes" )==1 ? "IS":"IS NOT" ) ." writable by this method \n";
     
        }
        
        echo "___________________ \n\n\n\n";
        
        echo " If the file tree to the right is empty there is a possibility that your server permissions are not compatible with this plugin. \n The startup information above may shed some light on things. \n Paste that information into the support forum for further assistance.";
        
        
		die();

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
            //dont think this is needed any more.. var wpide_file_root_url = "<?php echo apply_filters("wpide_file_root_url", WP_CONTENT_URL );?>";
			var user_nonce_addition = '';
            
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
                            
                            jQuery.ajaxSetup({async:true});//enable async again
							
							
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
            
                $("#fancyeditordiv").css("height", ($('body').height()-120) + 'px' );
                
                //set up the git commit overlay
                $('#gitdiv').dialog({
                   autoOpen: false,
                   title: 'Git commit',
                   width: 750
                });
                 
				// Handler for .ready() called.
				the_filetree() ;
				
				//inialise the color assist
    			$("#wpide_color_assist img").ImageColorPicker({
          			afterColorSelected: function(event, color){ 
                        jQuery("#wpide_color_assist_input").val(color); 
              		}
      			});
                $("#wpide_color_assist").hide(); //hide it until it's needed
                
                $("#wpide_color_assist_send").click(function(e){
                    e.preventDefault();
                    editor.insert( jQuery("#wpide_color_assist_input").val().replace('#', '') );
                    
                    $("#wpide_color_assist").hide(); //hide it until it's needed again
                });
                
                $(".close_color_picker a").click(function(e){
                    e.preventDefault();
                    $("#wpide_color_assist").hide(); //hide it until it's needed again
                });
				
                $("#wpide_toolbar_buttons").on('click', "a.restore", function(e){
                    e.preventDefault();
                    var file_path = jQuery(".wpide_tab.active", "#wpide_toolbar").data( "backup" );
                    
                    jQuery("#wpide_message").hide(); //might be shortly after a save so a message may be showing, which we don't need
                    jQuery("#wpide_message").html('<span><strong>File available for restore</strong><p> ' + file_path + '</p><a class="button red restore now" href="'+ wpide_app_path + file_path +'">Restore this file now &#10012;</a><a class="button restore cancel" href="#">Cancel &#10007;</a><br /><em class="note"><strong>note: </strong>You can browse all file backups if you navigate to the backups folder (plugins/WPide/backups/..) using the filetree.</em></span>');
                	jQuery("#wpide_message").show();
                });
                $("#wpide_toolbar_buttons").on('click', "a.restore.now", function(e){
                    e.preventDefault();
                    
                    var data = { restorewpnonce: user_nonce_addition + jQuery('#_wpnonce').val() };
                	jQuery.post( wpide_app_path + jQuery(".wpide_tab.active", "#wpide_toolbar").data( "backup" )
                                , data, function(response) { 
                        
                        if (response == -1){
                            alert("Problem restoring file.");
                        }else{
                            alert( response);
                            jQuery("#wpide_message").hide();
                        }
                           
                	});	
    
                });
                $("#wpide_toolbar_buttons" ).on('click', "a.cancel", function(e){
                    e.preventDefault();
                    
                    jQuery("#wpide_message").hide(); //might be shortly after a save so a message may be showing, which we don't need
                });
                
                
                
                $("#wpide_git" ).on('click', function(e){
                    e.preventDefault();
                          
                    $('#gitdiv').dialog( "open" );
      
                });
                
                $("#gitdiv .show_changed_files" ).on('click', function(e){
                    e.preventDefault();
                          
                	var data = { action: 'wpide_show_changed_files', _wpnonce: jQuery('#_wpnonce').val(), _wp_http_referer: jQuery('#_wp_http_referer').val(),
                                    gitpath: jQuery('#gitpath').val(), gitbinary: jQuery('#gitbinary').val() };

					jQuery.post(ajaxurl, data, function(response) {
                    
						//with the response (which is a nonce), build the json data to pass to the image editor. The edit key (nonce) is only valid to edit this image
						alert(response);
						
					});
      
                });
                
				
			});
		</script>
		

		
		<div id="poststuff" class="metabox-holder has-right-sidebar">
		
			<div id="side-info-column" class="inner-sidebar">
				
            <div id="wpide_info">
                <div id="wpide_info_content"></div> 
            </div>
            <br style="clear:both;" />
                 <div id="wpide_color_assist">
                    <div class="close_color_picker"><a href="close-color-picker">x</a></div>
                    <h3>Colour Assist</h3>
                    <img src='<?php echo plugins_url("images/color-wheel.png", __FILE__ ); ?>' />
                    <input type="button" class="button" id="wpide_color_assist_send" value="&lt; Send to editor" />
                    <input type="text" id="wpide_color_assist_input" name="wpide_color_assist_input" value="" />
                    
                </div>
                
                 
		
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
				  <div id="wpide_message"></div>
				  <a class="button restore" style="display:none;" title="Restore the active tab" href="#">Restore &#10012;</a> 
                  
                  </div>
							
							
				<div id='fancyeditordiv'></div>
				
				<form id="wpide_save_container" action="" method="get">
                <div id="wpide_footer_message"></div> 
                <div id="wpide_footer_message_last_saved"></div>
                <div id="wpide_footer_message_unsaved"></div>
                
                <a href="#" id="wpide_save" alt="Keyboard shortcut to save [Ctrl/Cmd + S]" title="Keyboard shortcut to save [Ctrl/Cmd + S]" class="button-primary">SAVE 
    			   FILE</a> 
                
                 <a href="#" id="wpide_git" alt="Open the overlay to commit some files to a Git repository" title="Open the overlay to commit some files to a Git repository" class="button-secondary">Git commit files</a> 
                   
				   
				   <input type="hidden" id="filename" name="filename" value="" />
				       <?php
				       if ( function_exists('wp_nonce_field') )
					   wp_nonce_field('plugin-name-action_wpidenonce');
				       ?>
				 </form>
                 
                 <div id="gitdiv">
                    <label>Local Git path</label><input type="text" name="gitpath" id="gitpath" value="" /> 
                    <label>Local Git binary</label><input type="text" name="gitbinary" id="gitbinary" value="" />
                    <a class="button show_changed_files" href="#">Show changed files</a>
                 </div>
			</div>	
				
			
		
		</div>
			
		<?php
	}

}

$wpide = new wpide();

endif; // class_exists check

?>
