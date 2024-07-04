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
    exit();
}

register_activation_hook(__FILE__, 'custom_payment_gateway_activate');

register_deactivation_hook(__FILE__, 'plugin_deactivation');

add_filter('woocommerce_store_api_disable_nonce_check', '__return_true'); // IMPORTANT this is for testing purposes only, it should be removed in production

function custom_payment_gateway_activate()
{
    custom_payment_gateway_create_page();
}

function plugin_deactivation()
{
    $page_title = 'FIB Payment Gateway QR Code';

    $page = get_page_by_title($page_title);

    if ($page) {
        wp_delete_post($page->ID, true); // 'true' to force delete without sending to trash
    }
}

function custom_payment_gateway_create_page()
{
    $page_title = 'FIB Payment Gateway QR Code';
    $page_content = '[custom_payment_qr_code]';
    $page_template = '';

    $page_check = get_page_by_title($page_title);

    if (!isset($page_check->ID)) {
        $new_page_id = wp_insert_post([
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'page_template' => $page_template,
        ]);

        update_option('custom_payment_gateway_page_id', $new_page_id);
    }
}

function custom_payment_qr_code_shortcode()
{
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    session_start();
    if ($order_id) {
        $order = wc_get_order($order_id);
        $payment_id = esc_js($_SESSION['payment_id']);

        if ($order) {
            if (isset($_SESSION['qr_data'])) {
                $qr_code_url = $_SESSION['qr_data'];
                return '<div style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 55vh;">
						<p> Scan the QR code bellow to procced the payment </p>
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
									console.log(response);
									if (response.data.status === "PAID") {
										window.location.href = "' . home_url('/checkout/order-received/') . '?order_id=' . $order_id . '";
									}
								},
								error: function(response) {
									console.log("Error checking payment status.");
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


add_shortcode('custom_payment_qr_code', 'custom_payment_qr_code_shortcode');

add_action('wp_ajax_check_payment_status', 'check_payment_status'); // this is for authenticated users
add_action('wp_ajax_nopriv_check_payment_status', 'check_payment_status'); // this is for non-authenticated users

function check_payment_status()
{
    $payment_id = sanitize_text_field($_GET['payment_id']);
    $order_id = sanitize_text_field($_GET['order_id']);
	
	$api_url_auth = get_option('fib_api_url_auth');
	$api_url_payment = get_option('fib_api_url_payment');
	$client_id = get_option('fib_client_id');
	$client_secret = get_option('fib_client_secret');
	if (empty($api_url_auth) || empty($client_id) || empty($client_secret) || empty($api_url_payment)) {
		throw new Exception(__('Please configure your FIB settings.', 'woocommerce-gateway-fib'));
	}
    $nonce = wp_create_nonce('wp_rest');

	$response_auth = wp_remote_post($api_url_auth, array(
		'headers' => array(
			'X-WP-Nonce' => $nonce,
			'Content-Type' => 'application/x-www-form-urlencoded',
		),
		'body' => array(
			'grant_type' => 'client_credentials',
			'client_id' => $client_id,
			'client_secret' => $client_secret,
		),
		'sslverify' => false, // IMPORTANT: remove this line in production
	));
	$response_body_auth = wp_remote_retrieve_body($response_auth);
	$response_data_auth = json_decode($response_body_auth, true);

	$response = wp_remote_get($api_url_payment . '/' . $payment_id . '/status', array(
		'headers' => array(
			'X-WP-Nonce' => $nonce,
			'Content-Type' => 'application/json',
			'Authorization' => 'Bearer ' . $response_data_auth['access_token'],
		),
		'sslverify' => false, // IMPORTANT: remove this line in production
	));
	$response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);
	
	
    if (is_wp_error($response)) {
        wp_send_json_error(['status' => 'error']);
    }

    if ($response_data['status'] === 'PAID') {
        // Payment is successful, update order status
        $order = wc_get_order($order_id);
        $order->payment_complete();
        WC()->cart->empty_cart();

        wp_send_json_success(['status' => 'PAID']);
    } else {
        wp_send_json_success(['status' => 'UNPAID']);
    }

    wp_die();
}

add_action('admin_menu', 'fib_payment_gateway_menu');

function fib_payment_gateway_menu() {
    add_menu_page(
        'FIB Payment Gateway Settings',
        'FIB Payment Gateway',
        'manage_options',
        'fib-payment-gateway',
        'fib_payment_gateway_settings_page',
        'dashicons-admin-generic'
    );
}

function fib_payment_gateway_settings_page() {
    ?>
    <div class="wrap">
        <h1>FIB Payment Gateway Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('fib_payment_gateway_settings_group');
            do_settings_sections('fib-payment-gateway');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
add_action('admin_init', 'fib_payment_gateway_settings_init');

function fib_payment_gateway_settings_init() {
    register_setting('fib_payment_gateway_settings_group', 'fib_api_url_auth', 'fib_api_url_auth_validation');
    register_setting('fib_payment_gateway_settings_group', 'fib_api_url_payment', 'fib_api_url_payment_validation');
    register_setting('fib_payment_gateway_settings_group', 'fib_client_id', 'fib_client_id_validation');
    register_setting('fib_payment_gateway_settings_group', 'fib_client_secret', 'fib_client_secret_validation');

    add_settings_section(
        'fib_payment_gateway_settings_section',
        'API Settings',
        'fib_payment_gateway_settings_section_callback',
        'fib-payment-gateway'
    );

    add_settings_field(
        'fib_api_url_auth',
        'API URL For Authentication',
        'fib_api_url_auth_callback',
        'fib-payment-gateway',
        'fib_payment_gateway_settings_section'
    );

	add_settings_field(
        'fib_api_url_payment',
        'API URL For Payment and check Status',
        'fib_api_url_payment_callback',
        'fib-payment-gateway',
        'fib_payment_gateway_settings_section'
    );


    add_settings_field(
        'fib_client_id',
        'Client ID',
        'fib_client_id_callback',
        'fib-payment-gateway',
        'fib_payment_gateway_settings_section'
    );

    add_settings_field(
        'fib_client_secret',
        'Client Secret',
        'fib_client_secret_callback',
        'fib-payment-gateway',
        'fib_payment_gateway_settings_section'
    );
}


function fib_payment_gateway_settings_section_callback() {
    echo 'Enter the FIB API settings below:';
}

function fib_api_url_auth_callback() {
    $value = get_option('fib_api_url_auth', '');
    echo '<input type="text" id="fib_api_url_auth" name="fib_api_url_auth" value="' . esc_attr($value) . '" class="regular-text">';
}

function fib_api_url_payment_callback() {
    $value = get_option('fib_api_url_payment', '');
    echo '<input type="text" id="fib_api_url_payment" name="fib_api_url_payment" value="' . esc_attr($value) . '" class="regular-text">';
}

function fib_client_id_callback() {
    $value = get_option('fib_client_id', '');
    echo '<input type="text" id="fib_client_id" name="fib_client_id" value="' . esc_attr($value) . '" class="regular-text">';
}

function fib_client_secret_callback() {
    $value = get_option('fib_client_secret', '');
    echo '<input type="text" id="fib_client_secret" name="fib_client_secret" value="' . esc_attr($value) . '" class="regular-text">';
}

function fib_api_url_auth_validation($input) {
	if (!filter_var($input, FILTER_VALIDATE_URL)) {
        add_settings_error(
            'fib_api_url_auth',
            'invalid-url',
            'Please enter a valid API Endpoint URL.',
            'error'
        );
        return get_option('fib_api_url_auth');
    }
    return esc_url_raw($input);
	// return sanitize_text_field($input);
}

function fib_api_url_payment_validation($input) {
	if (!filter_var($input, FILTER_VALIDATE_URL)) {
		add_settings_error(
			'fib_payment_api_url',
			'invalid-url',
			'Please enter a valid API Endpoint URL.',
			'error'
		);
		return get_option('fib_payment_api_url');
	}
	return esc_url_raw($input);
}

function fib_client_id_validation($input) {

	if (empty($input) || !preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
		add_settings_error(
			'fib_client_id',
			'invalid-client-id',
			'Please enter a valid API Client ID.',
			'error'
		);
		return get_option('fib_client_id');
	}
    return sanitize_text_field($input);
}

function fib_client_secret_validation($input) {
	if (empty($input)) {
        add_settings_error(
            'fib_client_secret',
            'invalid-client-secret',
            'Please enter a valid API Client Secret.',
            'error'
        );
        return get_option('fib_client_secret');
    }
    return sanitize_text_field($input);
}


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
