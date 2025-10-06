<?php
/**
 * Plugin Name: Device Style Loader with File Check
 * Description: Підключає стилі для desktop, tablet, mobile з перевіркою, чи існує файл.
 * Version: 1.1
 * ├──
 * │ ====== Структура ====== 
 * ├ wp-content/
 * ├── mu-plugins/
 * │   └── device-style-loader.php
 * ├── themes/
 * │   └── ваша_тема/
 * │       └── css/
 * │           ├── desktop.css
 * │           ├── tablet.css
 * │           ├── mobile.css
 * │           └── responsive.css (опціонально)
 * ├========================================================
 * 
 */

add_action('wp_enqueue_scripts', function () {
    $theme_uri  = get_stylesheet_directory_uri() . '/css/';
    $theme_path = get_stylesheet_directory() . '/css/';

    // Desktop ≥ 1025px
    if (file_exists($theme_path . 'desktop.css')) {
        wp_enqueue_style('desktop-style', $theme_uri . 'desktop.css', [], null);
        wp_style_add_data('desktop-style', 'media', '(min-width: 1025px)');
    }

    // Tablet: 768px – 1024px
    if (file_exists($theme_path . 'tablet.css')) {
        wp_enqueue_style('tablet-style', $theme_uri . 'tablet.css', [], null);
        wp_style_add_data('tablet-style', 'media', '(min-width: 768px) and (max-width: 1024px)');
    }

    // Mobile ≤ 767px
    if (file_exists($theme_path . 'mobile.css')) {
        wp_enqueue_style('mobile-style', $theme_uri . 'mobile.css', [], null);
        wp_style_add_data('mobile-style', 'media', '(max-width: 767px)');
    }

    // Universal responsive fallback (optional)
    if (file_exists($theme_path . 'responsive.css')) {
        wp_enqueue_style('responsive-style', $theme_uri . 'responsive.css', [], null);
        wp_style_add_data('responsive-style', 'media', 'all');
    }
});
