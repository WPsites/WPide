=== WPide ===
Contributors: WPsites, Thomas Wieczorek
Tags: code, theme editor, plugin editor, code editor
Requires at least: 3.0
Tested up to: 3.6
Stable tag: 2.3.2

WordPress code editor with auto completion of both WordPress and PHP functions with reference, syntax highlighting, line numbers, tabbed editing, automatic backup.

== Description ==

WPide is a WordPress code editor with the long term goal of becoming the ultimate environment to code/develop WordPress themes and plugins. You can edit any files in your wp-content, not just plugins and themes. Code completion will help you remember your WordPress/PHP commands providing function reference along the way. Edit multiple concurrent files with the tabbed editor.

Please come forward (either on github or the WordPress support forum) with any bugs, annoyances or any improvements you can suggest. I'd like this plugin to be the best it can be and that's only going to happen if users chip in with their feedback. Code contributions welcome, over on Github.

This plugin would not be possible without the Ajax.org Cloud9 Editor (http://ace.ajax.org/) which is the embedded code editor that powers much of the functionality.

This plugin performs best in the Chrome web browser.

= Current Features: =

*   Syntax highlighting
*   PHP syntax checking before saving to disk to try and banish white screen of death after uploading invalid PHP
*   Line numbers
*   Code autocomplete for WordPress and PHP functions along with function description, arguments and return value where applicable
*   Colour assist - a colour picker that only shows once you double click a hex colour code in the editor. You can also drag your own image into the colour picker to use instead of the default swatch (see other notes for info).
*   Automatic backup of every file you edit. (one daily backup and one hourly backup of each file stored in plugins/WPide/backups/filepath)
*   File tree allowing you to access and edit any file in your wp-content folder (plugins, themes, uploads etc)
*   Create new files and directories
*   Highlight matching parentheses
*   Code folding
*   Auto indentation
*   Tabbed interface for editing multiple files (editing both plugin and theme files at the same time)
*   Using the WordPress filesystem API, although currently direct access is forced (edit WPide.php in the constructor to change this behaviour) ftp/ssh connections aren't setup yet, since WP will not remember a password need to work out how that will work. Maybe use modal to request password when you save but be able to click save all and save a batch with that password. Passwords defined in wp-config.php are persistent and would fix this problem but people don't generally add those details. Open to ideas here.
*   Image editing/drawing (this is currently not working..)

= Feature ideas and improvements: =

*   Improve the code autocomplete command information, providing more information on the commands, adding links through to the WordPress codex and PHP.net website for further info.
*   Add find and replace functionality
*   Create an admin panel to choose between syntax highlighting themes and turn on/off other Ajax.org Cloud9 functionality
*   Better automated file backup process
*   Templates/shortcuts for frequently used code snippets, maybe even with an interface to accept variables that could be injected into code snippet templates.
*   Integration with version control systems such as Git


As with most plugins this one is open source. For issue tracking, further information and anyone wishing to get involved and help contribute to this project can do so over on github https://github.com/WPsites/WPide


== Installation ==

1. Upload the WPide folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Access WPide by clicking the WPide menu item in your main administration menu

== Frequently Asked Questions ==

= What is in place to stop me from breaking my website - "The white screen of death" =

When you edit a PHP file, before that file is saved to the filesystem it is syntax checked to make sure there isn't something obvious that will break your site.

Every file that you edit is backed up before your first save to the filesystem and then on subsequent saves WPide will try and make a backup. It will save a maximum of 1 backup per hour to the server.

As you edit or more specifically save PHP files the restore button will display which will allow you to restore the most recent backup.

If your WordPress install is fully functional then you can use the file tree to browse all of your backed up files (plugins/WPide/backups..), if your WordPress install isn't responding then restoring the file using the restore button or directly via FTP/SSH is the only way.

The backed up PHP files cannot be accessed/restored from the web directly without the 40 digit nonce/key so should not pose a security concern.

= Can I override the default file permissions when creating files/directories on the local filesystem =

Yes you can using the below WordPress settings in wp-config.php which will effect files created with WPide and files added during the WordPress upgrade process.

define('FS_CHMOD_DIR', (0755 & ~ umask()));
define('FS_CHMOD_FILE', (0644 & ~ umask()));

= Whenever I try to edit an image the application says that it could not load the image =
Either the image contains no image data (its a new empty file) or the image is not accessible to the image editor. Your images need to be accessible to the web. i.e. if you're developing a site on your local machine behind a router/firewall your local web server could not be accessible to the web.

== Screenshots ==

1. Editor view, showing line numbers and syntax highlighting.
2. Image editor in action
3. Showing auto complete, function reference and file tree.
4. Default colour picker image

== Changelog ==
= 2.3.2 =
* Update the Ace component to 1.1.1 which includes some bug fixes, a PHP worker (showing PHP errors as you work) and a greatly improved search box.
* Fixed issue with file save showing javascript alert as if there was a failure when there wasn't
* Order folders and files alphabetically 

= 2.3.1 =
* As a quick fix I have commentted out the git functionality as the namespacing used is causing issues with old versions of PHP

= 2.3 =
* Added initial git functions using the following library: PHP-Stream-Wrapper-for-Git from https://github.com/teqneers/PHP-Stream-Wrapper-for-Git
* Initial Git functionality added - it's very experimental!

= 2.2 =
* Add restore recent backup facility - It's a primative implementation at this point but it does the job. See FAQ note.
* Turned on the LESS mode when a .LESS file is edited
* Made the autocomplete functionality only be enabled for PHP files otherwise it can be a pain to write txt files like this one!

= 2.1 =
* Ramped up the version number because the last one was just getting silly
* Interface changes to make the editor take up more screen space. Including hiding the WP admin menu and footer.

= 2.0.16 =
* Fixed problem saving PHP documents - PHP-Parser library wasn't included in the codebase correctly

= 2.0.15 =
* PHP syntax checking before saving to disk (Using: https://github.com/nikic/PHP-Parser)

= 2.0.14 =
* Fixed error Warning: Creating default object from empty value in WPide.php
* Updated the ace editor to current build

= 2.0.13 =
* Added colour assist - a colour picker that displays when you double click a hex colour code in the editor (see other notes for info).
* Added a confirm box to stop you exiting the editor by mistake and losing unsaved chnages.
* Added 'wpide_filesystem_root' filter (see other notes for info).
* A number of bug fixes.

= 2.0.12 =
* Added links to the WordPress codex and the PHP manual from within the function refrence for further info

= 2.0.11 =
* Newly created files use to contain a space, instead it now defaults to a blank file.

= 2.0.10 =
* Fixed a problem with file loading (ajax) indicator not showing.

= 2.0.9 =
* Upload snapshot of current ajaxorg editor (master/build/src) at 00:30 on the 22 May 2012. Which fixes some issues with selecting big blocks of text, code folding seems better with gutter interface hidden when not in use

= 2.0.8 =
* Fix browser compatibility issues

= 2.0.7 =
* Fixing issue with closing tabs not focusing onto next tab once closed.
* Fixed issue with detecting ajax url correctly which was causing all WPide ajax requests to fail if WordPress was installed in a subdirectory.
* Stopped autocomplete from trying to work when a js/css file is being edited.

= 2.0.6 =
* Cleaned up the WPide class and modified the way the class is passed to WordPress actions/filters.

= 2.0.5 =
* On startup the editor page now shows extra debuggin information for the filesystem API initialisation.

= 2.0.4 =
* On startup the initial editor page now shows some startup info regarding file permissions to help with debugging.

= 2.0.3 =
* If WPide cannot access your files due to permissions then when it starts up it will give you an alert to say this.

= 2.0.2 =
* Image editing is now available using the SumoPaint image editor and drawing application http://www.sumopaint.com/

= 2.0.1 =
* You can now create new files/folders

= 2.0 =
* Recreated this plugin as a dedicated WPide section/app rather than extending the built in plugin/theme editor (just incase WP remove it)
* Now using the WP filesystem API (although currently restricted to local access)
* More security checks on file opening and editing
* Added new file tree for exploring the file system and opening files (any file in wp-content)
* Massive overhaul to code autocomplete functionality with the addition of function information right in the app
* Update the ajaxorg Ace Editor to the current branch
* Tabbed editing

= 1.0.6 =
* Added link to meta section of plugin list for easy install of V2 Dev version if you have dismissed the alert.

= 1.0.5 =
* Added the facility to download and run the cutting edge development version of WPide from the Github repository

= 1.0.4 =
* Implemented JavaScript and CSS mode for better syntax highlighing and checking  (Thanks to Thomas Wieczorek)
* Organise and format source code

= 1.0.2 =
* Tidy and comment code
* Added message when backup file is generated
* Adjust code complete dropdown position
* Improved editor responsiveness when using delete or enter keys

= 1.0.1 =
* Fixed "Folder name case" issue.

= 1.0 =
* Initial release.

== Other Feature notes ==

= You can modify the filesystem root using the 'wpide_filesystem_root' filter =

So to restrict editing to the Twenty Eleven theme only you could do this:

add_filter('wpide_filesystem_root', 'wpide_filesystem_root_override');
function wpide_filesystem_root_override($path){ 
    // the default path variable will be WP_CONTENT_DIR
    return $path . "/themes/twentyeleven"; 
}

= Colour assist =

The colour picker only shows if you double click a hex colour value in the editor (3 or 6 characters with a proceeding hash #FF0000)

The default colour picker has limited colours. You can replace this image with an image of your own by dragging and dropping a new image onto the default one (due to security reasons this can only be an image from the same domain). 

Using this you can either create your own swatch of colours or just drag in your websites logo or header image.

If you close the editor any custom colour picker image will be forgotten. We maybe thing about making this persist and also make the image uploadable as well as drag+drop.

== Dev Notes ==

Maybe some interesting things here we could implement to help with following the WordPress standard and more advanced code syntax checking

http://magp.ie/2011/01/10/tidy-and-format-your-php-and-meet-wordpress-standards-on-coda-and-textwrangler/

Checkout the following WordPress plugin "WP Live CSS Editor" to work out how to do LIVE css editing. Combining a LESS compiler with live CSS editing/compile would be a dream.

https://github.com/lennie/git-webcommit/ may be a route to git functionality 

== Contributors ==

Simon Dunton - http://www.wpsites.co.uk
Thomas Wieczorek - http://www.wieczo.net
