<?php
defined('_JEXEC') or die('Restricted access');

class plgHikashoppaymentTriplea extends hikashopPaymentPlugin{

	var $accepted_currencies = array(
		'AUD','CAD','EUR','GBP','JPY','USD','NZD','CHF','SGD',
		'NOK','MYR','PHP','TWD',
		'HKD','IDR','KRW','SEK','THB','VND'
	);
	var $debugData = array();
	var $multiple = true;
	var $name = 'triplea';
	var $pluginConfig = array(
		'url' => array('URL', 'input'),
		'merchant_key'=>array('TripleA Merchant Key', 'input'),
		'access_token'=>array('TripleA Access Token','input'),
		'notification'=>array('Allow payment notifications from TripleA','boolean','1'),
		'notify_secret'=>array('Notify Secret','input'),
		'sandbox'=>array('Set as testing environment','boolean','0'),
		'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
		'pending_status' => array('PENDING_STATUS', 'orderstatus'),
		'verified_status' => array('VERIFIED_STATUS', 'orderstatus')
	);

	function onAfterOrderConfirm(&$order,&$methods,$method_id){
		parent::onAfterOrderConfirm($order, $methods, $method_id);

		$payment_url = $this->payment_params->url.'/payment';

		$curl = curl_init();

		curl_setopt_array($curl, array(
  			CURLOPT_URL => $payment_url,
  			CURLOPT_RETURNTRANSFER => true,
  			CURLOPT_ENCODING => '',
  			CURLOPT_MAXREDIRS => 10,
  			CURLOPT_TIMEOUT => 0,
  			CURLOPT_FOLLOWLOCATION => true,
  			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS =>'{
				"type": "triplea",
				"merchant_key": "'.$this->payment_params->merchant_key.'",
				"order_currency": "'.$this->currency->currency_code.'",
				"order_amount": '.$order->order_full_price.',
				"payer_id": "'.(string)$order->order_user_id.'",
				"payer_name": "'.$order->cart->billing_address->address_firstname.' '.$order->cart->billing_address->address_lastname.'",
				"payer_email": "'.$this->user->user_email.'",
				"payer_phone": "'.(string)$order->cart->billing_address->address_telephone.'",
				"success_url": "'.HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id='.$order->order_id.$this->url_itemid.'",
				"cancel_url": "'.HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id='.$order->order_id.$this->url_itemid.'",
				"notify_url": "'.HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment='.$this->name.'&tmpl=component&lang='.$this->locale.$this->url_itemid.'",
				"notify_secret": "'.$this->payment_params->notify_secret.'",
				"notify_txs": true,
				"sandbox": '.$this->payment_params->sandbox.'
			}',
			CURLOPT_HTTPHEADER => array(
			'Authorization: Bearer '.$this->payment_params->access_token,
			'Content-Type: application/json'
			),
  			)
		);

		$response = curl_exec($curl);

		curl_close($curl);
//		echo $response;					//for debug only
		$responsearr = json_decode($response,true);
		$hosted_url = $responsearr['hosted_url'];
//		var_dump($responsearr);				//for debug only
//		echo $responsearr['hosted_url'];		//for debug only
		header('Location: '.$hosted_url);

	}
/*
	function onPaymentNotification(&$statuses){

//		All the logic for registering the payment confirmation/cancellation/etc is still missing


//		$vars	= array();
//		$data	= array();

		$curl = curl_init();

		curl_setopt_array($curl, array(
  			CURLOPT_URL => 'https://api.triple-a.io/api/v2/payment/QCN-592345-PMT?verbose=1',
  			CURLOPT_RETURNTRANSFER => true,
  			CURLOPT_ENCODING => '',
  			CURLOPT_MAXREDIRS => 10,
  			CURLOPT_TIMEOUT => 0,
  			CURLOPT_FOLLOWLOCATION => true,
  			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  			CURLOPT_CUSTOMREQUEST => 'GET',
  			CURLOPT_HTTPHEADER => array(
    				'Authorization: Bearer '.$this->payment_params->access_token
  			),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		echo $response;
	}
*/
	function getPaymentDefaultValues(&$element) {
		$element->payment_name='TripleA';
		$element->payment_description='TripleA - pay with crypto';
		$element->payment_images='';
		$element->payment_params->url='https://api.triple-a.io/api/v2';
		$element->payment_params->merchant_key='Your merchant key with TripleA';
		$element->payment_params->access_token='Your access token with TripleA';
		$element->payment_params->sandbox=true;
		$element->payment_params->notification=1;
		$element->payment_params->details=0;
		$element->payment_params->invalid_status='cancelled';
		$element->payment_params->pending_status='created';
		$element->payment_params->verified_status='confirmed';
		$element->payment_params->address_override=1;
	}
}
