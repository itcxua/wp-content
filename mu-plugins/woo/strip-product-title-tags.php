<?php
/*
Plugin Name: Strip HTML from WooCommerce Product Titles
Description: Видаляє HTML-теги з назв товарів при виводі
Version: 1.0
*/

add_filter('the_title', function($title, $id) {
    if (is_admin()) return $title;

    $post_type = get_post_type($id);
    if ($post_type === 'product') {
        return wp_strip_all_tags($title);
    }

    return $title;
}, 10, 2);
