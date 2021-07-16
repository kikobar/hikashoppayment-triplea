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
		'notify_secret'=>array('Notify Secret','input'),
		'sandbox'=>array('Set as testing environment','boolean','0'),
		'invalid_status' => array('INVALID_STATUS', 'orderstatus'),
		'pending_status' => array('PENDING_STATUS', 'orderstatus'),
		'verified_status' => array('VERIFIED_STATUS', 'orderstatus')
	);

	function onAfterOrderConfirm(&$order,&$methods,$method_id){
		parent::onAfterOrderConfirm($order, $methods, $method_id);

		$postvars = array();

		$postvars['notify_url']	= HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=notify&notif_payment=triplea&tmpl=component&&invoice='.$order->order_id.'lang='.$this->locale.$this->url_itemid;
		$postvars['success_url'] = HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=checkout&task=after_end&order_id='.$order->order_id.$this->url_itemid;
		$postvars['cancel_url']	= HIKASHOP_LIVE.'index.php?option=com_hikashop&ctrl=order&task=cancel_order&order_id='.$order->order_id.$this->url_itemid;
		$postvars['type'] = 'triplea';
		$postvars['merchant_key'] = $this->payment_params->merchant_key;
		$postvars['order_currency'] = $this->currency->currency_code;
		$postvars['order_amount'] = $order->order_full_price;
		$postvars['notify_secret'] = $this->payment_params->notify_secret;
		$postvars['notify_txs'] = true;
		$postvars['payer_id'] = $order->order_user_id;
		$postvars['payer_name'] = $order->cart->billing_address->address_firstname." ".$order->cart->billing_address->address_lastname;
		$postvars['payer_email'] = $this->user->user_email;
		$postvars['payer_phone'] = $order->cart->billing_address->address_telephone;
		$postvars['sandbox'] = (bool)$this->payment_params->sandbox;
//		$postvars['webhook_data'] = array(
//					"order_id" => $order->order_id);

		$httpheader = array(
			'Autorization: Bearer '.$this->payment_params->access_token,
			'Content-type: application/json'
			);
		$payment_url = $this->payment_params->url.'/payment';

		var_dump($postvars);

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
  			CURLOPT_POSTFIELDS => $postvars,
  			CURLOPT_HTTPHEADER => $httpheader
  			),
		);

		$response = curl_exec($curl);

		curl_close($curl);
		echo $response;

	}
/*

//	This is where the payment notification function should sit

	function onPaymentNotification(&$statuses){
//		$vars	= array();
//		$data	= array();
	}

*/

	function getPaymentDefaultValues(&$element) {
		$element->payment_name='TripleA';
		$element->payment_description='You can pay by TripleA using this payment method';
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
