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
    
        $args = array(
            'title' => $page_title,
            'post_type' => 'page',
            'posts_per_page' => 1,
        );
    
        $page_query = new WP_Query($args);
    
        if ($page_query->have_posts()) {
            while ($page_query->have_posts()) {
                $page_query->the_post();
                wp_delete_post(get_the_ID(), true);
            }
        }
    
        wp_reset_postdata();
    }
}
