<?php
class SMA_Checkout {

    public function __construct() {
        add_action( 'woocommerce_after_order_notes', array( $this, 'add_delivery_notes_and_date_picker' ) );
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_delivery_fields' ) );
        add_action( 'woocommerce_checkout_create_order', array( $this, 'split_order_by_addresses' ), 10, 2 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_delivery_fields' ) );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_delivery_fields_admin' ) );
        add_action( 'woocommerce_before_checkout_form', array( $this, 'show_custom_checkout_notification' ) );
        add_action( 'woocommerce_after_checkout_form', array( $this, 'update_checkout_button_text' ) );
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
    public function filter_excluded_products( $saved_addresses, $cart_items ) {
        $excluded_products = get_option( 'sma_excluded_products', array() );
        $excluded_categories = get_option( 'sma_excluded_categories', array() );
    
        foreach ( $cart_items as $cart_key => $item ) {
            $product_id = $item['product_id'];
            $product = wc_get_product( $product_id );
    
            // Check if the product ID is in the exclusion list
            if ( in_array( $product_id, $excluded_products ) ) {
                wc_add_notice( sprintf( __( 'Product %s is not eligible for multiple shipping addresses.', 'ship-multiple-addresses' ), $product->get_name() ), 'error' );
                unset( WC()->cart->cart_contents[ $cart_key ] );
                continue;
            }
    
            // Check if the product category is in the exclusion list
            $product_categories = $product->get_category_ids();
            if ( array_intersect( $product_categories, $excluded_categories ) ) {
                wc_add_notice( sprintf( __( 'Product %s in excluded category cannot be shipped to multiple addresses.', 'ship-multiple-addresses' ), $product->get_name() ), 'error' );
                unset( WC()->cart->cart_contents[ $cart_key ] );
            }
        }
    
        return WC()->cart->cart_contents;
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
    
        echo '<div class="woocommerce-info sma-custom-notification">' . esc_html( $custom_text ) . '</div>';
    }
    
    /**
     * Display UI for assigning products to multiple addresses.
     */
    public function show_multiple_addresses_ui() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $saved_addresses = get_user_meta( get_current_user_id(), 'sma_saved_addresses', true );
        $cart_items      = WC()->cart->get_cart();

        if ( empty( $saved_addresses ) ) {
            echo '<p>' . esc_html__( 'Please add shipping addresses first.', 'ship-multiple-addresses' ) . '</p>';
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
                                <select name="sma_addresses[<?php echo esc_attr( $cart_key ); ?>]">
                                    <?php foreach ( $saved_addresses as $key => $address ) : ?>
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
        $assigned_addresses = isset( $_POST['sma_addresses'] ) ? $_POST['sma_addresses'] : array();

        foreach ( WC()->cart->get_cart() as $cart_key => $item ) {
            if ( empty( $assigned_addresses[ $cart_key ] ) ) {
                wc_add_notice( __( 'Please assign an address to all products.', 'ship-multiple-addresses' ), 'error' );
                break;
            }
        }
    }

    /**
     * Split the order into multiple child orders based on assigned addresses.
     */
    public function split_order_by_addresses( $order, $data ) {
        $assigned_addresses = isset( $_POST['sma_addresses'] ) ? $_POST['sma_addresses'] : array();

        if ( empty( $assigned_addresses ) ) {
            return;
        }

        $saved_addresses = get_user_meta( get_current_user_id(), 'sma_saved_addresses', true );
        $cart_items      = WC()->cart->get_cart();
        $orders          = array();

        // Group items by address
        foreach ( $cart_items as $cart_key => $item ) {
            $address_key = $assigned_addresses[ $cart_key ];

            if ( ! isset( $orders[ $address_key ] ) ) {
                $orders[ $address_key ] = array();
            }

            $orders[ $address_key ][] = $item;
        }

        // Create sub-orders for each address
        foreach ( $orders as $address_key => $items ) {
            $address = $saved_addresses[ $address_key ];

            // Create a new sub-order
            $sub_order = wc_create_order( array( 'customer_id' => $order->get_customer_id() ) );

            foreach ( $items as $item ) {
                $product = wc_get_product( $item['product_id'] );
                $sub_order->add_product( $product, $item['quantity'] );
            }

            // Set shipping address
            $shipping_address = array(
                'first_name' => $address['name'],
                'address_1'  => $address['address_1'],
                'city'       => $address['city'],
                'state'      => $address['state'],
                'postcode'   => $address['postcode'],
            );
            $sub_order->set_address( $shipping_address, 'shipping' );

            // Calculate shipping costs
            $this->calculate_shipping( $sub_order, $shipping_address );

            $sub_order->calculate_totals();
            $sub_order->save();

            // Add note to main order
            $order->add_order_note( sprintf(
                __( 'Sub-order #%s created for address: %s', 'ship-multiple-addresses' ),
                $sub_order->get_id(),
                $address['address_1']
            ) );

            // Add note to sub-order for reference
            $sub_order->add_order_note( __( 'This order was split from a parent order.', 'ship-multiple-addresses' ) );

            // Trigger the custom email for the sub-order
            do_action( 'woocommerce_order_status_processing', $sub_order->get_id(), $sub_order );
        }

        // Mark the parent order
        $order->add_order_note( __( 'Order split into multiple shipments.', 'ship-multiple-addresses' ) );
    }

    /**
     * Calculate shipping costs for a sub-order based on the address.
     *
     * @param WC_Order $order
     * @param array    $shipping_address
     */
    private function calculate_shipping( $order, $shipping_address ) {
        $packages = [];

        // Prepare shipping package
        $packages[] = array(
            'contents'    => $order->get_items(),
            'destination' => array(
                'country'   => 'US', // Adjust for multi-country support.
                'state'     => $shipping_address['state'],
                'postcode'  => $shipping_address['postcode'],
                'city'      => $shipping_address['city'],
                'address'   => $shipping_address['address_1'],
            ),
        );

        // Calculate shipping costs
        WC()->shipping()->calculate_shipping( $packages );

        // Add the first available shipping method
        $shipping_methods = WC()->shipping()->get_packages()[0]['rates'];
        if ( ! empty( $shipping_methods ) ) {
            $method = current( $shipping_methods ); // Get the first method
            $order->add_shipping( $method );
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
