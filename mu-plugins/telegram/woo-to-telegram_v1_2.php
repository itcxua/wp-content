<?php
/**
 * Plugin Name: WooCommerce to Telegram (With Delivery Type)
 * Description: Повідомлення про замовлення WooCommerce у Telegram з уточненням доставки.
 */

add_action('woocommerce_checkout_order_processed', function ($order_id, $posted_data, $order) {
    if (!$order_id || !$order instanceof WC_Order) return;

    $first_name = $order->get_billing_first_name();
    $last_name  = $order->get_billing_last_name();
    $full_name  = trim("$first_name $last_name");
    $phone      = $order->get_billing_phone();
    $email      = $order->get_billing_email();
    $company    = $order->get_billing_company();
    $address    = $order->get_billing_address_1();
    $note       = $order->get_customer_note();
    $payment    = $order->get_payment_method_title();
    $shipping   = $order->get_shipping_method();
    $total      = $order->get_formatted_order_total();

    // 🔍 Визначення типу доставки (через текст в адресі або нотатці)
    $delivery_type = '';
    $combined_text = strtolower($address . ' ' . $note);
    if (strpos($combined_text, 'адресн') !== false || strpos($combined_text, 'кур\'єр') !== false) {
        $delivery_type = '(Потрібна адресна доставка)';
    } else {
        $delivery_type = '(На відділення)';
    }

    $items = '';
    foreach ($order->get_items() as $item) {
        $items .= "• " . $item->get_name() . " × " . $item->get_quantity() . "\n";
    }

    $message = "🛒 <b>Нове замовлення №{$order_id}</b>\n";
    $message .= "👤 Ім’я: $full_name\n";
    if ($company) $message .= "🏢 Компанія: $company\n";
    $message .= "📞 Телефон: $phone\n";
    $message .= "📧 Email: $email\n";
    if (!empty($address)) $message .= "📍 Адреса: $address\n";
    if (!empty($note)) $message .= "🗒 Коментар: $note\n";
    $message .= "🚚 Доставка: $shipping $delivery_type\n";
    $message .= "💳 Оплата: $payment\n";
    $message .= "📦 Товари:\n$items";
    $message .= "💰 Сума: $total";

    send_to_telegram_woo($message);
}, 10, 3);

function send_to_telegram_woo($text) {
    $bot_token = '7811814017:AAFNyLHzZgVu-TQgtJr8yu1uPznnmfvg6Eg';
    $chat_id   = '-1002549057153';

    $text = strip_tags($text);

    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $args = array(
        'body' => array(
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'HTML',
        )
    );

    $response = wp_remote_post($url, $args);
    file_put_contents(__DIR__ . '/woo-log.txt', "Telegram response:\n" . print_r($response, true), FILE_APPEND);
}
