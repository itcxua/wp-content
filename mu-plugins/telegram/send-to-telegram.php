<?php
// 🔒 Захист від прямого доступу до файлу (наприклад, через браузер)
if (php_sapi_name() !== 'cli' && !defined('WPINC')) {
    die;
}

// 📌 Реєстрація функції на хук 'init' WordPress
add_action('init', 'rkp_send_to_telegram');

function rkp_send_to_telegram() {
    // 🔄 Перевіряємо, чи це POST-запит
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    // ❗ Перевіряємо, чи є поля з форми
    if (!isset($_POST['form_fields']) || !is_array($_POST['form_fields'])) return;

    // 🔍 Отримуємо дані з полів форми
    $fields = $_POST['form_fields'];
    $name = trim($fields['name'] ?? '');
    $tel  = trim($fields['phone'] ?? '');

    // 🚫 Не відправляємо, якщо обидва поля порожні
    if ($name === '' && $tel === '') {
        file_put_contents(__DIR__ . "/telegram_log.txt", date("Y-m-d H:i:s") . " ❌ Порожня заявка\n", FILE_APPEND);
        return;
    }

    // 📨 Формування повідомлення для Telegram
    $message = "🔔 Нова заявка на Безкоштовну консультацію з сайту remontkonstruktor-partner.com:\n"
             . "👤 Ім’я: " . ($name ?: '---') . "\n"
             . "📞 Телефон: " . ($tel ?: '---') . "\n"
             . "🕒 Час: " . date("Y-m-d H:i");

    // 🔑 Дані для доступу до Telegram API
    $token = "7642059102:AAGtXw6krmMUjN1F_7F0PQGM7iL8bVFPWq4";
    $chat_id = "-4757414483";

    // 📡 URL API Telegram
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $message];

    // ⚙️ Налаштування HTTP-запиту
    $options = [
        "http" => [
            "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
            "method"  => "POST",
            "content" => http_build_query($data),
        ]
    ];

    // 📤 Відправлення повідомлення
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);

    // 📝 Логування відправленого повідомлення
    file_put_contents(__DIR__ . "/telegram_log.txt", date("Y-m-d H:i:s") . " ✅ Надіслано: $message\n", FILE_APPEND);

    // 🐞 Збереження debug-інформації (що було у POST)
    file_put_contents(__DIR__.'/debug.txt', print_r($_POST, true));
}

// 📧 Блокування відправки Email у Elementor Pro, якщо обов’язкові поля порожні
add_action('elementor_pro/forms/new_record', function($record, $handler) {
    // 🔄 Перевірка, чи є метод отримання налаштувань форми
    if (!method_exists($handler, 'get_form_settings')) return;

    // 📋 Отримуємо всі поля з форми
    $fields = $record->get('fields');
    $required = ['name', 'phone']; // Обов'язкові поля

    // ❌ Перевіряємо, чи не порожнє якесь із полів
    foreach ($required as $field_id) {
        if (empty($fields[$field_id]['value'])) {
            // 🛑 Запис у лог і додавання помилки до поля
            error_log("❌ Email не надіслано: поле '$field_id' порожнє");
            $handler->add_error($field_id, 'Це поле обов’язкове');
            return;
        }
    }
}, 10, 2);
