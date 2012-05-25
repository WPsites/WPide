=== WPide ===
Contributors: WPsites, Thomas Wieczorek
Tags: code, theme editor, plugin editor, code editor
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 2.0.12

WordPress code editor with auto completion of both WordPress and PHP functions with reference, syntax highlighting, line numbers, tabbed editing, automatic backup.

== Description ==

WPide is a WordPress code editor with the long term goal of becoming the ultimate environment to code/develop WordPress themes and plugins. You can edit any files in your wp-content, not just plugins and themes. Code completion will help you remember your WordPress/PHP commands providing function reference along the way. Edit multiple concurrent files with the tabbed editor.

Please come forward (either on github or the WordPress support forum) with any bugs, annoyances or any improvements you can suggest. I'd like this plugin to be the best it can be and that's only going to happen if users chip in with their feedback. Code contributions welcome, over on Github.

This plugin would not be possible without the Ajax.org Cloud9 Editor (http://ace.ajax.org/) which is the embedded code editor that powers much of the functionality.

This plugin performs best in the Chrome web browser.

= Current Features: =

*   Syntax highlighting
*   Line numbers
*   Code autocomplete for WordPress and PHP functions along with function description, arguments and return value where applicable
*   Automatic backup of every file you edit. (one daily backup and one hourly backup of each file stored in plugins/WPide/backups/filepath)
*   File tree allowing you to access and edit any file in your wp-content folder (plugins, themes, uploads etc)
*   Create new files and directories
*   Highlight matching parentheses
*   Code folding
*   Auto indentation
*   Tabbed interface for editing multiple files (editing both plugin and theme files at the same time)
*   Using the WordPress filesystem API, although currently direct access is forced (edit WPide.php in the constructor to change this behaviour) ftp/ssh connections aren't setup yet, since WP will not remember a password need to work out how that will work. Maybe use modal to request password when you save but be able to click save all and save a batch with that password. Passwords defined in wp-config.php are persistent and would fix this problem but people don't generally add those details. Open to ideas here.
*   Image editing/drawing (requires Flash -  will move over to HTML5 when there is a decent alternative)

= Feature ideas and improvements: =

*   Improve the code autocomplete command information, providing more information on the commands, adding links through to the WordPress codex and PHP.net website for further info.
*   Add find and replace functionality
*   Create an admin panel to choose between syntax highlighting themes and turn on/off other Ajax.org Cloud9 functionality
*   Better automated file backup process
*   Templates/shortcuts for frequently used code snippets, maybe even with an interface to accept variables that could be injected into code snippet templates.
*   Integration with version control systems such as Git


As with most plugins this one is open source. For issue tracking, further information and anyone wishing to get involved and help contribute to this project can do so over on github https://github.com/WPsites/WPide

== Contributors ==

Simon Dunton - http://www.wpsites.co.uk
Thomas Wieczorek - http://www.wieczo.net


== Installation ==

1. Upload the WPide folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Access WPide by clicking the WPide menu item in your main administration menu

== Frequently Asked Questions ==

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

== Changelog ==

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

== DEV NOTES ==

Maybe some interesting things here we could implement to help with following the WordPress standard and more advanced code syntax checking

http://magp.ie/2011/01/10/tidy-and-format-your-php-and-meet-wordpress-standards-on-coda-and-textwrangler/

Checkout the following WordPress plugin "WP Live CSS Editor" to work out how to do LIVE css editing. Combining a LESS compiler with live CSS editing/compile would be a dream.

