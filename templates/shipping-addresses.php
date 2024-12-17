<?php
/**
 * Template for Shipping Addresses Management
 *
 * This template is used to display and manage multiple shipping addresses.
 * Override by copying it to: theme/multi-shipping/shipping-addresses.php
 *
 * @package Ship_Multiple_Addresses
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$saved_addresses = get_user_meta( $current_user->ID, 'sma_saved_addresses', true );

$saved_addresses = ! empty( $saved_addresses ) ? $saved_addresses : array();
?>

<div class="sma-shipping-addresses">
    <h2><?php esc_html_e( 'Manage Shipping Addresses', 'ship-multiple-addresses' ); ?></h2>

    <?php if ( ! empty( $saved_addresses ) ) : ?>
        <ul class="sma-address-list">
            <?php foreach ( $saved_addresses as $key => $address ) : ?>
                <li class="sma-address-item">
                    <p>
                        <?php echo esc_html( $address['name'] ); ?>, 
                        <?php echo esc_html( $address['address_1'] ); ?>,
                        <?php echo esc_html( $address['city'] ); ?>, 
                        <?php echo esc_html( $address['state'] ); ?>,
                        <?php echo esc_html( $address['postcode'] ); ?>
                    </p>
                    <button class="sma-delete-address" data-address-key="<?php echo esc_attr( $key ); ?>">
                        <?php esc_html_e( 'Delete', 'ship-multiple-addresses' ); ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else : ?>
        <p><?php esc_html_e( 'No saved addresses found.', 'ship-multiple-addresses' ); ?></p>
    <?php endif; ?>

    <form id="sma-add-address" method="post" class="sma-address-form">
        <h3><?php esc_html_e( 'Add New Shipping Address', 'ship-multiple-addresses' ); ?></h3>
        
        <p>
            <label for="sma_name"><?php esc_html_e( 'Full Name', 'ship-multiple-addresses' ); ?></label>
            <input type="text" name="sma_name" id="sma_name" required>
        </p>

        <p>
            <label for="sma_address_1"><?php esc_html_e( 'Address', 'ship-multiple-addresses' ); ?></label>
            <input type="text" name="sma_address_1" id="sma_address_1" required>
        </p>

        <p>
            <label for="sma_city"><?php esc_html_e( 'City', 'ship-multiple-addresses' ); ?></label>
            <input type="text" name="sma_city" id="sma_city" required>
        </p>

        <p>
            <label for="sma_state"><?php esc_html_e( 'State', 'ship-multiple-addresses' ); ?></label>
            <input type="text" name="sma_state" id="sma_state" required>
        </p>

        <p>
            <label for="sma_postcode"><?php esc_html_e( 'Postcode', 'ship-multiple-addresses' ); ?></label>
            <input type="text" name="sma_postcode" id="sma_postcode" required>
        </p>

        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Address', 'ship-multiple-addresses' ); ?></button>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Handle address deletion
        $('.sma-delete-address').on('click', function(e) {
            e.preventDefault();

            const addressKey = $(this).data('address-key');
            const data = {
                action: 'sma_delete_address',
                key: addressKey,
                security: sma_ajax_object.security,
            };

            $.post(sma_ajax_object.ajax_url, data, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            });
        });
    });
</script>
