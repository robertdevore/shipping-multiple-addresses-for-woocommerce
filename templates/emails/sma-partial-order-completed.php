<?php
/**
 * Partial Order Completed Email (HTML)
 */
$order = $args['order'];
?>
<h2><?php echo esc_html( $email_heading ); ?></h2>
<p><?php esc_html_e( 'A portion of your order has been completed and shipped.', 'ship-multiple-addresses' ); ?></p>
<p><?php printf( __( 'Order ID: %s', 'ship-multiple-addresses' ), $order->get_id() ); ?></p>
<p><a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"><?php esc_html_e( 'View Order', 'ship-multiple-addresses' ); ?></a></p>