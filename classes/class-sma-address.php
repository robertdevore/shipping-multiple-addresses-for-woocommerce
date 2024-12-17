<?php
class SMA_Address {

    public function __construct() {
        // Hook for saving a new address
        add_action( 'wp_ajax_sma_save_address', array( $this, 'save_address' ) );
        add_action( 'wp_ajax_sma_delete_address', array( $this, 'delete_address' ) );
    }

    /**
     * Save a new shipping address.
     */
    public function save_address() {
        check_ajax_referer( 'sma_nonce', 'security' );

        // Verify user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to save an address.', 'ship-multiple-addresses' ) ) );
        }

        // Sanitize POST data
        $name      = sanitize_text_field( $_POST['sma_name'] );
        $address_1 = sanitize_text_field( $_POST['sma_address_1'] );
        $city      = sanitize_text_field( $_POST['sma_city'] );
        $state     = sanitize_text_field( $_POST['sma_state'] );
        $postcode  = sanitize_text_field( $_POST['sma_postcode'] );

        // Validate required fields
        if ( empty( $name ) || empty( $address_1 ) || empty( $city ) || empty( $state ) || empty( $postcode ) ) {
            wp_send_json_error( array( 'message' => __( 'All fields are required.', 'ship-multiple-addresses' ) ) );
        }

        // Get current user
        $user_id         = get_current_user_id();
        $saved_addresses = get_user_meta( $user_id, 'sma_saved_addresses', true );

        if ( ! is_array( $saved_addresses ) ) {
            $saved_addresses = array();
        }

        // Add new address
        $saved_addresses[] = array(
            'name'      => $name,
            'address_1' => $address_1,
            'city'      => $city,
            'state'     => $state,
            'postcode'  => $postcode,
        );

        // Save addresses back to user meta
        update_user_meta( $user_id, 'sma_saved_addresses', $saved_addresses );

        wp_send_json_success( array( 'message' => __( 'Address saved successfully!', 'ship-multiple-addresses' ) ) );
    }

    /**
     * Delete a saved address by its key.
     */
    public function delete_address() {
        check_ajax_referer( 'sma_nonce', 'security' );

        // Verify user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'You must be logged in to delete an address.', 'ship-multiple-addresses' ) ) );
        }

        $key = isset( $_POST['key'] ) ? intval( $_POST['key'] ) : null;

        // Get current user
        $user_id         = get_current_user_id();
        $saved_addresses = get_user_meta( $user_id, 'sma_saved_addresses', true );

        if ( isset( $saved_addresses[ $key ] ) ) {
            unset( $saved_addresses[ $key ] ); // Remove address
            update_user_meta( $user_id, 'sma_saved_addresses', array_values( $saved_addresses ) ); // Re-index
            wp_send_json_success( array( 'message' => __( 'Address deleted successfully.', 'ship-multiple-addresses' ) ) );
        }

        wp_send_json_error( array( 'message' => __( 'Invalid address key.', 'ship-multiple-addresses' ) ) );
    }
}

new SMA_Address();
