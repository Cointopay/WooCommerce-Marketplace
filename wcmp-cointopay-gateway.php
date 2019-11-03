<?php

/**
 * Plugin Name: WCMp Cointopay Gateway
 * Plugin URI: https://therightsw.com/
 * Description: WCMp Cointopay Gateway is a payment gateway for woocommerce shopping plateform also compatible with WC Marketplace.
 * Author: Goshila Sadaf
 * Version: 1.0
 * Author URI: https://therightsw.com/
 *
 * Text Domain: wcmp-cointopay-gateway
 * Domain Path: /languages/
 */
if (!defined('ABSPATH')) {
    // Exit if accessed directly
    exit;
}
if (!class_exists('WCMP_Cointopay_Gateway_Dependencies')) {
    require_once 'includes/class-wcmp-cointopay-gateway-dependencies.php';
}
require_once 'includes/wcmp-cointopay-gateway-core-functions.php';
require_once 'wcmp-cointopay-gateway-config.php';

if (!defined('WCMP_COINTOPAY_GATEWAY_PLUGIN_TOKEN')) {
    exit;
}
if (!defined('WCMP_COINTOPAY_GATEWAY_TEXT_DOMAIN')) {
    exit;
}

if(!WCMP_Cointopay_Gateway_Dependencies::woocommerce_active_check()){
    add_action('admin_notices', 'woocommerce_inactive_notice');
}
add_filter('automatic_payment_method', 'admin_cointopay_mode_i', 10);
/**
     * Add payment gatway to woocommerce
     * @param array $arg
     * @return array
     */
   function admin_cointopay_mode_i($arg) {
        $arg['cointopay'] = __('Cointopay', 'wcmp-cointopay-gateway');
        return $arg;
    }
if (!class_exists('WCMP_Cointopay_Gateway') && WCMP_Cointopay_Gateway_Dependencies::woocommerce_active_check()) {
    require_once( 'classes/class-wcmp-cointopay-gateway.php' );
    global $WCMP_Cointopay_Gateway;
    $WCMP_Cointopay_Gateway = new WCMP_Cointopay_Gateway(__FILE__);
    $GLOBALS['WCMP_Cointopay_Gateway'] = $WCMP_Cointopay_Gateway;
}
