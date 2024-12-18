<?php
class SMA_Checkout {

    public function __construct() {
        add_action( 'woocommerce_after_order_notes', array( $this, 'add_delivery_notes_and_date_picker' ) );
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_delivery_fields' ) );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'split_order_by_addresses' ), 10, 2 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_delivery_fields' ) );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_delivery_fields_admin' ) );
        add_action( 'woocommerce_before_checkout_form', array( $this, 'show_custom_checkout_notification' ) );
        add_action( 'woocommerce_before_checkout_form', array( $this, 'show_multiple_addresses_ui' ), 10 );
        add_action( 'woocommerce_after_checkout_form', array( $this, 'update_checkout_button_text' ) );
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_addresses_assignment' ) );
        add_filter( 'woocommerce_checkout_process', array( $this, 'filter_excluded_products' ) );
    }

    /**
     * Summary of filter_excluded_products
     * 
     * @param mixed $saved_addresses
     * @param mixed $cart_items
     * 
     * @return mixed
     */
    public function filter_excluded_products() {
        $excluded_products  = get_option( 'sma_excluded_products', array() );
        $excluded_categories = get_option( 'sma_excluded_categories', array() );
    
        foreach ( WC()->cart->get_cart() as $cart_key => $item ) {
            $product_id = $item['product_id'];
            $product    = wc_get_product( $product_id );
    
            // Check for excluded product
            if ( in_array( $product_id, $excluded_products, true ) ) {
                wc_add_notice( sprintf( __( 'Product %s is not eligible for multiple shipping addresses.', 'ship-multiple-addresses' ), $product->get_name() ), 'error' );
                unset( WC()->cart->cart_contents[ $cart_key ] );
                continue;
            }
    
            // Check for excluded category
            $product_categories = $product->get_category_ids();
            if ( array_intersect( $product_categories, $excluded_categories ) ) {
                wc_add_notice( sprintf( __( 'Product %s is in an excluded category and cannot be shipped to multiple addresses.', 'ship-multiple-addresses' ), $product->get_name() ), 'error' );
                unset( WC()->cart->cart_contents[ $cart_key ] );
            }
        }
    }

    /**
     * Add Delivery Notes and Date Picker fields at checkout.
     */
    public function add_delivery_notes_and_date_picker( $checkout ) {
        echo '<div id="sma-delivery-fields"><h3>' . __( 'Delivery Options', 'ship-multiple-addresses' ) . '</h3>';

        // Delivery Notes Field
        woocommerce_form_field( 'sma_delivery_notes', array(
            'type'        => 'textarea',
            'class'       => array( 'sma-delivery-notes form-row-wide' ),
            'label'       => __( 'Delivery Notes', 'ship-multiple-addresses' ),
            'placeholder' => __( 'Enter any delivery instructions here.', 'ship-multiple-addresses' ),
            'required'    => false,
        ), $checkout->get_value( 'sma_delivery_notes' ) );

        // Delivery Date Picker
        woocommerce_form_field( 'sma_delivery_date', array(
            'type'        => 'text',
            'class'       => array( 'sma-delivery-date sma-field sma-date-picker form-row-wide' ),
            'label'       => __( 'Preferred Delivery Date', 'ship-multiple-addresses' ),
            'placeholder' => __( 'Select a delivery date', 'ship-multiple-addresses' ),
            'required'    => true,
        ), $checkout->get_value( 'sma_delivery_date' ) );

        echo '</div>';
    }

    /**
     * Validate delivery fields during checkout.
     */
    public function validate_delivery_fields() {
        if ( empty( $_POST['sma_delivery_date'] ) ) {
            wc_add_notice( __( 'Please select a preferred delivery date.', 'ship-multiple-addresses' ), 'error' );
        }
    }

    /**
     * Save delivery fields as order meta.
     */
    public function save_delivery_fields( $order_id ) {
        if ( ! empty( $_POST['sma_delivery_notes'] ) ) {
            update_post_meta( $order_id, '_sma_delivery_notes', sanitize_textarea_field( $_POST['sma_delivery_notes'] ) );
        }

        if ( ! empty( $_POST['sma_delivery_date'] ) ) {
            error_log( 'Saving delivery date: ' . sanitize_text_field( $_POST['sma_delivery_date'] ) );
            update_post_meta( $order_id, '_sma_delivery_date', sanitize_text_field( $_POST['sma_delivery_date'] ) );
        }
    }

    /**
     * Display delivery fields in the WooCommerce admin order details.
     */
    public function display_delivery_fields_admin( $order ) {
        $delivery_notes = get_post_meta( $order->get_id(), '_sma_delivery_notes', true );
        $delivery_date  = get_post_meta( $order->get_id(), '_sma_delivery_date', true );

        if ( $delivery_notes ) {
            echo '<p><strong>' . __( 'Delivery Notes:', 'ship-multiple-addresses' ) . '</strong> ' . esc_html( $delivery_notes ) . '</p>';
        }

        if ( $delivery_date ) {
            echo '<p><strong>' . __( 'Preferred Delivery Date:', 'ship-multiple-addresses' ) . '</strong> ' . esc_html( $delivery_date ) . '</p>';
        }
    }
    
    public function show_custom_checkout_notification() {
        $custom_text = get_option( 'sma_checkout_notification', __( 'Ship items to multiple addresses.', 'ship-multiple-addresses' ) );
    
        echo '<div class="woocommerce-info sma-custom-notification">';
        echo esc_html( $custom_text );
        echo ' <button type="button" id="sma-show-address-ui" class="button">' . esc_html__( 'Assign Addresses', 'ship-multiple-addresses' ) . '</button>';
        echo '</div>';
    }    

    /**
     * Display UI for assigning products to multiple addresses.
     */
    public function show_multiple_addresses_ui() {
        if ( ! is_user_logged_in() ) {
            echo '<p>' . esc_html__( 'You must be logged in to assign products to multiple addresses.', 'ship-multiple-addresses' ) . '</p>';
            return;
        }
    
        $saved_addresses = get_user_meta( get_current_user_id(), 'sma_saved_addresses', true );
    
        // Ensure saved addresses have unique keys
        $saved_addresses_with_keys = [];
        foreach ( $saved_addresses as $key => $address ) {
            $unique_key = $key ?: uniqid();
            $saved_addresses_with_keys[ $unique_key ] = $address;
        }
    
        // Save updated addresses
        update_user_meta( get_current_user_id(), 'sma_saved_addresses', $saved_addresses_with_keys );
    
        $cart_items = WC()->cart->get_cart();
    
        if ( empty( $saved_addresses_with_keys ) ) {
            echo '<p>' . esc_html__( 'Please add shipping addresses first in your account.', 'ship-multiple-addresses' ) . '</p>';
            return;
        }
    
        ?>
        <div id="sma-multiple-addresses-section">
            <h3><?php esc_html_e( 'Assign Products to Addresses', 'ship-multiple-addresses' ); ?></h3>
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Product', 'ship-multiple-addresses' ); ?></th>
                        <th><?php esc_html_e( 'Quantity', 'ship-multiple-addresses' ); ?></th>
                        <th><?php esc_html_e( 'Shipping Address', 'ship-multiple-addresses' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $cart_items as $cart_key => $cart_item ) : ?>
                        <tr>
                            <td><?php echo esc_html( $cart_item['data']->get_name() ); ?></td>
                            <td><?php echo esc_html( $cart_item['quantity'] ); ?></td>
                            <td>
                                <select name="sma_addresses[<?php echo esc_attr( $cart_key ); ?>]" data-cart-key="<?php echo esc_attr( $cart_key ); ?>">
                                    <option value=""><?php esc_html_e( 'Select Address', 'ship-multiple-addresses' ); ?></option>
                                    <?php foreach ( $saved_addresses_with_keys as $key => $address ) : ?>
                                        <option value="<?php echo esc_attr( $key ); ?>">
                                            <?php echo esc_html( $address['name'] . ' - ' . $address['city'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Validate that addresses have been assigned to all products.
     */
    public function validate_addresses_assignment() {
        // Log raw POST data for debugging
        error_log( 'Raw POST data: ' . print_r( $_POST, true ) );
    
        // Check if the sma_addresses field is present
        if ( isset( $_POST['sma_addresses'] ) ) {
            // Decode the JSON input
            $assigned_addresses = json_decode( stripslashes( html_entity_decode( $_POST['sma_addresses'] ) ), true );
    
            // Log the decoded data
            error_log( 'Decoded assigned addresses: ' . print_r( $assigned_addresses, true ) );
    
            // Validate that the decoded data is an array and not empty
            if ( ! is_array( $assigned_addresses ) || empty( $assigned_addresses ) ) {
                wc_add_notice( __( 'Please assign an address to all products.', 'ship-multiple-addresses' ), 'error' );
                return;
            }
    
            // Ensure every cart item has an assigned address
            foreach ( WC()->cart->get_cart() as $cart_key => $item ) {
                if ( empty( $assigned_addresses[ $cart_key ] ) ) {
                    wc_add_notice( __( 'Please assign an address to all products.', 'ship-multiple-addresses' ), 'error' );
                    return;
                }
            }
        } else {
            wc_add_notice( __( 'Please assign an address to all products.', 'ship-multiple-addresses' ), 'error' );
        }
    }

    /**
     * Split the order into multiple child orders based on assigned addresses.
     */
    public function split_order_by_addresses( $order, $data ) {
        $assigned_addresses = isset( $_POST['sma_addresses'] )
            ? json_decode( stripslashes( html_entity_decode( $_POST['sma_addresses'] ) ), true )
            : [];
    
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            error_log( 'JSON decoding error: ' . json_last_error_msg() );
            wc_add_notice( __( 'Invalid address data received.', 'ship-multiple-addresses' ), 'error' );
            return;
        }
    
        $saved_addresses = get_user_meta( get_current_user_id(), 'sma_saved_addresses', true );
    
        if ( ! is_array( $saved_addresses ) || empty( $saved_addresses ) ) {
            error_log( 'No saved addresses found.' );
            wc_add_notice( __( 'No saved addresses available.', 'ship-multiple-addresses' ), 'error' );
            return;
        }
    
        // Rebuild saved addresses with keys
        $saved_addresses_with_keys = [];
        foreach ( $saved_addresses as $key => $address ) {
            $unique_key = $key ?: uniqid();
            $saved_addresses_with_keys[ $unique_key ] = $address;
        }
    
        $orders = [];
        foreach ( WC()->cart->get_cart() as $cart_key => $item ) {
            $product = wc_get_product( $item['product_id'] );
            if ( ! $product || ! isset( $assigned_addresses[ $cart_key ] ) ) {
                error_log( "Invalid product or address key for cart item: $cart_key" );
                continue;
            }
        
            $address_key = $assigned_addresses[ $cart_key ];
        
            if ( ! isset( $orders[ $address_key ] ) ) {
                $orders[ $address_key ] = [];
            }
        
            $orders[ $address_key ][] = $item;
        }

        foreach ( $orders as $address_key => $items ) {
            $address = $saved_addresses_with_keys[ $address_key ];
            $sub_order = wc_create_order( [ 'customer_id' => $order->get_customer_id() ] );
    
            if ( ! $sub_order ) {
                error_log( "Failed to create sub-order for address key: $address_key" );
                continue;
            }
    
            foreach ( $items as $item ) {
                $product = wc_get_product( $item['product_id'] );
                if ( $product ) {
                    $sub_order->add_product( $product, $item['quantity'] );
                }
            }
    
            $shipping_address = [
                'first_name' => $address['name'],
                'address_1'  => $address['address_1'],
                'city'       => $address['city'],
                'state'      => $address['state'],
                'postcode'   => $address['postcode'],
            ];
            $sub_order->set_address( $shipping_address, 'shipping' );
    
            $this->calculate_shipping( $sub_order, $shipping_address );
    
            $sub_order->calculate_totals();
            $sub_order->save();
    
            $order->add_order_note( sprintf( __( 'Sub-order #%s created for address: %s', 'ship-multiple-addresses' ), $sub_order->get_id(), $address['address_1'] ) );
        }
    
        $order->add_order_note( __( 'Order split into multiple shipments.', 'ship-multiple-addresses' ) );
    }

    /**
     * Calculate shipping costs for a sub-order based on the address.
     *
     * @param WC_Order $order
     * @param array    $shipping_address
     */
    private function calculate_shipping( $order, $shipping_address ) {
        // Prepare the package contents
        $contents = [];
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            if ( $product && $product->needs_shipping() ) { // Ensure product is valid and needs shipping
                $contents[] = [
                    'product_id' => $item->get_product_id(),
                    'quantity'   => $item->get_quantity(),
                    'data'       => $product,
                ];
            }
        }

        if ( empty( $contents ) ) {
            error_log( 'No valid shippable items found for sub-order.' );
            return;
        }

        // Build the package
        $packages = [
            [
                'contents'    => $contents,
                'destination' => [
                    'country'   => 'US',
                    'state'     => $shipping_address['state'],
                    'postcode'  => $shipping_address['postcode'],
                    'city'      => $shipping_address['city'],
                    'address'   => $shipping_address['address_1'],
                ],
            ],
        ];

        $shipping = WC()->shipping();
        $shipping->calculate_shipping( $packages );

        // Apply the first available shipping method
        $available_methods = $packages[0]['rates'] ?? [];
        if ( ! empty( $available_methods ) ) {
            $method = reset( $available_methods );
            $order->add_shipping( $method );
        } else {
            error_log( 'No available shipping methods for package.' );
        }
    }

    public function update_checkout_button_text() {
        $button_text = get_option( 'sma_button_text', __( 'Set Multiple Addresses', 'ship-multiple-addresses' ) );
    
        echo '<script>
            jQuery(document).ready(function($) {
                $("#sma-multi-address-button").text("' . esc_js( $button_text ) . '");
            });
        </script>';
    }

}

new SMA_Checkout();
