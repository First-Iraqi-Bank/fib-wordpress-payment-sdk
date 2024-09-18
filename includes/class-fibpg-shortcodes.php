<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


require_once 'class-fibpg-api-auth.php';
require_once 'class-fibpg-status-payment.php';


class FIBPG_Shortcodes
{
    public static function init()
    {
        add_shortcode('fibpg_custom_payment_qr_code', [__CLASS__, 'fibpg_payment_qr_code_shortcode']);
        add_action('wp_ajax_check_payment_status', [__CLASS__, 'fibpg_check_payment_status']); // for authenticated users
        add_action('wp_ajax_nopriv_check_payment_status', [__CLASS__, 'fibpg_check_payment_status']); // for non-authenticated users
        add_action('wp_ajax_regenerate_qr_code', [__CLASS__, 'fibpg_regenerate_qr_code']);
        add_action('wp_ajax_nopriv_regenerate_qr_code', [__CLASS__, 'fibpg_regenerate_qr_code']);
    }

    public static function fibpg_payment_qr_code_shortcode()
    {
        ob_start();
        wc_print_notices();
    
        // Validate and sanitize nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'custom_payment_qr_code_nonce')) {
            wp_die('Invalid nonce');
        }
    
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        session_start();
    
        if ($order_id) {
            $nonce = wp_create_nonce("custom_payment_qr_code_nonce");
            $redirect_url = add_query_arg(["order_id" => $order_id, "nonce" => $nonce], home_url("/checkout"));
            $order = wc_get_order($order_id);
            $payment_id = isset($_SESSION['payment_id']) ? sanitize_text_field($_SESSION['payment_id']) : '';
    
            if ($order) {
                if (isset($_SESSION['qr_data'])) {
                    $qr_code_url = isset($_SESSION['qr_data']) ? sanitize_text_field($_SESSION['qr_data']) : '';
                    return '<div class="qr-code-container">
                        <p>Scan the QR code below to proceed with the payment</p>
                        <img id="qr-code-img" src="' . filter_var($qr_code_url, FILTER_SANITIZE_URL) . '" alt="QR Code">
                        <button id="regenerate-qr-code" class="qr-code-button">Regenerate QR Code</button>
                        <input type="hidden" id="payment-id" value="' . esc_attr($payment_id) . '">
                        <input type="hidden" id="order-id" value="' . esc_attr($order_id) . '">
                    </div>';
                } else {
                    return '<p style="text-align: center;">QR code not available.</p>';
                }
            }
        }
        return 'Order not found.';
    }
    

    public static function fibpg_check_payment_status()
    {
        session_start();
        $payment_id = isset($_SESSION['payment_id']) ? sanitize_text_field($_SESSION['payment_id']) : '';
        $order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
        
        $access_token = FIBPG_API_Auth::get_access_token();

        if (empty($payment_id) || empty($order_id) || empty($access_token)) {
            $errors = wc_get_notices('error');
            wp_send_json_error(['errors' => $errors]);
            exit;
        }

        $paymen_status = FIBPG_STATUS_Payment::payment_status($payment_id, $access_token);
        if ($paymen_status === false) {
            $errors = wc_get_notices('error');
            wp_send_json_error(['errors' => $errors]);
        }
        if ($paymen_status === 'PAID') {
            $order = wc_get_order($order_id);
            $order->payment_complete();
            WC()->cart->empty_cart();
            $order->update_meta_data('fib_payment_status', 'Paid via FIB Payment Gateway');
            $order->save();
            wp_send_json_success(['status' => 'PAID']);
        } else {
            wp_send_json_success(['status' => 'UNPAID']);
        }

        wp_die();
    }

    public static function fibpg_regenerate_qr_code()
    {
        session_start();
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($order_id) {
            $new_qr_code_url = self::call_back_api_to_regenerate_qr_code(order_id: $order_id);
            if ($new_qr_code_url) {
                $sanitized_qr_code_url = sanitize_text_field($new_qr_code_url);
                wp_send_json_success(['qr_code_url' => filter_var($sanitized_qr_code_url, FILTER_SANITIZE_URL)]);
            } else {
                wp_send_json_error(['message' => 'Failed to regenerate QR code.']);
            }
        } else {
            wp_send_json_error(['message' => 'Invalid order ID.']);
        }
    }

    public static function call_back_api_to_regenerate_qr_code($order_id)
    {
        try {
            $order = wc_get_order($order_id);
            $payment_id = isset($_SESSION['payment_id']) ? sanitize_text_field($_SESSION['payment_id']) : '';

			$access_token = FIBPG_API_Auth::get_access_token();
            if (empty($access_token)) {
                throw new Exception('Failed to obtain access token.');
            }
            $cancel_response_code = FIBPG_API_Payment::cancel_qr_code($payment_id, $access_token);
            if ($cancel_response_code != 204 && $cancel_response_code != 201) {
                throw new Exception('Failed to cancel previous QR code. Response code: ' . $cancel_response_code);
            }
            $qr_code = FIBPG_API_Payment::create_qr_code($order, $access_token);
            $sanitized_qr_code = filter_var($qr_code, FILTER_SANITIZE_URL);

            return $sanitized_qr_code;
		} catch (Exception $e) {
			wc_add_notice($e->getMessage(), 'error');
			error_log($e->getMessage());
			return false;
		}
    }
}

FIBPG_Shortcodes::init();
