<?php

class MVX_Cointopay_Gateway_Payment_Method extends WC_Payment_Gateway {

    public function __construct() {
        global $MVX_Cointopay_Gateway;
        $this->id = 'mvx-cointopay-payments';
        $this->icon = $MVX_Cointopay_Gateway->plugin_url . 'assets/images/cointopay.png';
        $this->has_fields = false;
        $this->method_title = __('Cointopay Payments (MVX Compatible)', 'mvx-cointopay-gateway');
        $this->order_button_text = __('Proceed to Cointopay', 'mvx-cointopay-gateway');
        $this->notify_url = WC()->api_request_url('MVX_Cointopay_Payments_Gateway');

        $this->init_form_fields();

        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->cointopay_merchant_id = $this->get_option('cointopay_merchant_id');
        $this->cointopay_security_code = $this->get_option('cointopay_security_code');
        $this->receiver_email = $this->get_option('receiver_email');
        $this->method = $this->get_option('method');
        $this->debug = $this->get_option('debug');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_api_cointopay', array($this, 'cointopay_ipn_response'));

        $this->admin_notices();
    }

    /**
     * Process the payment and return the result.
     *
     * @param  int $order_id
     *
     * @return array
     */
    public function process_payment($order_id) {global $woocommerce;
		global $MVX;
		
		$order = wc_get_order($order_id);
		$cointopay_alt_coin = get_post_meta( $order_id, 'cointopay_alt_coin', true);

		$item_names = array();

		if (sizeof($order->get_items()) > 0) : foreach ($order->get_items() as $item) :
		if ($item['qty']) $item_names[] = $item['name'] . ' x ' . $item['qty'];
		endforeach; endif;

		$item_name = sprintf( __('Order %s' , 'woocommerce'), $order->get_order_number() ) . " - " . implode(', ', $item_names);

		$data = array(
			'SecurityCode' => $this->cointopay_security_code,
			'MerchantID' => $this->cointopay_merchant_id,
			'Amount' => number_format($order->get_total(), 8, '.', ''),
			'AltCoinID' => $cointopay_alt_coin,
			'output' => 'json',
			'inputCurrency' => get_woocommerce_currency(),
			'CustomerReferenceNr' => $order_id,
			'returnurl' => rawurlencode(esc_url($this->get_return_url($order))),
			'transactionconfirmurl' => site_url('/?wc-api=Cointopay'),
			'transactionfailurl' => rawurlencode(esc_url($order->get_cancel_order_url())),
		);


        // Sets the post params.
        $params = array(
            'body' => 'SecurityCode=' . $this->cointopay_security_code . '&MerchantID=' . $this->cointopay_merchant_id . '&Amount=' . number_format($order->get_total(), 8, '.', '') . '&AltCoinID='.$cointopay_alt_coin.'&output=json&inputCurrency=' . get_woocommerce_currency() . '&CustomerReferenceNr=' . $order_id . '&returnurl='.rawurlencode(esc_url($this->get_return_url($order))).'&transactionconfirmurl='.site_url('/?wc-api=Cointopay') .'&transactionfailurl='.rawurlencode(esc_url($order->get_cancel_order_url())),
        );


            $url = 'https://app.cointopay.com/MerchantAPI?Checkout=true';

        if ('yes' == $this->debug) {
            doCointopayLog('Setting payment options with the following data: ' . print_r($data, true));
        }

        $response = wp_safe_remote_post($url, $params);
        if (!is_wp_error($response) && 200 == $response['response']['code'] && 'OK' == $response['response']['message']) {
			$order->update_status('processing');
            $results = json_decode($response['body']);
					return array(
					'result' => 'success',
					'redirect' => $results->RedirectURL
					);
        } else {
            if ('yes' == $this->debug) {
                doCointopayLog('Failed to configure payment options: ' . print_r($response, true));
            }
        }
				
	}


    /**
     * Set Cointopay payment options.
     *
     * @param string $pay_key
     */
	public function admin_options()
	{
		?>
		<h3><?php _e('Cointopay Checkout', 'Cointopay');?></h3>

		<div id="wc_get_started">
			<span class="main"><?php _e('Provides a secure way to accept crypto currencies.', 'Cointopay'); ?></span>
			<p><a href="https://app.cointopay.com/index.jsp?#Register" target="_blank" class="button button-primary"><?php _e('Join free', 'Cointopay'); ?></a> <a href="https://cointopay.com" target="_blank" class="button"><?php _e('Learn more about WooCommerce and Cointopay', 'Cointopay'); ?></a></p>
		</div>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

    public function init_form_fields() {

        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'mvx-cointopay-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Cointopay Payments', 'mvx-cointopay-gateway'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Title', 'mvx-cointopay-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'mvx-cointopay-gateway'),
                'default' => __('Cointopay', 'mvx-cointopay-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'mvx-cointopay-gateway'),
                'type' => 'textarea',
                'description' => __('This controls the description which the user sees during checkout.', 'mvx-cointopay-gateway'),
                'default' => __('Pay via Cointopay', 'mvx-cointopay-gateway')
            ),
            'cointopay_merchant_id' => array(
                'title' => __('Cointopay Merchant Id', 'mvx-cointopay-gateway'),
                'type' => 'text',
                'description' => __('Please enter your Cointopay Merchant Id; this is needed in order to take payment.', 'mvx-cointopay-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'cointopay_security_code' => array(
                'title' => __('Cointopay Security Code', 'mvx-cointopay-gateway'),
                'type' => 'text',
                'description' => __('Please enter your Cointopay Security Code; this is needed in order to take payment.', 'mvx-cointopay-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'debug' => array(
                'title' => __('Debug Log', 'mvx-cointopay-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable logging', 'mvx-cointopay-gateway'),
                'default' => 'no',
                'description' => '',
            )
        );
    }
   public function mvx_coins_select_options() {
		return array(
			'' => __( 'Nope', 'cmb2' ),
			'standard' => __( 'Option One', 'cmb2' ),
			'custom'   => __( 'Option Two', 'cmb2' ),
			'none'     => __( 'Option Three', 'cmb2' ),
		);
   }
   protected function admin_notices() {
        if (is_admin()) {
            if ('yes' == $this->get_option('enabled') && ( empty($this->cointopay_merchant_id) || empty($this->cointopay_security_code) || empty($this->receiver_email) )) {
                add_action('admin_notices', array($this, 'gateway_not_configured_message'));
            }
            if (!$this->using_supported_currency()) {
                add_action('admin_notices', array($this, 'unsupported_currency_not_message'));
            }
        }
    }

    public function using_supported_currency() {
        if (!in_array(get_woocommerce_currency(), apply_filters('mvx_cointopay_supported_currencies', array('AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'TWD', 'THB', 'TRY', 'USD')))) {
            return false;
        }

        return true;
    }

    public function gateway_not_configured_message() {
        $id = 'woocommerce_mvx-cointopay-payments_';
		$c_cointopay_merchant_id = intval($_POST[$id . 'cointopay_merchant_id']);
		if (isset($c_cointopay_merchant_id) && 0 !== $c_cointopay_merchant_id){
        if (!empty($c_cointopay_merchant_id) && isset($c_cointopay_merchant_id) && !empty($c_cointopay_merchant_id)) {
            return;
        }
        echo $c_cointopay_merchant_id.'<div class="error"><p><strong>' . __('Cointopay Payments Disabled For WC Marketplace', 'mvx-cointopay-gateway') . '</strong>: ' . __('You must fill the Merchant ID, Security code options.', 'mvx-cointopay-gateway') . '</p></div>';
		}
    }

    public function unsupported_currency_not_message() {
        echo '<div class="error"><p><strong>' . __('Cointopay Payments Disabled', 'mvx-cointopay-gateway') . '</strong>: ' . __('Cointopay does not support your store currency.', 'mvx-cointopay-gateway') . '</p></div>';
    }

    public function cointopay_ipn_response() {
		global $woocommerce;
		$woocommerce->cart->empty_cart();
		$order_id = intval($_REQUEST['CustomerReferenceNr']);
		$o_status = sanitize_text_field($_REQUEST['status']);
		$o_TransactionID = sanitize_text_field($_REQUEST['TransactionID']);
		$o_ConfirmCode = sanitize_text_field($_REQUEST['ConfirmCode']);
		$notenough = sanitize_text_field($_REQUEST['notenough']);

		$order = new WC_Order($order_id);
		$data = [ 
				   'mid' => $this->cointopay_merchant_id , 
				   'TransactionID' => $o_TransactionID ,
				   'ConfirmCode' => $o_ConfirmCode
			  ];
	  $response = $this->validateOrder($data);
	  if($response->Status !== $o_status)
	  {
		  get_header();
		  echo '<div class="container" style="text-align: center;"><div><div>
			<br><br>
			<h2 style="color:#ff0000">Failure!</h2>
			<img style="margin: auto;" src="'.plugins_url( 'assets/images/fail.png', __FILE__ ).'">
			<p style="font-size:20px;color:#5C5C5C;">We have detected different order status. Your order has been halted.</p>
			<a href="'.site_url().'" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
			<br><br>
			</div>
			</div>
			</div>';
			get_footer();
		  exit;
	  }
	   else if($response->CustomerReferenceNr == $order_id)
	  {
			if ($o_status == 'paid' && $notenough==0) {
			// Do your magic here, and return 200 OK to Cointopay.

			if ($order->get_status() == 'completed')
			{
				$order->update_status( 'completed', sprintf( __( 'IPN: Payment completed notification from Cointopay', 'woocommerce' ) ) );
			}
			else
			{
				$order->payment_complete();
				$order->update_status( 'completed', sprintf( __( 'IPN: Payment completed notification from Cointopay', 'woocommerce' ) ) );

			}
			 $order->add_order_note(__('The payment was successful.', 'mvx-cointopay-gateway'));
		     $order->payment_complete();
		
			get_header();
			echo '<div class="container" style="text-align: center;"><div><div>
			<br><br>
			<h2 style="color:#0fad00">Success!</h2>
			<img style="margin: auto;" src="'.plugins_url( 'assets/images/check.png', __FILE__ ).'">
			<p style="font-size:20px;color:#5C5C5C;">The payment has been received and confirmed successfully.</p>
			<a href="'.site_url().'" style="background-color: #0fad00;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
			<br><br>
			<br><br>
			</div>
			</div>
			</div>';
			get_footer();
		/*	echo "<script>
			setTimeout(function () {
			window.location.href= '".site_url()."';
			}, 5000);
			</script>";*/
			//header('HTTP/1.1 200 OK');
			exit;
		}
		else if ($o_status == 'failed' && $notenough == 1) {

			$order->update_status( 'on-hold', sprintf( __( 'IPN: Payment failed notification from Cointopay because notenough', 'woocommerce' ) ) );
			get_header();
			echo '<div class="container" style="text-align: center;"><div><div>
			<br><br>
			<h2 style="color:#ff0000">Failure!</h2>
			<img style="margin: auto;" src="'.plugins_url( 'assets/images/fail.png', __FILE__ ).'">
			<p style="font-size:20px;color:#5C5C5C;">The payment has been failed.</p>
			<a href="'.site_url().'" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
			<br><br>
			<br><br>
			</div>
			</div>
			</div>';
			get_footer();
		/*	echo "<script>
			setTimeout(function () {
			window.location.href= '".site_url()."';
			}, 5000);
			</script>";*/
			exit;
		}
		else{

			$order->update_status( 'failed', sprintf( __( 'IPN: Payment failed notification from Cointopay', 'woocommerce' ) ) );
			get_header();
			echo '<div class="container" style="text-align: center;"><div><div>
			<br><br>
			<h2 style="color:#ff0000">Failure!</h2>
			<img style="margin: auto;" src="'.plugins_url( 'assets/images/fail.png', __FILE__ ).'">
			<p style="font-size:20px;color:#5C5C5C;">The payment has been failed.</p>
			<a href="'.site_url().'" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
			<br><br>
			<br><br>
			</div>
			</div>
			</div>';
			get_footer();
		/*	echo "<script>
			setTimeout(function () {
			window.location.href= '".site_url()."';
			}, 5000);
			</script>";*/
			exit;
		}
	  }
	  else if($response == 'not found')
	  {
		  $order->update_status( 'failed', sprintf( __( 'We have detected different order status. Your order has not been found.', 'woocommerce' ) ) );
		  get_header();
			echo '<div class="container" style="text-align: center;"><div><div>
			<br><br>
			<h2 style="color:#ff0000">Failure!</h2>
			<img style="margin: auto;" src="'.plugins_url( 'assets/images/fail.png', __FILE__ ).'">
			<p style="font-size:20px;color:#5C5C5C;">We have detected different order status. Your order has not been found..</p>
			<a href="'.site_url().'" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
			<br><br>
			<br><br>
			</div>
			</div>
			</div>';
			get_footer();
	  }
	  else{
		  $order->update_status( 'failed', sprintf( __( 'We have detected different order status. Your order has been halted.', 'woocommerce' ) ) );
		  get_header();
			echo '<div class="container" style="text-align: center;"><div><div>
			<br><br>
			<h2 style="color:#ff0000">Failure!</h2>
			<img style="margin: auto;" src="'.plugins_url( 'assets/images/fail.png', __FILE__ ).'">
			<p style="font-size:20px;color:#5C5C5C;">We have detected different order status. Your order has been halted.</p>
			<a href="'.site_url().'" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
			<br><br>
			<br><br>
			</div>
			</div>
			</div>';
			get_footer();
	  }
	}

	public function validateOrder($data){
	   $data_j = array(
            'MerchantID' => $data['mid'],
            'Call' => 'QA',
            'APIKey' => '_',
			'output' => 'json',
			'TransactionID' => $data['TransactionID'],
			'ConfirmCode' => $data['ConfirmCode'],
        );

        // Sets the post params.
        $params = array(
            'body' => 'MerchantID='.$data['mid'].'&Call=QA&APIKey=_&output=json&TransactionID='.$data['TransactionID'].'&ConfirmCode='.$data['ConfirmCode'],
			'authentication' => 1,
			'cache-control' => 'no-cache',
        );


            $url = 'https://app.cointopay.com/v2REAPI?';

        if ('yes' == $this->debug) {
            doCointopayLog('Setting payment options with the following data: ' . print_r($data, true));
        }

        $response = wp_safe_remote_post($url, $params);
         $results = json_decode($response['body']);
				if($results->CustomerReferenceNr)
				{
					return $results;
				}
				else if($response == '"not found"')
				  {
					  get_header();
					   echo '<div class="container" style="text-align: center;"><div><div>
								<br><br>
								<h2 style="color:#ff0000">Failure!</h2>
								<img style="margin: auto;" src="'.plugins_url( 'assets/images/fail.png', __FILE__ ).'">
								<p style="font-size:20px;color:#5C5C5C;">Your order not found.</p>
								<a href="'.site_url().'" style="background-color: #ff0000;border: none;color: white; padding: 15px 32px; text-align: center;text-decoration: none;display: inline-block; font-size: 16px;" >    Back     </a>
								<br><br>
			
								</div>
								</div>
								</div>';
								get_footer();
							  exit;
				  }
			   
				   echo $response;
				  
			}
    protected function delete_associated_commission($order_id) {
        global $wpdb;
        if (MVX_Cointopay_Gateway_Dependencies::mvx_active_check()) {
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

}
