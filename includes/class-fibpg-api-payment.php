<?php

if (!defined('ABSPATH')) {
    exit();
}

class FIBPG_API_Payment {

    public static function create_qr_code($order, $access_token) {
        session_start();
        $fib_base_url = get_option('fibpg_base_url');

        // Create a nonce
		$nonce = wp_create_nonce('wp_rest');

        if (empty($fib_base_url)) {
            wc_add_notice(esc_html__('Please configure your FIB settings.', 'error'), 'error' ); 
            exit;
        }
        if (empty($access_token)) {
            wc_add_notice(esc_html__('Unauthorized or expired token.', 'error'), 'error' ); 
            exit;
        }

        $response = wp_remote_post(esc_url_raw($fib_base_url . '/protected/v1/payments'), array(
            'headers' => array(
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . sanitize_text_field($access_token),
            ),
            'body' => wp_json_encode(array(
                'monetaryValue' => array(
                    'amount' => $order->get_total(),
                    'currency' => 'IQD',
                ),
                'statusCallbackUrl' => esc_url_raw('https://URL_TO_UPDATE_YOUR_PAYMENT_STATUS'),
                'description' => sanitize_text_field('FIB Payment for Order #' . $order->get_id()),
            )),
            // 'sslverify' => false, // IMPORTANT: remove this line in production
            'timeout' => 45,
        ));

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code != 200 && $response_code != 201) {
            wc_add_notice(esc_html__('Something went wrong: ' . wp_remote_retrieve_body($response), 'error'), 'error' );
            exit;
        }
        $_SESSION['payment_id'] = sanitize_text_field($response_data['paymentId']);
        return filter_var($response_data['qrCode'], FILTER_SANITIZE_URL);
    }
    public static function cancel_qr_code($payment_id, $access_token) {
        session_start();
        $fib_base_url = get_option('fibpg_base_url');
        $payment_id = sanitize_text_field($payment_id);
        $access_token = sanitize_text_field($access_token);
		$nonce = wp_create_nonce('wp_rest');

        if (empty($fib_base_url)) {
            wc_add_notice(esc_html__('Please configure your FIB settings.', 'error'), 'error' );
            exit;
        }
        if (empty($access_token)) {
            wc_add_notice(esc_html__('Unauthorized or expired token.', 'error'), 'error' );
            exit;
        }
        if (empty($payment_id)) {
            wc_add_notice(esc_html__('Payment Id is not provided.', 'error'), 'error' );
            exit;
        }

        $response = wp_remote_post(esc_url_raw($fib_base_url . '/protected/v1/payments/') . $payment_id . '/cancel', array(
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
