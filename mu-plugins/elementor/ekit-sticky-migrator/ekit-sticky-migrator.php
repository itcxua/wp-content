<?php
/**
 * Plugin Name: EKIT Sticky Meta Migrator (Admin UI Advanced)
 * Description: Адмін-інтерфейс для перенесення ekit_sticky_on з JSON Elementor до окремого meta_key.
 * Version: 1.2
 * Author: itcxua
 */

add_action('admin_menu', function () {
    add_management_page(
        'EKIT Sticky Migrator',
        'EKIT Sticky Migrator',
        'manage_options',
        'ekit-sticky-migrator',
        'ekit_sticky_migrator_admin_page'
    );
});

function ekit_sticky_migrator_admin_page() {
    $log_file = __DIR__ . '/ekit-migrator.log';
    $message = '';

    if (isset($_POST['ekit_action']) && check_admin_referer('ekit_migrate_action')) {
        global $wpdb;

        switch ($_POST['ekit_action']) {
            case 'migrate':
                $results = $wpdb->get_results("
                    SELECT post_id, meta_value
                    FROM {$wpdb->prefix}postmeta
                    WHERE meta_key = '_elementor_data'
                ");

                $migrated = 0;
                foreach ($results as $row) {
                    $json = json_decode($row->meta_value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) continue;

                    $found = ekit_recursive_search($json, 'ekit_sticky_on');
                    if ($found !== null) {
                        update_post_meta($row->post_id, 'ekit_sticky_on', $found);
                        $migrated++;
                    }
                }

                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Міграція: $migrated записів.\n", FILE_APPEND);
                $message = "✅ Міграція завершена. Опрацьовано: $migrated записів.";
                break;

            case 'clean':
                $deleted = $wpdb->query("
                    DELETE FROM {$wpdb->prefix}postmeta
                    WHERE meta_key = 'ekit_sticky_on'
                ");
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] Очищено: $deleted записів.\n", FILE_APPEND);
                $message = "🧹 Очищено $deleted записів з meta_key = ekit_sticky_on.";
                break;

            case 'check':
                $results = $wpdb->get_results("
                    SELECT post_id, meta_value
                    FROM {$wpdb->prefix}postmeta
                    WHERE meta_key = '_elementor_data'
                ");

                $count = 0;
                foreach ($results as $row) {
                    $json = json_decode($row->meta_value, true);
                    if (json_last_error() !== JSON_ERROR_NONE) continue;

                    if (ekit_recursive_search($json, 'ekit_sticky_on') !== null) {
                        $count++;
                    }
                }

                $message = "ℹ️ Виявлено $count записів, які містять ekit_sticky_on.";
                break;
        }
    }

    echo '<div class="wrap"><h1>EKIT Sticky Migrator</h1>';
    if ($message) {
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    }

    echo '<form method="post">';
    wp_nonce_field('ekit_migrate_action');

    submit_button('🔁 Повторно виконати міграцію', 'primary', 'ekit_action', false);
    echo '<input type="hidden" name="ekit_action" value="migrate">';
    echo '</form>';

    echo '<form method="post" style="margin-top: 1em;">';
    wp_nonce_field('ekit_migrate_action');
    submit_button('🧹 Очистити ekit_sticky_on', 'delete', 'ekit_action', false);
    echo '<input type="hidden" name="ekit_action" value="clean">';
    echo '</form>';

    echo '<form method="post" style="margin-top: 1em;">';
    wp_nonce_field('ekit_migrate_action');
    submit_button('⚙️ Перевірити наявність ekit_sticky_on', 'secondary', 'ekit_action', false);
    echo '<input type="hidden" name="ekit_action" value="check">';
    echo '</form>';

    echo '<h2>Журнал</h2>';
    $log = file_exists($log_file) ? file_get_contents($log_file) : '';
    echo '<pre style="background:#f5f5f5; max-height:300px; overflow:auto; padding:1em;">' . esc_html($log) . '</pre>';
    echo '</div>';
}

function ekit_recursive_search($array, $key) {
    foreach ($array as $k => $v) {
        if ($k === $key) return $v;
        if (is_array($v)) {
            $result = ekit_recursive_search($v, $key);
            if ($result !== null) return $result;
        }
    }
    return null;
}
