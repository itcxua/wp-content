<?php
/**
 * Plugin Name: Contact Form 7 to Telegram
 * Description: Відправляє повідомлення з Contact Form 7 у Telegram.
 */

add_action('wpcf7_mail_sent', function ($contact_form) {
    if ($contact_form->id() != '036b9fc') return;

    $submission = WPCF7_Submission::get_instance();
    if ($submission) {
        $data = $submission->get_posted_data();

        $name    = sanitize_text_field($data['your-name']);
        $email   = sanitize_email($data['your-email']);
        $message = sanitize_textarea_field($data['your-message']);
        $product = isset($data['product']) ? sanitize_text_field($data['product']) : '';

        $text = "✉️ <b>Нова заявка з форми</b>\n";
        $text .= "👤 Ім’я: $name\n";
        $text .= "📧 Email: $email\n";
        $text .= "📝 Повідомлення: $message\n";
        if ($product) $text .= "📦 Продукт: $product";

        send_to_telegram_cf7($text);
    }
});

function send_to_telegram_cf7($text) {
    $bot_token = '7697997945:AAHtPdQuz11x03201yaJP_JMXaW9ILFfsBg';
    $chat_id   = '-4737689419';

    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $args = array(
        'body' => array(
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
        )
    );
    wp_remote_post($url, $args);
}
