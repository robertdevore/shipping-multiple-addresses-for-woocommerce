<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class SMA_Partial_Order_Email
 * Sends notifications for partial order completions (sub-orders).
 */
class SMA_Partial_Order_Email extends WC_Email {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id             = 'sma_partial_order_completed';
        $this->title          = __( 'Partial Order Completed', 'ship-multiple-addresses' );
        $this->description    = __( 'Sent when a sub-order is marked as completed.', 'ship-multiple-addresses' );
        $this->template_html  = 'emails/sma-partial-order-completed.php';
        $this->template_plain = 'emails/plain/sma-partial-order-completed.php';
        $this->placeholders   = array(
            '{order_id}'       => '',
            '{order_status}'   => '',
            '{order_link}'     => '',
        );

        add_action( 'woocommerce_order_status_completed', array( $this, 'trigger' ), 10, 2 );

        parent::__construct();

        $this->template_base = SMA_PLUGIN_DIR . 'templates/';
    }

    /**
     * Trigger the email.
     *
     * @param int      $order_id
     * @param WC_Order $order
     */
    public function trigger( $order_id, $order = false ) {
        if ( ! $order || ! $this->is_enabled() ) {
            return;
        }

        if ( $order->get_parent_id() ) { // Only for sub-orders
            $this->object = $order;
            $this->placeholders['{order_id}']     = $order->get_id();
            $this->placeholders['{order_status}'] = $order->get_status();
            $this->placeholders['{order_link}']   = $order->get_view_order_url();

            $this->send( $order->get_billing_email(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }
    }

    /**
     * Get content HTML.
     */
    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
        ), '', $this->template_base );
    }

    /**
     * Get content plain.
     */
    public function get_content_plain() {
        return wc_get_template_html( $this->template_plain, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
        ), '', $this->template_base );
    }
}