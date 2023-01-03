<?php

class MVX_Cointopay_Gateway {

    public $plugin_url;
    public $plugin_path;
    public $version;
    public $token;
    public $library;
    public $shortcode;
    public $admin;
    public $frontend;
    public $template;
    public $settings;
    public $dc_wp_fields;
    public $payment_admin_settings;

    public function __construct($file) {

        $this->file = $file;
        $this->plugin_url = trailingslashit(plugins_url('', $plugin = $file));
        $this->plugin_path = trailingslashit(dirname($file));
        $this->token = 'mvx-cointopay-gateway';
        $this->version = '1.2.6';

        add_action('init', array(&$this, 'init'), 0);
        $mvx_cointopay_settings = get_option('woocommerce_mvx-cointopay-payments_settings');
        $this->payment_admin_settings = get_option('mvx_payment_settings_name');
		
        if (isset($mvx_cointopay_settings['enabled']) && $mvx_cointopay_settings['enabled'] == 'yes' && MVX_Cointopay_Gateway_Dependencies::mvx_active_check()) {
            add_action('woocommerce_order_status_cancelled', array(&$this, 'woocommerce_order_status_cancelled'));
        }
    }

    /**
     * initilize plugin on WP init
     */
    function init() {

        if (!is_admin() || defined('DOING_AJAX')) {
            $this->load_class('frontend');
            $this->frontend = new MVX_Cointopay_Gateway_Frontend();
        }
        if (class_exists('WC_Payment_Gateway')) {
            $this->load_class('payment-method');
            add_filter('woocommerce_payment_gateways', array($this, 'add_cointopay_gateway'));
        }
    }

    /**
     * Add WooCommerce cointopay gateway
     * @param array $methods
     * @return array payment methods
     */
    public function add_cointopay_gateway($methods) {
        $methods[] = 'MVX_Cointopay_Gateway_Payment_Method';
        return $methods;
    }

    public function woocommerce_order_status_cancelled($order_id) {
        global $wpdb;
        if (!$order = wc_get_order($order_id)) {
            return;
        }
        if ('mvx-cointopay-payments' == $order->get_payment_method()) {
            $vendor_orders_in_order = get_mvx_vendor_orders(array('order_id' => $order_id));
            if (!empty($vendor_orders_in_order)) {
                $commission_ids = wp_list_pluck($vendor_orders_in_order, 'commission_id');
                if ($commission_ids && is_array($commission_ids)) {
                    foreach ($commission_ids as $commission_id) {
                        wp_delete_post($commission_id);
                    }
                }
            }
            $wpdb->delete($wpdb->prefix . 'mvx_vendor_orders', array('order_id' => $order_id), array('%d'));
            delete_post_meta($order_id, '_commissions_processed');
        }
    }

    public function load_class($class_name = '') {
        if ('' != $class_name && '' != $this->token) {
            require_once ('class-' . esc_attr($this->token) . '-' . esc_attr($class_name) . '.php');
        } // End If Statement
    }

    // End load_class()

    /** Cache Helpers ******************************************************** */

    /**
     * Sets a constant preventing some caching plugins from caching a page. Used on dynamic pages
     *
     * @access public
     * @return void
     */
    function nocache() {
        if (!defined('DONOTCACHEPAGE'))
            define("DONOTCACHEPAGE", "true");
        // WP Super Cache constant
    }

}
