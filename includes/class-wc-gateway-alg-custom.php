<?php

add_action( 'plugins_loaded', 'init_wc_gateway_alg_custom_class' );
require_once 'class-fibpg-api-auth.php';
require_once 'class-fibpg-api-payment.php';

if ( ! function_exists( 'init_wc_gateway_alg_custom_class' ) ) {

	/**
	 * Load the class for creating custom gateway once plugins are loaded.
	 */
	function init_wc_gateway_alg_custom_class() {

		if ( class_exists( 'WC_Payment_Gateway' ) && ! class_exists( 'WC_Gateway_Alg_Custom_Template' ) ) {

			/**
			 * WC_Gateway_Alg_Custom_Template class.
			 *
			 * @version 1.6.3
			 * @since   1.0.0
			 */
			class WC_Gateway_Alg_Custom_Template extends WC_Payment_Gateway {

				/**
				 * Check WC version for Backward compatibility.
				 *
				 * @var string
				 */
				public $is_wc_version_below_3 = null;

				/**
				 * The current count of the payment gateway being referenced.
				 *
				 * @var string
				 */
				public $id_count = null;
				
				/**
				 * Constructor.
				 *
				 * @version 1.1.0
				 * @since   1.0.0
				 */
				public function __construct() {
					$this->is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );
					return true;
				}

				/**
				 * Initialise gateway settings form fields.
				 *
				 * @version 1.3.0
				 * @since   1.0.0
				 * @todo    [dev] check if we really need `is_admin()` for `$shipping_methods`
				 * @todo    [dev] maybe redo `'yes' !== get_option( 'alg_wc_cpg_load_shipping_method_instances', 'yes' )`
				 */
				public function init_form_fields() {
					// Prepare shipping methods.
					$shipping_methods                  = array();
					$do_load_shipping_method_instances = get_option( 'alg_wc_cpg_load_shipping_method_instances', 'yes' );
					if ( 'disable' !== $do_load_shipping_method_instances && is_admin() ) {
						$data_store = WC_Data_Store::load( 'shipping-zone' );
						$raw_zones  = $data_store->get_zones();
						foreach ( $raw_zones as $raw_zone ) {
							$zones[] = new WC_Shipping_Zone( $raw_zone );
						}
						$zones[] = new WC_Shipping_Zone( 0 );
						foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
							$shipping_methods[ $method->get_method_title() ] = array();

							$shipping_methods[ $method->get_method_title() ][ $method->id ] = sprintf(
								// Translators: %1$s shipping method name.
								__( 'Any &quot;%1$s&quot; method', 'woocommerce' ),
								$method->get_method_title()
							);
							foreach ( $zones as $zone ) {
								$shipping_method_instances = $zone->get_shipping_methods();
								$shipping_method_instances = ( 'yes' === $do_load_shipping_method_instances ? $zone->get_shipping_methods() : array() );
								foreach ( $shipping_method_instances as $shipping_method_instance_id => $shipping_method_instance ) {
									if ( $shipping_method_instance->id !== $method->id ) {
										continue;
									}
									$option_id = $shipping_method_instance->get_rate_id();

									$option_instance_title = sprintf(
										// Translators: %1$s shipping method title, %2$s shipping method id.
										__( '%1$s (#%2$s)', 'woocommerce' ),
										$shipping_method_instance->get_title(),
										$shipping_method_instance_id
									);

									$option_title = sprintf(
										// Translators: %1$s zone name, %2$s shipping method instance name.
										__( '%1$s &ndash; %2$s', 'woocommerce' ),
										$zone->get_id() ? $zone->get_zone_name() :
										__( 'Other locations', 'woocommerce' ),
										$option_instance_title
									);
									$shipping_methods[ $method->get_method_title() ][ $option_id ] = $option_title;
								}
							}
						}
					}
					// Prepare icon description.
					$icon_desc = ( '' !== ( $icon_url = $this->get_option( 'icon', '' ) ) ?
						'<img src="' . $icon_url . '" alt="' . $this->title . '" title="' . $this->title . '" />' : '' );
					// Form fields.
					$this->form_fields = require 'settings/class-wc-gateway-alg-custom-form-fields.php';
				}

				public function process_payment( $order_id ) {

					try {
						$fibpg_order = wc_get_order($order_id);
						
						$fibpg_order->update_status('pending', esc_html__('Awaiting QR code payment', 'fib-payments-gateway'));
			
						wc_reduce_stock_levels($order_id);
			
						$fibpg_nonce = wp_create_nonce('custom_payment_qr_code_nonce');
			
						$site_url = get_site_url();
						
						$fibpg_redirect_url = esc_url_raw(
							trailingslashit($site_url) . 'fib-payment-gateway-qr-code/?order_id=' . $order_id . '&nonce=' . $fibpg_nonce
						);
						
						$test = $this->get_fibpg_customer_url($fibpg_order);
						
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

				/**
				 * Get input fields.
				 *
				 * @version 1.5.0
				 * @since   1.3.0
				 * @todo    [dev] add `style`
				 * @todo    [dev] customizable key (i.e. instead of `sanitize_title( $input_field['title'] )`)
				 * @todo    [dev] more field types (e.g. `radio`, maybe `file` etc.)
				 * @todo    [dev] more options for types (e.g. for `min`, `max`, `step` for `number` etc.)
				 * @todo    [dev] customizable template (e.g. `fieldset` etc.) and position (i.e. before or after the description)
				 */
				public function get_input_fields() {
					$html         = '';
					$input_fields = array();
					for ( $i = 1; $i <= apply_filters( 'alg_wc_custom_payment_gateways_values', 1, 'total_input_fields', $this ); $i++ ) { //phpcs:ignore
						$title = $this->get_option( 'input_fields_title_' . $i, '' );
						if ( '' !== $title ) {
							$input_fields[] = array(
								'title'       => $title,
								'required'    => ( 'yes' === $this->get_option( 'input_fields_required_' . $i, 'no' ) ),
								'type'        => $this->get_option( 'input_fields_type_' . $i, 'text' ),
								'placeholder' => $this->get_option( 'input_fields_placeholder_' . $i, '' ),
								'class'       => $this->get_option( 'input_fields_class_' . $i, '' ),
								'value'       => $this->get_option( 'input_fields_value_' . $i, '' ),
								'options'     => $this->get_option( 'input_fields_options_' . $i, '' ),
							);
						}
					}
					foreach ( $input_fields as $input_field ) {

						$html .= '<p class="form-row form-row-wide' . ( $input_field['required'] ? ' validate-required' : '' ) . '">';

						$html .= '<label for="alg_wc_cpg_input_fields_' . $this->id . '_' . sanitize_title( $input_field['title'] ) . '" class="">' .
							$input_field['title'] . ( $input_field['required'] ? '&nbsp;<abbr class="required" title="required">*</abbr>' : '' ) . '</label>';

						$html .= '<span class="woocommerce-input-wrapper">';
						switch ( $input_field['type'] ) {
							case 'select':
								$html  .= '<select' .
									' name="alg_wc_cpg_input_fields[' . $this->id . '][' . $input_field['title'] . ']"' .
									' id="alg_wc_cpg_input_fields_' . $this->id . '_' . sanitize_title( $input_field['title'] ) . '"' .
									' class="' . $input_field['class'] . '"' .
								'>';
								$values = explode( PHP_EOL, $input_field['options'] );
								foreach ( $values as $value ) {
									$html .= '<option value="' . $value . '" ' . selected( $input_field['value'], $value, false ) . '>' . $value . '</option>';
								}
								$html .= '</select>';
								break;
							case 'textarea':
								$html .= '<textarea' .
									' name="alg_wc_cpg_input_fields[' . $this->id . '][' . $input_field['title'] . ']"' .
									' id="alg_wc_cpg_input_fields_' . $this->id . '_' . sanitize_title( $input_field['title'] ) . '"' .
									' placeholder="' . $input_field['placeholder'] . '"' .
									' class="' . $input_field['class'] . '"' .
								'>' . $input_field['value'] . '</textarea>';
								break;
							default: // e.g. `text`.
								$html .= '<input' .
									' type="' . $input_field['type'] . '"' .
									' name="alg_wc_cpg_input_fields[' . $this->id . '][' . $input_field['title'] . ']"' .
									' id="alg_wc_cpg_input_fields_' . $this->id . '_' . sanitize_title( $input_field['title'] ) . '"' .
									' placeholder="' . $input_field['placeholder'] . '"' .
									' class="' . $input_field['class'] . '"' .
									( 'checkbox' !== $input_field['type'] ? ' value="' . $input_field['value'] . '"' : '' ) .
								'>';
						}
						$html .= '</span>';

						if ( $input_field['required'] ) {
							$html .= '<input' .
								' type="hidden"' .
								' name="alg_wc_cpg_input_fields_required[' . $this->id . '][' . $input_field['title'] . ']"' .
								' value="1"' .
							'>';
						}

						$html .= '</p>';
					}
					return $html;
				}

				/**
				 * Init.
				 *
				 * @param string $id_count ID Count.
				 * @version 1.3.0
				 * @since   1.0.0
				 */
				public function init( $id_count ) {
					$this->id                 = 'alg_custom_gateway_' . $id_count;
					$this->has_fields         = false;
					$this->method_title       = get_option( 'alg_wc_custom_payment_gateways_admin_title_' . $id_count, __( 'FIB Payments Gateway', 'fib-payments-gateway' ));
					$this->method_description = __( 'FIB Payments Gateway', 'fib-payments-gateway' );
					$this->id_count           = $id_count;
					// Load the settings.
					$this->init_form_fields();
					$this->init_settings();
					// Define user set variables.
					$this->title                  = $this->get_option( 'title', '' );
					$this->description            = $this->get_option( 'description', '' );
					$this->instructions           = $this->get_option( 'instructions', '' );
					// Actions.
					add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
					add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
				}

				/**
				 * If There are no payment fields show the description if set.
				 * Override this in your gateway if you have some.
				 */
				public function payment_fields() {
					$description = $this->get_description();
					if ( $description ) {
						echo wpautop( wptexturize( $description ) ); // @codingStandardsIgnoreLine.
					}

					if ( '' !== $this->get_input_fields() ) {
						echo wpautop( wptexturize( $this->get_input_fields() ) ); // @codingStandardsIgnoreLine.
					}

					if ( $this->supports( 'default_credit_card_form' ) ) {
						$this->credit_card_form(); // Deprecated, will be removed in a future version.
					}
				}
			}

			/**
			 * Add WC Gateway Classes.
			 *
			 * @param array $methods Gateway Methods.
			 * @return array
			 * @version 1.6.0
			 * @since   1.0.0
			 */
			function add_wc_gateway_alg_custom_classes( $methods ) {
				$_methods = array();
				for ( $i = 1; $i <= apply_filters( 'alg_wc_custom_payment_gateways_values', 1, 'total_gateways' ); $i++ ) { //phpcs:ignore
					$_methods[ $i ] = new WC_Gateway_Alg_Custom_Template();
					$_methods[ $i ]->init( $i );
					$methods[] = $_methods[ $i ];
				}
				return $methods;
			}
			add_filter( 'woocommerce_payment_gateways', 'add_wc_gateway_alg_custom_classes' );
		}
	}
}
