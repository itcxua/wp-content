<?php
////////////////////////////////////////////////////////////////////
//   колонка "ID" для таксономий (рубрик, меток и т.д.) в админке //
////////////////////////////////////////////////////////////////////
add_action('admin_init', 'admin_area_ID');
function admin_area_ID() {
// ========================================================
//   "ID" для таксономий (рубрик, меток и т.д.) в админке
// ========================================================
   foreach (get_taxonomies() as $taxonomy) {
        add_action("manage_edit-${taxonomy}_columns",          'tax_add_col');
        add_filter("manage_edit-${taxonomy}_sortable_columns", 'tax_add_col');
        add_filter("manage_${taxonomy}_custom_column",         'tax_show_id', 10, 3);
    }
    add_action('admin_print_styles-edit-tags.php', 'tax_id_style');
    function tax_add_col($columns) {return $columns + array ('tax_id' => 'ID');}
    function tax_show_id($v, $name, $id) {return 'tax_id' === $name ? $id : $v;}
    function tax_id_style() {print '<style>#tax_id{width:4em}</style>';}

// ================================================
//     "ID" для постов и страниц в админке
// ================================================
    add_filter('manage_posts_columns',          'posts_add_col', 5);
    add_action('manage_posts_custom_column',    'posts_show_id', 5, 2);
    add_filter('manage_pages_columns',          'posts_add_col', 5);
    add_action('manage_pages_custom_column',    'posts_show_id', 5, 2);
    add_action('admin_print_styles-edit.php',   'posts_id_style');
    // ==================================
    function posts_add_col($defaults) {$defaults['wps_post_id'] = __('ID'); return $defaults;}
    function posts_show_id($column_name, $id) {if ($column_name === 'wps_post_id') echo $id;}
    function posts_id_style() {print '<style>#wps_post_id{width:4em}</style>';}
}

// =========== END ===============================================



////////////////////////////////////////////////////////////////////
//          добавление столбца в таблицу продуктов                //
//                  WooCommerce Dashboard                         //
////////////////////////////////////////////////////////////////////
/**
 * @snippet       New Products Table Column @ WooCommerce Admin
 * @how-to        Watch tutorial @ https://businessbloomer.com/?p=19055
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 3.7
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
add_filter( 'manage_edit-product_columns', 'bbloomer_admin_products_visibility_column' );

function bbloomer_admin_products_visibility_column( $columns ){
   $columns['visibility'] = 'Visibility';
   return $columns;
}

add_action( 'manage_product_posts_custom_column', 'bbloomer_admin_products_visibility_column_content', 10, 2 );

function bbloomer_admin_products_visibility_column_content( $column, $product_id ){
    if ( $column == 'visibility' ) {
        $product = wc_get_product( $product_id );
      echo $product->get_catalog_visibility();
    }
}


// =========== END ===============================================

?>