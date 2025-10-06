<?php
/**
 * Plugin Name: WooCommerce EUR to UAH Full Manager
 * Description: Зберігання ціни в EUR, автоматичний розрахунок UAH, власний курс, логування, вибір джерела (НБУ / ПриватБанк).
 * Author: ChatGPT
 */

// === 1. Налаштування в адмінці ===
add_action('admin_init', function () {
    register_setting('general', 'eur_exchange_source');
    add_settings_field('eur_exchange_source_field', 'Джерело курсу EUR → UAH', function () {
        $val = get_option('eur_exchange_source', 'nbu');
        echo '<select name="eur_exchange_source">';
        echo '<option value="nbu" ' . selected($val, 'nbu', false) . '>НБУ</option>';
        echo '<option value="privat" ' . selected($val, 'privat', false) . '>ПриватБанк</option>';
        echo '</select>';
    }, 'general');

    register_setting('general', 'eur_log_enabled');
    add_settings_field('eur_log_enabled_field', 'Увімкнути лог оновлення курсу', function () {
        $val = get_option('eur_log_enabled', 'no');
        echo '<select name="eur_log_enabled">';
        echo '<option value="yes" ' . selected($val, 'yes', false) . '>Так</option>';
        echo '<option value="no" ' . selected($val, 'no', false) . '>Ні</option>';
        echo '</select>';
    }, 'general');
});

// === 2. Логування ===
function log_eur_rate($rate, $source) {
    if (get_option('eur_log_enabled') !== 'yes') return;
    $log = sprintf("[%s] Джерело: %s | Курс: %.2f\n", current_time('mysql'), strtoupper($source), $rate);
    file_put_contents(WP_CONTENT_DIR . '/eur_rate_log.txt', $log, FILE_APPEND);
}

// === 3. Отримання курсу ===
function fetch_eur_exchange_rate() {
    $source = get_option('eur_exchange_source', 'nbu');
    $rate = false;

    if ($source === 'nbu') {
        $response = wp_remote_get('https://bank.gov.ua/NBUStatService/v1/statdirectory/exchange?valcode=EUR&json');
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data[0]['rate'])) $rate = floatval($data[0]['rate']);
        }
    }

    if ($source === 'privat') {
        $response = wp_remote_get('https://api.privatbank.ua/p24api/pubinfo?json&exchange&coursid=5');
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            foreach ($data as $row) {
                if ($row['ccy'] === 'EUR' && $row['base_ccy'] === 'UAH') {
                    $rate = floatval($row['sale']);
                    break;
                }
            }
        }
    }

    if ($rate) {
        update_option('eur_to_uah_rate', $rate);
        update_option('eur_to_uah_rate_updated', current_time('mysql'));
        log_eur_rate($rate, $source);
        return $rate;
    }

    return false;
}

// === 4. Cron: щоденне оновлення ===
add_action('init', function () {
    if (false === get_transient('eur_to_uah_rate')) {
        fetch_eur_exchange_rate();
        set_transient('eur_to_uah_rate', true, 24 * HOUR_IN_SECONDS);
    }
});

// === 5. Поля в товарі ===
add_action('woocommerce_product_options_pricing', function () {
    global $post;
    $eur = get_post_meta($post->ID, '_eur_price', true);
    $custom_rate = get_post_meta($post->ID, '_custom_eur_rate', true);
    $rate = $custom_rate > 0 ? $custom_rate : get_option('eur_to_uah_rate', 42.0);
    $updated = get_option('eur_to_uah_rate_updated', '');

    woocommerce_wp_text_input([
        'id' => '_eur_price',
        'label' => 'Ціна в EUR',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);

    woocommerce_wp_text_input([
        'id' => '_custom_eur_rate',
        'label' => 'Власний курс EUR → грн',
        'description' => 'Якщо заповнено — цей курс буде використано',
        'type' => 'number',
        'custom_attributes' => ['step' => '0.01', 'min' => '0']
    ]);

    if ($eur) {
        $uah = round($eur * $rate, 2);
        echo "<p><strong>≈ {$uah} грн</strong> (курс: {$rate} грн/€)";
        if ($updated) echo " — оновлено: <code>{$updated}</code>";
        echo "</p>";
    }

    echo '<p><a href="' . admin_url('post.php?action=update_price_from_eur&post=' . $post->ID) . '" class="button">Оновити ціну з EUR</a></p>';
});

// === 6. Збереження полів ===
add_action('woocommerce_admin_process_product_object', function ($product) {
    $eur = floatval($_POST['_eur_price'] ?? 0);
    $custom_rate = floatval($_POST['_custom_eur_rate'] ?? 0);
    $product->update_meta_data('_eur_price', $eur);
    $product->update_meta_data('_custom_eur_rate', $custom_rate);
    $rate = $custom_rate > 0 ? $custom_rate : get_option('eur_to_uah_rate', 42.0);
    $uah = round($eur * $rate, 2);
    $product->set_regular_price($uah);
});

// === 7. Масове оновлення і поштучне ===
add_action('admin_notices', function () {
    if (get_current_screen()->id === 'edit-product') {
        $url = add_query_arg('force_recalc_prices', '1');
        echo '<div class="notice notice-info is-dismissible">
            <p><a href="' . esc_url($url) . '" class="button button-primary">🔄 Оновити ціни з EUR (масово)</a></p>
        </div>';
    }
});

add_action('admin_init', function () {
    if (isset($_GET['force_recalc_prices']) && current_user_can('manage_woocommerce')) {
        $products = wc_get_products(['limit' => -1, 'type' => 'simple']);
        $rate = get_option('eur_to_uah_rate', 42.0);
        $count = 0;

        foreach ($products as $product) {
            $eur = $product->get_meta('_eur_price');
            $custom_rate = $product->get_meta('_custom_eur_rate');
            $use_rate = $custom_rate > 0 ? $custom_rate : $rate;
            if ($eur && is_numeric($eur)) {
                $uah = round($eur * $use_rate, 2);
                $product->set_regular_price($uah);
                $product->save();
                $count++;
            }
        }

        add_action('admin_notices', function () use ($count) {
            echo '<div class="notice notice-success is-dismissible"><p>🔁 Оновлено цін: <strong>' . $count . '</strong></p></div>';
        });
    }

    if (isset($_GET['action']) && $_GET['action'] === 'update_price_from_eur' && isset($_GET['post'])) {
        $post_id = intval($_GET['post']);
        if (current_user_can('edit_post', $post_id)) {
            $product = wc_get_product($post_id);
            $eur = $product->get_meta('_eur_price');
            $custom_rate = $product->get_meta('_custom_eur_rate');
            $rate = $custom_rate > 0 ? $custom_rate : get_option('eur_to_uah_rate', 42.0);
            if ($eur && is_numeric($eur)) {
                $uah = round($eur * $rate, 2);
                $product->set_regular_price($uah);
                $product->save();
                wp_redirect(admin_url("post.php?post=$post_id&action=edit&updated_from_eur=1"));
                exit;
            }
        }
    }

    if (isset($_GET['updated_from_eur']) && $_GET['updated_from_eur'] == 1) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-success is-dismissible"><p>💱 Ціна успішно оновлена з EUR</p></div>';
        });
    }
});
