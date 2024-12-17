<?php
/**
 * Split Order Notification Email (Plain Text)
 *
 * @package Ship_Multiple_Addresses
 */

defined( 'ABSPATH' ) || exit;

$order = $args['order'];

echo $email_heading . "\n\n";
echo __( 'Your order has been split into multiple shipments. Below are the details for this split order.', 'ship-multiple-addresses' ) . "\n\n";

echo __( 'Order ID:', 'ship-multiple-addresses' ) . ' ' . $order->get_id() . "\n";
echo __( 'View your order here:', 'ship-multiple-addresses' ) . ' ' . $order->get_view_order_url() . "\n";

echo "\n" . __( 'Thank you for shopping with us.', 'ship-multiple-addresses' ) . "\n";
