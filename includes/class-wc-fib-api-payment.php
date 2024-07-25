<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_FIB_API_Payment {

    public static function create_qr_code($order, $access_token) {
        session_start();
        $fib_base_url = get_option('fib_base_url');

        // Create a nonce
		$nonce = wp_create_nonce('wp_rest');

        if (empty($fib_base_url)) {
            wc_add_notice('Please configure your FIB settings.', 'error' ); 
            exit;
        }
        if (empty($access_token)) {
            wc_add_notice('Unauthorized or expired token.', 'error' ); 
            exit;
        }

        $response = wp_remote_post($fib_base_url . '/protected/v1/payments', array(
            'headers' => array(
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'body' => wp_json_encode(array(
                'monetaryValue' => array(
                    'amount' => $order->get_total(),
                    'currency' => 'IQD',
                ),
                'statusCallbackUrl' => 'https://URL_TO_UPDATE_YOUR_PAYMENT_STATUS',
                'description' => 'Lorem ipsum dolor sit amet.',
            )),
            // 'sslverify' => false, // IMPORTANT: remove this line in production
        ));

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code != 200 && $response_code != 201) {
            wc_add_notice('Something went wrong: ' . wp_remote_retrieve_body($response), 'error' );
            exit;
        }
        $_SESSION['payment_id'] = $response_data['paymentId'];
        return $response_data['qrCode'];
    }
    public static function cancel_qr_code($payment_id, $access_token) {
        session_start();
        $fib_base_url = get_option('fib_base_url');

		$nonce = wp_create_nonce('wp_rest');

        if (empty($fib_base_url)) {
            wc_add_notice('Please configure your FIB settings.', 'error' );
            exit;
        }
        if (empty($access_token)) {
            wc_add_notice('Unauthorized or expired token.', 'error' );
            exit;
        }
        if (empty($payment_id)) {
            wc_add_notice('Payment Id is not provided.', 'error' );
            exit;
        }

        $response = wp_remote_post($fib_base_url . '/protected/v1/payments/' . $payment_id . '/cancel', array(
            'headers' => array(
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        $response_code = wp_remote_retrieve_response_code($response);

        unset($_SESSION['payment_id']);
        return $response_code;
    }
}
