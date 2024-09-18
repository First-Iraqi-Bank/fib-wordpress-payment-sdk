<?php

if (!defined('ABSPATH')) {
    exit();
}

class FIBPG_Payment_Settings {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'settings_init']);
    }

    public static function add_menu() {
        add_menu_page(
            esc_html__('FIB Payment Gateway Settings', 'fibpg-payment'),
            esc_html__('FIB Payment Gateway', 'fibpg-payment'),
            'manage_options',
            'fibpg-payment-gateway',
            [__CLASS__, 'settings_page'],
            'dashicons-admin-generic'
        );
    }

    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('FIB Payment Gateway Settings', 'fibpg-payment'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('fibpg_payment_gateway_settings_group');
                do_settings_sections('fibpg-payment-gateway');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function settings_init() {
        register_setting('fibpg_payment_gateway_settings_group', 'fibpg_base_url', [__CLASS__, 'api_url_auth_validation']);
        register_setting('fibpg_payment_gateway_settings_group', 'fibpg_client_id', [__CLASS__, 'client_id_validation']);
        register_setting('fibpg_payment_gateway_settings_group', 'fibpg_client_secret', [__CLASS__, 'client_secret_validation']);

        add_settings_section(
            'fibpg_payment_gateway_settings_section',
            esc_html__('API Settings', 'fibpg-payment'),
            [__CLASS__, 'settings_section_callback'],
            'fibpg-payment-gateway'
        );

        add_settings_field(
            'fibpg_base_url',
            esc_html__('FIB Base URL', 'fibpg-payment'),
            [__CLASS__, 'api_url_auth_callback'],
            'fibpg-payment-gateway',
            'fibpg_payment_gateway_settings_section'
        );

        add_settings_field(
            'fibpg_client_id',
            esc_html__('Client ID', 'fibpg-payment'),
            [__CLASS__, 'client_id_callback'],
            'fibpg-payment-gateway',
            'fibpg_payment_gateway_settings_section'
        );

        add_settings_field(
            'fibpg_client_secret',
            esc_html__('Client Secret', 'fibpg-payment'),
            [__CLASS__, 'client_secret_callback'],
            'fibpg-payment-gateway',
            'fibpg_payment_gateway_settings_section'
        );
    }

    public static function settings_section_callback() {
        esc_html_e('Enter the FIB API settings below:', 'fibpg-payment');
    }

    public static function api_url_auth_callback() {
        $value = get_option('fibpg_base_url', '');
        echo '<input type="text" id="fibpg_base_url" name="fibpg_base_url" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public static function client_id_callback() {
        $value = get_option('fibpg_client_id', '');
        echo '<input type="text" id="fibpg_client_id" name="fibpg_client_id" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public static function client_secret_callback() {
        $value = get_option('fibpg_client_secret', '');
        echo '<input type="text" id="fibpg_client_secret" name="fibpg_client_secret" value="' . esc_attr($value) . '" class="regular-text">';
    }

    public static function api_url_auth_validation($input) {
        if (!filter_var($input, FILTER_VALIDATE_URL)) {
            add_settings_error(
                'fibpg_base_url',
                'invalid-url',
                esc_html__('Please enter a valid API Endpoint URL.', 'fibpg-payment'),
                'error'
            );
            return get_option('fibpg_base_url');
        }
        return esc_url_raw($input);
    }

    public static function client_id_validation($input) {
        if (empty($input) || !preg_match('/^[a-zA-Z0-9_-]+$/', $input)) {
            add_settings_error(
                'fibpg_client_id',
                'invalid-client-id',
                esc_html__('Please enter a valid API Client ID.', 'fibpg-payment'),
                'error'
            );
            return get_option('fibpg_client_id');
        }
        return sanitize_text_field($input);
    }

    public static function client_secret_validation($input) {
        if (empty($input)) {
            add_settings_error(
                'fibpg_client_secret',
                'invalid-client-secret',
                esc_html__('Please enter a valid API Client Secret.', 'fibpg-payment'),
                'error'
            );
            return get_option('fibpg_client_secret');
        }
        return sanitize_text_field($input);
    }
}

FIBPG_Payment_Settings::init();
