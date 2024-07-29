<?php
// create  Menu

function register_my_menus()
{
    register_nav_menus(
        array(
            'header' => __('Header'),
            'footer' => __('Footer'),
            'advertise' => __('Advertise'),
            'top-picks' => __('Top picks'),
            'community' => __('Community'),
            'topics-right-menu' => __('Topics Right SubMenu'),
            'right-menu' => __('Right Menu'),
            'extra-menu' => __('Extra'),
        )
    );
}
add_action('init', 'register_my_menus');

function custom_submenu_classes($classes, $args, $depth) {
    // Check if this is the specific menu location you want to modify
    if ($args->theme_location == 'header') { // Replace 'header-menu' with your actual menu location
        if(isset($args->add_class_has_child_box)) {
        $classes[] = $args->add_class_has_child_box; // Add the class to child menu
        }
    }
    return $classes;
}
add_filter('nav_menu_submenu_css_class', 'custom_submenu_classes', 10, 3);


function menu_set_dropdown( $sorted_menu_items, $args ) {
    if ($args->theme_location == 'header') { 
    $last_top = 0;
    foreach ( $sorted_menu_items as $key => $obj ) {
       
        if ( 0 == $obj->menu_item_parent ) {
           
            $last_top = $key;
        } else {
          if(isset($args->add_li_class_has_child)) {
            $sorted_menu_items[$last_top]->classes['dropdown'] = $args->add_li_class_has_child;
        }
   
           
        }
    }
}
    return $sorted_menu_items;
}
add_filter( 'wp_nav_menu_objects', 'menu_set_dropdown', 10, 2 );