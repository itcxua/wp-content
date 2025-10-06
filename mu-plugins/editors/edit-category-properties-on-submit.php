<?php
/*
Plugin Name: Update Category Properties on Submit
Description: Зберігає parent, опис, slug при стандартному оновленні категорії product_cat.
Version: 1.0
Author: AutoGen
*/

// Додаємо поля в форму редагування категорії
add_action('product_cat_edit_form_fields', function ($term) {
    ?>
    <tr class="form-field">
        <th scope="row"><label for="custom_parent">Батьківська категорія</label></th>
        <td>
            <?php
            wp_dropdown_categories([
                'taxonomy'         => 'product_cat',
                'hide_empty'       => 0,
                'name'             => 'custom_parent',
                'orderby'          => 'name',
                'hierarchical'     => true,
                'show_option_none' => '— Немає —',
                'selected'         => $term->parent,
                'exclude'          => $term->term_id // виключити саму себе
            ]);
            ?>
        </td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="custom_desc">Опис (Description)</label></th>
        <td><textarea name="custom_desc" id="custom_desc" rows="4" cols="40"><?= esc_textarea($term->description) ?></textarea></td>
    </tr>
    <tr class="form-field">
        <th scope="row"><label for="custom_slug">Slug (мітка)</label></th>
        <td><input name="custom_slug" id="custom_slug" type="text" value="<?= esc_attr($term->slug) ?>" /></td>
    </tr>
    <?php
}, 10, 1);

// Зберігаємо при натисканні "Update"
add_action('edited_product_cat', function ($term_id) {
    if (!current_user_can('manage_categories')) return;

    $parent = isset($_POST['custom_parent']) ? intval($_POST['custom_parent']) : null;
    $desc   = isset($_POST['custom_desc']) ? sanitize_text_field($_POST['custom_desc']) : '';
    $slug   = isset($_POST['custom_slug']) ? sanitize_title($_POST['custom_slug']) : '';

    // Сам собі parent бути не може
    if ($parent == $term_id) {
        $parent = 0;
    }

    $args = [];
    if (!is_null($parent)) $args['parent'] = $parent;
    if ($desc !== '')      $args['description'] = $desc;
    if ($slug !== '')      $args['slug'] = $slug;

    if (!empty($args)) {
        wp_update_term($term_id, 'product_cat', $args);
    }
}, 10, 1);
