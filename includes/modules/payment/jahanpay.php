<?php

/*
Jahanpay payment service
  
coder: mehdi mohammadi and M.Meshkatian
website : http://qoohost.ir
*/
require_once ('ext/lib/nusoap.php');

class osC_Payment_jahanpay extends osC_Payment {
	var $_title, $_code = 'jahanpay', $_status = false, $_sort_order, $_order_id;
	function osC_Payment_jahanpay() {
		global $osC_Database, $osC_Language, $osC_ShoppingCart;
		$this->_title = $osC_Language->get('payment_jahanpay_title');
		$this->_method_title = $osC_Language->get('payment_jahanpay_method_title');
		$this->_status = (MODULE_PAYMENT_jahanpay_STATUS == '1') ? true : false;
		$this->_sort_order = MODULE_PAYMENT_jahanpay_SORT_ORDER;
		$this->form_action_url = 'jahanpay.php';
		if ($this->_status === true) {
			if ((int) MODULE_PAYMENT_jahanpay_ORDER_STATUS_ID > 0) {
				$this->order_status = MODULE_PAYMENT_jahanpay_ORDER_STATUS_ID;
			}
			if ((int) MODULE_PAYMENT_jahanpay_ZONE > 0) {
				$check_flag = false;
				$Qcheck = $osC_Database->query('select zone_id from :table_zones_to_geo_zones where geo_zone_id = :geo_zone_id and zone_country_id = :zone_country_id order by zone_id');
				$Qcheck->bindTable(':table_zones_to_geo_zones', TABLE_ZONES_TO_GEO_ZONES);
				$Qcheck->bindInt(':geo_zone_id', MODULE_PAYMENT_jahanpay_ZONE);
				$Qcheck->bindInt(':zone_country_id', $osC_ShoppingCart->getBillingAddress('country_id'));
				$Qcheck->execute();
				while ($Qcheck->next()) {
					if ($Qcheck->valueInt('zone_id') < 1) {
						$check_flag = true;
						break;
					}
					elseif ($Qcheck->valueInt('zone_id') == $osC_ShoppingCart->getBillingAddress('zone_id')) {
						$check_flag = true;
						break;
					}
				}
				if ($check_flag === false) {
					$this->_status = false;
				}
			}
		}
	}
	function selection() {
		return array('id' => $this->_code, 'module' => $this->_method_title);
	}
	function pre_confirmation_check() {
		return false;
	}
	function confirmation() {
		global $osC_Language, $osC_CreditCard;
		$this->_order_id = osC_Order :: insert(ORDERS_STATUS_PREPARING);
		$confirmation = array('title' => $this->_method_title, 'fields' => array(array('title' => $osC_Language->get('payment_jahanpay_description'))));
		return $confirmation;
	}
	function process_button() {
		
		global $osC_Currencies, $osC_ShoppingCart, $osC_Language, $osC_Database;
		$currency = MODULE_PAYMENT_jahanpay_CURRENCY;
		$amount = round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2);
		 $client = new SoapClient("http://www.jpws.me/directservice?wsdl");
		$order = $this->_order_id;
		$orderId = $order;
		$callbackUrl = osc_href_link(FILENAME_CHECKOUT, 'process&order='.$orderId.'', 'SSL', null, null, true);
		$result = $client->requestpayment(MODULE_PAYMENT_jahanpay_PIN, $amount/10, $callbackUrl, $orderId);	
		$resultStr = $result;
        if($resultStr['result']==1 ){
	  $au = $resultStr['au'];
		$osC_Database->simpleQuery("insert into `" . DB_TABLE_PREFIX . "online_transactions`
		(orders_id,receipt_id,transaction_method,transaction_date,transaction_amount,transaction_id) values
		                    ('$orderId','$au','jahanpay','','$amount','')
				  ");
					//
		 $process_button_string = osc_draw_hidden_field('MID', MODULE_PAYMENT_jahanpay_PIN).
		  osc_draw_hidden_field('form', $resultStr['form']).
                               osc_draw_hidden_field('OrderId', $orderId).
                               osc_draw_hidden_field('CallBack', $callbackUrl).
                               osc_draw_hidden_field('Amount', $amount/10);

      return $process_button_string;
      
	    ;}else{
	      osC_Order :: remove($this->_order_id);
	    echo '<div style="font-size:11px; color:#cc0000; width:500; border:1px solid #cc0000; padding:5px; background:#ffffcc;">' ."مشکلی در اتصال به سرور به وجود آمده است".$resultStr['result']. '</div><div style="display:none">';
	    
;}
	}
	function get_error() {
		global $osC_Language;
		return $error;
	}
	function process() {
	      global   $osC_Database,$osC_Customer, $osC_Currencies, $osC_ShoppingCart, $_POST, $_GET, $osC_Language, $messageStack;
$order_id=$_GET['order'];
	  $find_ord_id = $osC_Database->query('select Receipt_id from :table_online_transactions where orders_id = :tbl_ord_id');
          $find_ord_id->bindTable(':table_online_transactions', DB_TABLE_PREFIX . "online_transactions");
          $find_ord_id->bindValue(':tbl_ord_id', $order_id);
          $find_ord_id->execute();
		  $rcp_id=$find_ord_id->value('Receipt_id') ;
		   		$client = new SoapClient("http://www.jpws.me/directservice?wsdl");
 $amount = round($osC_Currencies->formatRaw($osC_ShoppingCart->getTotal(), $currency), 2);
 $StrResult = $client->verification(MODULE_PAYMENT_jahanpay_PIN, $amount/10 , $rcp_id , $order_id, $_POST + $_GET );

if($StrResult['result']==1){
$osC_Database->simpleQuery("update `" . DB_TABLE_PREFIX . "online_transactions` set transaction_id = '".$rcp_id."',transaction_date = '" . date("YmdHis") . "' where 1 and ( orders_id = '".$order_id."' )");
					//
						$Qtransaction = $osC_Database->query('insert into :table_orders_transactions_history (orders_id, transaction_code, transaction_return_value, transaction_return_status, date_added) values (:orders_id, :transaction_code, :transaction_return_value, :transaction_return_status, now())');
						$Qtransaction->bindTable(':table_orders_transactions_history', TABLE_ORDERS_TRANSACTIONS_HISTORY);
						$Qtransaction->bindInt(':orders_id', $order_id);
						$Qtransaction->bindInt(':transaction_code', 1);
						$Qtransaction->bindValue(':transaction_return_value', $rcp_id);
						$Qtransaction->bindInt(':transaction_return_status', 1);
						$Qtransaction->execute();
						//
						$this->_order_id = osC_Order :: insert();
						$comments = $osC_Language->get('payment_jahanpay_method_authority') . '[' . $rcp_id . ']';
						osC_Order :: process($this->_order_id, $this->order_status, $comments);
	;}else{
		
		osC_Order :: remove($this->_order_id);
	
        $messageStack->add_session('checkout', check_jahanpay_state_error($StrResult['result']), 'error');
		
        osc_redirect(osc_href_link(FILENAME_CHECKOUT, 'checkout&view=paymentInformationForm', 'SSL', null, null, true));		
		
		;}

		
		;}
}
##-----------------------------------------------------------------------------____________________CHECK_jahanpay_STATE_ERROR
function check_jahanpay_state_error($Error){
  $errorCode = array(
			-20=>'api نامعتبر است' ,
			-21=>'آی پی نامعتبر است' ,
			-22=>'مبلغ تعیین شده خیلی کم است' ,
			-23=>'مبلغ از سقف مجاز بیشتر است' ,
			-24=>'مبلغ نامعتبر است' ,
			-6=>'ارتباط با بانک برقرار نشد' ,
			-26=>'درگاه غیرفعال است' ,
			-27=>'آی پی شما مسدود است' ,
			-9=>'خطای ناشناخته' ,
			-29=>'آدرس کال بک خالی است ' ,
			-30=>'چنین تراکنشی یافت نشد' ,
			-31=>'تراکنش انجام نشده ' ,
			-32=>'تراکنش انجام شده اما مبلغ نادرست است ' ,
			//1 => "تراکنش با موفقیت انجام شده است " ,
		);
		
    return $errorCode[$Error];
}
?>