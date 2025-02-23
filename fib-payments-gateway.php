<?php
/**
 * Plugin Name: FIB Payments Gateway
 * Plugin URI: https://github.com/First-Iraqi-Bank
 * Description: Adds the FIB Payments gateway to your WooCommerce website.
 * Version: 2.1.0
 *
 * Author: Gateway ICT Solutions
 * Author URI: https://www.the-gw.com/
 *
 * Text Domain: fib-payments-gateway
 * Domain Path: /languages/
 *
 * Tested up to: 9.5
 *
 * Copyright: © 2009-2023 Emmanouil Psychogyiopoulos.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * @package cpgw
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

register_activation_hook( __FILE__, 'fib_payments_gateway_activation' );

register_deactivation_hook( __FILE__, 'fib_payments_gateway_deactivation' );

require_once plugin_dir_path(__FILE__) . 'includes/class-fibpg-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-fibpg-payment-settings.php';

/**
 * Activation callback function.
 */
function fib_payments_gateway_activation() {
	$fibpg_page_title = __('FIB Payment Gateway QR Code', 'fib-payments-gateway');
	$fibpg_page_content = '[fibpg_payment_qr_code]';
	$fibpg_page_template = '';

	$args = [
		'title' => $fibpg_page_title,
		'post_type' => 'page',
		'posts_per_page' => 1,
	];

	
	$fibpg_page_query = new WP_Query($args);

	if (!$fibpg_page_query->have_posts()) {
		$fibpg_new_page_id = wp_insert_post([
			'post_title'   => sanitize_text_field($fibpg_page_title),
			'post_content' => $fibpg_page_content,
			'post_status' => 'publish',
			'post_type' => 'page',
			'page_template' => $fibpg_page_template,
		]);

		update_option('fibpg_payment_gateway_page_id', $fibpg_new_page_id);
	}

	wp_reset_postdata();
}

/**
 * Deactivation callback function.
 */
function fib_payments_gateway_deactivation() {
    $fibpg_page_title = __('FIB Payment Gateway QR Code', 'fib-payments-gateway');
    
        $args = array(
            'title' => $fibpg_page_title,
            'post_type' => 'page',
            'posts_per_page' => 1,
        );
    
        $fibpg_page_query = new WP_Query($args);
    
        if ($fibpg_page_query->have_posts()) {
            while ($fibpg_page_query->have_posts()) {
                $fibpg_page_query->the_post();
                wp_delete_post(get_the_ID(), true);
            }
        }
    
        wp_reset_postdata();
}

if ( ! class_exists( 'Alg_WC_Custom_Payment_Gateways' ) ) :

	/**
	 * Main FIB_Payments_Gateway
	 *
	 * @class   FIB_Payments_Gateway
	 * @version 1.6.2
	 * @since   1.0.0
	 */
	final class FIB_Payments_Gateway {

		/**
		 * Plugin version.
		 *
		 * @var   string
		 * @since 1.0.0
		 */
		public $version = '2.0.0';

		/**
		 * The single instance of the class.
		 *
		 * @var   FIB_Payments_Gateway The single instance of the class
		 * @since 1.0.0
		 */
		protected static $_instance = null;

		/**
		 * The core file reference.
		 *
		 * @var   string The path of the core file
		 * @since 1.0.0
		 */
		protected $core = null;

		/**
		 * Main FIB_Payments_Gateway Instance
		 *
		 * Ensures only one instance of FIB_Payments_Gateway is loaded or can be loaded.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @static
		 * @return  FIB_Payments_Gateway - Main instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * FIB_Payments_Gateway Constructor.
		 *
		 * @version 1.6.0
		 * @since   1.0.0
		 * @access  public
		 */
		public function __construct() {

			// Check for active plugins.
			if (
			! $this->is_plugin_active( 'woocommerce/woocommerce.php' )
			) {
				return;
			}

			// Set up localisation.
			load_plugin_textdomain( 'fib-payments-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );

			
			// Include required files.
			$this->includes();

			// Admin.
			if ( is_admin() ) {
				$this->admin();
			}

			add_action( 'woocommerce_blocks_loaded', array( $this, 'alg_gateway_block_support' ) );
		}

		/**
		 * Is plugin active.
		 *
		 * @param   string $plugin Plugin Name.
		 * @return  bool
		 * @version 1.6.0
		 * @since   1.6.0
		 */
		public function is_plugin_active( $plugin ) {
			return ( function_exists( 'is_plugin_active' ) ? is_plugin_active( $plugin ) :
			(
				in_array( $plugin, apply_filters( 'active_plugins', (array) get_option( 'active_plugins', array() ) ), true ) ||
				( is_multisite() && array_key_exists( $plugin, (array) get_site_option( 'active_sitewide_plugins', array() ) ) )
			)
			);
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @version 1.2.0
		 * @since   1.0.0
		 */
		public function includes() {
			// Functions.
			require_once 'includes/alg-wc-custom-payment-gateways-functions.php';
			// Core.
			$this->core = require_once 'includes/class-alg-wc-custom-payment-gateways-core.php';
		}

		/**
		 * Admin.
		 *
		 * @version 1.6.2
		 * @since   1.2.0
		 */
		public function admin() {
			// Action links.
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'action_links' ) );
			// HPOS compatibility
			add_action( 'before_woocommerce_init', array( $this, 'cpg_declare_hpos_compatibility' ) );
		}

		public function cpg_declare_hpos_compatibility() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			}
		}

		/**
		 * Show action links on the plugin screen.
		 *
		 * @version 1.2.1
		 * @since   1.0.0
		 * @param   mixed $links Links.
		 * @return  array
		 */
		public function action_links( $links ) {
			$custom_links   = array();
			$custom_links[] = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=alg_wc_custom_payment_gateways' ) . '">' . __( 'Settings', 'woocommerce' ) . '</a>';
			return array_merge( $custom_links, $links );
		}

		/**
		 * Get the plugin url.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return  string
		 */
		public function plugin_url() {
			return untrailingslashit( plugin_dir_url( __FILE__ ) );
		}

		/**
		 * Get the plugin path.
		 *
		 * @version 1.0.0
		 * @since   1.0.0
		 * @return  string
		 */
		public function plugin_path() {
			return untrailingslashit( plugin_dir_path( __FILE__ ) );
		}

		/**
		 * Adds support for the custom payment gateway in WooCommerce Blocks.
		 *
		 * @return void
		 */
		public function alg_gateway_block_support() {
			// Check if the AbstractPaymentMethodType class exists (required for WooCommerce Blocks compatibility).
			if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				return;
			}

			// Include the custom payment gateway support class for WooCommerce Blocks.
			require_once __DIR__ . '/includes/class-wc-gateway-blocks-support.php';

			// Register the custom payment gateway with WooCommerce Blocks.
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					// Register the custom payment gateway with the PaymentMethodRegistry.
					$payment_method_registry->register( new WC_Gateway_Blocks_Support() );
				}
			);
		}
	}

endif;

if ( ! function_exists( 'FIB_Payments_Gateway' ) ) {
	/**
	 * Returns the main instance of FIB_Payments_Gateway to prevent the need to use globals.
	 *
	 * @version 1.0.0
	 * @since   1.0.0
	 * @return  FIB_Payments_Gateway
	 */
	function FIB_Payments_Gateway() {
		return FIB_Payments_Gateway::instance();
	}
}

FIB_Payments_Gateway();
