<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * FIB Payments Blocks integration
 *
 * @since 1.0.3
 */

 
final class Gateway_FIBPG_Blocks_Support extends AbstractPaymentMethodType
{

	/**
	 * The gateway instance.
	 *
	 * @var FIBPG_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'fib';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize()
	{
		$this->settings = get_option('woocommerce_fibpg_settings', []);

		// Error handling for missing settings
		if (empty($this->settings)) {
            return new WP_Error('missing_settings', __('FIB Payments settings are not configured properly.', 'woocommerce-gateway-fib'));
        }

		$gateways = WC()->payment_gateways->payment_gateways();

		// Error handling for missing gateway
        if (!isset($gateways[$this->name])) {
            return new WP_Error('gateway_missing', __('FIB Gateway is not available', 'woocommerce-gateway-fib'));
        }

		$this->gateway = $gateways[$this->name];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return bool
	 */
	public function is_active(): bool
	{
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles()
	{
		$script_path= '/assets/js/frontend/blocks.js';
		$script_asset_path = FIBPG_Payments::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
		$script_asset= file_exists($script_asset_path)
			? require($script_asset_path)
			: array(
				'dependencies' => array(),
				'version'      => '1.2.0'
			);
		$script_url= FIBPG_Payments::plugin_url() . $script_path;

		wp_register_script(
            'wc-fib-payments-blocks',
            $script_url,
            $script_asset['dependencies'] ?? [],
            $script_asset['version'] ?? '1.2.1',
            true
        );

		if (function_exists('wp_set_script_translations')) {
			wp_set_script_translations('wc-fib-payments-blocks', 'woocommerce-gateway-fib', FIBPG_Payments::plugin_abspath() . 'languages/');
		}
		return ['wc-fib-payments-blocks'];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data()
	{
		return [
			'title'=> $this->get_setting('title'),
			'description'=> $this->get_setting('description'),
			'icon'=> plugin_dir_url(__DIR__) . 'assets/images/fi_logo.png',
			'supports'=> array_filter($this->gateway->supports, [$this->gateway, 'supports'])
		];
	}
}


