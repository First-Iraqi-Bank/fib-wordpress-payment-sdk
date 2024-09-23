<?php

if (!defined('ABSPATH')) {
    exit();
}

class FIBPG_API_Auth {

    public static function get_access_token() {
        $fibpg_base_url = get_option('fibpg_base_url');
        $fibpg_client_id = get_option('fibpg_client_id');
        $fibpg_client_secret = get_option('fibpg_client_secret');

        // Create a nonce
		$nonce = wp_create_nonce('wp_rest');
        
        if (empty($fibpg_base_url) || empty($fibpg_client_id) || empty($fibpg_client_secret)) {
            wc_add_notice(
                esc_html__('Please configure your FIB gateway settings and credentials.', 'fib-payments-gateway'),
                'error'
            );            
            return;
        }
        try{
            $response = wp_remote_post(esc_url_raw($fibpg_base_url . '/auth/realms/fib-online-shop/protocol/openid-connect/token'), array(
                'headers' => array(
                    'X-WP-Nonce' => $nonce,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => array(
                    'grant_type' => 'client_credentials',
                    'client_id' => sanitize_text_field($fibpg_client_id),
                    'client_secret' => sanitize_text_field($fibpg_client_secret),
                ),
                'sslverify' => false, // IMPORTANT: remove this line in production
                'timeout' => 45,
            ));

           
            if (is_wp_error($response)) {
                wc_add_notice(
                    sprintf(
                        /* translators: %s: Error message */
                        esc_html__('Error connecting to the API: %s', 'fib-payments-gateway'),
                        esc_html($response->get_error_message())
                    ),
                    'error'
                );
                error_log('API connection error: ' . esc_html($response->get_error_message()));
                return; 
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_data = json_decode($response_body, true);

            $response_data = json_decode($response_body, true);


            if ($response_code != 200 && $response_code != 201) {
                $error_message = isset($response_data['error_description']) ? $response_data['error_description'] : __('Unknown error occurred.', 'fib-payments-gateway');
                wc_add_notice(esc_html($error_message), 'error');
                error_log('API error: ' . esc_html($response_body));
                return;
            }

            if ($response_data === null) {
                wc_add_notice(
                    esc_html__('Invalid response from the server. Please check the server status or API credentials.', 'fib-payments-gateway'),
                    'error'
                );
                error_log('Failed to decode JSON: ' . esc_html($response_body));
                return;
            }
            return sanitize_text_field($response_data['access_token']);
        }catch(Exception $e){
            wc_add_notice(
                esc_html__('An error occurred: ', 'fib-payments-gateway') . esc_html($e->getMessage()),
                'error'
            );
            error_log('Exception occurred: ' . esc_html($e->getMessage()));
        }

    }
}
