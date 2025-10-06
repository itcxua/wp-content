<?php
/**
 * Plugin Name: Move Shipping Block via JS
 * Description: Переміщує спосіб доставки DOM-блоком у верхню частину сторінки перед реквізитами.
 * Author: itcxua
 * Version: 1.5
 */

// Виводимо блок "Спосіб доставки" перед реквізитами рахунку
//add_action('woocommerce_checkout_before_customer_details', 'itcxua_output_shipping_above_billing', 1);
function itcxua_output_shipping_above_billing() {
    if (is_checkout() && !is_wc_endpoint_url()) {
        echo '<div class="itcxua-shipping-methods" style="margin-bottom:20px;">';
        echo '<h3 style="font-size: 20px; margin-bottom: 10px;">Спосіб доставки</h3>';
        do_action('woocommerce_review_order_before_shipping');
        echo '</div>';
    }
}

add_action('wp_footer', 'itcxua_move_shipping_block_js');
function itcxua_move_shipping_block_js() {
    if (!is_checkout()) return;

    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const shippingBlock = document.querySelector('.woocommerce-shipping-totals');
        const customerDetails = document.querySelector('#customer_details');

        if (shippingBlock && customerDetails) {
            const cloned = shippingBlock.cloneNode(true);
            cloned.classList.add('cloned-shipping-method');
            customerDetails.parentNode.insertBefore(cloned, customerDetails);

            // Приховуємо оригінал внизу
            shippingBlock.style.display = 'none';
        }
    });
    </script>
    <style>
    .cloned-shipping-method {
        padding: 20px 0;
        border-bottom: 1px solid #333;
    }
    .cloned-shipping-method label {
        margin-left: 8px;
    }
    </style>
    <?php
}
