<?php

if (!defined('ABSPATH')) {
    exit();
}

class FIB_Payment_Settings {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'settings_init']);
    }

    public static function add_menu() {
        add_menu_page(
            'FIB Payment Gateway Settings',
            'FIB Payment Gateway',
            'manage_options',
            'fib-payment-gateway',
            [__CLASS__, 'settings_page'],
            'dashicons-admin-generic'
        );
    }

    public static function settings_page() {
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

    public static function settings_init() {
        register_setting('fib_payment_gateway_settings_group', 'fib_api_url_auth', [__CLASS__, 'api_url_auth_validation']);
        register_setting('fib_payment_gateway_settings_group', 'fib_api_url_payment', [__CLASS__, 'api_url_payment_validation']);
        register_setting('fib_payment_gateway_settings_group', 'fib_client_id', [__CLASS__, 'client_id_validation']);
        register_setting('fib_payment_gateway_settings_group', 'fib_client_secret', [__CLASS__, 'client_secret_validation']);

        add_settings_section(
            'fib_payment_gateway_settings_section',
            'API Settings',
            [__CLASS__, 'settings_section_callback'],
            'fib-payment-gateway'
        );

        add_settings_field(
            'fib_api_url_auth',
            'API URL For Authentication',
            [__CLASS__, 'api_url_auth_callback'],
            'fib-payment-gateway',
            'fib_payment_gateway_settings_section'
        );

        add_settings_field(
            'fib_api_url_payment',
            'API URL For Payment and Check Status',
            [__CLASS__, 'api_url_payment_callback'],
            'fib-payment-gateway',
            'fib_payment_gateway_settings_section'
        );

        add_settings_field(
            'fib_client_id',
            'Client ID',
            [__CLASS__, 'client_id_callback'],
            'fib-payment-gateway',
            'fib_payment_gateway_settings_section'
        );

        add_settings_field(
            'fib_client_secret',
            'Client Secret',
            [__CLASS__, 'client_secret_callback'],
            'fib-payment-gateway',
            'fib_payment_gateway_settings_section'
        );
    }

    public static function settings_section_callback() {
        echo 'Enter the FIB API settings below:';
    }

    public static function api_url_auth_callback() {
        $value = get_option('fib_api_url_auth', '');
        echo '<input type="text" id="fib_api_url_auth" name="fib_api_url_auth" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public static function api_url_payment_callback() {
        $value = get_option('fib_api_url_payment', '');
        echo '<input type="text" id="fib_api_url_payment" name="fib_api_url_payment" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public static function client_id_callback() {
        $value = get_option('fib_client_id', '');
        echo '<input type="text" id="fib_client_id" name="fib_client_id" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public static function client_secret_callback() {
        $value = get_option('fib_client_secret', '');
        echo '<input type="text" id="fib_client_secret" name="fib_client_secret" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public static function api_url_auth_validation($input) {
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
    }

    public static function api_url_payment_validation($input) {
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

    public static function client_id_validation($input) {
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

    public static function client_secret_validation($input) {
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
}

FIB_Payment_Settings::init();
