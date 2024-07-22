# WooCommerce FIB Payments Gateway

    A FIB payment gateway for your WooCommerce development needs, with built-in support for subscriptions and the block-based checkout.

<!-- ### Building instructions

To build this project, run: 

```
nvm use
npm install
npm run packages-update
npm run build
``` -->

# WooCommerce FIB Payments Gateway

    This WordPress plugin adds the FIB Payments gateway to WooCommerce, allowing users to make payments using the First Iraqi Bank's payment system.

## Features

    - Integrates FIB payment gateway with WooCommerce.
    - Provides a custom page to display a QR code for payment.
    - A custom button to regenarate the QR Code.
    - Automatically checks payment status and updates order status upon payment completion.
    - Supports WooCommerce Blocks for a seamless checkout experience.

## Requirements

    - WordPress 4.2 or higher.
    - WooCommerce plugin installed and activated.
    - PHP session support enabled on your server.
    - A valid FIB account and API credentials.

## Set Up & Installation

    1. Download the Plugin:

        - Download the plugin from the GitHub repository as a ZIP file.

    2. Install WooCommerce:

        - Ensure the WooCommerce plugin is installed and activated in your WordPress admin panel.

    3. Install the Plugin:

        - In your WordPress dashboard, go to Plugins > Add New > Upload Plugin.
        - Upload the downloaded ZIP file and click 'Install Now'.
        - Alternatively, you can install the plugin directly through the WordPress plugins screen.

    4. Activate the Plugin:

        - After installation, activate the plugin through the 'Plugins' screen in WordPress.

    5. Configure the Plugin:

        - In the sidebar, go to FIB Payment Gateway and enter your FIB credentials:
            - FIB Base URL: The base URL for the FIB payment API.
            - Client ID: Your FIB payment API key.
            - Client Secret: Your FIB payment API secret.

## Usage

    After installation and activation, the FIB Payments gateway will be available as a payment method in WooCommerce. You can enable it from `WooCommerce > Settings > Payments`.

    To display a QR code for payment, use the shortcode `[custom_payment_qr_code]` in your posts or pages. This shortcode dynamically generates a QR code based on the order details and allows users to scan and make payments.

## Configuration

    - API Settings: Configure your FIB API settings including the Base URL, Client ID, and Client Secret.
    - QR Code Settings: Customize the appearance and functionality of the QR code displayed to users.

## Hooks and Filters

    - Actions:
        - wp_ajax_check_payment_status: Handles AJAX request for authenticated users to check payment status.
        - wp_ajax_nopriv_check_payment_status: Handles AJAX request for non-authenticated users to check payment status.

    - Filters:
        - fib_payment_gateway_api_endpoint: Filter to modify the FIB API endpoint.
        - fib_payment_gateway_qr_code: Filter to customize the QR code generation process.

## Customization

    The plugin provides several hooks and filters allowing developers to extend its functionality and integrate custom features as needed.

## Troubleshooting

    - Common Issues:

        - Ensure that your FIB credentials are correct and the API endpoint is reachable.
        - Verify that the WooCommerce plugin is properly configured and active.
        - Check for conflicts with other plugins that might interfere with the payment gateway.

    - Debugging:

        - Enable debugging in WordPress by adding define('WP_DEBUG', true); and define('WP_DEBUG_LOG', true); to your wp-config.php file.
        - Check the debug.log file in the wp-content directory for any errors or warnings.

## License

    This plugin is licensed under the GNU General Public License v3.0.

## Author

    First Iraqi Bank

## Plugin URI

    [GitHub Repository](https://github.com/First-Iraqi-Bank/fib-wordpress-payment-sdk)

## Support

    For support, please visit the plugin's GitHub repository issues section.
