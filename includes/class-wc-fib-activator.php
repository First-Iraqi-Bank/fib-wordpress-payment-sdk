<?php

class wc_fib_activator
{
    /**
     * Initialize the plugin table on plugin activation
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        self::custom_payment_gateway_create_page();
    }

    private static function custom_payment_gateway_create_page()
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
}
