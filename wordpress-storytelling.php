<?php
/**
 * @package wordpress-storytelling
 * @version 3.0.0
 */
/*
Plugin Name: Enhanced Storytelling Application
Plugin URI:  https://github.com/dainst/wordpress-storytelling
Description: The Enhanced Storytelling Application (ESA) is a tool designed to allow users to create multimedia narratives on epigraphic content.
Author:	     Philipp Franck
Author URI:	 http://www.dainst.org/
Version:     3.0.0
*/
/*

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/


// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die();
}

/**
 * ******************************************* Path Globals
 */
define('ESA_DIR', '/' . basename(dirname(__FILE__)));
define('ESA_PATH', plugin_dir_path(__FILE__));
define('ESA_FILE', __FILE__);
define('ESA_NAME', basename(dirname(__FILE__)));
define('ESA_BASE', plugin_basename(__FILE__));


/**
 * ******************************************* Check PHP version
 */
if (version_compare(phpversion(), '7', '<')) {
    add_filter("plugin_action_links_" . ESA_BASE, function($actions, $plugin_file) {
        $actions["version_hint"] = "<span style='color:red'>This Plugin cannot be used with PHP version below 7. You have: " . phpversion() . "</span>";
        return $actions;
    }, 10, 2);
    error_log("Enhanced Storytelling Plugin cannot be used with PHP version below 7. You have: " . phpversion());
    return;
}


/**
 * ******************************************* require classes
 */
require_once('esa_datasource.class.php');
require_once('esa_item.class.php');
require_once('esa_item_transfer.class.php');
require_once('esa_map_widget.class.php');

$plugins = glob(ESA_PATH . "plugins/*/index.php");
foreach ($plugins as $plugin) {
    require_once($plugin);
}

require_once('functions/esa_datasources.php');
require_once('functions/esa_settings.php');
require_once('functions/esa_script_loader.php');
require_once('functions/esa_install.php');
require_once('functions/esa_item_shortcode.php');
require_once('functions/esa_item_add_media.php');
require_once('functions/esa_item_search.php');
require_once('functions/esa_item_story_map.php');
require_once('functions/esa_template_functions.php');
require_once('functions/esa_thumbnails.php');
require_once('functions/esa_item_wrapper.php');
require_once('functions/esa_item_tags.php');
require_once('functions/esa_item_comments.php');
require_once('functions/esa_item_cache.php');
require_once('functions/esa_edit_post.php');
require_once('functions/esa_item_display_settings.php');

require_once('functions/esa_page_info.php');
require_once('functions/esa_page_cache_all.php');
require_once('functions/esa_page_settings.php');