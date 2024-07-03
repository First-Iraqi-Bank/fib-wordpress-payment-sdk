<?php

/**
 * Plugin Name: WooCommerce FIB Payments Gateway
 * Plugin URI: https://github.com/First-Iraqi-Bank/fib-wordpress-payment-sdk
 * Description: Adds the FIB Payments gateway to your WooCommerce website.
 * Version: 1.1.0
 *
 * Author: By Hazhee Himdad
 * Author URI: https://hazhee.com
 *
 * Text Domain: woocommerce-gateway-fib
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 4.2
 * Tested up to: 4.9
 *
 * Copyright: © 2009-2023 Emmanouil Psychogyiopoulos.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

register_activation_hook( __FILE__, 'custom_payment_gateway_activate' );

register_deactivation_hook(__FILE__, 'plugin_deactivation');


add_filter( 'woocommerce_store_api_disable_nonce_check', '__return_true' );


function custom_payment_gateway_activate() {
    custom_payment_gateway_create_page();
}

function plugin_deactivation() {
    $page_title = 'FIB Payment Gateway QR Code';

    $page = get_page_by_title($page_title);

    if ($page) {
        wp_delete_post($page->ID, true); // 'true' to force delete without sending to trash
    }
}

function custom_payment_gateway_create_page() {
    $page_title = 'FIB Payment Gateway QR Code';
    $page_content = '[custom_payment_qr_code]';
    $page_template = '';

    $page_check = get_page_by_title( $page_title );

    if ( ! isset( $page_check->ID ) ) {
        $new_page_id = wp_insert_post( array(
            'post_title'     => $page_title,
            'post_content'   => $page_content,
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'page_template'  => $page_template,
        ) );

        update_option( 'custom_payment_gateway_page_id', $new_page_id );
    }
}

function custom_payment_qr_code_shortcode() {
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
	session_start();
    if ($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            if (isset($_SESSION['qr_data'])) {
                $qr_code_url = $_SESSION['qr_data'];
				return '<div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 55vh;">
					<p> Scan the QR code bellow to procced the payment </p>
                    <img src="' . $qr_code_url . '" alt="QR Code" style="width: 300px; height: 300px;">
                </div>';
            } else {
                return 'QR code not found test.';
            }
        }
    }

    return 'Order not found.';
}
add_shortcode('custom_payment_qr_code', 'custom_payment_qr_code_shortcode');

/**
 * WC FIB Payment gateway plugin class.
 *
 * @class WC_FIB_Payments
 */
class WC_FIB_Payments
{

	/**
	 * Plugin bootstrapping.
	 */
	public static function init()
	{

		// FIB Payments gateway class.
		add_action('plugins_loaded', array(__CLASS__, 'includes'), 0);

		// Make the FIB Payments gateway available to WC.
		add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));

		add_filter('woocommerce_payment_process', array(__CLASS__, 'add_gateway'));

		// Registers WooCommerce Blocks integration.
		add_action('woocommerce_blocks_loaded', array(__CLASS__, 'woocommerce_gateway_fib_woocommerce_block_support'));
	}

	/**
	 * Add the FIB Payment gateway to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateway($gateways)
	{

		$options = get_option('woocommerce_fib_settings', array());

		if (isset($options['hide_for_non_admin_users'])) {
			$hide_for_non_admin_users = $options['hide_for_non_admin_users'];
		} else {
			$hide_for_non_admin_users = 'no';
		}

		if (('yes' === $hide_for_non_admin_users && current_user_can('manage_options')) || 'no' === $hide_for_non_admin_users) {
			$gateways[] = 'WC_Gateway_FIB';
		}
		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes()
	{

		// Make the WC_Gateway_FIB class available.
		if (class_exists('WC_Payment_Gateway')) {
			require_once 'includes/class-wc-gateway-fib.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url()
	{
		return untrailingslashit(plugins_url('/', __FILE__));
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath()
	{
		return trailingslashit(plugin_dir_path(__FILE__));
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function woocommerce_gateway_fib_woocommerce_block_support()
	{
		if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
			require_once 'includes/blocks/class-wc-fib-payments-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
					$payment_method_registry->register(new WC_Gateway_FIB_Blocks_Support());
				}
			);
		}
	}
}

WC_FIB_Payments::init();
