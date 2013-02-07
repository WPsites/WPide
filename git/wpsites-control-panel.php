<?php
/*
Plugin Name: WP sites control panel
Plugin URI: http://www.wpsites.co.uk
Description: 
Version: 0.1
Author: Simon @ WPsites
Author Email: simon@wpsites.co.uk

*/

    require_once('autoload.php.dist');
    use TQ\Git\Cli\Binary;
    use TQ\Git\Repository\Repository;
    
add_action('admin_head', 'sitest');

function sitest(){
    
    if ($_GET['test']==='test'){
        //echo "test";
        error_reporting(E_ALL);
        ini_set("display_errors", 1);
        
        $repo_path = '/var/www/control_compare_co_uk/wp-content/PHP-Stream-Wrapper-for-Git2/';
        
        $git = Repository::open($repo_path, new Binary('/var/www/git'), 0755 );
        echo $git->getFileCreationMode()." ----- ";
        // get status of working directory
        $branch = $git->getCurrentBranch();
        
        echo "working on branch: " . $branch . "<br />";
        
        //$git->add(array("testing.txt"), $force = true);
        
        //$git->commit("manual commit after previous changes", array("testing.txt"), $author = null);
        
        $args = array(
            'title'           => 'Differences',
        	'title_left'      => 'Old Version',
        	'title_right'     => 'New Version'
        );
        
        $contents = $git->showFile('testing.txt');
        $contents2 = $git->showFile('testing.txt', 'HEAD^');
        $diff_table = wp_text_diff($contents2, $contents, $args); 
        
        echo "here is the diuff table " . $diff_table;
        
            
    }

}
