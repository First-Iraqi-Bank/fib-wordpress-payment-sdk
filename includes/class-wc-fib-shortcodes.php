<?php
require_once 'class-wc-fib-api-auth.php';
require_once 'class-wc-fib-status-payment.php';

if (!defined('ABSPATH')) {
    exit();
}

class WC_FIB_Shortcodes
{
    public static function init()
    {
        add_shortcode('custom_payment_qr_code', [__CLASS__, 'custom_payment_qr_code_shortcode']);
        add_action('wp_ajax_check_payment_status', [__CLASS__, 'check_payment_status']); // for authenticated users
        add_action('wp_ajax_nopriv_check_payment_status', [__CLASS__, 'check_payment_status']); // for non-authenticated users
        add_action('wp_ajax_regenerate_qr_code', [__CLASS__, 'regenerate_qr_code']);
        add_action('wp_ajax_nopriv_regenerate_qr_code', [__CLASS__, 'regenerate_qr_code']);
    }

    public static function custom_payment_qr_code_shortcode()
    {
        ob_start();
        wc_print_notices();
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'custom_payment_qr_code_nonce')) {
            return 'Invalid nonce.';
        }
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        session_start();
        if ($order_id) {
            $nonce = wp_create_nonce("custom_payment_qr_code_nonce");
            $redirect_url = add_query_arg(["order_id" => $order_id, "nonce" => $nonce], home_url("/checkout"));

            $order = wc_get_order($order_id);
            $payment_id = esc_js($_SESSION['payment_id']);

            if ($order) {
                if (isset($_SESSION['qr_data'])) {
                    $qr_code_url = $_SESSION['qr_data'];
                    return '<div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 55vh;">
            <p> Scan the QR code below to proceed with the payment </p>
            <img id="qr-code-img" src="' .
                        $qr_code_url .
                        '" alt="QR Code" style="width: 300px; height: 300px;">
            <button id="regenerate-qr-code" style="margin-top: 20px; border: none; background: #47B39E; color: white; padding: 15px; cursor: pointer; border-radius: 5px">Regenerate QR Code</button>
        </div>
        <script type="text/javascript">
            function checkPaymentStatus() {
                jQuery.ajax({
                    url: "' .
                    admin_url('admin-ajax.php').
                    '",
                    data: {
                        action: "check_payment_status",
                        payment_id: "' .
                        $payment_id.
                        '",
                        order_id: "' .
                        $order_id.
                        '"
                    },
                    success: function(response) {
                        try {
                            if (response.success && response.data.status === "PAID") {
                                window.location.href = "' .
                                home_url('/checkout/order-received/').
                                '?order_id='.
                                $order_id.
                                '";
                            } else if (!response.success) {
                                console.error(response.errors);
                            }

                        } catch (e) {
                            throw new Error("Error checking payment status.");
                        }
                    },
                    error: function(response) {
                        throw new Error("Error something went wrong.");
                    }
                });
            }
            setInterval(checkPaymentStatus, 5000);
            jQuery("#regenerate-qr-code").on("click", function() {
                jQuery.ajax({
                    url: "' .
                    admin_url('admin-ajax.php').
                    '",
                    data: {
                        action: "regenerate_qr_code",
                        order_id: "' .
                        $order_id.
                        '"
                    },
                    success: function(response) {
                        if (response.success) {
                            jQuery("#qr-code-img").attr("src", response.data.qr_code_url);
                        } else {
                            throw new Error("Failed to regenerate QR code.");
                        }
                    },
                    error: function() {
                        throw new Error("Error something went wrong.");
                    }
                });
            });
        </script>
        ';
                } else {
                    return '<p style="text-align: center;">QR code not available.</p>';
                }
            }
        }
        return 'Order not found.';
    }

    public static function check_payment_status()
    {
        session_start();
        $payment_id = $_SESSION['payment_id'];
        $order_id = sanitize_text_field($_GET['order_id']);
        
        $access_token = WC_FIB_API_Auth::get_access_token();

        if (empty($payment_id) || empty($order_id) || empty($access_token)) {
            $errors = wc_get_notices('error');
            wp_send_json_error(['errors' => $errors]);
            exit;
        }

        $paymen_status = WC_FIB_STATUS_Payment::payment_status($payment_id, $access_token);
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

    public static function regenerate_qr_code()
    {
        session_start();
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($order_id) {
            $new_qr_code_url = self::call_back_api_to_regenerate_qr_code($order_id);
            if ($new_qr_code_url) {
                $_SESSION['qr_data'] = $new_qr_code_url;
                wp_send_json_success(['qr_code_url' => $new_qr_code_url]);
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
            $payment_id = $_SESSION['payment_id'];

			$access_token = WC_FIB_API_Auth::get_access_token();
            WC_FIB_API_Payment::cancel_qr_code($payment_id, $access_token);
			$qr_code = WC_FIB_API_Payment::create_qr_code($order, $access_token);
			return $qr_code;
		} catch (Exception $e) {
			wc_add_notice($e->getMessage(), 'error');
			error_log($e->getMessage());
			return false;
		}
    }
}

WC_FIB_Shortcodes::init();
