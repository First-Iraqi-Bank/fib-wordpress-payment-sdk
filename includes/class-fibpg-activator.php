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
        $fibpg_page_title = __('FIB Payment Gateway QR Code', 'fib-payments-gateway');
        $fibpg_page_content = '[fibpg_payment_qr_code]';
        $fibpg_page_template = '';

        $args = [
            'title' => $fibpg_page_title,
            'post_type' => 'page',
            'posts_per_page' => 1,
        ];

        $fibpg_page_query = new WP_Query($args);

        if (!$fibpg_page_query->have_posts()) {
            $fibpg_new_page_id = wp_insert_post([
                'post_title'   => sanitize_text_field($fibpg_page_title),
                'post_content' => $fibpg_page_content,
                'post_status' => 'publish',
                'post_type' => 'page',
                'page_template' => $fibpg_page_template,
            ]);

            update_option('fibpg_payment_gateway_page_id', $fibpg_new_page_id);
        }

        wp_reset_postdata();
    }
}
