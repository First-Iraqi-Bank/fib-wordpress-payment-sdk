<?php

/**
 * WC_Gateway_FIB class
 *	
 * @author   template by SomewhereWarm <info@somewherewarm.gr>
 * @package  WooCommerce FIB Payments Gateway
 * @since    1.0.0
 */

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

		$this->has_fields         = false;
		$this->supports           = array(
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

		$this->method_title       = _x('FIB Payment', 'FIB payment method', 'woocommerce-gateway-fib');
		$this->method_description = __('Allows fib payments.', 'woocommerce-gateway-fib');

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->username                    = $this->get_option('username');
		$this->password              = $this->get_option('password');
		$this->url              = $this->get_option('url');

		// Actions.
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{

		$this->form_fields = array(
			'username' => array(
				'title'       => __('Username', 'woocommerce-gateway-fib'),
				'type'        => 'text',
				'description' => __('your fib username', 'woocommerce-gateway-fib'),
				'default'     => _x('FIB Payment', 'FIB payment method', 'woocommerce-gateway-fib'),
				'desc_tip'    => true,
			),
			'password' => array(
				'title'       => __('Password', 'woocommerce-gateway-fib'),
				'type'        => 'password',
				'description' => __('your fib password', 'woocommerce-gateway-fib'),
				'default'     => __('The goods are yours. No money needed.', 'woocommerce-gateway-fib'),
				'desc_tip'    => true,
			),
			'url' => array(
				'title'       => 'FIB API URL',
				'type'        => 'text',
				'description' => 'the apis url of fib to generate the qr code',
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int  $order_id
	 * @return array
	 */
	public function process_payment($order_id)
	{
		$payment_result = $this->get_option('result');
		$order = wc_get_order($order_id);

		if ('success' === $payment_result) {
			if ($order->get_total() > 0) {
				$qrCodeUrl = $this->get_fib_customer_url($order);
				$_SESSION['qr_data'] = base64_encode($qrCodeUrl);
			} else {
				// Payment complete
				$order->payment_complete();
			}
			$order->payment_complete();

			// Remove cart
			WC()->cart->empty_cart();

			// Return thankyou redirect with iframe
			return array(
				'result' 	=> 'success',
				'redirect'	=> $this->get_return_url($order),
			);
		} else {
			$message = __('Order payment failed. To make a successful payment using FIB Payments, please review the gateway settings.', 'woocommerce-gateway-fib');
			$order->update_status('failed', $message);
			throw new Exception($message);
		}
	}

	// function check_fib_payment_status()
	// {
	// 	$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
	// 	$order = wc_get_order($order_id);

	// 	if (!$order) {
	// 		wp_send_json_error('Order not found.');
	// 	}

	// 	// Replace this with the actual check for the FIB payment status
	// 	// For example, check if the order status is 'completed'
	// 	$payment_status = $order->get_status();

	// 	if ($payment_status === 'completed') {
	// 		wp_send_json_success('completed');
	// 	} else {
	// 		wp_send_json_error('pending');
	// 	}
	// }

	/**
	 * Get FIB customer URL.
	 *
	 * @param  WC_Order  $order
	 * @return string
	 */
	public function get_fib_customer_url($order)
	{
		// Create a nonce
		$nonce = wp_create_nonce('wp_rest');

		// Make a request to the FIB API to generate the QR code
		$response = wp_remote_post('https://fib.stage.fib.iq/auth/realms/fib-online-shop/protocol/openid-connect/token', array(
			'headers' => array(
				'X-WP-Nonce' => $nonce,
				'Content-Type' => 'application/x-www-form-urlencoded', // Ensure the content type is set for form data
			),
			'body' => array(
				'grant_type' => 'client_credentials', // Replace 'your_grant_type' with the actual grant type
				'client_id' => 'fib-client-19', // Replace 'your_client_id' with your actual client ID
				'client_secret' => '480eb521-900f-4070-b0aa-2289ef144766', // Replace 'your_client_secret' with your actual client secret
			),
		));
		if (is_wp_error($response)) {
			throw new Exception(__('Failed to generate FIB customer URL.', 'woocommerce-gateway-fib'));
		}

		// Parse the response and get the FIB customer URL
		$response_body = wp_remote_retrieve_body($response);
		$response_data = json_decode($response_body, true);

		$response2 = wp_remote_post('https://fib.stage.fib.iq/protected/v1/payments', array(
			'headers' => array(
				'X-WP-Nonce' => $nonce,
				'Content-Type' => 'application/json', // Set the content type to JSON
				'Authorization' => 'Bearer ' . $response_data['access_token'], // Add the Authorization bearer header
			),
			'body' => json_encode(array(
				'monetaryValue' => array(
					'amount' => '500.00',
					'currency' => 'IQD',
				),
				'statusCallbackUrl' => 'https://URL_TO_UPDATE_YOUR_PAYMENT_STATUS',
				'description' => 'Lorem ipsum dolor sit amet.',
			)),
		));

		$response_body2 = wp_remote_retrieve_body($response2);
		$response_data2 = json_decode($response_body2, true);

		// Store the QR code in a session
		return $response_data2['qrCode'];
	}

	// /**
	//  * Process subscription payment.
	//  *
	//  * @param  float     $amount
	//  * @param  WC_Order  $order
	//  * @return void
	//  */
	// public function process_subscription_payment($amount, $order)
	// {
	// 	$payment_result = $this->get_option('result');

	// 	if ('success' === $payment_result) {
	// 		$order->payment_complete();
	// 	} else {
	// 		$order->update_status('failed', __('Subscription payment failed. To make a successful payment using FIB Payments, please review the gateway settings.', 'woocommerce-gateway-fib'));
	// 	}
	// }
}
