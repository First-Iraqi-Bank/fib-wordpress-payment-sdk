<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_FIB_API_Payment {

    public static function create_qr_code($order, $access_token) {
        $api_url_payment = get_option('fib_api_url_payment');
        // Create a nonce
		$nonce = wp_create_nonce('wp_rest');

        if (empty($api_url_payment)) {
            throw new Exception(__('Please configure your FIB settings.', 'woocommerce-gateway-fib'));
        }

        $response = wp_remote_post($api_url_payment, array(
            'headers' => array(
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'body' => json_encode(array(
                'monetaryValue' => array(
                    'amount' => $order->get_total(),
                    'currency' => 'IQD',
                ),
                'statusCallbackUrl' => 'https://URL_TO_UPDATE_YOUR_PAYMENT_STATUS',
                'description' => 'Lorem ipsum dolor sit amet.',
            )),
            'sslverify' => false, // IMPORTANT: remove this line in production
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            throw new Exception("Something went wrong: $error_message");
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        $_SESSION['payment_id'] = $response_data['paymentId'];

        return $response_data['qrCode'];
    }
}
