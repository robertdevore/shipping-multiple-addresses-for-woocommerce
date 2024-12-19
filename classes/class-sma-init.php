<?php
class SMA_Init {

    public function __construct() {
        // Include classes.
        require_once SMA_PLUGIN_DIR . 'classes/class-sma-address.php';
        require_once SMA_PLUGIN_DIR . 'classes/class-sma-cart.php';
        require_once SMA_PLUGIN_DIR . 'classes/class-sma-checkout.php';
        require_once SMA_PLUGIN_DIR . 'classes/class-sma-settings.php';

        // Load text domain for translations.
        load_plugin_textdomain( 'ship-multiple-addresses', false, basename( dirname( __FILE__, 2 ) ) . '/languages' );

        // Enqueue scripts and styles.
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        // Enqueue scripts and styles.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Add address management to My Account.
        add_filter( 'woocommerce_account_menu_items', array( $this, 'add_address_tab' ) );
        add_action( 'woocommerce_account_sma-manage-addresses_endpoint', array( $this, 'display_manage_addresses_content' ) );
        add_action( 'init', array( $this, 'register_manage_addresses_endpoint' ) );
    }

    /**
     * Enqueue frontend scripts and styles.
     * 
     * @since  1.0.0
     * @return void
     */
    public function enqueue_scripts() {
        // Add the stylesheets.
        wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css' );
        wp_enqueue_style( 'sma-frontend', SMA_PLUGIN_URL . 'assets/css/sma-frontend.css', [], SMA_PLUGIN_VERSION );
        // Add the scripts.
        wp_enqueue_script( 'jquery-ui-datepicker' );
        wp_enqueue_script( 'sma-frontend', SMA_PLUGIN_URL . 'assets/js/sma-frontend.js', array( 'jquery' ), SMA_PLUGIN_VERSION, true );
        wp_localize_script( 'sma-frontend', 'sma_ajax_object', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'security' => wp_create_nonce( 'sma_nonce' ),
        ) );
    }

    /**
     * Enqueue backend scripts and styles
     * 
     * @since  1.0.0
     * @return void
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_style( 'sma-admin-css', SMA_PLUGIN_URL . 'assets/css/sma-admin.css', array(), SMA_PLUGIN_VERSION );
    }

    /**
     * Register a new endpoint for managing addresses in My Account.
     * 
     * @since  1.0.0
     * @return void
     */
    public function register_manage_addresses_endpoint() {
        add_rewrite_endpoint( 'sma-manage-addresses', EP_ROOT | EP_PAGES );
    }

    /**
     * Add a custom tab for multiple addresses in My Account navigation.
     * 
     * @since  1.0.0
     * @return mixed
     */
    public function add_address_tab( $items ) {
        $items['sma-manage-addresses'] = __( 'Manage Addresses', 'ship-multiple-addresses' );
        return $items;
    }

    /**
     * Display the Manage Addresses content.
     * 
     * @since  1.0.0
     * @return void
     */
    public function display_manage_addresses_content() {
        wc_get_template( 'shipping-addresses.php', array(), '', SMA_PLUGIN_DIR . 'templates/' );
    }

}
