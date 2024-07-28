<?php

/**
 * Plugin Name: FIB Payments Gateway
 * Plugin URI: https://github.com/First-Iraqi-Bank
 * Description: Adds the FIB Payments gateway to your WooCommerce website.
 * Version: 1.1.0
 *
 * Author: First Iraqi Bank
 * Author URI: https://fib.iq/en
 *
 * Text Domain: fib-payments-gateway
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
    exit();
}

register_activation_hook(__FILE__, 'fib_payment_activation');

register_deactivation_hook(__FILE__, 'plugin_deactivation');

// add_filter('woocommerce_store_api_disable_nonce_check', '__return_true'); // IMPORTANT this is for testing purposes only, it should be removed in production

function fib_payment_activation()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wc-fib-activator.php';
	wc_fib_activator::activate();
}

function plugin_deactivation()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-wc-fib-deactivator.php';
	wc_fib_deactivator::deactivate();
}

// Include the shortcodes class file for the QR code
require_once plugin_dir_path(__FILE__) . 'includes/class-wc-fib-shortcodes.php';

// Initialize the shortcodes
WC_FIB_Shortcodes::init();

// Include the settings class
require_once plugin_dir_path(__FILE__) . 'includes/class-fib-payment-settings.php';

if (is_admin()) {
    add_action('admin_notices', function() {
        settings_errors();
    });
}

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
        add_action('plugins_loaded', [__CLASS__, 'includes'], 0);

        // Make the FIB Payments gateway available to WC.
        add_filter('woocommerce_payment_gateways', [__CLASS__, 'add_gateway']);

        add_filter('woocommerce_payment_process', [__CLASS__, 'add_gateway']);

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', [__CLASS__, 'woocommerce_gateway_fib_woocommerce_block_support']);
    }

    /**
     * Add the FIB Payment gateway to the list of available gateways.
     *
     * @param array
     */
    public static function add_gateway($gateways)
    {
        $options = get_option('woocommerce_fib_settings', []);

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
            add_action('woocommerce_blocks_payment_method_type_registration', function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new WC_Gateway_FIB_Blocks_Support());
            });
        }
    }
}

WC_FIB_Payments::init();