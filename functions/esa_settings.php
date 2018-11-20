<?php
global $esa_settings;
$esa_settings = array(
    'post_types' => array('post', 'page'), // post types which can contain embedded content (esa items)
    'add_media_entry' => 'Storytelling Application', // how is the entry called  in the add media dialogue
    'default_modules' => array('tags', 'comments', 'search', 'cache', 'map'),
    'script_suffix' => "",
    'modules' => false // will be filled
);

add_action('init', function() {
    global $esa_settings;
    foreach (esa_get_modules(true) as $modNr => $mod) {
        $esa_settings['modules'][$mod] = call_user_func("esa_get_module_settings_$mod"); // @ TODO use filters instead!
        load_settings($esa_settings['modules'], $mod);
    }
});

function load_settings(&$setting, $setting_name, $option_domain = "esa_settings") {
    $option_name = $option_domain . '_' .$setting_name;
    $default_value = isset($setting[$setting_name]['default']) ? $setting[$setting_name]['default'] : null;
    if (!is_null($default_value)) {
        $setting[$setting_name]['value'] = get_option($option_name, $default_value);
    }
    if (isset($setting[$setting_name]['children']) and is_array($setting[$setting_name]['children'])) {
        foreach ($setting[$setting_name]['children'] as $sub_setting_name => $sub_setting) {
            load_settings($setting[$setting_name]['children'], $sub_setting_name, $option_name);
        }
    }
}

/**
 * @param e. G. "modules", "tags", "color", "red"
 * @return array|null
 */
function esa_get_settings() {
    global $esa_settings;
    $args = func_get_args();
    if (!count($args)) {
        return $esa_settings;
    }
    $set = $esa_settings;
    while (count($args)) {
        if (isset($set['children'])) {
            $set = $set['children'];
        }
        $sub = array_shift($args);
        if (!isset($set[$sub])) {
            return null;
        }
        $set = $set[$sub];
    }
    return isset($set['value']) ? $set['value'] : $set;
}

/**
 * returns a list of activated modules. if $include_inactive is set to false or we have not settings loaded,
 * in installation f. E. we get all modules
 *
 * @param bool $include_inactive - all filters or only active... defaults to false
 * @return array|null
 */
function esa_get_modules($include_inactive = false) {
    $modules = array();
    if ($include_inactive or !esa_get_settings('modules')) {
        $modules = esa_get_settings('default_modules');
    }
    $modules = apply_filters('esa_get_modules', $modules);
    if ($include_inactive or !esa_get_settings('modules')) {
        return $modules;
    }
    return array_keys(array_filter(esa_get_settings('modules'), function($item){return $item['children']['activate']['value'];}));
}

/**
 * @return array
 */
function esa_get_post_types() {
    return array_merge(esa_get_settings('post_types'), (!!esa_get_settings('modules', 'search', 'activate') ? array('esa_item_wrapper') : array()));
}