<?php

if (!defined('ABSPATH')) {
    exit();
}

class WC_FIB_STATUS_PAYMENT {

    public static function payment_status($payment_id, $access_token) {
        $fib_base_url = get_option('fib_base_url');

        if (empty($fib_base_url)) {
            wc_add_notice('Please configure your FIB settings.', 'error' ); 
            exit;
        }
        if (empty($access_token)) {
            wc_add_notice('Unauthorized or expired token.', 'error' ); 
            exit;
        }
        // Create a nonce
		$nonce = wp_create_nonce('wp_rest');

        $response = wp_remote_get($fib_base_url . '/protected/v1/payments/' . $payment_id . '/status', array(
            'headers' => array(
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
            // 'sslverify' => false, // IMPORTANT: remove this line in production
        ));

        $response_code = wp_remote_retrieve_response_code($response);
        
        $response_body = wp_remote_retrieve_body($response);

        $response_data = json_decode($response_body, true);

        if ($response_code != 200 && $response_code != 201) {
            if($response_code == 403){
                wc_add_notice('Access denied, please try again.', 'error' ); 
                return false;
            }
            wc_add_notice('something went wrong: ' . wp_remote_retrieve_body($response), 'error' ); 
            exit;
        }

        return $response_data['status'];
    }
}
