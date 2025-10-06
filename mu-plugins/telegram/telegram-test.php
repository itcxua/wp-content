<?php
/**
 * Plugin Name: Telegram API Test
 * Description: Тестовий плагін для перевірки надсилання повідомлення у Telegram.
 */

add_action('init', function () {
    // Активується за посиланням: вашсайт.com/?send_telegram_test=1
    if (isset($_GET['send_telegram_test'])) {
        $bot_token = '7697997945:AAHtPdQuz11x03201yaJP_JMXaW9ILFfsBg';
        $chat_id   = '-4737689419';
        $message   = "✅ Тестове повідомлення з WordPress";

        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $args = array(
            'body' => array(
                'chat_id' => $chat_id,
                'text' => $message,
                'parse_mode' => 'HTML',
            )
        );

        $response = wp_remote_post($url, $args);

        echo '<pre>';
        print_r($response);
        echo '</pre>';
        exit;
    }
});
