=== WPide ===
Contributors: WPsites, Thomas Wieczorek
Tags: code, theme editor, plugin editor, code editor
Requires at least: 3.0
Tested up to: 3.3.2
Stable tag: 2.0

WordPress code editor with auto completion of both WordPress and PHP functions with reference, syntax highlighting, line numbers, tabbed editing, automatic backup.

== Description ==

WPide is a WordPress code editor with the long term goal of becoming the ultimate environment to code/develop WordPress themes and plugins. You can edit any files in your wp-content, not just plugins and themes. Code completion will help you remember your WordPress/PHP commands providing function reference along the way. Edit multiple concurrent files with the tabbed editor.

Please come forward (either on github or the WordPress support forum) with any bugs, annoyances or any improvements you can suggest. I'd like this plugin to be the best it can be and that's only going to happen if users chip in with their feedback. Code contributions welcome, over on Github.

This plugin would not be possible without the Ajax.org Cloud9 Editor (http://ace.ajax.org/) which is the embeded code editor that powers much of the functionality.

= Current Features: =

*   Syntax highlighting
*   Line numbers
*   Code autocomplete for WordPress and PHP functions along with function description, arguments and return value where applicable
*   Automatic backup of every file you edit. (one daily backup and one hourly backup of each file stored in plugins/WPide/backups/filepath)
*   File tree allowing you to access and edit any file in your wp-content folder (plugins, themes, uploads etc)
*   Highlight matching parentheses
*   Code folding
*   Auto indentation
*   Tabbed interface for editing multiple files (editing both plugin and theme files at the same time)
*   Using the WordPress filesystem API, although currently direct access is forced (edit WPide.php in the constructor to change this behaviour) ftp/ssh connections aren't setup yet, since WP will not remember a password need to work out how that will work. Maybe use modal to request password when you save but be able to click save all and save a batch with that password. Passwords defined in wp-config.php are persistent and would fix this problem but people don't generaly add those details. Open to ideas here.

= Feature ideas and improvements: =

*   Create new files and directories
*   Image editing (combining many of the tools available in most Paint programs with high-quality features that have become ubiquitous in image editing programs)
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

== Frequently Asked Questions ==

= Does this plugin work on Internet Explorer =

No support for Internet Explorer right now

== Screenshots ==

1. Editor view, showing line numbers and syntax highlighting.

== Changelog ==

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

