<?php

/**
 * Plugin Name: Shipping Multiple Addresses for WooCommerce®
 * Description: Coming Soon
 * Plugin URI:  https://github.com/robertdevore/shipping-multiple-addresses-for-woocommerce/
 * Version:     1.0.0
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: shipping-multiple-addresses-for-woocommerce
 * Domain Path: /languages
 * Update URI:  https://github.com/deviodigital/shipping-multiple-addresses-for-woocommerce/
 * 
 * WC requires at least: 5.0
 * WC tested up to:      8.0
 */

defined( 'ABSPATH' ) || exit;

require 'includes/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/deviodigital/shipping-multiple-addresses-for-woocommerce/',
    __FILE__,
    'shipping-multiple-addresses-for-woocommerce'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );
 
// Define plugin constants.
define( 'SMA_PLUGIN_VERSION', '1.0' );
define( 'SMA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include core files.
require_once SMA_PLUGIN_DIR . 'classes/class-sma-init.php';

/**
 * Initialize the plugin.
 */
function sma_init() {
    new SMA_Init();
}
add_action( 'plugins_loaded', 'sma_init' );

/**
 * Register custom email class for split orders.
 */
function sma_register_custom_email( $email_classes ) {
    if ( class_exists( 'WC_Email' ) ) {
        require_once SMA_PLUGIN_DIR . 'classes/class-sma-split-order-email.php';
        $email_classes['SMA_Split_Order_Email'] = new SMA_Split_Order_Email();
    }
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'sma_register_custom_email' );

/**
 * Register custom email class for partial orders.
 */
function sma_register_partial_order_email( $email_classes ) {
    if ( class_exists( 'WC_Email' ) ) {
        require_once SMA_PLUGIN_DIR . 'classes/class-sma-partial-order-email.php';
        $email_classes['SMA_Partial_Order_Email'] = new SMA_Partial_Order_Email();
    }
    return $email_classes;
}
add_filter( 'woocommerce_email_classes', 'sma_register_partial_order_email' );

/**
 * Summary of sma_display_sub_orders
 * @param mixed $order
 * @return void
 */
function sma_display_sub_orders( $order ) {
    $sub_orders = wc_get_orders( array( 'parent' => $order->get_id(), 'limit' => -1 ) );

    if ( ! empty( $sub_orders ) ) {
        echo '<h4>' . __( 'Sub-Orders', 'ship-multiple-addresses' ) . '</h4>';
        echo '<ul style="list-style: disc; padding-left: 20px;">';
        foreach ( $sub_orders as $sub_order ) {
            echo '<li>
                <a href="' . esc_url( admin_url( 'post.php?post=' . $sub_order->get_id() . '&action=edit' ) ) . '">'
                . sprintf( __( 'Sub-Order #%d - %s', 'ship-multiple-addresses' ), $sub_order->get_id(), wc_get_order_status_name( $sub_order->get_status() ) ) . '</a>
            </li>';
        }
        echo '</ul>';
    }
}
add_action( 'woocommerce_admin_order_data_after_order_details', 'sma_display_sub_orders' );

/**
 * Summary of sma_add_custom_order_columns
 * 
 * @param mixed $columns
 * @return array
 */
function sma_add_custom_order_columns( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $column ) {
        $new_columns[ $key ] = $column;
        if ( 'order_date' === $key ) {
            $new_columns['parent_order'] = __( 'Parent Order', 'ship-multiple-addresses' );
        }
    }
    return $new_columns;
}
add_filter( 'manage_edit-shop_order_columns', 'sma_add_custom_order_columns' );

/**
 * Summary of sma_display_parent_order_column
 * @param mixed $column
 * @param mixed $post_id
 * @return void
 */
function sma_display_parent_order_column( $column, $post_id ) {
    if ( 'parent_order' === $column ) {
        $order = wc_get_order( $post_id );
        if ( $order->get_parent_id() ) {
            echo '<a href="' . esc_url( admin_url( 'post.php?post=' . $order->get_parent_id() . '&action=edit' ) ) . '">'
                 . sprintf( __( '#%d', 'ship-multiple-addresses' ), $order->get_parent_id() ) . '</a>';
        } else {
            echo __( 'N/A', 'ship-multiple-addresses' );
        }
    }
}
add_action( 'manage_shop_order_posts_custom_column', 'sma_display_parent_order_column', 10, 2 );
