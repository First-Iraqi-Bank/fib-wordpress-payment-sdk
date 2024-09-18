<?php

/**
 * Plugin Name: FIB Payments Gateway
 * Plugin URI: https://github.com/First-Iraqi-Bank
 * Description: Adds the FIB Payments gateway to your WooCommerce website.
 * Version: 1.1.0
 *
 * Author: Gateway ICT Solutions
 * Author URI: https://www.the-gw.com/
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

register_activation_hook(__FILE__, 'fibpg_payment_activation');

register_deactivation_hook(__FILE__, 'fibpg_deactivation');

require_once plugin_dir_path(__FILE__) . 'includes/class-fibpg-activator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-fibpg-deactivator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-fibpg-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-fibpg-payment-settings.php';
// add_filter('woocommerce_store_api_disable_nonce_check', '__return_true'); // IMPORTANT this is for testing purposes only, it should be removed in production

function fibpg_payment_activation()
{
	FIBPG_Activator::activate();
}

function fibpg_deactivation()
{
	FIBPG_Deactivator::deactivate();
}

// Initialize the shortcodes
FIBPG_Shortcodes::init();

function fibpg_enqueue_styles() {
    // Enqueue your CSS file
    wp_enqueue_style('fib-payments-css', plugin_dir_url(__FILE__) . 'assets/css/fib-payments.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'fibpg_enqueue_styles');

function fibpg_enqueue_scripts() {

    if (is_user_logged_in()) { // Modify the condition as needed
        wp_enqueue_script('fib-payments-js', plugin_dir_url(__FILE__) . 'assets/js/fib-payments.js', array('jquery'), '1.0.0', true);

        // Localize the script with the ajaxurl variable
        wp_localize_script('fib-payments-js', 'fibPaymentsData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
        ));
    }
}
add_action('wp_enqueue_scripts', 'fibpg_enqueue_scripts');





if (is_admin()) {
    add_action('admin_notices', 'settings_errors');
}

/**
 * WC FIB Payment gateway plugin class.
 *
 * @class FIBPG_Payments
 */
class FIBPG_Payments
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

        // Registers WooCommerce Blocks integration.
        add_action('woocommerce_blocks_loaded', [__CLASS__, 'woocommerce_gateway_fib_woocommerce_block_support']);
    }

    /**
     * Add the FIB Payment gateway to the list of available gateways.
     * @param array $gateways
     * @param array
     */
    public static function add_gateway($gateways)
    {
        $options = get_option('woocommerce_fibpg_settings', []);
        if (isset($options['hide_for_non_admin_users'])) {
            $hide_for_non_admin_users = $options['hide_for_non_admin_users'];
        } else {
            $hide_for_non_admin_users = 'no';
        }
        if (('yes' === $hide_for_non_admin_users && current_user_can('manage_options')) || 'no' === $hide_for_non_admin_users) {
            $gateways[] = 'FIBPG_Gateway';
        }
        return $gateways;
    }

    /**
     * Plugin includes.
     */
    public static function includes()
    {
        // Make the FIBPG_Gateway class available.
        if (class_exists('WC_Payment_Gateway')) {
            require_once 'includes/class-fibpg-gateway.php';
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
            require_once 'includes/blocks/class-fibpg-payments-blocks.php';
            add_action('woocommerce_blocks_payment_method_type_registration', function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                $payment_method_registry->register(new Gateway_FIBPG_Blocks_Support());
            });
        }
    }
}

FIBPG_Payments::init();