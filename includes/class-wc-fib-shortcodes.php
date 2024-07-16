<?php
require_once 'class-wc-fib-api-auth.php';
require_once 'class-wc-fib-status-payment.php';

if (!defined('ABSPATH')) {
    exit();
}

class WC_FIB_Shortcodes {

    public static function init() {
        add_shortcode('custom_payment_qr_code', [__CLASS__, 'custom_payment_qr_code_shortcode']);
        add_action('wp_ajax_check_payment_status', [__CLASS__, 'check_payment_status']); // for authenticated users
        add_action('wp_ajax_nopriv_check_payment_status', [__CLASS__, 'check_payment_status']); // for non-authenticated users
    }

    public static function custom_payment_qr_code_shortcode() {
        ob_start();
        wc_print_notices();
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        session_start();
        if ($order_id) {
            $order = wc_get_order($order_id);
            $payment_id = esc_js($_SESSION['payment_id']);

            if ($order) {
                if (isset($_SESSION['qr_data'])) {
                    $qr_code_url = $_SESSION['qr_data'];
                    return '<div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 55vh;">
                        <p> Scan the QR code below to proceed with the payment </p>
                        <img src="' .
                        $qr_code_url .
                        '" alt="QR Code" style="width: 300px; height: 300px;">
                    </div>
                    <script type="text/javascript">
                        function checkPaymentStatus() {
                            jQuery.ajax({
                                url: "' . admin_url('admin-ajax.php') . '",
                                data: {
                                    action: "check_payment_status",
                                    payment_id: "' . $payment_id . '",
                                    order_id: "' . $order_id . '"
                                },
                                success: function(response) {
                                    try {
                                        if (response.success && response.data.status === "PAID") {
                                            window.location.href = "' . home_url('/checkout/order-received/') . '?order_id=' . $order_id . '";
                                        } else if (!response.success) {
                                            window.location.href = "' . home_url('/checkout') . '?order_id=' . $order_id . '";
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
                    </script>
                    ';
                } else {
                    return '<p style="text-align: center;">QR code not available.</p>';
                }
            }
        }
        return 'Order not found.';
    }

    public static function check_payment_status() {
        $payment_id = sanitize_text_field($_GET['payment_id']);

        $order_id = sanitize_text_field($_GET['order_id']);
        $access_token = WC_FIB_API_Auth::get_access_token();

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
}

WC_FIB_Shortcodes::init();
