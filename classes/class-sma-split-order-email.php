<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class SMA_Split_Order_Email
 * Custom email notification for split orders.
 */
class SMA_Split_Order_Email extends WC_Email {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->id             = 'sma_split_order_email';
        $this->title          = __( 'Split Order Notification', 'ship-multiple-addresses' );
        $this->description    = __( 'Notification for split sub-orders created during checkout.', 'ship-multiple-addresses' );

        $this->template_html  = 'emails/split-order-notification.php';
        $this->template_plain = 'emails/plain/split-order-notification.php';
        $this->placeholders   = array(
            '{order_id}'   => '',
            '{order_link}' => '',
        );

        // Triggers for sending this email
        add_action( 'woocommerce_order_status_processing', array( $this, 'trigger' ), 10, 2 );

        // Load the parent class constructor
        parent::__construct();

        $this->template_base = SMA_PLUGIN_DIR . 'templates/';
    }

    /**
     * Trigger the email notification.
     *
     * @param int      $order_id
     * @param WC_Order $order
     */
    public function trigger( $order_id, $order = false ) {
        if ( ! SMA_Settings::is_email_enabled() ) {
            return; // Exit if emails are disabled in settings.
        }

        if ( ! $order ) {
            return;
        }

        $this->object = $order;

        $this->placeholders['{order_id}']   = $order->get_id();
        $this->placeholders['{order_link}'] = esc_url( $order->get_view_order_url() );

        // Send the email
        $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
    }

    /**
     * Get the HTML content for the email.
     */
    public function get_content_html() {
        return wc_get_template_html(
            $this->template_html,
            array(
                'order'        => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
            ),
            '',
            $this->template_base
        );
    }

    /**
     * Get the plain text content for the email.
     */
    public function get_content_plain() {
        return wc_get_template_html(
            $this->template_plain,
            array(
                'order'        => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
            ),
            '',
            $this->template_base
        );
    }
}
