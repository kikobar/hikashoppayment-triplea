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
		$postvars['merchant_key'] = $pluginConfig['merchant_key'];
		$postvars['access_token'] = $pluginConfig['access_token'];
		$postvars['order_currency'] = $this->currency->currency_code;
		$postvars['order_amount'] = $order->order_full_price;
		$postvars['notify_secret'] = $pluginConfig['notify_secret'];
		$postvars['notify_txs'] = true;
		$postvars['payer_id'] = $order->order_uer_id;
		$postvars['order_id'] = $order->order_id;
		$postvars['sandbox'] = $pluginConfig['sandbox'];
		$postvars['webhook_data'] = array(
					"order_id" => $order_id);

		$httpheader = array();
		$httpheader['Autorization'] = "Bearer $access_token";
		$httpheader['Content-Type'] = "application/json";

		$curl = curl_init();

		curl_setopt_array($curl, array(
  			CURLOPT_URL => $pluginConfig['url'].'/payment',
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
	function onPaymentNotification(&$statuses){
		$vars	= array();
		$data	= array();
		$filter	=& JFilterInput::getInstance();

		foreach($_REQUEST as $key => $val){
			$vars[$key] = $val;
		}

		$order_id = (int)@$vars['invoice'];
		$order_status = '';
		$dbOrder	= $this->getOrder($order_id);

		if(empty($dbOrder)){
			echo "Could not load any order for your notification ".@$vars['invoice'];
			return false;
		}

		$this->loadPaymentParams($dbOrder);
		if(empty($this->payment_params))
			return false;
		$this->loadOrderData($dbOrder);
		if($this->payment_params->debug){
			echo print_r($vars,true)."\n\n\n";
			echo print_r($dbOrder,true)."\n\n\n";
		}

		$url			= HIKASHOP_LIVE.'administrator/index.php?option=com_hikashop&ctrl=order&task=edit&order_id='.$order_id;
		$order_text		= "\r\n".JText::sprintf('NOTIFICATION_OF_ORDER_ON_WEBSITE',$dbOrder->order_number,HIKASHOP_LIVE);
		$order_text		.= "\r\n".str_replace('<br/>',"\r\n",JText::sprintf('ACCESS_ORDER_WITH_LINK',$url));

		$email = new stdClass();
		$history = new stdClass();
		$response = $vars['ok_txn_status'];

		$verified = preg_match( "#completed#i", $response);

		// verify transacrion through TripleA
		$req = 'ok_verify=true';

		foreach ($_POST as $key => $value) {
			$value = urlencode(stripslashes($value));
			$req .= "&$key=$value";
		}

		$header  = "POST /ipn-verify.html HTTP/1.0\r\n";
		$header .= "Host: www.okpay.com\r\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$header .= "Content-Length: " . strlen($req) . "\r\n\r\n";
		$fp = fsockopen ('www.okpay.com', 80, $errno, $errstr, 30);

		if (!$fp)
		{
			// HTTP ERROR
		} else
		{
			// NO HTTP ERROR
			fputs ($fp, $header . $req);
			while (!feof($fp))
			{
			$res = fgets ($fp, 1024);
			if (strcmp ($res, "VERIFIED") == 0)
			{
				$vars['ok_response'] = $res;
			}
			else if (strcmp ($res, "INVALID") == 0)
			{
				// If 'INVALID', send an email. TODO: Log for manual investigation.
				$vars['ok_response'] = $res;
			}
			else if (strcmp ($res, "TEST")== 0)
			{
				$vars['ok_response'] = $res;
			}
			}
			fclose ($fp);
		}

		$vars['ok_verified'] = preg_match('/verified/i', @$vars['ok_response']);
		// end verify transacrion through TripleA

		if(!$verified && !$vars['ok_verified']){
			$email->subject = JText::sprintf('NOTIFICATION_REFUSED_FOR_THE_ORDER','TripleA').'invalid transaction';
			$email->body = JText::sprintf("Hello,\r\n A TripleA notification was refused because it could not be verified by the TripleA server")."\r\n\r\n".JText::sprintf('CHECK_DOCUMENTATION',HIKASHOP_HELPURL.'payment-triplea-error#invalidtnx').$order_text;

			$this->modifyOrder($order_id,null,false,$email);
			return false;
		} else {
			$email->subject = JText::sprintf('NOTIFICATION_REFUSED_FOR_THE_ORDER','TripleA').'invalid transaction';
			$email->body = JText::sprintf("Hello,\r\n A TripleA notification was refused because it could not be verified by the tripleA server")."\r\n\r\n".JText::sprintf('CHECK_DOCUMENTATION',HIKASHOP_HELPURL.'payment-triplea-error#invalidtnx').$order_text;

			$this->modifyOrder($order_id,null,false,$email);
		}

		$history->notified=0;
		$history->amount=@$vars['ok_txn_gross'].@$vars['ok_txn_currency'];
		$history->data = ob_get_clean();

		$price_check = round($dbOrder->order_full_price, (int)$this->currency->currency_locale['int_frac_digits'] );

		if($price_check != @$vars['ok_txn_gross'] || $this->currency->currency_code != @$vars['ok_txn_currency']){
			$order_status = $this->payment_params->invalid_status;

			$email->subject=JText::sprintf('NOTIFICATION_REFUSED_FOR_THE_ORDER','TripleA').JText::_('INVALID_AMOUNT');
			$email->body=str_replace('<br/>',"\r\n",JText::sprintf('AMOUNT_RECEIVED_DIFFERENT_FROM_ORDER','TripleA',$history->amount,$price_check.$this->currency->currency_code))."\r\n\r\n".JText::sprintf('CHECK_DOCUMENTATION',HIKASHOP_HELPURL.'payment-triplea-error#amount').$order_text;

			$this->modifyOrder($order_id,$order_status,$history,$email);
			return false;
		}

		$order_status = $this->payment_params->verified_status;
		$history->notified=1;

		if($dbOrder->order_status == $order_status)
			return true;
		$mail_status=$statuses[$order_status];
		$email->subject = JText::sprintf('PAYMENT_NOTIFICATION_FOR_ORDER','TripleA',$vars['ok_txn_status'],$dbOrder->order_number);
		$email->body = str_replace('<br/>',"\r\n",JText::sprintf('PAYMENT_NOTIFICATION_STATUS','TripleA',$vars['ok_txn_status'])).' '.JText::sprintf('ORDER_STATUS_CHANGED',$mail_status)."\r\n\r\n".$order_text;

		$this->modifyOrder($order_id,$order_status,$history,$email);

		return true;
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
