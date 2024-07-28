<?php
require_once 'functions.php';
// Include the API classes
require_once 'class-wc-fib-api-auth.php';
require_once 'class-wc-fib-api-payment.php';

loadEnvironmentVariables(__DIR__ . '/../.env');

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

/**
 * FIB Gateway.
 *
 * @class    WC_Gateway_FIB
 * @version  1.0.7
 */
class WC_Gateway_FIB extends WC_Payment_Gateway
{

	/**
	 * Payment gateway instructions.
	 * @var string
	 *
	 */
	protected $instructions;

	/**
	 * Whether the gateway is visible for non-admin users.
	 * @var boolean
	 *
	 */
	protected $hide_for_non_admin_users;

	/**
	 * Unique id for the gateway.
	 * @var string
	 *
	 */
	public $id = 'fib';

	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{

		$_SESSION['user'] = 'hey';

		$this->has_fields = false;
		$this->supports = array(
			'pre-orders',
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_suspension',
			'subscription_reactivation',
			'subscription_amount_changes',
			'subscription_date_changes',
			'multiple_subscriptions'
		);

		$this->method_title = _x('FIB Payment', 'FIB payment method', 'woocommerce-gateway-fib');
		$this->method_description = __('Allows fib payments.', 'woocommerce-gateway-fib');

		// Load the settings.
		$this->init_settings();

		// Actions.
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'check_response'));
	}

	public function process_payment($order_id)
	{
		session_start();
		try {
			$order = wc_get_order($order_id);

			$order->update_status('pending', 'Awaiting QR code payment', 'woocommerce-gateway-fib');

			wc_reduce_stock_levels($order_id);

			$custom_page_id = get_option('custom_payment_gateway_page_id');
			$custom_page_url = get_permalink($custom_page_id);
			$qrCodeUrl = $this->get_fib_customer_url($order);

			$_SESSION['qr_data'] = $qrCodeUrl;

			$payment_id = $this->get_payment_id_from_api();
			$_SESSION['payment_id'] = $payment_id;

			$nonce = wp_create_nonce('custom_payment_qr_code_nonce');
			$redirect_url = add_query_arg(['order_id' => $order_id, 'nonce' => $nonce], $custom_page_url);

			return array(
				'result'   => 'success',
				'redirect' => $redirect_url,
			);
		} catch (Exception $e) {
			wc_add_notice($e->getMessage(), 'error');
		}
	}
	
	/**
	 * Get FIB customer URL.
	 *
	 * @param  WC_Order  $order
	 * @return string
	 */
	public function get_fib_customer_url($order)
	{
		try {
			$access_token = WC_FIB_API_Auth::get_access_token();
			$qr_code = WC_FIB_API_Payment::create_qr_code($order, $access_token);
			return $qr_code;
		} catch (Exception $e) {
			wc_add_notice($e->getMessage(), 'error');
			error_log($e->getMessage());
			return false;
		}
	}

	public function get_payment_id_from_api()
	{
		if (isset($_SESSION['payment_id'])) {
			return $_SESSION['payment_id'];
		}
		throw new Exception('Payment ID not found.', 'woocommerce-gateway-fib');
	}
}
