<?php

class wc_fib_deactivator
{
    /**
     * Deactivate the plugin
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        self::custom_payment_gateway_delete_page();
    }

    private static function custom_payment_gateway_delete_page()
    {
        $page_title = 'FIB Payment Gateway QR Code';
        $page_check = get_page_by_title($page_title);

        if (isset($page_check->ID)) {
            wp_delete_post($page_check->ID, true);
        }
    }
}
