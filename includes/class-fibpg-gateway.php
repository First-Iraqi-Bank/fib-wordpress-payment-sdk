<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

require_once 'functions.php';
// Include the API classes
require_once 'class-fibpg-api-auth.php';
require_once 'class-fibpg-api-payment.php';

fibpg_load_environment_variables(__DIR__ . '/../.env');

/**
 * FIB Gateway.
 *
 * @class    FIBPG_Gateway
 * @version  1.2.1
 */
class FIBPG_Gateway extends WC_Payment_Gateway
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

		$this->method_title = esc_html_x('FIB Payment', 'FIB payments Gateway', 'fib-payments-gateway');
		$this->method_description = esc_html__('Allows FIB payments.', 'fib-payments-gateway');


		$this->init_form_fields();
		// Load the settings.
		$this->init_settings();

		
        // Define user-configurable values
        $this->title        = $this->get_option('title', 'FIB Payments');
        $this->description  = $this->get_option('description', 'Pay With FIB');

		// Actions.
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
		add_action('woocommerce_api_wc_gateway_' . $this->id, array($this, 'check_response'));
	}


	public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'   => __('Enable/Disable', 'fib-payments-gateway'),
                'type'    => 'checkbox',
                'label'   => __('Enable FIB Payments', 'fib-payments-gateway'),
                'default' => 'no'
            ),
            'title' => array(
                'title'       => __('Title', 'fib-payments-gateway'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'fib-payments-gateway'),
                'default'     => __('FIB Payments', 'fib-payments-gateway'),
                'desc_tip'    => true
            ),
            'description' => array(
                'title'       => __('Description', 'fib-payments-gateway'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'fib-payments-gateway'),
                'default'     => __('Pay securely through FIB Payments.', 'fib-payments-gateway'),
                'desc_tip'    => true
            )
        );
    }
	
	public function process_payment($order_id)
	{
		try {
			$fibpg_order = wc_get_order($order_id);
        	$fibpg_order->update_status('pending', esc_html__('Awaiting QR code payment', 'fib-payments-gateway'));

			wc_reduce_stock_levels($order_id);

       		 $fibpg_nonce = wp_create_nonce('custom_payment_qr_code_nonce');

        	$site_url = get_site_url();

       	 	$fibpg_redirect_url = esc_url_raw(
            	trailingslashit($site_url) . 'fib-payment-gateway-qr-code/?order_id=' . $order_id . '&nonce=' . $fibpg_nonce
        	);
			$this->get_fibpg_customer_url($fibpg_order);
			
			return array(
				'result'   => 'success',
				'redirect' => $fibpg_redirect_url,
			);
		} catch (Exception $e) {
			wc_add_notice(esc_html($e->getMessage()), 'error'); // Escape error message
		}
	}
	
	/**
	 * Get FIB customer URL.
	 *
	 * @param  WC_Order  $order
	 * @return string
	 */
	public function get_fibpg_customer_url($order)
	{
		try {
			$fibpg_access_token = FIBPG_API_Auth::get_access_token();

			return FIBPG_API_Payment::create_qr_code($order, $fibpg_access_token);
			
		} catch (Exception $e) {
			wc_add_notice(esc_html($e->getMessage()), 'error');
			error_log(message: $e->getMessage());
			return false;
		}
	}
}
