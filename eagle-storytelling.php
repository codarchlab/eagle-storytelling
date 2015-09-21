<?php
/**
 * @package eagle-storytelling
 * @version 0.8
 */
/*
Plugin Name: Eagle Storytelling Application
Plugin URI:  http://wordpress.org/plugins/eagle-storytelling/
Description: Create your own EAGLE story! 
Author:	     Wolfgang Schmidle & Philipp Franck
Author URI:	 http://www.dainst.org/
Version:     0.9

*/

include('esa_settings.php');


/****************************************/

// user stuff

/*
register_activation_hook(__FILE__, function() {
	add_role('esa_story_author', 'Story Author', array());
	add_role('esa_story_contributor', 'Story Contributor', array());
});


register_deactivation_hook(__FILE__, function() {
	remove_role('esa_story_author');
	remove_role('esa_story_contributor');
});
*/
add_action('admin_init', function () {
	
	

	
	$roles = array('subscriber', 'editor', 'author', 'administrator');
	foreach ($roles as $role) {
		$role = get_role($role);
		$role->add_cap('read');
		$role->add_cap('create_story');
		$role->add_cap('edit_story');
		$role->add_cap('delete_story');
		$role->add_cap('publish_story');
		$role->add_cap('delete_published_story');
		$role->add_cap('edit_published_story');	
		$role->add_cap('manage_story_keyword');
		$role->add_cap('edit_story_keyword');
		$role->add_cap('delete_story_keyword');
		$role->add_cap('assign_story_keyword');
		$role->add_cap('upload_files');
	}
	
	$roles = array('administrator');
	foreach ($roles as $role) {
		$role = get_role($role);
		$role->add_cap('edit_others_story');
		$role->add_cap('read_private_posts');
	}
			
	
	
});

/****************************************/

add_action('admin_menu', function () {
	
	//create new top-level menu
	add_menu_page('Eagle Storytelling Application', 'Eagle Storytelling Application', 'administrator', __FILE__, function() {
		$url = admin_url('admin.php');
		echo "	<h1>Eagle Storytelling Application</h1>
				<form method='POST' action='$url'>
    				<input type='hidden' name='action' value='esa_flush_cache'>
    				<input type='submit' value='Flush esa_item cache!' class='button'>
				</form>";
	});

});

add_action('admin_action_esa_flush_cache', function() {
	global $wpdb;
	
	$sql = "truncate {$wpdb->prefix}esa_item_cache;";
	
	$wpdb->query($sql);
	
    wp_redirect($_SERVER['HTTP_REFERER']);
    exit();
	
});


/****************************************/

function esa_create_keywords_widget() {

    $labels = array(
        'name' => _x('Keywords', 'Keywords', 'Flexible'),
        'singular_name' => _x('Keyword', 'taxonomy singular name', 'Flexible'),
        'search_items' => __('Search Keywords', 'Flexible'),
        'all_items' => __('All Keywords', 'Flexible'),
        'parent_item' => __('Parent Keyword', 'Flexible'),
        'parent_item_colon' => __('Parent Keyword:', 'Flexible'),
        'edit_item' => __('Edit Keyword', 'Flexible'),
        'update_item' => __('Update Keyword', 'Flexible'),
        'add_new_item' => __('Add New Keyword', 'Flexible'),
        'new_item_name' => __('New Keyword Name', 'Flexible'),
        'menu_name' => __('Keyword', 'Flexible')
    );

    register_taxonomy('story_keyword', array('story'), array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'keyword'),
    	'capabilities' => array(
    		'manage_terms' => 'manage_story_keyword',
    		'edit_terms' => 'edit_story_keyword',
    		'delete_terms' => 'delete_story_keyword',
    		'assign_terms' => 'assign_story_keyword'
    	)
    ));
    
}

add_action('init', 'esa_create_keywords_widget', 0);



/****************************************/
/* create editor page for stories */ 

function esa_register_story_post_type() {

    $labels = array(
        'name' => _x('Stories', 'post type general name', 'Flexible'),
        'singular_name' => _x('Story', 'post type singular name', 'Flexible'),
        'add_new' => _x('Add New', 'story item', 'Flexible'),
        'add_new_item' => __('Add New Story', 'Flexible'),
        'edit_item' => __('Edit Story', 'Flexible'),
        'new_item' => __('New Story', 'Flexible'),
        'all_items' => __('All Stories', 'Flexible'),
        'view_item' => __('View Story', 'Flexible'),
        'search_items' => __('Search Stories', 'Flexible'),
        'not_found' => __('Nothing found', 'Flexible'),
        'not_found_in_trash' => __('Nothing found in Trash', 'Flexible'),
        'parent_item_colon' => ''
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => apply_filters('et_portfolio_posttype_rewrite_args', array('slug' => 'story', 'with_front' => false)),
        'capability_type' => 'story',
    	'capabilities' => array(
    		'publish_post' => 'publish_story',
    		'publish_posts' => 'publish_story',
    		'edit_posts' => 'edit_story',
    		'edit_post' => 'edit_story',
    		'edit_others_posts' => 'edit_others_story',
    		'read_private_posts' => 'read_private_story',
    		'edit_post' => 'edit_story',
    		'delete_post' => 'delete_story',
    		'read_post' => 'read_story',
    	),
    	'exclude_from_search' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'editor', 'excerpt', 'revisions', 'thumbnail')
    );

    register_post_type('story', $args);
}

add_action('init', 'esa_register_story_post_type');


/****************************************/
/* register save story */


add_action('save_post', function($post_id) {
		
	$post = get_post($post_id);
	global $wpdb;

	if (!wp_is_post_revision($post_id) and ($post->post_type == 'story')) {
		
		$regex = get_shortcode_regex();
		preg_match_all("#$regex#s", $post->post_content, $shortcodes, PREG_SET_ORDER);

		echo "<pre>", print_r($shortcodes,1), "</pre>";
		
		if ($shortcodes) {
			
			$sql = "delete from wp_esa_item_to_post where post_id='$post_id'";
			$wpdb->query($sql);
			
			foreach($shortcodes as $shortcode) {
				if ($shortcode[2] == 'esa') {
					$atts = shortcode_parse_atts($shortcode[3]);
					echo "<pre>", print_r($atts,1), "</pre>";
					
					$wpdb->insert(
							$wpdb->prefix . 'esa_item_to_post',
							array(
									"post_id" => $post_id,
									"esa_item_source" => $atts['source'],
									"esa_item_id" => $atts['id']
							)
					);
					
					
				}
			}
		}
	}		
});



/****************************************/
/* register template for the story pages */ 

function esa_get_story_post_type_template($single_template) {
     global $post;

     if ($post->post_type == 'story') {
          $single_template = dirname( __FILE__ ) . '/template/single-story.php';
     }
     return $single_template;
}

add_filter( 'single_template', 'esa_get_story_post_type_template' );


/****************************************/
/* register template for page "stories" */ 

function esa_get_stories_page_template( $page_template )
{
    
	if ( is_page( 'stories' ) ) {
        $page_template = dirname( __FILE__ ) . '/template/page-stories.php';
    }
    return $page_template;
}

add_filter( 'page_template', 'esa_get_stories_page_template' );


/****************************************/
/* register template for page "search stories" */ 

function esa_get_search_stories_page_template($page_template) {
   	if ((get_query_var('post_type') == "story") or (get_query_var('taxonomy') == 'story_keyword')){
        $page_template = dirname( __FILE__ ) . '/template/search-stories.php'; 
    }
    return $page_template;
}

add_filter('search_template', 'esa_get_search_stories_page_template');
add_filter('archive_template', 'esa_get_search_stories_page_template');
add_filter('404_template', 'esa_get_search_stories_page_template');



function searchfilter($query) {
    if ($query->is_search && $query->post_type == "story") {
        $query->set('meta_key','_wp_page_template');
        $query->set('meta_value', dirname( __FILE__ ) . '/template/search-stories.php');    
   	}
	return $query;
}

//add_filter('pre_get_posts','searchfilter');


/**
 * Register style sheet.
 */

function esa_register_plugin_styles() {
	global $post;
	global $is_esa_story_page;
	if ((get_post_type() == 'story') or ($is_esa_story_page)) {
		
		// css
		wp_register_style('eagle-storytelling', plugins_url('eagle-storytelling/css/eagle-storytelling.css'));
		wp_enqueue_style('eagle-storytelling' );
		wp_register_style('esa_item', plugins_url('eagle-storytelling/css/esa_item.css'));
		wp_enqueue_style('esa_item');
		wp_register_style('leaflet', 'http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css');
		wp_enqueue_style('leaflet');
		
		//js
		wp_enqueue_script('esa_item.js', plugins_url() .'/eagle-storytelling/js/esa_item.js', array('jquery'));
	}
}

add_action( 'wp_enqueue_scripts', 'esa_register_plugin_styles' );







/****************************************/




/**
 * 
 * Make search able to serach inside of esa_item_cache to find Entries by it's content in esa item. 
 * 
 * 
 */

add_filter('query_vars', function($public_query_vars) {
	$public_query_vars[] = 'esa_item_source';
	$public_query_vars[] = 'esa_item_id';
	return $public_query_vars;
});

add_filter('posts_search', function($sql) {
	
	global $wp_query;
	global $wpdb;
	
	$sqst = "select
				esai2post.post_id
			from
				{$wpdb->prefix}esa_item_to_post as esai2post
			left join {$wpdb->prefix}esa_item_cache as esai on (esai.id = esai2post.esa_item_id and esai.source = esai2post.esa_item_source)
				where";
	
	if (isset($wp_query->query['s']) and ($wp_query->query['s'] != '')) {
		$where = "\n\t esai.searchindex like '%{$wp_query->query['s']}%'";
		$sqlr = "AND (({$wpdb->prefix}posts.ID in ($sqst $where)) or (1 = 1 $sql))";
	}
	if (isset($wp_query->query['esa_item_source']) and isset($wp_query->query['esa_item_id'])
		and $wp_query->query['esa_item_source'] and $wp_query->query['esa_item_id']) {
		$story = true;
		$where = "esai.id = '{$wp_query->query['esa_item_id']}' and esai.source = '{$wp_query->query['esa_item_source']}'";
		$sqlr = "AND {$wpdb->prefix}posts.ID in ($sqst $where)";
	}
	
	
	//echo "<pre>"; print_r($wp_query->query); die($sql);
	
	if (($wp_query->query['post_type'] != 'story') or (!$sql and !$story)) {
		return $sql;
	}
	
	//echo '<pre style="border:1px solid red; background: silver">', $sql, '</pre>';				
	//echo '<pre style="border:1px solid red; background: silver">', print_r($sqlr, 1), '</pre>';
	
	return $sqlr;
	
});


/* 
// view Query
add_action('found_posts', function() {
	global $wp_query;
	if (is_search()) {
		echo '<pre style="border:1px solid red; background: silver">', print_r($wp_query->request, 1), '</pre>';
	}
});*/

/****************************************/


/** 
 * add media submenu!
 */


require_once('esa_datasource.class.php');
require_once('esa_item.class.php');


// add them to media menu


add_filter('media_upload_tabs', function($tabs) {
    return array_merge($tabs, array('esa' => 'EAGLE Storytelling Application'));
});


// create submenu

add_action('media_upload_esa', function() {
	
	
	add_action('admin_print_styles-media-upload-popup', function() {
		wp_enqueue_style('colors');
		wp_enqueue_style('media');
		wp_enqueue_style('media-views');
		wp_enqueue_style('esa_item', plugins_url() .'/eagle-storytelling/css/esa_item.css');
		wp_enqueue_style('esa_item-admin', plugins_url() .'/eagle-storytelling/css/esa_item-admin.css');
		wp_enqueue_style('leaflet', 'http://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.3/leaflet.css');
	});
	
	
	add_action('admin_print_scripts-media-upload-popup', function() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('esa_item.js', plugins_url() .'/eagle-storytelling/js/esa_item.js', array('jquery'));
		wp_enqueue_script('esa_mediamenu.js', plugins_url() .'/eagle-storytelling/js/esa_mediamenu.js', array('jquery'));
	});
	
		/**
		 * this builds the iframe wich is in fact the add media dialogue of our plugin!
		 *
		 */
	return wp_iframe('media_esa_dialogue'); 
});


function media_esa_dialogue() {
	
	
	$time = microtime(true);
	
	global $esa_datasources;
	
	// get current search engine
	$engine = isset($_GET['esa_source']) ? $_GET['esa_source'] : array_keys($esa_datasources)[0];
	
	// get engine interface
	if (!$engine or !file_exists(plugin_dir_path(__FILE__) . "datasources/$engine.class.php")) {
		echo "Error: Search engine $engine not found!"; return;
	}
	
	require_once(plugin_dir_path(__FILE__) . "datasources/$engine.class.php");
	$ed_class = "\\esa_datasource\\$engine";
	$eds = new $ed_class;
	
	$post_id = isset($_REQUEST['post_id']) ? intval($_REQUEST['post_id']) : 0;
	
	
	//media_upload_header();
	
	echo "<div id='esa-mediaframe'>";
	
	echo "<div class='media-frame-router'>";
	echo "<div class='media-router'>";
	
	// create search engine menu
	foreach ($esa_datasources as $source => $label) {
		$sel = ($source == $engine) ? 'active' : '';
		echo "<a class='media-menu-item $sel' href='?tab=esa&esa_source=$source'>$label</a>";
	}
	echo "</div>";
	echo "</div>"; //esa_item_list_sidebar
	
	

	/*
	if (get_user_setting('uploader')) { // todo: user-rights
		$form_class .= ' html-uploader';
	}*/
	
	
	
	//Sidebar
	echo "<div id='esa_item_list_sidebar'>";
	echo "<div id='esa_item_preview' class='esa_item esa_item_$engine'></div>";
	
	echo '<div id="esa_item_settings"><form>';
	echo '<p>Some <strong>optional</strong> parameters to define <br />the looks of your Item. Leave out for <a title="reset to default settings" href="#" onclick="esa_ds.reset_form()"> default</a>.</p>';
	
	echo '<div class="esa_item_setting">';
	echo '<label for="height">' . __('Height') . '</label>';
	echo '<input type="number" min="0" name="height" value="">';
	echo "</div>";
	
	echo '<div class="esa_item_setting">';
	echo '<label for="width">' . __('Width') . '</label>';
	echo '<input type="number" min="0" name="width" value="">';
	echo "</div>";
	
	echo '<div class="esa_item_setting">';
	echo '<label for="align">' . __('Align') . '</label>';
	echo '<select height="1" name="align">
			<option value="" selected>none</option>
			<option value="left">Left</option>
			<option value="right">Right</option>
		</select>';
	echo "</div>";
	
	if (count($eds->optional_classes)) {
		echo '<div class="esa_item_setting">';
		echo '<label for="mode">' . __('Modus') . '</label>';
		echo '<select height="1" name="mode">
				<option value="" selected>none</option>';
		foreach ($eds->optional_classes as $key => $caption) {
			echo "<option value='$key'>$caption</option>";
		}
		echo '</select>';
	
		echo "</div>";
	}
	
	echo "</form></div>";
	
	
	echo "</div>"; //esa_item_list_sidebar
	
	echo "<div class='media-frame-content'>";
	
	echo "<div class='attachments-browser'>";

	
	//echo "<h3 class='media-title'>{$eds->title}</h3>";
	$success = $eds->search();	
	$eds->search_form();
	echo '<div id="media-items">';
	if ($success) {
		$eds->show_result();
	} else {
		$eds->show_errors();
	}
	echo '</div>'; //media-items
	
	echo '</div>'; //attachments-browser
	
	echo "</div>"; //media-frame-content
	
	echo "<div class='media-frame-toolbar'>";
	echo "<div class='media-toolbar'>";
	echo '<div class="media-toolbar-primary search-form">';
	echo '<input type="button" class="button button-primary media-button" id="go_button" disabled="disabled" onclick="esa_ds.insert()" value="' . esc_attr__('Insert into Post') . '" />';
	echo "</div>"; //media-toolbar-primary search-form
	echo "</div>"; //media-toolbar
	echo "</div>"; //media-frame-toolbar

	echo "</div>"; // esa-mediaframe
	//echo "<div class='timestamp'>Time: ", microtime(true) -$time, "</div>";
}



/**
 *  the esa_item shortcode 
 *  
 *  attribute
 *  id (mandatory) - unique id of the datatset as used in the external data source 
 *  source (mandatory) - name of the datasource as used in this plugin
 *  
 *  height - height of the item in px
 *  width - width of the item in px
 *  align (left|right|none) - align of the object in relation to the surrounding text (deafult is right)
 *  
 *  bsp: 
 *  
 *  [esa source="wiki" id="berlin"] 
 *  
 */


function esa_shortcode($atts, $context) {
	
	if (!isset($atts['source']) or !isset($atts['id'])) {
		return;
	}
	
	$css = array();
	$classes = array();
	$tags = array();
	
	if (isset($atts['height'])) {
		$css['height'] = $atts['height'] . 'px';
	}
	if (isset($atts['width'])) {
		$css['width'] = $atts['width'] . 'px';
	}

	if (isset($atts['align'])) {
		if ($atts['align'] == 'right') {
			$classes[] = 'esa_item_right';
			//$css['float'] = 'left';
		} elseif ($atts['align'] == 'left') {
			$classes[] = 'esa_item_left';
			//$css['float'] = 'right';
		} else {
			$classes[] = 'esa_item_none';
			//$css['float'] = 'none';
		}
	}
	
	$item = new esa_item($atts['source'], $atts['id'], false, false, $classes, $css);

	
	ob_start();
	$item->html();
	return ob_get_clean();
	

}

add_shortcode('esa', 'esa_shortcode');





		
		

/** 
 * the caching mechanism for esa_items 
 * 
 * how it works: everytime a esa_item get displayed, it look in the cache if there is a non expired cache of this item. if not,
 * it fetches the contents from the corresponding api and caches it
 * that has two reasons: 
 * - we want to make the embedded esa_items searchable
 * - page loading would be quite slow, if every items content had to be fetched again from the api
 * 
 * how long may content eb kept in cache? that has to be diskussed.
 * 
 * 
 */

/**
 * need a special table for that cache
 */
function esa_install () {

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	
	global $wpdb;
	
	$table_name = $wpdb->prefix . "esa_item_cache";
	$sql = 
	"CREATE TABLE $table_name (
  		source VARCHAR(12) NOT NULL,
  		id VARCHAR(200) NOT NULL,
  		content LONGTEXT NULL,
  		searchindex TEXT NULL,
  		url TEXT NULL,
  		timestamp DATETIME NOT NULL,
  		PRIMARY KEY  (source, id)
	)
	COLLATE utf8_general_ci 
	ENGINE = MYISAM
	;";
	
	dbDelta( $sql );

	// because esa_item has wo columns as index, we can't solve this with a taxonomy...
	$table_name = $wpdb->prefix . "esa_item_to_post";
	$sql = 
	"CREATE TABLE $table_name (
		post_id BIGINT(20) UNSIGNED NOT NULL,
		esa_item_source VARCHAR(12) NOT NULL,
  		esa_item_id VARCHAR(200) NOT NULL
  	)
	COLLATE utf8_general_ci
	ENGINE = MYISAM
	;";
	
	dbDelta( $sql );
	
}
register_activation_hook( __FILE__, 'esa_install' );




/**
 * tinyMCE with esa_objects 
 * 
 * -- experimental -- 
 * 
 */


add_action( 'init', 'esa_mce' );
function esa_mce() {
	add_filter("mce_external_plugins", function($plugin_array) {
		$plugin_array['esa_item'] = plugins_url() . '/eagle-storytelling/js/esa_mce.js';
		$plugin_array['noneditable'] = plugins_url() . '/eagle-storytelling/js/mce_noneditable.js';
		add_editor_style(plugins_url() .'/eagle-storytelling/css/esa_item.css');
		add_editor_style(plugins_url() .'/eagle-storytelling/css/esa_item-admin.css');
		return $plugin_array;
	});
	
	add_filter('mce_buttons', function($buttons) {
		array_push( $buttons, 'whatever' );
		return $buttons;
	});
	
};


add_action('wp_ajax_esa_shortcode', function() {
	if (isset($_POST['esa_shortcode'])) {
		
		$result = array();
		
		if (!isset($_POST['featured_image']) or ($_POST['featured_image'] == '')) {
			$post_id = $_POST['post_id'];
			$thumpnail = get_post_meta($post_id, 'esa_thumbnail', true);
			$result['featured_image'] = $thumpnail;
		}
		
		$result['esa_item'] = do_shortcode(str_replace('\\', '', rawurldecode($_POST['esa_shortcode'])));

		//$result['debug'] = $post;
		
		echo json_encode($result);
		
		wp_die();
	}
	echo "ERROR"; // todo: do something more useful
	wp_die();
});

add_action('wp_ajax_esa_set_featured_image', function() {
	
	
	if (isset($_POST['image_url']) and isset($_POST['post'])) {
		
		$image_url = $_POST['image_url'];
		$post_id = $_POST['post'];
			
		if (!add_post_meta($_POST['post'], 'esa_thumbnail', $image_url, true)) {
			update_post_meta($_POST['post'], 'esa_thumbnail', $image_url);
		}
		
		echo $image_url;
		wp_die();
	}
	echo 'ERROR'; // 
	wp_die();
});




/* thumbnailing */

function esa_thumpnail($post, $return = false) {
	
	
	// check if esa thumpnail exists
	if ($esa_thumbnail_url = get_post_meta($post->ID, 'esa_thumbnail', true)) {
		$thumbnail = "<img src='$esa_thumbnail_url' alt='thumbnail' />";
	}
	
	// check if regular thumpnail exists
	if (!$thumbnail) {
		$thumbnail = get_the_post_thumbnail($post->ID, array(150, 150));
	}

	if (!$return) {
		echo "<div class='story-thumbnail'>$thumbnail</div>";
	}

	return $thumbnail;
	
} 

add_filter('admin_post_thumbnail_html', function($html) {
	
	global $post;
	
	$thumbnail = '';
	$style1 = 'style="display: none;"';
	$style2 = 'style="display: block;"';
	
	// check if esa thumpnail exists
	if ($esa_thumbnail_url = get_post_meta($post->ID, 'esa_thumbnail', true)) {
		$thumbnail = "";
		$style2 = 'style="display: none;"';
		$style1 = 'style="display: block;"';
	}

	$text1 = "Featured image from embedded content. Use the <img src='' alt='img'>-buttons in the upper right corner of embedded content to select or deselect.</p>";
	$text2 = "Use this to select a featured image from the media library or to upload images. Alternatively use the <img src='' alt='img'>-buttons in the upper right corner of embedded content to set Images from there as featured image.</p>";	
	
	return "<span id='esa_thumpnail_content_1' $style1>
				<img id='esa_thumpnail_admin_picture' src='$esa_thumbnail_url' alt='{$post->post_title}' /> 
				<p>$text1</p>
			</span>
			<span id='esa_thumpnail_content_2' $style2>
				$text2 $html
			</span>";
});

/* esa users */

function esa_dropdown_users($selected) {

	// get users who has at least one story published
	global $wpdb;
	$sql = "
			select 
				count(post_author) as post_count, 
				display_name,
				users.ID
			from 
				{$wpdb->prefix}posts as posts 
				left join {$wpdb->prefix}users as users on (posts.post_author=users.ID) 
			where 
				post_type='story' 
				and post_status='publish' 
			group by 
				posts.post_author
			order by
				post_count desc";
	
	$users = $wpdb->get_results($sql);
	
	//print_r($sql);echo"<hr>";print_r($result);
	
	// some copied wp_dropdown_users

	$output = '';
	if (!empty($users)) {
		$name = 'author';
		$id = 'story-author-dropdown';
		$output = "<select name='{$name}' id='{$id}'>\n";
		$output .= "\t<option value='0'>&lt;all&gt;</option>\n";

		$found_selected = false;
		foreach ((array) $users as $user) {
			$user->ID = (int) $user->ID;
			$_selected = selected($user->ID, $selected, false);
			if ($_selected) {
				$found_selected = true;
			}
			$display = "{$user->display_name} ({$user->post_count})";
			$output .= "\t<option value='$user->ID'$_selected>" . esc_html($display) . "</option>\n";
		}

		$output .= "</select>";
	}

	echo $output;
	
}


/* esa tag cloud */ 
function esa_keyword_cloud($args = array()) {
	/**
	 * mostly copied from wp_generate_tag_cloud  (wp-includes/category-template.php)
	 */
	
	$defaults = array(
		'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
		'format' => 'flat', 'separator' => "\n", 'orderby' => 'name', 'order' => 'ASC',
		'exclude' => '', 'include' => '', 'link' => 'view', 'taxonomy' => 'story_keyword', 'post_type' => 'story', 'echo' => true, 
		'topic_count_text' => null, 'topic_count_text_callback' => null,
		'topic_count_scale_callback' => 'default_topic_count_scale', 'filter' => 1,
		'selected' => ''
	);
	$args = wp_parse_args( $args, $defaults );

	$tags = get_terms( $args['taxonomy'], array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) ); // Always query top tags

	if ( empty( $tags ) || is_wp_error( $tags ) )
		return;

	// Juggle topic count tooltips:
	if ( isset( $args['topic_count_text'] ) ) {
		// First look for nooped plural support via topic_count_text.
		$translate_nooped_plural = $args['topic_count_text'];
	} elseif ( ! empty( $args['topic_count_text_callback'] ) ) {
		// Look for the alternative callback style. Ignore the previous default.
		if ( $args['topic_count_text_callback'] === 'default_topic_count_text' ) {
			$translate_nooped_plural = _n_noop( '%s topic', '%s topics' );
		} else {
			$translate_nooped_plural = false;
		}
	} elseif ( isset( $args['single_text'] ) && isset( $args['multiple_text'] ) ) {
		// If no callback exists, look for the old-style single_text and multiple_text arguments.
		$translate_nooped_plural = _n_noop( $args['single_text'], $args['multiple_text'] );
	} else {
		// This is the default for when no callback, plural, or argument is passed in.
		$translate_nooped_plural = _n_noop( '%s topic', '%s topics' );
	}
	
	$tags_sorted = apply_filters( 'tag_cloud_sort', $tags, $args );
	
	if ( $tags_sorted !== $tags ) {
		$tags = $tags_sorted;
		unset( $tags_sorted );
	} else {
		if ( 'RAND' === $args['order'] ) {
			shuffle( $tags );
		} else {
			// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
			if ( 'name' === $args['orderby'] ) {
				uasort( $tags, '_wp_object_name_sort_cb' );
			} else {
				uasort( $tags, '_wp_object_count_sort_cb' );
			}
	
			if ( 'DESC' === $args['order'] ) {
				$tags = array_reverse( $tags, true );
			}
		}
	}
	
	if ( $args['number'] > 0 )
		$tags = array_slice( $tags, 0, $args['number'] );
	
	$counts = array();
	$real_counts = array(); // For the alt tag
	foreach ( (array) $tags as $key => $tag ) {
		$real_counts[ $key ] = $tag->count;
		$counts[ $key ] = call_user_func( $args['topic_count_scale_callback'], $tag->count );
	}
	
	$min_count = min( $counts );
	$spread = max( $counts ) - $min_count;
	if ( $spread <= 0 )
		$spread = 1;
	$font_spread = $args['largest'] - $args['smallest'];
	if ( $font_spread < 0 )
		$font_spread = 1;
	$font_step = $font_spread / $spread;

	// Assemble the data that will be used to generate the tag cloud markup.
	$tags_data = array();
	
	$tags_data[] = array(
			'id'         => 'X_x',
			'name'	     => '<all>',
			'title'      => '<all>',
			'slug'       => '',
			'real_count' => '10',
			'class'	     => 'tag-link-X_x',
			'font_size'  => ($args['smallest'] + $args['largest']) / 2
	);
	
	
	
	foreach ( $tags as $key => $tag ) {
		$tag_id = isset( $tag->id ) ? $tag->id : $key;
	
		$count = $counts[ $key ];
		$real_count = $real_counts[ $key ];
	
		if ( $translate_nooped_plural ) {
			$title = sprintf( translate_nooped_plural( $translate_nooped_plural, $real_count ), number_format_i18n( $real_count ) );
		} else {
			$title = call_user_func( $args['topic_count_text_callback'], $real_count, $tag, $args );
		}
	
		$tags_data[] = array(
				'id'         => $tag_id,
				'name'	     => $tag->name,
				'title'      => $title,
				'slug'       => $tag->slug,
				'real_count' => $real_count,
				'class'	     => 'tag-link-' . $tag_id,
				'font_size'  => $args['smallest'] + ( $count - $min_count ) * $font_step,
		);
	}

	
	$a = array();
	
	// generate the output links array
	foreach ( $tags_data as $key => $tag_data ) {
		$a[] = "<input 
					type='radio' 
					value='" . esc_attr( $tag_data['slug'] ) . "' 
					class='" . esc_attr( $tag_data['class'] ) . "' 
					id='esa_cloud_item_$key'
					name='term'" .
					(($args['selected'] == $tag_data['slug']) ? "checked='checked' " : ' ') .

				"/>
				<label
					for='esa_cloud_item_$key'
					style='font-size: " . esc_attr( str_replace( ',', '.', $tag_data['font_size'] ) . $args['unit'] ) . ";'
					title='" . esc_attr( $tag_data['title'] ) . "'
				>".	esc_html( $tag_data['name'] ) . "
				</label>";
	}

	$return = join( $args['separator'], $a );


	echo "<div id='story_keywords'>$return</div>";
}
	

?>