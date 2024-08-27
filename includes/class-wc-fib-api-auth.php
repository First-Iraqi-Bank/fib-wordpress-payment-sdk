<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_FIB_API_Auth {

    public static function get_access_token() {
        $fib_base_url = get_option('fib_base_url');
        $client_id = get_option('fib_client_id');
        $client_secret = get_option('fib_client_secret');

        // Create a nonce
		$nonce = wp_create_nonce('wp_rest');
        
        if (empty($fib_base_url) || empty($client_id) || empty($client_secret)) {
            wc_add_notice("Please configure your FIB gateway settings and crediantials.", 'error' ); 
            exit;
        }
        try{
            $response = wp_remote_post( $fib_base_url . '/auth/realms/fib-online-shop/protocol/openid-connect/token', array(
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
                'timeout' => 45,
            ));

            if (is_wp_error($response)) {
                wc_add_notice('Error connecting to the API: ' . $response->get_error_message(), 'error');
                error_log('API connection error: ' . $response->get_error_message());
                exit;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            $response_data = json_decode($response_body, true);

            // echo $response_data;
            if ($response_code != 200 && $response_code != 201) {
                $error_message = isset($response_data['error_description']) ? $response_data['error_description'] : 'Unknown error occurred.';
                wc_add_notice($error_message, 'error');
                error_log('API error: ' . $response_body);
                exit;
            }

            if ($response_data === null) {
                wc_add_notice('Invalid response from the server. Please check the server status or API credentials.', 'error');
                error_log('Failed to decode JSON: ' . $response_body);
                exit;
            }

            return $response_data['access_token'];
        }catch(Exception $e){
            wc_add_notice($e->getMessage(), 'error');
            error_log($e->getMessage());
        }

    }
}
