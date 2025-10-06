<?php
/*
Plugin Name: Extended Bulk Edit for WooCommerce Categories
Description: Масове редагування властивостей категорій WooCommerce з валідацією.
Version: 3.0
Author: AutoGen
*/

add_action('admin_footer-edit-tags.php', function () {
    $screen = get_current_screen();
    if ($screen->taxonomy !== 'product_cat') return;

    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const bulkSelects = document.querySelectorAll('select[name="action"], select[name="action2"]');
        bulkSelects.forEach(select => {
            const opt = new Option("Масова зміна властивостей", "bulk_edit_custom");
            select.appendChild(opt);
        });

        document.querySelector('#posts-filter')?.addEventListener('submit', function (e) {
            const action = document.querySelector('select[name="action"]').value || document.querySelector('select[name="action2"]').value;
            if (action === 'bulk_edit_custom') {
                e.preventDefault();

                const selected = Array.from(document.querySelectorAll('tbody input[type="checkbox"]:checked')).map(i => i.value);
                if (!selected.length) {
                    alert('Оберіть категорії!');
                    return;
                }

                const parentSelect = document.createElement('select');
                parentSelect.innerHTML = `<option value="">— Без змін —</option><option value="0">— Немає —</option>`;
                <?php foreach ($terms as $term): ?>
                    parentSelect.innerHTML += `<option value="<?= $term->term_id ?>"><?= esc_js($term->name) ?></option>`;
                <?php endforeach; ?>

                const dialog = document.createElement('div');
                dialog.style.cssText = 'position:fixed;top:20%;left:30%;background:#fff;border:1px solid #ccc;padding:20px;z-index:10000';
                dialog.innerHTML = `
                    <h3>Масова зміна категорій</h3>
                    <label>Батьківська категорія:</label><br/>
                    ${parentSelect.outerHTML}<br/><br/>
                    <label>Опис:</label><br/>
                    <textarea id="bulk_desc" style="width:100%;height:50px" placeholder="Залиште порожнім, щоб не змінювати"></textarea><br/><br/>
                    <label>Slug (мітка):</label><br/>
                    <input type="text" id="bulk_slug" style="width:100%" placeholder="Не змінювати"><br/><br/>
                    <button id="bulk_apply">Застосувати</button>
                `;

                document.body.appendChild(dialog);

                document.getElementById('bulk_apply').addEventListener('click', function () {
                    const parent = dialog.querySelector('select').value;
                    const desc = document.getElementById('bulk_desc').value.trim();
                    const slug = document.getElementById('bulk_slug').value.trim();

                    // Перевірка: чи parent не входить до вибраних
                    if (selected.includes(parent)) {
                        alert('Категорія не може бути своєю батьківською!');
                        return;
                    }

                    fetch(ajaxurl, {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: new URLSearchParams({
                            action: 'epc_bulk_update_all',
                            ids: selected.join(','),
                            parent_id: parent,
                            desc: desc,
                            slug: slug,
                            _wpnonce: '<?php echo wp_create_nonce("epc_nonce"); ?>'
                        })
                    }).then(res => res.json()).then(res => {
                        if (res.success) {
                            alert('Категорії оновлено');
                            location.reload();
                        } else {
                            alert('Помилка: ' + res.data);
                        }
                    });
                });
            }
        });
    });
    </script>
    <?php
});

// AJAX: оновлення parent, description, slug
add_action('wp_ajax_epc_bulk_update_all', function () {
    check_ajax_referer('epc_nonce');

    if (!current_user_can('manage_categories')) {
        wp_send_json_error('Недостатньо прав');
    }

    $ids = array_map('intval', explode(',', $_POST['ids']));
    $parent_id = $_POST['parent_id'] === '' ? null : intval($_POST['parent_id']);
    $desc = sanitize_text_field($_POST['desc']);
    $slug = sanitize_title($_POST['slug']);

    foreach ($ids as $id) {
        // Валідація: не може бути parent сам собі
        if ($parent_id !== null && $id === $parent_id) continue;

        $args = [];
        if ($parent_id !== null) $args['parent'] = $parent_id;
        if (!empty($desc)) $args['description'] = $desc;
        if (!empty($slug)) $args['slug'] = $slug . '-' . $id;

        wp_update_term($id, 'product_cat', $args);
    }

    wp_send_json_success('Оновлено');
});
