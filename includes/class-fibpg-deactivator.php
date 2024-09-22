<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class FIBPG_Deactivator
{
   /**
     * Deactivate the plugin.
     *
     * This method is triggered when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    public static function deactivate()
    {
        self::remove_payment_gateway_page();
    }

    /**
     * Remove the custom payment gateway page.
     *
     * This method deletes the FIB Payment Gateway QR Code page if it exists.
     *
     * @return void
     */
    private static function remove_payment_gateway_page()
    {
        $fibpg_page_title = 'FIB Payment Gateway QR Code';
    
        $args = array(
            'title' => $fibpg_page_title,
            'post_type' => 'page',
            'posts_per_page' => 1,
        );
    
        $fibpg_page_query = new WP_Query($args);
    
        if ($fibpg_page_query->have_posts()) {
            while ($fibpg_page_query->have_posts()) {
                $fibpg_page_query->the_post();
                wp_delete_post(get_the_ID(), true);
            }
        }
    
        wp_reset_postdata();
    }
}
