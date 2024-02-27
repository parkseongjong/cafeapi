<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/config_for.php';

// https://cybertronchain.com/apis/for/latoken.php

//require_once '/var/www/html/wallet2/config/nconfig.php';
//require_once '/var/www/html/wallet2/config/proc_config.php';


$kind = (isset($_GET['kind'])) ? $_GET['kind'] : '';

$ok_json = array('code'=>200,'error'=>false, 'msg'=>err_message(200));


$b_from_currency = array('USDT');
$b_currency = array('CTC');

$code = (isset($_GET['code'])) ? $_GET['code'] : '';

$db = getDbInstance();
$db->where('code', $code);
$db->where('use_yn', 'Y');
$a_row = $db->getOne('for_auth_list');
$auth_id = '';

if ( empty($a_row) ) {
	jsonReturn(array('code'=>801,'error'=>true,'msg'=>err_message(801)));
} else {
	$auth_id = $a_row['id'];
}
$to_address = 'aa';



switch($kind) {

	case 'create_invoice':
		$userId = (isset($_GET['userId'])) ? $_GET['userId'] : '';
		$currency = (isset($_GET['currency'])) ? $_GET['currency'] : '';
		$to_currency = (isset($_GET['to_currency'])) ? $_GET['to_currency'] : '';
		$amount = (isset($_GET['amount'])) ? $_GET['amount'] : '';
		$description = (isset($_GET['description'])) ? $_GET['description'] : '';
		//$return_url = (isset($_GET['return_url'])) ? $_GET['return_url'] : '';
		$client = (isset($_GET['client'])) ? $_GET['client'] : '';
		$email = (isset($_GET['email'])) ? $_GET['email'] : '';
		$phone = (isset($_GET['phone'])) ? $_GET['phone'] : '';

		// 필수값 체크
		if ( empty($userId) || empty($currency) || empty($amount) || empty($to_currency) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}
		// 통화 체크
		if ( !in_array($currency, $b_from_currency) ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
		}
		// 통화 체크
		if ( !in_array($to_currency, $b_currency) ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
		}
		// 숫자 체크
		if ( !is_numeric($amount) || $amount <= 0 ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
		}
		// user id 체크
		$db = getDbInstance();
		$db->where('id', $userId);
		$userData = $db->getOne('admin_accounts', 'wallet_address');
		if ( empty($userData) || empty($userData['wallet_address']) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		} else {

			
			$hosted_url = '';
			$status = 'waiting';
			$now = strtotime('Now');
			$expired_at = strtotime('+10 minutes');

			$order_num = create_invoice_number();
			$ok_json['order_id'] = $order_num;
			$ok_json['currency'] = $currency;
			$ok_json['amount'] = $amount;
			$ok_json['status'] = $status;
			//$ok_json['return_url'] = $return_url;
			//$ok_json['hosted_url'] = $hosted_url;
			$ok_json['created_at'] = $now;
			$ok_json['expired_at'] = $expired_at;
			
			$insertArr = array();
			$insertArr['for_auth_id'] = $auth_id;
			$insertArr['order_num'] = $order_num;
			$insertArr['user_id'] = $userId;
			$insertArr['from_currency'] = $currency;
			$insertArr['from_amount'] = $amount;
			$insertArr['from_address'] = $userData['wallet_address'];
			$insertArr['to_currency'] = $to_currency;
			$insertArr['to_address'] = $to_address;
			$insertArr['status'] = $status;
			$insertArr['created_at'] = $now;
			$insertArr['expired_at'] = $expired_at;
			$insertArr['description'] = htmlspecialchars(stripslashes($description));
			$db = getDbInstance();
			$last_id = $db->insert('for_transaction_list', $insertArr);

			if ( $last_id ) {
				jsonReturn($ok_json);
			} else {
				jsonReturn(array('code'=>811,'error'=>true,'msg'=>err_message(811)));
			}
		}
		break;


	case 'get_invoice':
		$order_id = (isset($_GET['order_id'])) ? $_GET['order_id'] : '';

		// 필수값 체크
		if ( empty($order_id) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}

		$return_url = '';
		$hosted_url = '';

		$db = getDbInstance();
		$db->where('order_num', $order_id);
		$infos = $db->getOne('for_transaction_list');
		if ( empty($infos) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		} else{
			$ok_json['order_id'] = $order_id;
			$ok_json['currency'] = $infos['from_currency'];
			$ok_json['amount'] = $infos['from_amount'];
			$ok_json['status'] = $infos['status'];
			//$ok_json['return_url'] = $return_url;
			//$ok_json['hosted_url'] = $hosted_url;
			$ok_json['created_at'] = $infos['created_at'];
			$ok_json['expired_at'] = $infos['expired_at'];
			$ok_json['details'] = array(
				'currency' => !empty($infos['to_currency']) ? $infos['to_currency'] : '',
				'amount' => !empty($infos['to_amount']) ? $infos['to_amount'] : '',
				'address' => !empty($infos['from_address']) ? $infos['from_address'] : '',
				'txid'=> !empty($infos['tx_id']) ? $infos['tx_id'] : '',
				'status' => !empty($infos['tx_status']) ? $infos['tx_status'] : '',
				'created_at' => !empty($infos['payment_at']) ? $infos['payment_at'] : ''
			);

			jsonReturn($ok_json);
		}


		break;


	
	case 'resolve_invoice':
		$order_id = (isset($_GET['order_id'])) ? $_GET['order_id'] : '';

		// 필수값 체크
		if ( empty($order_id) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}

		
		$db = getDbInstance();
		$db->where('order_num', $order_id);
		$infos = $db->getOne('for_transaction_list');
		if ( empty($infos) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		} else{
		}
		
		jsonReturn($ok_json);

		break;

	case 'refund_invoice':
		$order_id = (isset($_GET['order_id'])) ? $_GET['order_id'] : '';

		// 필수값 체크
		if ( empty($order_id) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}
		
		// order_id 체크
		$db = getDbInstance();
		$db->where('order_num', $order_id);
		$infos = $db->getOne('for_transaction_list');
		if ( empty($infos) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		} else{
		}

		jsonReturn($ok_json);

		break;

	case 'get_ex_rate':
		$from_currency = (isset($_GET['from_currency'])) ? $_GET['from_currency'] : '';
		$to_currency = (isset($_GET['to_currency'])) ? $_GET['to_currency'] : '';

		// 필수값 체크
		if ( empty($from_currency) || empty($to_currency) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}
		
		// 통화 체크
		if ( !in_array($from_currency, $b_from_currency) ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
		}
		// 통화 체크
		if ( !in_array($to_currency, $b_currency) ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
		}

		$ok_json['from_currency'] = $from_currency;
		$ok_json['to_currency'] = $to_currency;

		$db = getDbInstance();
		$from_amount = '';
		$to_amount = '';
		$from_amount = $db->where('module_name', 'krw_per_'.strtolower($from_currency).'_kiosk')->getValue('settings', 'value');
		$to_amount = $db->where('module_name', 'krw_per_'.strtolower($to_currency).'_kiosk')->getValue('settings', 'value');
		
		if ( empty($from_amount) || empty($to_amount) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}
		$ok_json['from_amount'] = $from_amount;
		$ok_json['to_amount'] = $to_amount;


		jsonReturn($ok_json);

		break;

	case 'withdrawal':

		$order_id = (isset($_GET['order_id'])) ? $_GET['order_id'] : '';
		$userId = (isset($_GET['userId'])) ? $_GET['userId'] : '';

		// 필수값 체크
		if ( empty($order_id) || empty($userId) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}

		// order_id 체크
		$db = getDbInstance();
		$db->where('order_num', $order_id);
		$infos = $db->getOne('for_transaction_list');
		if ( empty($infos) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		} else{
			// 만료일 체크
			$now = strtotime('Now');
			if ( $infos['expired_at'] < $now ) {
				// 만료일 경과
				jsonReturn(array('code'=>807,'error'=>true,'msg'=>err_message(807)));
			}
		}
		// user id 체크
		$db = getDbInstance();
		$db->where('id', $userId);
		$userData = $db->getOne('admin_accounts', 'wallet_address');
		if ( empty($userData) || empty($userData['wallet_address']) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}

		// 결제시작

		// 결제종료
		
		$ok_json['txid'] = 'transaction hash value';
		$ok_json['status'] = 'transaction hash result : success/failed/pending';
		jsonReturn($ok_json);
		
		break;

	case 'get_withdrawal':

		$order_id = (isset($_GET['order_id'])) ? $_GET['order_id'] : '';

		// 필수값 체크
		if ( empty($order_id) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}

		// order_id 체크
		$db = getDbInstance();
		$db->where('order_num', $order_id);
		$infos = $db->getOne('for_transaction_list');
		if ( empty($infos) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}

		$ok_json['status'] = !empty($infos['tx_status']) ? $infos['tx_status'] : '';
		$details = array(
			'txid' => !empty($infos['tx_id']) ? $infos['tx_id'] : '',
			'address' => !empty($infos['from_address']) ? $infos['from_address'] : '',
			'amount' => !empty($infos['from_amount']) ? $infos['from_amount'] : ''
		);
		$ok_json['details'] = $details;

		jsonReturn($ok_json);

		break;

	default:
		jsonReturn(array('code'=>802,'error'=>true,'msg'=>err_message(802)));
		break;



}


?>
