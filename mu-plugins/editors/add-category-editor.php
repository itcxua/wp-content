<?php
////////////////////////////////////////////////////////////////////////////
//                                                                        //
//  Редактор для категорий (рубрик), меток и произвольных таксономий      //
//                                                                        //
////////////////////////////////////////////////////////////////////////////

remove_filter( 'pre_term_description', 'wp_filter_kses' );
remove_filter( 'term_description', 'wp_kses_data' );

function mayak_category_description($container = ''){
    $content = is_object($container) && isset($container->description) ? html_entity_decode($container->description) : '';
    $editor_id = 'tag_description';
    $settings = 'description';
    ?>
    <tr class="form-field">
        <th scope="row" valign="top"><label for="description">Описание</label></th>
        <td><?php wp_editor($content, $editor_id, array(
                    'textarea_name' => $settings,
                    'editor_css' => '<style> .html-active .wp-editor-area{border:0;}</style>',
        )); ?><br />
            <span class="description">Описание по умолчанию не отображается, однако некоторые темы могут его показывать.</span>
        </td>
    </tr>
    <?php
}
add_filter('edit_category_form_fields', 'mayak_category_description');
add_filter('edit_tag_form_fields', 'mayak_category_description');
// add_filter('product_cat_edit_form_fields', 'mayak_category_description');



/**
 * ===========================================================
 *      Убираем старое поле
 * ========================================
/*
function mayak_remove_category_description(){
    if ( $mk_description->id == 'edit-category' or 'edit-tag' ){
    ?>
        <script type="text/javascript">
        jQuery(function($) {
        $('textarea#description').closest('tr.form-field').remove();
        });
        </script>
    <?php
    }
}
add_action('admin_head', 'mayak_remove_category_description');
*/
// =========== END ===============================================

?>