<?php
/**
 * Split Order Notification Email (HTML)
 *
 * @package Ship_Multiple_Addresses
 */

defined( 'ABSPATH' ) || exit;

$order = $args['order'];
?>

<h2><?php echo esc_html( $email_heading ); ?></h2>

<p><?php esc_html_e( 'Your order has been split into multiple shipments. Below are the details for this split order.', 'ship-multiple-addresses' ); ?></p>

<h3><?php esc_html_e( 'Order Details', 'ship-multiple-addresses' ); ?></h3>
<p>
    <strong><?php esc_html_e( 'Order ID:', 'ship-multiple-addresses' ); ?></strong> <?php echo esc_html( $order->get_id() ); ?><br>
    <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"><?php esc_html_e( 'View your order here.', 'ship-multiple-addresses' ); ?></a>
</p>

<?php do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<p><?php esc_html_e( 'Thank you for shopping with us.', 'ship-multiple-addresses' ); ?></p>
