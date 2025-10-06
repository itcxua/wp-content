<?php
/**
 * Plugin Name: MU Copy To Anywhere
 * Description: Додає кнопку "COPY TO ..." для копіювання постів/сторінок/Elementor/WooCommerce у WordPress.
 * Author: itcxua
 * Version: 1.1
 */

add_action('post_submitbox_misc_actions', function () {
    global $post;
    if (!$post || !current_user_can('edit_posts')) return;

    $post_types = get_post_types(['public' => true], 'objects');
    echo '<div class="misc-pub-section"><label>📄 COPY TO: </label><select id="copy-to-type" style="margin-top: 4px;">';

    foreach ($post_types as $type => $obj) {
        if ($type !== $post->post_type) {
            echo '<option value="' . esc_attr($type) . '">' . esc_html($obj->labels->singular_name) . '</option>';
        }
    }

    echo '</select>';
    echo '<button type="button" class="button button-small" id="copy-to-btn" style="margin-top:5px;">🔁 Copy</button>';
    echo '</div>';
});

add_action('admin_footer', function () {
    global $post;
    if (!is_admin() || !$post) return;
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const btn = document.getElementById("copy-to-btn");
        const type = document.getElementById("copy-to-type");

        btn?.addEventListener("click", function () {
            if (!confirm("Підтвердити копіювання цього посту?")) return;
            const data = {
                action: "mu_copy_post",
                post_id: <?= (int)$post->ID ?>,
                post_type: type.value,
                _wpnonce: "<?= wp_create_nonce('mu_copy_nonce') ?>"
            };

            fetch(ajaxurl, {
                method: "POST",
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams(data)
            })
            .then(res => res.json())
            .then(res => {
                if (res.success && res.link) {
                    alert("Скопійовано успішно");
                    window.open(res.link, "_blank");
                } else {
                    alert("Помилка: " + res.data);
                }
            });
        });
    });
    </script>
    <?php
});

add_action('wp_ajax_mu_copy_post', function () {
    check_ajax_referer('mu_copy_nonce');

    $post_id = (int)$_POST['post_id'];
    $new_type = sanitize_text_field($_POST['post_type']);

    if (!current_user_can('edit_posts') || !$post_id || !$new_type) {
        wp_send_json_error('Invalid request.');
    }

    $original = get_post($post_id);
    if (!$original) wp_send_json_error('Original not found.');

    $new_post = [
        'post_title'   => $original->post_title . ' (копія)',
        'post_content' => $original->post_content,
        'post_status'  => 'draft',
        'post_type'    => $new_type,
        'post_author'  => get_current_user_id(),
    ];

    $new_id = wp_insert_post($new_post);
    if (is_wp_error($new_id)) wp_send_json_error($new_id->get_error_message());

    // Копіювання мета-полів
    $meta = get_post_meta($post_id);
    foreach ($meta as $key => $values) {
        foreach ($values as $value) {
            update_post_meta($new_id, $key, maybe_unserialize($value));
        }
    }

    // Копіювання термінів, зображення, ACF, WooCommerce
    do_action('mu_copy_post_extended', $post_id, $new_id);

    wp_send_json_success(['link' => get_edit_post_link($new_id, 'raw')]);
});

add_action('mu_copy_post_extended', function ($from_id, $to_id) {
    // Копіювання термінів
    $taxonomies = get_object_taxonomies(get_post_type($from_id));
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($from_id, $taxonomy, ['fields' => 'ids']);
        wp_set_object_terms($to_id, $terms, $taxonomy);
    }

    // Копіювання зображення
    $thumb = get_post_thumbnail_id($from_id);
    if ($thumb) {
        set_post_thumbnail($to_id, $thumb);
    }

    // Копіювання ACF
    if (function_exists('get_fields')) {
        $fields = get_fields($from_id);
        foreach ($fields as $k => $v) {
            update_field($k, $v, $to_id);
        }
    }

    // WooCommerce: якщо це продукт
    if (class_exists('WooCommerce') && get_post_type($from_id) === 'product') {
        $product = wc_get_product($from_id);
        if ($product) {
            $meta = $product->get_meta_data();
            foreach ($meta as $m) {
                update_post_meta($to_id, $m->key, $m->value);
            }
        }
    }
});
