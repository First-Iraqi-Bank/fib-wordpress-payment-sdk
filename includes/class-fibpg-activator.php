<?php
if (!defined('ABSPATH')) {
    exit();
}
class FIBPG_Activator
{
    /**
     * Initialize the plugin table on plugin activation
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        self::fipg_payment_gateway_create_page();
    }

    private static function fipg_payment_gateway_create_page()
    {
        $page_title = 'FIB Payment Gateway QR Code';
        $page_content = '[fibpg_custom_payment_qr_code]';
        $page_template = '';

        $args = [
            'title' => $page_title,
            'post_type' => 'page',
            'posts_per_page' => 1,
        ];

        $page_query = new WP_Query($args);

        if (!$page_query->have_posts()) {
            $new_page_id = wp_insert_post([
                'post_title' => $page_title,
                'post_content' => $page_content,
                'post_status' => 'private',
                'post_type' => 'page',
                'page_template' => $page_template,
            ]);

            update_option('fibpg_payment_gateway_page_id', $new_page_id);
        }

        wp_reset_postdata();
    }
}
