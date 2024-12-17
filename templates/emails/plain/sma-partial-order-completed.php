<?php
/**
 * Partial Order Completed Email (Plain Text)
 */
$order = $args['order'];
?>
<?php echo $email_heading; ?>

<?php esc_html_e( 'A portion of your order has been completed and shipped.', 'ship-multiple-addresses' ); ?>
Order ID: <?php echo $order->get_id(); ?>
View Order: <?php echo esc_url( $order->get_view_order_url() ); ?>