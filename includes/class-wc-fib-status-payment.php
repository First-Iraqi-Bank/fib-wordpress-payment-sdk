<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_FIB_STATUS_PAYMENT {

    public static function payment_status($payment_id, $access_token) {
        $api_url_payment = get_option('fib_api_url_payment');
        // Create a nonce
		$nonce = wp_create_nonce('wp_rest');

        if (empty($api_url_payment)) {
            throw new Exception(__('Please configure your FIB settings.', 'woocommerce-gateway-fib'));
        }

        $response = wp_remote_get($api_url_payment . '/' . $payment_id . '/status', array(
            'headers' => array(
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            'sslverify' => false, // IMPORTANT: remove this line in production
        ));

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if (is_wp_error($response)) {
            wp_send_json_error(['status' => 'error']);
        }

        return $response_data['status'];
    }
}
