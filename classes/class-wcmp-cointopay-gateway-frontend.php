<?php

class WCMP_Cointopay_Gateway_Frontend {

    public function __construct() {
        add_filter('wcmp_transaction_item_totals', array(&$this, 'wcmp_transaction_item_totals'), 10, 2);
    }
    /**
     * Set payment method cointopay in frontend
     * @param array $item_totals
     * @param int $transaction_id
     * @return array
     */
    public function wcmp_transaction_item_totals($item_totals, $transaction_id){
        $transaction_mode = get_post_meta($transaction_id, 'transaction_mode', true);
        if($transaction_mode == 'cointopay'){
            $item_totals['via'] = array('label' => __('Transaction Mode', 'wcmp-cointopay-gateway'), 'value' => __('Cointopay', 'wcmp-cointopay-gateway'));
        }
        return $item_totals;
    }
    

}
