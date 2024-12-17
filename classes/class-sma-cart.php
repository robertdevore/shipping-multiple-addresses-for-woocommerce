<?php
class SMA_Cart {

    public function __construct() {
        // Hook to add the duplicate cart button
        add_action( 'woocommerce_cart_actions', array( $this, 'add_duplicate_cart_button' ) );
        
        // AJAX handler for duplicating the cart
        add_action( 'wp_ajax_sma_duplicate_cart', array( $this, 'duplicate_cart' ) );
        add_action( 'wp_ajax_nopriv_sma_duplicate_cart', array( $this, 'duplicate_cart' ) );
    }

    /**
     * Add a "Duplicate Cart" button on the Cart Page.
     */
    public function add_duplicate_cart_button() {
        echo '<button type="button" id="sma-duplicate-cart" class="button">'
            . __( 'Duplicate Cart', 'ship-multiple-addresses' )
            . '</button>';
    }

    /**
     * Handle AJAX request to duplicate the cart.
     */
    public function duplicate_cart() {
        // Verify the nonce for security
        check_ajax_referer( 'sma_nonce', 'security' );

        // Get the WooCommerce cart
        $cart = WC()->cart;

        // Check if the cart is empty
        if ( $cart->is_empty() ) {
            wp_send_json_error( array( 'message' => __( 'Cart is empty.', 'ship-multiple-addresses' ) ) );
        }

        // Duplicate cart items
        $cart_contents = $cart->get_cart();
        foreach ( $cart_contents as $cart_item ) {
            $cart->add_to_cart( $cart_item['product_id'], $cart_item['quantity'], $cart_item['variation_id'], $cart_item['variation'], $cart_item['cart_item_data'] );
        }

        // Send success response
        wp_send_json_success( array( 'message' => __( 'Cart duplicated successfully!', 'ship-multiple-addresses' ) ) );
    }
}

new SMA_Cart();