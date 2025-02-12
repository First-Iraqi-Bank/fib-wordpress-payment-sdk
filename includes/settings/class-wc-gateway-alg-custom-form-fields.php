<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$div_style = 'background-color: #fefefe; padding: 10px; border: 1px solid #d8d8d8; width: fit-content; font-style: italic; font-size: small;';

$fields = array(
	'enabled'                => array(
		'title'   => __( 'Enable/Disable', 'woocommerce' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable FIB payments gateway', 'fib-payments-gateway' ),
		'default' => 'no',
	),
	'title'                  => array(
		'title'       => __( 'Title', 'woocommerce' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
		'default'     => __( 'FIB Payment', 'fib-payments-gateway' ),
		'desc_tip'    => true,
	),
	'description'            => array(
		'title'       => __( 'Description', 'woocommerce' ),
		'type'        => 'textarea',
		'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
		'default'     => __( 'Pay With FIB.', 'fib-payments-gateway' ),
		'desc_tip'    => true,
	),
);
return $fields;
