<?php

/**
 * Plugin Name: WCMP Cointopay Gateway
 * Plugin URI: https://cointopay.com/
 * Description: WCMP Cointopay Gateway is a payment gateway for woocommerce shopping plateform also compatible with WC Marketplace.
 * Author: Cointopay.com
 * Version: 1.2.3
 * Author URI: https://cointopay.com/
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


if(!WCMP_Cointopay_Gateway_Dependencies::woocommerce_active_check()){
    add_action('admin_notices', 'woocommerce_inactive_notice');
}

if (!class_exists('WCMP_Cointopay_Gateway') && WCMP_Cointopay_Gateway_Dependencies::woocommerce_active_check()) {
    require_once( ABSPATH . 'wp-content/plugins/wcmp-cointopay-gateway/classes/class-wcmp-cointopay-gateway.php' );
    global $WCMP_Cointopay_Gateway;
    $WCMP_Cointopay_Gateway = new WCMP_Cointopay_Gateway(__FILE__);
    $GLOBALS['WCMP_Cointopay_Gateway'] = $WCMP_Cointopay_Gateway;
	function WCMP_Cointopay_Gateway_admin_js() {
    $url = plugins_url('assets/js/wcmp_custom_js.js', __FILE__);
    echo '<script type="text/javascript" src="'. $url . '"></script>';
	}
	add_action('wp_footer', 'WCMP_Cointopay_Gateway_admin_js');
}
add_action( 'wp_ajax_nopriv_getMerchantCoinsByAjax', 'getMerchantCoinsByAjax' );
add_action( 'wp_ajax_getMerchantCoinsByAjax', 'getMerchantCoinsByAjax' );
function getMerchantCoinsByAjax()
{
	$merchantId = '';
	$merchantId = sanitize_text_field($_REQUEST['merchant']);
	if(isset($merchantId) && $merchantId !== '')
	{
		$option = '';
		$arr = getMerchantCoins($merchantId);
		foreach($arr as $key => $value)
		{
			$option .= '<option value="'.$key.'">'.$value.'</option>';
		}
		//print_r($arr);
		echo $option;exit();
	}
}

function getMerchantCoins($merchantId)
{
	$params    = array(
				'body' => 'MerchantID=' . $merchantId . '&output=json',
			);
			$url       = 'https://cointopay.com/CloneMasterTransaction';
			$response  = wp_safe_remote_post($url, $params);
			if (( false === is_wp_error($response) ) && ( 200 === $response['response']['code'] ) && ( 'OK' === $response['response']['message'] )) {
				$php_arr = json_decode($response['body']);
	$new_php_arr = array();

	if(!empty($php_arr))
	{
		for($i=0;$i<count($php_arr)-1;$i++)
		{
			if(($i%2)==0)
			{
				$new_php_arr[$php_arr[$i+1]] = $php_arr[$i];
			}
		}
	}
	//print_r($php_arr);
	return $new_php_arr;
	}
}
//* Do NOT include the opening php tag shown above. Copy the code shown below.

//* Add select field to the checkout page
add_action('woocommerce_after_order_notes', 'cointopay_add_select_checkout_field');
function cointopay_add_select_checkout_field( $checkout ) {
	 global $WCMP_Cointopay_Gateway;
if(get_option('woocommerce_wcmp-cointopay-payments_settings') && get_option('woocommerce_wcmp-cointopay-payments_settings') !== ''){
		$cointopay_payments_settings = get_option('woocommerce_wcmp-cointopay-payments_settings', true);
if($cointopay_payments_settings['enabled'] === 'yes' && $cointopay_payments_settings['cointopay_merchant_id'] !== ''){
    // The user link
    $cointopay_merchant_id = $cointopay_payments_settings['cointopay_merchant_id'];

	woocommerce_form_field( 'cointopay_alt_coin', array(
	    'type'          => 'select',
	    'class'         => array( 'cointopay_alt_coin' ),
	    'label'         => __( 'Alt Coin' ),
	    'options'       => array(
	    	'blank'		=> __( 'Select Alt Coin', 'wps' ),
	    )
 ),

	$checkout->get_value( 'cointopay_alt_coin' ));
}
}
}
add_action('woocommerce_checkout_process', 'cointopay_process_custom_payment');
function cointopay_process_custom_payment(){
    if($_POST['payment_method'] != 'wcmp-cointopay-payments')
        return;

    if( !isset($_POST['cointopay_alt_coin']) || empty($_POST['cointopay_alt_coin']) )
        wc_add_notice( __( 'Please select valid Alt Coin', $this->domain ), 'error' );

}
//* Do NOT include the opening php tag shown above. Copy the code shown below.
//* Update the order meta with field value
 add_action('woocommerce_checkout_update_order_meta', 'cointopay_select_checkout_field_update_order_meta');
 function cointopay_select_checkout_field_update_order_meta( $order_id ) {
   if ($_POST['cointopay_alt_coin']) update_post_meta( $order_id, 'cointopay_alt_coin', esc_attr($_POST['cointopay_alt_coin']));
 }
add_action( 'woocommerce_after_order_notes', 'cointopay_checkout_hidden_field', 10, 1 );
function cointopay_checkout_hidden_field( $checkout ) {
	global $WCMP_Cointopay_Gateway;
    if(get_option('woocommerce_wcmp-cointopay-payments_settings') && get_option('woocommerce_wcmp-cointopay-payments_settings') !== ''){
		$cointopay_payments_settings = get_option('woocommerce_wcmp-cointopay-payments_settings', true);
if($cointopay_payments_settings['enabled'] === 'yes' && $cointopay_payments_settings['cointopay_merchant_id'] !== ''){
    // The user link
    $cointopay_merchant_id = $cointopay_payments_settings['cointopay_merchant_id'];

    // Output the hidden link
   echo '<input type="hidden" class="input-hidden" name="cointopay_merchant_id" id="cointopay_merchant_id" value="' . $cointopay_merchant_id . '" />';
	}
	}
}
add_action('wp_head', 'wcmp_cointopay_pluginname_ajaxurl');
function wcmp_cointopay_pluginname_ajaxurl()
 {
?>
<script type="text/javascript">
var ajaxurl = '<?php echo admin_url('admin-ajax.php');?>';
</script>
<?php
}
		