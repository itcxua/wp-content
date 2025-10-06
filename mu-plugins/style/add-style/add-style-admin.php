<?php
/**
 *
 * ==========          STAGE 1          ========== *
 * KOD #16 - customize admin page footer text
 * source - http://wordpress.stackexchange.com/a/6005/8922
*/

function custom_admin_footer () {
 echo "Don't change anything without my permission!";
}
add_filter ('admin_footer_text', 'custom_admin_footer');

/**
 * Добавление ПЕРВЫЙ стиль в админку
 * Итак, сначала подключим файл во фронтэнде сайта:
 */
function admin_style_frontend() {
  wp_enqueue_style( 'admin_style', get_stylesheet_directory_uri() . '/admin.css' );
}
add_action( 'wp_enqueue_scripts', 'admin_style_frontend' );


/* ============================================== *
 * Теперь сделаем так, чтобы файл подключался только в админке:
* ============================================== */
function admin_style_backend() {
  wp_enqueue_style( 'admin_style', get_stylesheet_directory_uri() . '/admin.css' );
}
add_action( 'admin_enqueue_scripts', 'admin_style_backend' );


?>