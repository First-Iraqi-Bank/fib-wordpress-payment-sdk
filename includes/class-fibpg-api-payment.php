<?php

if (!defined('ABSPATH')) {
    exit();
}

class FIBPG_API_Payment
{
    /**
     * Create a QR code for the payment.
     *
     * @param string $access_token The access token for API authorization.
     * @return string|void The QR code URL or void on failure.
     */
    public static function create_qr_code($order, $access_token)
    {
        session_start();
        $fibpg_base_url = get_option('fibpg_base_url');
        // Create a nonce
        $nonce = wp_create_nonce('wp_rest');

        if (empty($fibpg_base_url)) {
            wc_add_notice(esc_html__('Please configure your FIB settings.', 'fib-payments-gateway'), 'error');
            exit();
        }
        if (empty($access_token)) {
            wc_add_notice(esc_html__('Unauthorized or expired token.', 'fib-payments-gateway'), 'error');
            exit();
        }

        $response = wp_remote_post(esc_url_raw($fibpg_base_url . '/protected/v1/payments'), [
            'headers' => [
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . sanitize_text_field($access_token),
            ],
            'body' => wp_json_encode([
                'monetaryValue' => [
                    'amount' => $order->get_total(),
                    'currency' => 'IQD',
                ],
                'statusCallbackUrl' => esc_url_raw('https://URL_TO_UPDATE_YOUR_PAYMENT_STATUS'),
                'description' => sanitize_text_field(sprintf(
                    /* translators: %s: Order ID */
                    __('FIB Payment for Order #%s', 'fib-payments-gateway'), 
                    $order->get_id()
                )),            ]),
            // 'sslverify' => false, // IMPORTANT: remove this line in production
            'timeout' => 45,
        ]);

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if ($response_code != 200 && $response_code != 201) {
            wc_add_notice(esc_html__('Something went wrong!', 'fib-payments-gateway'), 'error');
            exit();
        }
        $_SESSION['payment_id'] = sanitize_text_field($response_data['paymentId']);
        return filter_var($response_data['qrCode'], FILTER_SANITIZE_URL);
    }

    /**
     * Cancel a QR code payment.
     *
     * @param string $payment_id The ID of the payment to cancel.
     * @param string $access_token The access token for API authorization.
     * @return int|void The response code or void on failure.
     */
    public static function cancel_qr_code($payment_id, $access_token)
    {
        session_start();
        $fibpg_base_url = get_option('fibpg_base_url');
        $fibpg_payment_id = sanitize_text_field($payment_id);
        $fibpg_access_token = sanitize_text_field($access_token);
        $nonce = wp_create_nonce('wp_rest');

        if (empty($fibpg_base_url)) {
            wc_add_notice(esc_html__('Please configure your FIB settings.', 'fib-payments-gateway'), 'error');
            exit();
        }
        if (empty($access_token)) {
            wc_add_notice(esc_html__('Unauthorized or expired token.', 'fib-payments-gateway'), 'error');
            exit();
        }
        if (empty($payment_id)) {
            wc_add_notice(esc_html__('Payment ID is not provided.', 'fib-payments-gateway'), 'error');
            exit();
        }

        $response = wp_remote_post(esc_url_raw($fibpg_base_url . '/protected/v1/payments/') . $fibpg_payment_id . '/cancel', [
            'headers' => [
                'X-WP-Nonce' => $nonce,
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $fibpg_access_token,
            ],
        ]);

        $response_code = wp_remote_retrieve_response_code($response);

        unset($_SESSION['payment_id']);
        return $response_code;
    }
}
