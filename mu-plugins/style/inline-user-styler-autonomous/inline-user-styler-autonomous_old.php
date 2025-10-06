<?php
/**
 * Plugin Name: Inline User Styler (Autonomous)
 * Description: Додає кастомні стилі з /css/, класи в <body> і <html>, підключає js, дозволяє live-перемикання тем.
 * Version: 1.5.0
 * Author: itcxua
 */

defined('ABSPATH') || exit;

// 🧱 1. Створення структурованих папок і базових файлів (тільки якщо відсутні)
function ius_initialize_directories_and_files() {
    $base_dir = WPMU_PLUGIN_DIR . '/inline-user-styler-autonomous/';

    $structure = [
        'css' => [
            'userstyle.css' => "/* Default desktop style */\nbody { background: #fff; color: #000; }",
            'userstyle.mobile.css' => "/* Mobile-specific style */\nbody { font-size: 14px; }"
        ],
        'js' => [
            'body-classes.js' => <<<JS
document.addEventListener("DOMContentLoaded", function () {
    const w = window.innerWidth;
    const b = document.body;
    if (w < 768) b.classList.add("style-mob");
    else if (w < 1200) b.classList.add("style-pad");
    else b.classList.add("style-desk");
    document.documentElement.setAttribute("data-userstyle-device-mode", w < 768 ? "mobile" : (w < 1200 ? "tablet" : "desktop"));
});
JS
            ,
            'theme-switcher.js' => <<<JS
(function () {
    const switcher = document.querySelector('[data-theme-switcher]');
    if (!switcher) return;
    const body = document.body;
    const applyTheme = theme => {
        body.classList.remove('theme-dark', 'theme-light', 'theme-custom');
        body.classList.add('theme-' + theme);
        localStorage.setItem('userTheme', theme);
    };
    const saved = localStorage.getItem('userTheme');
    if (saved) applyTheme(saved);
    switcher.querySelectorAll('button[data-theme]').forEach(btn => {
        btn.addEventListener('click', () => applyTheme(btn.dataset.theme));
    });
})();
JS
        ],
        'templates' => [
            'theme-switcher.html' => <<<HTML
<style>
.theme-light button[data-theme="light"], .theme-light button[data-theme="custom"],
.theme-dark button[data-theme="dark"], .theme-dark button[data-theme="light"],
.theme-custom button[data-theme="custom"], .theme-custom button[data-theme="dark"] {
    display: none;
}
#theme-switcher button {width: 150px;}
</style>            
<div id="theme-switcher" data-theme-switcher style="position:fixed;bottom:10px;right:10px;z-index:9999;">
  <button data-theme="light">☀️ Light</button>
  <button data-theme="dark">🌙 Dark</button>
  <button data-theme="custom">🎨 Custom</button>
</div>
HTML
        ]
    ];

    foreach ($structure as $dir => $files) {
        $full_dir = $base_dir . $dir;
        if (!file_exists($full_dir)) {
            wp_mkdir_p($full_dir);
        }
        foreach ($files as $filename => $content) {
            $full_file = $full_dir . '/' . $filename;
            if (!file_exists($full_file)) {
                file_put_contents($full_file, $content);
            }
        }
    }
}
add_action('init', 'ius_initialize_directories_and_files');

// 🧩 2. Вставка стилів із /css/
function ius_insert_inline_styles() {
    $base = WPMU_PLUGIN_DIR . '/inline-user-styler-autonomous/css/';

    if (file_exists($base . 'userstyle.css')) {
        $css = file_get_contents($base . 'userstyle.css');
        echo "<style id='ius-style-desktop'>\n{$css}\n</style>";
    }

    if (file_exists($base . 'userstyle.mobile.css')) {
        $css = file_get_contents($base . 'userstyle.mobile.css');
        echo "<style id='ius-style-mobile' media='(max-width: 768px)'>\n{$css}\n</style>";
    }
}
add_action('wp_head', 'ius_insert_inline_styles', 1);

// 🎯 3. Класи <body> і атрибут <html>
add_filter('body_class', function ($classes) {
    if (wp_is_mobile()) $classes[] = 'style-mob'; else $classes[] = 'style-desk';
    $classes[] = 'user-style-enabled';
    return $classes;
});

add_filter('language_attributes', function ($output) {
    $mode = wp_is_mobile() ? 'mobile' : 'desktop';
    return $output . ' data-userstyle-device-mode="' . esc_attr($mode) . '"';
});

// 📌 4. Підключення скриптів
function ius_enqueue_user_js() {
    $url = WPMU_PLUGIN_URL . '/inline-user-styler-autonomous/js/';
    $dir = WPMU_PLUGIN_DIR . '/inline-user-styler-autonomous/js/';

    if (file_exists($dir . 'body-classes.js')) {
        wp_enqueue_script('ius-body-classes', $url . 'body-classes.js', [], null, true);
    }

    if (file_exists($dir . 'theme-switcher.js')) {
        wp_enqueue_script('ius-theme-switcher', $url . 'theme-switcher.js', [], null, true);
    }
}
add_action('wp_enqueue_scripts', 'ius_enqueue_user_js');

// 💡 5. Вивід theme-switcher.html у footer
function ius_insert_theme_switcher_html() {
    $file = WPMU_PLUGIN_DIR . '/inline-user-styler-autonomous/templates/theme-switcher.html';
    if (file_exists($file)) {
        echo file_get_contents($file);
    }
}
add_action('wp_footer', 'ius_insert_theme_switcher_html');
