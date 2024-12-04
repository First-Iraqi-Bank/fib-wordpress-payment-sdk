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
                    'checkoutUrl' => home_url(),
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
    
        if ($order_id) {
            $fibpg_nonce = wp_create_nonce("custom_payment_qr_code_nonce");
            $redirect_url = add_query_arg(["order_id" => $order_id, "nonce" => $fibpg_nonce], home_url("/checkout"));
            $fibpg_order = wc_get_order($order_id);
            
            $fibpg_qr_code_url = get_post_meta($order_id, '_fib_qr_data', true);

            $fibpg_payment_id = get_post_meta($order_id, '_fib_payment_id', true);

            $personal_app_link = get_post_meta($order_id, '_fib_personal_app_link', true);
            $business_app_link = get_post_meta($order_id, '_fib_business_app_link', true);
            $corporate_app_link = get_post_meta($order_id, '_fib_corporate_app_link', true);

            $readable_code = get_post_meta($order_id, '_fib_readable_code', true);
    
            if ($fibpg_order) {
                if ($fibpg_qr_code_url) {
                    return '<div class="qr-code-container">
                    <p>' . esc_html__('Scan the QR code below to proceed with the payment', 'fib-payments-gateway') . '</p>
                    <img id="qr-code-img" src="' . $fibpg_qr_code_url . '" alt="QR Code">
                    <p class="readable-code" style="font-size: 14px; color: #777; margin-top: 15px;">
                        ' . esc_html($readable_code) . '
                    </p>

                    <div class="mobile-only" style="justify-content: center; gap: 5px; margin-top: 20px;">
                        <a href="' . esc_url($personal_app_link) . '" target="_blank" style="padding: 8px 12px; background-color: #5EBEA4; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; text-align: center; display: inline-block;">Personal App Link</a>
                        <a href="' . esc_url($business_app_link) . '" target="_blank" style="padding: 8px 12px; background-color: #1587C8; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; text-align: center; display: inline-block;">Business App Link</a>
                        <a href="' . esc_url($corporate_app_link) . '" target="_blank" style="padding: 8px 12px; background-color: #5372C3; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; text-align: center; display: inline-block;">Corporate App Link</a>
                    </div>
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
        $nonce = isset($_GET['nonce']) ? wp_unslash($_GET['nonce']) : '';
        // translators: %s: order ID
        if (!wp_verify_nonce(sanitize_text_field($nonce), 'custom_payment_qr_code_nonce')) {
            wp_die(esc_html__('Invalid nonce', 'fib-payments-gateway'));
        }
        $fibpg_order_id = isset($_GET['order_id']) ? sanitize_text_field(wp_unslash($_GET['order_id'])) : '';
        $fibpg_payment_id = get_post_meta($fibpg_order_id, '_fib_payment_id', true);

        
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
        $nonce = isset($_GET['nonce']) ? wp_unslash($_GET['nonce']) : '';
        if (!wp_verify_nonce(sanitize_text_field($nonce), 'custom_payment_qr_code_nonce')) {
            wp_die(esc_html__('Invalid nonce', 'fib-payments-gateway'));
        }
        $fibpg_order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($fibpg_order_id) {
            $fibpg_new_qr_code_url = self::call_back_api_to_regenerate_qr_code(order_id: $fibpg_order_id);
            if ($fibpg_new_qr_code_url) {
                $personal_app_link = get_post_meta($fibpg_order_id, '_fib_personal_app_link', true);
                $business_app_link = get_post_meta($fibpg_order_id, '_fib_business_app_link', true);
                $corporate_app_link = get_post_meta($fibpg_order_id, '_fib_corporate_app_link', true);
                $readable_code = get_post_meta($fibpg_order_id, '_fib_readable_code', true);

                // Prepare the updated HTML for mobile-only links
                $mobile_links_html = '
                    <a href="' . esc_url($personal_app_link) . '" target="_blank" style="padding: 8px 12px; background-color: #5EBEA4; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; text-align: center; display: inline-block;">Personal App Link</a>
                    <a href="' . esc_url($business_app_link) . '" target="_blank" style="padding: 8px 12px; background-color: #1587C8; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; text-align: center; display: inline-block;">Business App Link</a>
                    <a href="' . esc_url($corporate_app_link) . '" target="_blank" style="padding: 8px 12px; background-color: #5372C3; color: white; text-decoration: none; border-radius: 5px; font-size: 12px; text-align: center; display: inline-block;">Corporate App Link</a>
                ';
                // Return the data including the QR code URL, the updated links, and the readable code
                wp_send_json_success([
                    'qr_code_url' => $fibpg_new_qr_code_url,
                    'mobile_links' => $mobile_links_html,
                    'readable_code' => $readable_code,
                ]);
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
            $fibpg_payment_id = get_post_meta($order_id, '_fib_payment_id', true);

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
