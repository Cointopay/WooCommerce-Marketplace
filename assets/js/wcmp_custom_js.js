jQuery(function($) {
	if($('input[id="cointopay_merchant_id"]').length>0){
		var merchant_idd = $('input[id="cointopay_merchant_id"]').val();
		if(merchant_idd != ''){
		var length_idd = merchant_idd.length;
		
			console.log(ajaxurl);
			$.ajax ({
				url: ajaxurl,
				showLoader: true,
				data: {merchant:merchant_idd, action:'getMerchantCoinsByAjax'},
				type: "POST",
				success: function(result) {
					$('select[id="cointopay_alt_coin"]').html('');
					//$('input[id="cointopay_merchant_id"]').css('border','1px solid #adadad');
					//$('.incorrect-merchant').remove();
					if (result.length) {
							$('select[id="cointopay_alt_coin"]').html(result);
						
					} else {
						//$('input[id="cointopay_merchant_id"]').css('border','1px solid red');
						//$('input[id="cointopay_merchant_id"]').closest('td').append('<span style="color:red" class="incorrect-merchant">MerchantID should be type Integer, please correct. </span>');
					}
				}
			});
	
	$('input[id="cointopay_merchant_id"]').on('change', function () {
		var merchant_id = $(this).val();
		var length_id = merchant_id.length;
		
			$.ajax ({
				url: ajaxurl,
				showLoader: true,
				data: {merchant:merchant_id, action:'getMerchantCoinsByAjax'},
				type: "POST",
				success: function(result) {
					$('select[id="cointopay_alt_coin"]').html('');
					//$('input[id="cointopay_merchant_id"]').css('border','1px solid #adadad');
					//$('.incorrect-merchant').remove();
					if (result.length) {
						$('select[id="cointopay_alt_coin"]').html(result);
					} else {
						//$('input[id="cointopay_merchant_id"]').css('border','1px solid red');
						//$('input[id="cointopay_merchant_id"]').closest('td').append('<span style="color:red" class="incorrect-merchant">MerchantID should be type Integer, please correct. </span>');
					}
				}
			});
		
	});
	}
	}
});