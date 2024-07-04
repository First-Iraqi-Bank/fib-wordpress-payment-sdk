# WooCommerce FIB Payments Gateway

A FIB payment gateway for your WooCommerce development needs, with built-in support for subscriptions and the block-based checkout.

### Building instructions

To build this project, run: 

```
nvm use
npm install
npm run packages-update
npm run build
```

# WooCommerce FIB Payments Gateway

This WordPress plugin adds the FIB Payments gateway to WooCommerce, allowing users to make payments using the First Iraqi Bank's payment system.

## Features

- Integrates FIB payment gateway with WooCommerce.
- Provides a shortcode to display a QR code for payment.
- Automatically checks payment status and updates order status upon payment completion.
- Supports WooCommerce Blocks for a seamless checkout experience.
- Support for env variables

## Requirements

- WordPress 4.2 or higher.
- WooCommerce plugin installed and activated.
- PHP session support enabled on your server.

## Installation

1. Download the plugin from the GitHub repository.
2. Upload the plugin files to the `/wp-content/plugins/woocommerce-gateway-fib` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Go to WooCommerce > Settings > Payments and configure the FIB Payments gateway settings.

## Usage

After installation and activation, the FIB Payments gateway will be available as a payment method in WooCommerce. You can enable it and configure its settings from WooCommerce > Settings > Payments.

To display a QR code for payment, use the shortcode `[custom_payment_qr_code]` in your posts or pages. This shortcode dynamically generates a QR code based on the order details and allows users to scan and make payments.

## Hooks and Filters

- `woocommerce_store_api_disable_nonce_check`: Disables nonce check for WooCommerce Store API.
- `wp_ajax_check_payment_status`: Handles AJAX request for authenticated users to check payment status.
- `wp_ajax_nopriv_check_payment_status`: Handles AJAX request for non-authenticated users to check payment status.

## Customization

The plugin provides several hooks and filters allowing developers to extend its functionality and integrate custom features as needed.

## License

This plugin is licensed under the GNU General Public License v3.0.

## Author

Hazhee Himdad

## Plugin URI

[GitHub Repository](https://github.com/First-Iraqi-Bank/fib-wordpress-payment-sdk)

## Support

For support, please visit the plugin's GitHub repository issues section.
