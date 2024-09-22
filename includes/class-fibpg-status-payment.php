<?php

if (!defined('ABSPATH')) {
    exit();
}

class FIBPG_STATUS_Payment {

    public static function payment_status($payment_id, $access_token) {
        $fibpg_base_url = get_option('fibpg_base_url');
        if (empty($fibpg_base_url)) {
            wc_add_notice(esc_html__('Please configure your FIB settings.', 'fib-payments-gateway'), 'error');
            return false;
        }
        if (empty($access_token)) {
            wc_add_notice(esc_html__('Unauthorized or expired token.', 'fib-payments-gateway'), 'error');
            return false;
        }

        $fibpg_payment_id = sanitize_text_field($payment_id);

        // Create a nonce
		$fibpg_nonce = wp_create_nonce('wp_rest');

        $response = wp_remote_get(esc_url_raw($fibpg_base_url . '/protected/v1/payments/' . $fibpg_payment_id . '/status'), array(
            'headers' => array(
                'X-WP-Nonce' => $fibpg_nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . sanitize_text_field($access_token),
            ),
            // 'sslverify' => false, // IMPORTANT: remove this line in production
            'timeout' => 45,
        ));

        $response_code = wp_remote_retrieve_response_code($response);
        
        $response_body = wp_remote_retrieve_body($response);

        $response_data = json_decode($response_body, true);

        if ($response_code != 200 && $response_code != 201) {
            if($response_code == 403){
                wc_add_notice(esc_html__('Access denied, please try again.', 'fib-payments-gateway'), 'error');
                return false;
            }
            wc_add_notice(
                sprintf(
                    /* translators: %s: Response body */
                    esc_html__('Something went wrong: %s', 'fib-payments-gateway'),
                    esc_html($response_body)
                ),
                'error'
            );            
            exit;
        }

        return sanitize_text_field($response_data['status']);
    }
}
