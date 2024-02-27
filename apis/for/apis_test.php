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


	/*
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
	*/

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
		$userData = $db->getOne('admin_accounts', 'wallet_address, email');
		if ( empty($userData) || empty($userData['wallet_address']) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}

		// 결제시작

		$amount = $infos['from_amount'];
		$token = strtolower($infos['from_currency']);
		$transfer_passwd_check = 'N';
		$id_auth_check = 'N';
		$amountToSend = $amount;
		$err_code = '';
		$err = ''; // unlock
		$unlocked = ''; // unlock
		$toAccount = '0xeefd4e236dfac8f3e4f76890600ac41cb2eb6286'; // ---------------------------------------------------------------------------------------

		define('WALLET_BASE_PATH', '/var/www/html/wallet2');
		require(WALLET_BASE_PATH.'/includes/web3/vendor/autoload.php');
		//use Web3\Web3;
		//use Web3\Contract;

		$wallet_directory_root = '/var/www/html/wallet2';
		require_once $wallet_directory_root.'/config/new_config.php';
		require_once '../config/config_wallet_original.php';

		require_once WALLET_BASE_PATH.'/lib/WalletProcess.php';
		$wi_wallet_process = new WalletProcess();

		$web3 = new Web3\Web3('http://'.$n_connect_ip.':'.$n_connect_port.'/');
		$eth = $web3->eth;

				
		$gasPriceInWei = 40000000000;
		$eth->gasPrice(function($err,$result)use(&$gasPriceInWei){
			$gasPriceInWei = $result->toString();
		});
		$gasPriceInWei = "0x".dechex($gasPriceInWei);
		$ok_json['gasPriceInWei'] = $gasPriceInWei;


		$getNewBalance = 0;
		$getNewCoinBalance = 0 ;

		$tokenArr = $contractAddressArr[$token];
		$tokenAbi = $tokenArr['abi'];
		$tokenContractAddress = $tokenArr['contractAddress'];
		$decimalDigit = $tokenArr['decimal'];

		$adminAddress =	$n_master_wallet_address;
		$adminPassword =	$n_master_wallet_pass;

		$walletAddress = $userData['wallet_address'];
		$fromAccount = $walletAddress;
		$fromAccountPass = $userData['email'].$n_wallet_pass_key;


	
		// USDT는 승인받은 사람이없기 때문에 무조건 수수료 ETH로 나감

		// ETH 잔액 조회
		$getNewBalance = $wi_wallet_process->wi_get_balance('eth', $walletAddress, $contractAddressArr);
		if ( $getNewBalance == -1 || $getNewBalance <= 0 ) {
			jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
		}
		// Coin 잔액 조회
		$getNewCoinBalance = $wi_wallet_process->wi_get_balance($token, $walletAddress, $contractAddressArr);
		if ( $getNewCoinBalance == -1 ) {
			jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
		}


		
		$personal = $web3->personal;
		try {
			$personal->unlockAccount($adminAddress, $adminPassword, function ($err, $unlocked) {
				if ($err !== null || !$unlocked) {
					throw new Exception($err->getMessage(), 3);
				}
			});
		} catch (Exception $e) {
			jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
		}

		if($getNewCoinBalance < trim($amount)){
			jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
		}


		$personal = $web3->personal;
		try {
			$personal->unlockAccount($fromAccount, $fromAccountPass, function ($err, $unlocked) {
				if ($err !== null || !$unlocked ) {
					throw new Exception($err->getMessage(), 4);
				}
			});
		} catch (Exception $e) {
			jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
		}

		$amountToSend = bcmul($amountToSend,$decimalDigit);

		$amountToSend = dec2hex($amountToSend);
		$amountToSend = '0x'.$amountToSend; // Must add 0x
		$gas = '0x9088';
		$transactionId = '';
		
		/*
		try {
			$otherTokenContract = new Web3\Contract($web3->provider, $tokenAbi);
			$otherTokenContract->at($tokenContractAddress)->send('transfer', $toAccount, $amountToSend, [
				'from' => $fromAccount,
				'gas' => '0x186A0',   //100000
				'gasprice'=>$gasPriceInWei
			], function ($err, $result) use ( $fromAccount, $toAccount,&$transactionId) {
				if ($err !== null) {
					throw new Exception($err->getMessage(), 5);
				} 
				$transactionId = $result;
			});
		} catch (Exception $e) {
			//$send_error_msg = '';
			//if(stristr($e->getMessage(), 'gas required exceeds allowance') == TRUE) {
			//	$send_error_msg = '(gas required exceeds allowance)';
			//} else if(stristr($e->getMessage(), 'insufficient funds') == TRUE) {
			//	$send_error_msg = '(insufficient funds)';
			//}
			$err_code = '661';

			//$last_id_dts = new_set_send_err_log ('send', $token, $userId, $toAccount, 'error', 'send'.$send_error_msg);
			//nproc_fn_logSave($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $err_code, array('User'=>$userId, 'Coin'=>$token));
			
			
		}*/
		


		if(!empty($transactionId)){
			$last_id = new_set_user_transactions($token, $userId, $toAccount, $amount, 0, 0, 'completed', $transactionId);
		} else {
			$err_code = '681';
		}

		$status = !empty($transactionId) ? 'send' : 'fail';
		$last_id_sl = new_set_user_transactions_all('send', $token, $userId, '', $fromAccount, $toAccount, $amount, 0, $transactionId, $status, '', '', '', '');
		
		if ( $err_code == '661' || $err_code == '681' ) {
			jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
		}

		
		// 결제종료

		$ok_json['txid'] = $transactionId;
		$ok_json['status'] = 'pending';
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




function dec2hex($number)
{
    $hexvalues = array('0','1','2','3','4','5','6','7',
               '8','9','A','B','C','D','E','F');
    $hexval = '';
     while($number != '0')
     {
        $hexval = $hexvalues[bcmod($number,'16')].$hexval;
        $number = bcdiv($number,'16',0);
    }
    return $hexval;
}


?>
