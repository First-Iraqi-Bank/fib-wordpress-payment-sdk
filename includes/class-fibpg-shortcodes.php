<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


require_once 'class-fibpg-api-auth.php';
require_once 'class-fibpg-status-payment.php';


class FIBPG_Shortcodes
{
    public static function init()
    {
        add_shortcode('fibpg_payment_qr_code', [__CLASS__, 'fibpg_payment_qr_code_shortcode']);
        add_action('wp_ajax_check_payment_status', [__CLASS__, 'fibpg_check_payment_status']); // for authenticated users
        add_action('wp_ajax_nopriv_check_payment_status', [__CLASS__, 'fibpg_check_payment_status']); // for non-authenticated users
        add_action('wp_ajax_regenerate_qr_code', [__CLASS__, 'fibpg_regenerate_qr_code']);
        add_action('wp_ajax_nopriv_regenerate_qr_code', [__CLASS__, 'fibpg_regenerate_qr_code']);

        // Enqueue styles and scripts conditionally
        add_action('wp_enqueue_scripts', [__CLASS__, 'fibpg_enqueue_styles_and_scripts']);
    }

    public static function fibpg_enqueue_styles_and_scripts()
    {
        // Check if the shortcode is present on the current page
        if (has_shortcode(get_post()->post_content, 'fibpg_payment_qr_code')) {
            // Enqueue CSS file
            wp_enqueue_style('fib-payments-css', plugin_dir_url(__FILE__) . '../assets/css/fib-payments.css', array(), '1.0.0');

            // Enqueue JavaScript file for logged-in users
            if (is_user_logged_in()) {
                wp_enqueue_script('fib-payments-js', plugin_dir_url(__FILE__) . '../resources/js/frontend/fib-payments.js', array('jquery'), '1.0.0', true);
                wp_localize_script('fib-payments-js', 'fibPaymentsData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                ));
            }
        }
    }

    public static function fibpg_payment_qr_code_shortcode()
    {
        ob_start();
        wc_print_notices();
    
        // Validate and sanitize nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'custom_payment_qr_code_nonce')) {
            wp_die(esc_html__('Invalid nonce', 'fib-payments-gateway'));
        }
    
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        session_start();
    
        if ($order_id) {
            $fibpg_nonce = wp_create_nonce("custom_payment_qr_code_nonce");
            $redirect_url = add_query_arg(["order_id" => $order_id, "nonce" => $fibpg_nonce], home_url("/checkout"));
            $fibpg_order = wc_get_order($order_id);
            $fibpg_payment_id = isset($_SESSION['payment_id']) ? sanitize_text_field($_SESSION['payment_id']) : '';
    
            if ($fibpg_order) {
                if (isset($_SESSION['qr_data'])) {
                    $fibpg_qr_code_url = isset($_SESSION['qr_data']) ? sanitize_text_field($_SESSION['qr_data']) : '';
                    return '<div class="qr-code-container">
                    <p>' . esc_html__('Scan the QR code below to proceed with the payment', 'fib-payments-gateway') . '</p>
                    <img id="qr-code-img" src="' . filter_var($fibpg_qr_code_url, FILTER_SANITIZE_URL) . '" alt="QR Code">
                    <button id="regenerate-qr-code" class="qr-code-button">' . esc_html__('Regenerate QR Code', 'fib-payments-gateway') . '</button>
                    <input type="hidden" id="payment-id" value="' . esc_attr($fibpg_payment_id) . '">
                    <input type="hidden" id="order-id" value="' . esc_attr($order_id) . '">
                    <input type="hidden" id="nonce" value="' . esc_attr($fibpg_nonce) . '">
                    </div>';
                } else {
                    return '<p style="text-align: center;">' . esc_html__('QR code not available.', 'fib-payments-gateway') . '</p>';
                }
            }
        }
        // translators: %s: order ID
        return esc_html__('Order not found.', 'fib-payments-gateway');
    }
    

    public static function fibpg_check_payment_status()
    {
        session_start();
        $nonce = isset($_GET['nonce']) ? wp_unslash($_GET['nonce']) : '';
        // translators: %s: order ID
        if (!wp_verify_nonce(sanitize_text_field($nonce), 'custom_payment_qr_code_nonce')) {
            wp_die(esc_html__('Invalid nonce', 'fib-payments-gateway'));
        }
        $fibpg_payment_id = isset($_SESSION['payment_id']) ? sanitize_text_field($_SESSION['payment_id']) : '';
        $fibpg_order_id = isset($_GET['order_id']) ? sanitize_text_field(wp_unslash($_GET['order_id'])) : '';
        
        $fibpg_access_token = FIBPG_API_Auth::get_access_token();

        if (empty($fibpg_payment_id) || empty($fibpg_order_id) || empty($fibpg_access_token)) {
            $errors = wc_get_notices('error');
            wp_send_json_error(['errors' => $errors]);
            exit;
        }

        $fibpg_paymen_status = FIBPG_STATUS_Payment::payment_status($fibpg_payment_id, $fibpg_access_token);
        if ($fibpg_paymen_status === false) {
            $errors = wc_get_notices('error');
            wp_send_json_error(['errors' => $errors]);
        }
        if ($fibpg_paymen_status === 'PAID') {
            $order = wc_get_order($fibpg_order_id);
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
        // translators: %s: order ID
        $nonce = isset($_GET['nonce']) ? wp_unslash($_GET['nonce']) : '';
        if (!wp_verify_nonce(sanitize_text_field($nonce), 'custom_payment_qr_code_nonce')) {
            wp_die(esc_html__('Invalid nonce', 'fib-payments-gateway'));
        }
        $fibpg_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($fibpg_order_id) {
            $fibpg_new_qr_code_url = self::call_back_api_to_regenerate_qr_code(order_id: $fibpg_order_id);
            if ($fibpg_new_qr_code_url) {
                $sanitized_qr_code_url = sanitize_text_field($fibpg_new_qr_code_url);
                wp_send_json_success(['qr_code_url' => filter_var($sanitized_qr_code_url, FILTER_SANITIZE_URL)]);
            } else {
                // translators: %s: error message
                wp_send_json_error(['message' => esc_html__('Failed to regenerate QR code.', 'fib-payments-gateway')]);
            }
        } else {
            wp_send_json_error(['message' => esc_html__('Invalid order ID.', 'fib-payments-gateway')]);
        }
    }

    public static function call_back_api_to_regenerate_qr_code($order_id)
    {
        try {
            $fibpg_order = wc_get_order($order_id);
            $fibpg_payment_id = isset($_SESSION['payment_id']) ? sanitize_text_field($_SESSION['payment_id']) : '';

			$fibpg_access_token = FIBPG_API_Auth::get_access_token();
            if (empty($fibpg_access_token)) {
                throw new Exception(esc_html__('Failed to obtain access token.', 'fib-payments-gateway'));
            }
            $cancel_response_code = FIBPG_API_Payment::cancel_qr_code($fibpg_payment_id, $fibpg_access_token);
            if ($cancel_response_code != 204 && $cancel_response_code != 201) {
                // translators: %s: response code
                throw new Exception(sprintf(esc_html__('Failed to cancel previous QR code. Response code: %d', 'fib-payments-gateway'), $cancel_response_code));
            }
            $fibpg_qr_code = FIBPG_API_Payment::create_qr_code($fibpg_order, $fibpg_access_token);
            $sanitized_qr_code = filter_var($fibpg_qr_code, FILTER_SANITIZE_URL);

            return $sanitized_qr_code;
		} catch (Exception $e) {
            wc_add_notice(esc_html__('An error occurred while processing your request. Please try again later.', 'fib-payments-gateway'), 'error');
            error_log(sprintf('FIBPG Error: %s', $e->getMessage()));
			return false;
		}
    }
}

FIBPG_Shortcodes::init();
