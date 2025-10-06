<?php
/**
 * Plugin Name: Crystal House Calculator
 * Description: Калькулятор послуг прибирання з відправкою в Telegram
 * Version: 1.0.3
 * Author: Авраменко Александр
 */

// Запрещаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

class CrystalHouseCalculator {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_crystal_send_telegram', array($this, 'handle_telegram_request'));
        add_action('wp_ajax_nopriv_crystal_send_telegram', array($this, 'handle_telegram_request'));
        add_action('wp_ajax_crystal_test_chats', array($this, 'handle_test_chats'));
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // Отладка
        add_action('wp_footer', array($this, 'debug_scripts'));
    }
    
    public function init() {
        add_shortcode('crystal_calculator', array($this, 'render_calculator'));
    }
    
    public function enqueue_scripts() {
        // Загружаем на всех страницах
        wp_enqueue_script('jquery');
        wp_enqueue_script('crystal-calculator', plugin_dir_url(__FILE__) . 'calculator.js', array('jquery'), '1.0.3', true);
        wp_localize_script('crystal-calculator', 'crystal_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('crystal_nonce'),
            'page_url' => get_permalink(),
            'page_title' => get_the_title()
        ));
    }
    
    public function debug_scripts() {
        if (is_user_logged_in() && current_user_can('administrator')) {
            ?>
            <script>
            console.log('Crystal Calculator Debug:');
            console.log('AJAX URL:', '<?php echo admin_url('admin-ajax.php'); ?>');
            console.log('Plugin URL:', '<?php echo plugin_dir_url(__FILE__); ?>');
            console.log('Calculator JS exists:', typeof crystal_ajax !== 'undefined');
            </script>
            <?php
        }
    }
    
    public function render_calculator($atts) {
        $atts = shortcode_atts(array(
            'bot_token' => get_option('crystal_bot_token', ''),
            'chat_ids' => get_option('crystal_chat_ids', '')
        ), $atts);
        
        ob_start();
        $template_path = plugin_dir_path(__FILE__) . 'calculator-template.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo '<div style="color:red;">Помилка: шаблон калькулятора не знайдено в ' . $template_path . '</div>';
        }
        
        return ob_get_clean();
    }
    
    public function handle_telegram_request() {
        // Логирование для отладки
        error_log('Crystal Calculator: AJAX request received');
        
        // Проверяем nonce
        if (!wp_verify_nonce($_POST['nonce'], 'crystal_nonce')) {
            error_log('Crystal Calculator: Nonce verification failed');
            wp_send_json_error('Невірна безпекова перевірка');
            return;
        }
        
        $bot_token = get_option('crystal_bot_token');
        $chat_ids_raw = get_option('crystal_chat_ids');
        
        if (empty($bot_token) || empty($chat_ids_raw)) {
            wp_send_json_error('Telegram налаштування не встановлені в адмін панелі');
            return;
        }
        
        // Парсим Chat IDs
        $chat_ids = array_filter(array_map('trim', explode("\n", $chat_ids_raw)));
        
        if (empty($chat_ids)) {
            wp_send_json_error('Не знайдено жодного Chat ID');
            return;
        }
        
        error_log('Crystal Calculator: Found ' . count($chat_ids) . ' chat IDs');
        
        // Получаем данные
        $form_data_json = sanitize_text_field($_POST['formData']);
        $data = json_decode(stripslashes($form_data_json), true);
        
        if (!$data || !isset($data['clientName']) || !isset($data['clientPhone'])) {
            wp_send_json_error('Відсутні обов\'язкові дані');
            return;
        }
        
        // Подготавливаем сообщения
        $messages = $this->prepare_telegram_messages($data);
        
        if (empty($messages)) {
            wp_send_json_error('Помилка підготовки повідомлення');
            return;
        }
        
        // Отправляем во все чаты
        $success_count = 0;
        $total_chats = count($chat_ids);
        
        foreach ($chat_ids as $chat_id) {
            $success = $this->send_multiple_telegram_messages($bot_token, $chat_id, $messages);
            if ($success) {
                $success_count++;
                error_log("Crystal Calculator: Messages sent successfully to chat $chat_id");
            } else {
                error_log("Crystal Calculator: Failed to send messages to chat $chat_id");
            }
            
            // Пауза между отправками в разные чаты
            sleep(1);
        }
        
        if ($success_count > 0) {
            $message = "Заявка успішно відправлена! Доставлено в $success_count з $total_chats чатів.";
            wp_send_json_success($message);
        } else {
            wp_send_json_error('Помилка відправки до Telegram у всіх чатах');
        }
    }
    
    private function send_telegram_message($bot_token, $chat_id, $message) {
        $url = "https://api.telegram.org/bot$bot_token/sendMessage";
        
        $data = array(
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        );
        
        $response = wp_remote_post($url, array(
            'body' => $data,
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            error_log('Crystal Calculator: Telegram API error - ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        error_log('Crystal Calculator: Telegram API response - ' . $body);
        
        return $result && $result['ok'];
    }
    
    private function send_multiple_telegram_messages($bot_token, $chat_id, $messages) {
        $all_sent = true;
        
        foreach ($messages as $index => $message) {
            $success = $this->send_telegram_message($bot_token, $chat_id, $message);
            if (!$success) {
                $all_sent = false;
            }
            
            if ($index < count($messages) - 1) {
                sleep(1);
            }
        }
        
        return $all_sent;
    }
    
    private function prepare_telegram_messages($data) {
        $messages = array();
        
        // 1. ОСНОВНОЕ СООБЩЕНИЕ
        $message1 = "🧹 <b>НОВА ЗАЯВКА НА ПРИБИРАННЯ</b>\n\n";
        
        // Контактная информация
        $message1 .= "👤 <b>КОНТАКТНА ІНФОРМАЦІЯ:</b>\n";
        $message1 .= "• Ім'я: " . ($data['clientName'] ?? 'Не вказано') . "\n";
        $message1 .= "• Телефон: " . ($data['clientPhone'] ?? 'Не вказано') . "\n\n";
        
        $message1 .= "📋 <b>ОСНОВНА ІНФОРМАЦІЯ:</b>\n";
        $message1 .= "• Пакет: " . ($data['package'] ?? 'Не вказано') . "\n";
        $message1 .= "• Площа: " . ($data['area'] ?? '0') . " м²\n";
        $message1 .= "• Забрудненість: " . ($data['soil'] ?? 'Стандарт') . "\n";
        
        if (!empty($data['rate'])) {
            $message1 .= "• Ставка: " . ($data['rate'] ?? '0') . " грн/м²\n";
        }
        
        $message1 .= "• Санвузли: " . ($data['bathrooms'] ?? '1') . " шт\n";
        $message1 .= "• Унітази: " . ($data['toilets'] ?? '1') . " шт\n";
        
        if (!empty($data['distance']) && $data['distance'] > 0) {
            $message1 .= "• Виїзд за місто: " . ($data['distance'] ?? '0') . " км\n";
        }
        
        if (!empty($data['loyaltyDiscount'])) {
            $message1 .= "• 5-те прибирання: Так (знижка -15%)\n";
        }
        
        $message1 .= "\n💰 <b>ЗАГАЛЬНА СУМА: " . ($data['total'] ?? '0') . "</b>";
        
        // Добавляем информацию о странице
        $message1 .= "\n\n🌐 <b>ДЖЕРЕЛО ЗАЯВКИ:</b>\n";
        if (!empty($data['pageTitle'])) {
            $message1 .= "• Сторінка: " . $data['pageTitle'] . "\n";
        }
        if (!empty($data['pageUrl'])) {
            $message1 .= "• URL: " . $data['pageUrl'] . "\n";
        }
        
        $message1 .= "• Час заявки: " . current_time('d.m.Y H:i:s');
        
        $messages[] = $message1;
        
        // 2. ДОПОЛНИТЕЛЬНЫЕ УСЛУГИ
        $extras = array();
        
        if (!empty($data['windows'])) {
            $extras[] = "🪟 Миття вікон (" . ($data['windowsArea'] ?? '0') . " м² × " . ($data['windowsRate'] ?? '240') . " грн)";
        }
        
        if (!empty($data['balcony'])) {
            $extras[] = "🏠 Балкон (" . ($data['balconyQty'] ?? '1') . " шт)";
        }
        
        if (!empty($data['loggia'])) {
            $extras[] = "🏢 Лоджія (" . ($data['loggiaQty'] ?? '1') . " шт)";
        }
        
        $additional_services = array(
            'walls' => '🧽 Миття стін',
            'ironing' => '👔 Прасування',
            'dishes' => '🍽️ Миття посуду',
            'pettray' => '🐱 Миття лотка улюбленця',
            'laundry' => '👕 Прання',
            'hood' => '💨 Миття витяжки',
            'oven' => '🔥 Миття духовки',
            'microwave' => '📱 Мікрохвильова',
            'fridge' => '❄️ Холодильник',
            'kitchencab' => '🚪 Шафи кухонні',
            'grill' => '🔥 Миття грилю',
            'addbath' => '🚿 Додатковий санвузол',
            'addtoilet' => '🚽 Додатковий унітаз',
            'organize' => '📦 Складання речей',
            'curtains' => '🪟 Штори/тюлі',
            'vacuum' => '🧹 Пилосос компанії',
            'keys' => '🗝️ Доставка ключів'
        );
        
        foreach ($additional_services as $key => $label) {
            if (!empty($data[$key])) {
                $qty = !empty($data[$key . 'Qty']) ? " (" . $data[$key . 'Qty'] . ")" : "";
                $amount = !empty($data['curtainsAmount']) && $key === 'curtains' ? " (" . $data['curtainsAmount'] . " грн)" : "";
                $extras[] = $label . $qty . $amount;
            }
        }
        
        // Химчистка
        $chemServices = [
            'chemsofa2Qty' => 'Диван 2 місця',
            'chemsofa3Qty' => 'Диван 3 місця', 
            'chemsofa4Qty' => 'Диван 4 місця',
            'chemsofa5Qty' => 'Диван 5 місць',
            'chemsofa6Qty' => 'Диван 6 місць',
            'chemarmchairQty' => 'Крісло',
            'chemmattress2Qty' => 'Матрац двомісний',
            'chemmattress1Qty' => 'Матрац одномісний',
            'chemchairQty' => 'Стільці',
            'chemheadboard' => 'Бильце ліжка'
        ];

        foreach ($chemServices as $key => $label) {
            if (!empty($data[$key]) && $data[$key] > 0) {
                $qty = $data[$key];
                if ($key === 'chemheadboard') {
                    $extras[] = "🧼 " . $label . " (" . $qty . " грн)";
                } else {
                    $extras[] = "🧼 " . $label . " (" . $qty . " шт)";
                }
            }
        }

        // Подушки отдельно
        if (!empty($data['chemPillow']) && $data['chemPillow'] > 0) {
            $pillowRate = $data['chemPillowRate'] ?? 60;
            $extras[] = "🧼 Подушки (" . $data['chemPillow'] . " × " . $pillowRate . " грн)";
        }
        
        if (!empty($extras)) {
            $message2 = "🔧 <b>ДОДАТКОВІ ПОСЛУГИ:</b>\n\n";
            foreach ($extras as $extra) {
                $message2 .= "• " . $extra . "\n";
            }
            $messages[] = $message2;
        }
        
        return $messages;
    }
    
    private function test_chat_access($bot_token, $chat_id) {
        $url = "https://api.telegram.org/bot$bot_token/getChat";
        
        $response = wp_remote_post($url, array(
            'body' => array('chat_id' => $chat_id),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);
        
        return $result && $result['ok'];
    }

    // И метод для тестирования всех чатов
    public function test_all_chats() {
        $bot_token = get_option('crystal_bot_token');
        $chat_ids_raw = get_option('crystal_chat_ids');
        
        if (empty($bot_token) || empty($chat_ids_raw)) {
            return array('error' => 'Налаштування не встановлені');
        }
        
        $chat_ids = array_filter(array_map('trim', explode("\n", $chat_ids_raw)));
        $results = array();
        
        foreach ($chat_ids as $chat_id) {
            $accessible = $this->test_chat_access($bot_token, $chat_id);
            $results[$chat_id] = $accessible;
        }
        
        return $results;
    }
    
    public function handle_test_chats() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Немає прав доступу');
            return;
        }
        
        $results = $this->test_all_chats();
        
        if (isset($results['error'])) {
            wp_send_json_error($results['error']);
        } else {
            wp_send_json_success($results);
        }
    }
    
    public function admin_menu() {
        add_options_page(
            'Crystal House Calculator',
            'Crystal Calculator',
            'manage_options',
            'crystal-calculator',
            array($this, 'admin_page')
        );
    }
    
    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('crystal_bot_token', sanitize_text_field($_POST['bot_token']));
            update_option('crystal_chat_ids', sanitize_textarea_field($_POST['chat_ids']));
            echo '<div class="notice notice-success"><p>Налаштування збережено!</p></div>';
        }
        
        $bot_token = get_option('crystal_bot_token', '');
        $chat_ids = get_option('crystal_chat_ids', '');
        ?>
        <div class="wrap">
            <h1>Crystal House Calculator</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row">Telegram Bot Token</th>
                        <td>
                            <input type="text" name="bot_token" value="<?php echo esc_attr($bot_token); ?>" class="regular-text" />
                            <p class="description">Отримайте токен від @BotFather в Telegram</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Chat IDs</th>
                        <td>
                            <textarea name="chat_ids" rows="5" class="large-text" placeholder="1679157073&#10;-1001234567890&#10;987654321"><?php echo esc_textarea($chat_ids); ?></textarea>
                            <p class="description">
                                <strong>Введіть кожен Chat ID з нового рядка:</strong><br>
                                • Особистий чат: звичайний ID (наприклад: 1679157073)<br>
                                • Група/канал: ID з мінусом (наприклад: -1001234567890)<br>
                                • Можна додавати до 10 отримувачів
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <h2>Тестування доступу до чатів</h2>
            <button type="button" id="testChats" class="button">Перевірити доступ до чатів</button>
            <div id="testResults" style="margin-top:10px;"></div>
            
            <h2>Використання</h2>
            <p>Додайте шорткод <code>[crystal_calculator]</code> на будь-яку сторінку або пост.</p>
            <p>Для Elementor: додайте віджет "Шорткод" і вставте <code>[crystal_calculator]</code></p>
            
            <h2>Відладка</h2>
            <p>Токен бота: <?php echo $bot_token ? '<span style="color:green;">Встановлено</span>' : '<span style="color:red;">Не встановлено</span>'; ?></p>
            <?php 
            if ($chat_ids) {
                $ids_array = array_filter(array_map('trim', explode("\n", $chat_ids)));
                echo '<p>Chat IDs (' . count($ids_array) . '): <span style="color:green;">Встановлено</span></p>';
                echo '<ul>';
                foreach ($ids_array as $id) {
                    echo '<li>' . esc_html($id) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>Chat IDs: <span style="color:red;">Не встановлено</span></p>';
            }
            ?>
            
            <h2>Важливі примітки</h2>
            <ul>
                <li><strong>Особистий чат:</strong> Користувач повинен спочатку написати боту (натиснути /start)</li>
                <li><strong>Група:</strong> Додайте бота в групу як адміністратора</li>
                <li><strong>Канал:</strong> Додайте бота в канал як адміністратора з правами публікації</li>
                <li><strong>URL сторінки:</strong> Автоматично додається до кожної заявки</li>
            </ul>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#testChats').click(function() {
                var $btn = $(this);
                var $results = $('#testResults');
                
                $btn.prop('disabled', true).text('Перевіряю...');
                $results.html('<p>Тестуємо доступ до чатів...</p>');
                
                $.post(ajaxurl, {
                    action: 'crystal_test_chats'
                }, function(response) {
                    if (response.success) {
                        var html = '<h3>Результати тестування:</h3><ul>';
                        $.each(response.data, function(chatId, accessible) {
                            var status = accessible ? '<span style="color:green;">✅ Доступний</span>' : '<span style="color:red;">❌ Недоступний</span>';
                            html += '<li><strong>' + chatId + ':</strong> ' + status + '</li>';
                        });
                        html += '</ul>';
                        $results.html(html);
                    } else {
                        $results.html('<p style="color:red;">Помилка: ' + response.data + '</p>');
                    }
                }).always(function() {
                    $btn.prop('disabled', false).text('Перевірити доступ до чатів');
                });
            });
        });
        </script>
        <?php
    }
}

new CrystalHouseCalculator();
