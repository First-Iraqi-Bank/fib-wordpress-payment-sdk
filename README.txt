# FIB Payments Gateway
- Contributors: thegateway
- Tags: payments, WooCommerce, gateway, FIB
- Tested up to: 9.5
- Requires PHP: 7.2
- Stable tag: 2.1.0
- License: GNU General Public License v3.0
- License URI: http://www.gnu.org/licenses/gpl-3.0.html

A FIB payment gateway for your WooCommerce development needs, with built-in support for subscriptions and the block-based checkout.

## Description
This WordPress plugin adds the FIB Payments gateway to WooCommerce, allowing users to make payments using the First Iraqi Bank's payment system.

## Permalink Configuration
The WordPress permalink should be set as Post name: for example:- http://localhost/wordpress/sample-post/

You can change this setting in your WordPress Admin Panel → Settings → Permalinks.

Please note that the permalink should not be set as following (your_wordpress_url/index.php/post), otherwise you will face page not found issue.

## Third-Party Services

This plugin relies on the First Iraqi Bank's payment service for processing transactions. The following external endpoints are used:

- **Create Payment**: ` https://fib.stage.fib.iq/protected/v1/payments`
- **Cancel Payment**: `https://fib.stage.fib.iq/protected/v1/payments/4d6f7625-60f7-48e3-82e3-b4592a4eb993/cancel`
- **Check Payment Status**: `https://fib.stage.fib.iq/protected/v1/payments/{paymentid}/status`

### Third-Party Service Information

- **Service Provider**: [First Iraqi Bank](https://fib.iq/) 
- **Service Terms of Use**: [Terms of Use](https://fib.iq/integrations/web-payments/)
- **Service Privacy Policy**: [Privacy Policy](https://fib.iq/privacy-and-security/)

The plugin sends payment data to the First Iraqi Bank’s API endpoints for the purposes of creating payments, checking payment statuses, and canceling payments. 

Please review the service provider's terms and privacy policy to understand how your data is handled and ensure you are compliant with their requirements.


## Features

- Integrates FIB payment gateway with WooCommerce.
- Provides a custom page to display a QR code for payment.
- A custom button to regenerate the QR Code.
- Personal, Business, and Cooporate direct button payment for mobile devices.
- Automatically checks payment status and updates order status upon payment completion.
- Supports WooCommerce Blocks for a seamless checkout experience.


## Requirements

- WooCommerce plugin installed and activated.
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

5. Enable the Payment Gateway:
    - Once activated, you must enable the payment gateway in WooCommerce → Settings → Payments.
    - Finish setup by checking Enable FIB payments gateway checkbox.
    - You can also change the title and description of the gateway to control how it appears during checkout.

6. Configure the Plugin:
    - In the sidebar, go to FIB Payment Gateway and enter your FIB credentials:
        - FIB Base URL: The base URL for the FIB payment API.
        - Client ID: Your FIB payment API key.
        - Client Secret: Your FIB payment API secret.

## Usage
After installation and activation, the FIB Payments gateway will be available as a payment method in WooCommerce. You can enable it from `WooCommerce > Settings > Payments`.

Upon plugin activation the plugin will automatically creates a new custom page, This custom page dynamically generates a QR code based on the order details and allows users to scan and make payments after checking out.

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
    - Make sure to put a proper wordpress permalink.

- Debugging:
    - Enable debugging in WordPress by adding `define('WP_DEBUG', true);` and `define('WP_DEBUG_LOG', true);` to your wp-config.php file.
    - Check the debug.log file in the wp-content directory for any errors or warnings.

## License
This plugin is licensed under the GNU General Public License v2.0 or later.

## Author
Gateway ICT Solutions

## Source Code
The source code for this plugin is available at [GitHub Repository](https://github.com/First-Iraqi-Bank/fib-wordpress-payment-sdk)

## Support
For support, please visit the plugin's GitHub repository issues section.

## Frequently Asked Questions
### How do I get FIB API credentials?
 - You can obtain FIB API credentials by contacting First Iraqi Bank support team at support@fib-payment.com.

## Changelog

**2.1.0**
* Allow all users to regenerate QR codes.
* Allow all users to check payment status.

**2.0.0**
* Major updates, bug fixes, and improvments.

**1.3.5**
* Bug Fixes, and improvments.

**1.3.3**
* Bug Fixes, and improvments.

**1.3.3**
* Bug Fixes, and improvments.

**1.3.2**
* Bug Fixes, and improvments.

**1.3.1**
* Fixes, and improvments.

**1.3.0**
* Added three app link buttons so user can directly open the relevent app without the need to scan the QR code.
* Added readable code, to enter the code manually.
* Improved QR code generation for better user experience.

**1.2.2**
* Added new payment error handling.
* Improved nonce security for API requests.
* Fixed potential XSS vulnerabilities.
* Enhanced logging for API responses.
* Updated documentation with clearer instructions.

**1.2.1**
* Fixed bugs related to payment status checks.
* Improved error handling and logging.
* Enhanced security measures for API credentials.
* Updated documentation for clarity and accuracy.
* Minor performance optimizations.

**1.2.0**
* Added new payment status check via AJAX.
* Refactored JavaScript to use localized variables.
* Updated function names and classes as per the team request.
* SANITIZE , VALIDATE, and ESCAPE data.
* updated the documentation.
* Added source map.
* Added more security concerns.
* Fixed redirect issue.

**1.1.0**
* Initial release.

## Upgrade Notice

**2.0.0**
* Important: The plugin structure has been updated and rebuilt. Ensure you clear your browser cache to load the latest version.

**1.2.0**
* Important: The JavaScript file structure was updated. Ensure you clear your browser cache to load the latest version.

**1.1.0**
* Initial release.
