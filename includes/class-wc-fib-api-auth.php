<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_FIB_API_Auth {

    public static function get_access_token() {
        $api_url_auth = get_option('fib_api_url_auth');
        $client_id = get_option('fib_client_id');
        $client_secret = get_option('fib_client_secret');

        // Create a nonce
		$nonce = wp_create_nonce('wp_rest');
        
        if (empty($api_url_auth) || empty($client_id) || empty($client_secret)) {
            throw new Exception(__('Please configure your FIB settings.', 'woocommerce-gateway-fib'));
        }

        $response = wp_remote_post($api_url_auth, array(
            'headers' => array(
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'client_credentials',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
            ),
            'sslverify' => false, // IMPORTANT: remove this line in production
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            throw new Exception("Something went wrong: $error_message");
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        return $response_data['access_token'];
    }
}
