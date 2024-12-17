<?php

class SMA_Admin_Orders {

public function __construct() {
    add_filter( 'post_class', array( $this, 'highlight_sub_orders' ), 10, 3 );
    add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'display_parent_order_link' ) );
}

/**
 * Add a CSS class to highlight sub-orders in the order list.
 */
public function highlight_sub_orders( $classes, $class, $post_id ) {
    $order = wc_get_order( $post_id );

    if ( $order && $order->get_parent_id() ) {
        $classes[] = 'sma-sub-order';
    }

    return $classes;
}

/**
 * Display a link to the parent order on the sub-order details page.
 */
public function display_parent_order_link( $order ) {
    if ( $order->get_parent_id() ) {
        $parent_order_id = $order->get_parent_id();
        echo '<p><strong>' . __( 'Parent Order:', 'ship-multiple-addresses' ) . '</strong> ';
        echo '<a href="' . esc_url( admin_url( 'post.php?post=' . $parent_order_id . '&action=edit' ) ) . '">' . __( 'View Parent Order', 'ship-multiple-addresses' ) . '</a></p>';
    }
}
}

new SMA_Admin_Orders();