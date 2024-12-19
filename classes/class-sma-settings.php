<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class SMA_Settings
 * 
 * Adds admin settings for enabling/disabling split order notifications.
 */
class SMA_Settings {

    public function __construct() {
        add_filter( 'woocommerce_get_sections_shipping', array( $this, 'add_settings_section' ) );
        add_filter( 'woocommerce_get_settings_shipping', array( $this, 'add_settings_fields' ), 10, 2 );
    }

    /**
     * Add a new section under WooCommerce > Settings > Shipping.
     * 
     * @since  1.0.0
     * @return mixed
     */
    public function add_settings_section( $sections ) {
        $sections['sma_split_orders'] = __( 'Split Orders Settings', 'ship-multiple-addresses' );
        return $sections;
    }

    /**
     * Add settings fields
     * 
     * @param mixed $settings
     * @param mixed $current_section
     * 
     * @since  1.0.0
     * @return mixed
     */
    public function add_settings_fields( $settings, $current_section ) {
        if ( 'sma_split_orders' === $current_section ) {
            $settings = array(
                array(
                    'title' => __( 'Split Orders Settings', 'ship-multiple-addresses' ),
                    'type'  => 'title',
                    'id'    => 'sma_split_orders_settings',
                ),
                array(
                    'title'   => __( 'Enable Split Order Notifications', 'ship-multiple-addresses' ),
                    'desc'    => __( 'Send email notifications for split sub-orders.', 'ship-multiple-addresses' ),
                    'id'      => 'sma_enable_split_order_email',
                    'type'    => 'checkbox',
                    'default' => 'yes',
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'sma_split_orders_settings',
                ),
            );
            $settings = array_merge( $settings, array(
                array(
                    'title'   => __( 'Enable Partial Order Completed Email', 'ship-multiple-addresses' ),
                    'id'      => 'sma_enable_partial_email',
                    'type'    => 'checkbox',
                    'default' => 'yes',
                    'desc'    => __( 'Send an email when a sub-order is completed.', 'ship-multiple-addresses' ),
                ),
            ) );
            $settings = array_merge( $settings, array(
                array(
                    'title' => __( 'Exclude Products and Categories', 'ship-multiple-addresses' ),
                    'type'  => 'title',
                    'id'    => 'sma_exclude_products_categories',
                ),
                array(
                    'title'   => __( 'Exclude Products', 'ship-multiple-addresses' ),
                    'id'      => 'sma_excluded_products',
                    'type'    => 'multiselect',
                    'class'   => 'wc-enhanced-select',
                    'options' => $this->get_all_products(),
                    'desc'    => __( 'Select products to exclude from multi-shipping.', 'ship-multiple-addresses' ),
                ),
                array(
                    'title'   => __( 'Exclude Categories', 'ship-multiple-addresses' ),
                    'id'      => 'sma_excluded_categories',
                    'type'    => 'multiselect',
                    'class'   => 'wc-enhanced-select',
                    'options' => $this->get_all_categories(),
                    'desc'    => __( 'Select categories to exclude from multi-shipping.', 'ship-multiple-addresses' ),
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'sma_exclude_products_categories',
                ),
            ) );

            $settings = array_merge( $settings, array(
                array(
                    'title' => __( 'Custom Checkout Notification & Button Text', 'ship-multiple-addresses' ),
                    'type'  => 'title',
                    'id'    => 'sma_checkout_text_settings',
                ),
                array(
                    'title'   => __( 'Checkout Notification', 'ship-multiple-addresses' ),
                    'id'      => 'sma_checkout_notification',
                    'type'    => 'text',
                    'default' => __( 'Ship items to multiple addresses.', 'ship-multiple-addresses' ),
                    'desc'    => __( 'Customize the notification text displayed at checkout.', 'ship-multiple-addresses' ),
                ),
                array(
                    'title'   => __( 'Button Text', 'ship-multiple-addresses' ),
                    'id'      => 'sma_button_text',
                    'type'    => 'text',
                    'default' => __( 'Set Multiple Addresses', 'ship-multiple-addresses' ),
                    'desc'    => __( 'Customize the button text for enabling multiple shipping.', 'ship-multiple-addresses' ),
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'sma_checkout_text_settings',
                ),
            ) );
    
        }
    
        return $settings;
    }
    
    /**
     * Retrieve all products for the Exclude Products dropdown.
     * 
     * @since  1.0.0
     * @return array
     */
    private function get_all_products() {
        $products = wc_get_products( array( 'limit' => -1, 'status' => 'publish' ) );
        $options = array();
    
        foreach ( $products as $product ) {
            $options[ $product->get_id() ] = $product->get_name();
        }
    
        return $options;
    }
    
    /**
     * Retrieve all categories for the Exclude Categories dropdown.
     * 
     * @since  1.0.0
     * @return array
     */
    private function get_all_categories() {
        $categories = get_terms( 'product_cat', array( 'hide_empty' => false ) );
        $options = array();
    
        foreach ( $categories as $category ) {
            $options[ $category->term_id ] = $category->name;
        }
    
        return $options;
    }

    /**
     * Check if split order emails are enabled.
     * 
     * @since  1.0.0
     * @return bool
     */
    public static function is_email_enabled() {
        return 'yes' === get_option( 'sma_enable_split_order_email', 'yes' );
    }
}

new SMA_Settings();
