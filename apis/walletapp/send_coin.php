<?php
header('Content-Type: application/json');
// https://cybertronchain.com/apis/walletapp/send_coin.php
require_once '../config/config.php';
require_once '../config/config_walletapp.php';
require_once '../config/config_wallet_original.php';
require_once '../config/proc_config.php';

//$requestData = file_get_contents('php://input');
//$requestData = json_decode($requestData, true);
$requestData = $_POST;

$kind = (isset($requestData['kind'])) ? $requestData['kind'] : '';

$ok_json = array('code'=>200,'error'=>false, 'msg'=>wa_err_message(200));


switch($kind) {

	case 'coin_ctc':

		$member_no = (isset($requestData['member_no'])) ? $requestData['member_no'] : '';
		$to_address = (isset($requestData['to_address'])) ? $requestData['to_address'] : '';
		$coin_type = (isset($requestData['coin_type'])) ? $requestData['coin_type'] : '';
		$amount = (isset($requestData['amount'])) ? $requestData['amount'] : '';

		
		// 필수값 체크
		if ( empty($member_no) || empty($to_address) || empty($coin_type) || empty($amount) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>wa_err_message(804)));
		}

		if ( !is_numeric($amount) || $amount <= 0 ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>wa_err_message(805)));
		}

		// From Check
		$db = getDbInstance();
		$db->where('id', $member_no);
		$userData = $db->getOne('admin_accounts', 'wallet_address, email');
		if ( empty($userData) || empty($userData['wallet_address']) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}

		// To Check
		$db = getDbInstance();
		$db->orwhere('wallet_address', $to_address);
		$db->orwhere('virtual_wallet_address', $to_address);
		$toData = $db->getOne('admin_accounts', 'wallet_address, email');
		if ( empty($toData) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}
		$toAccount = $to_address;

		$token = strtolower($coin_type);
		$transfer_passwd_check = 'N';
		$id_auth_check = 'N';
		$amountToSend = $amount;
		$err_code = '';
		$err = ''; // unlock
		$unlocked = ''; // unlock

		// From 권한 체크

		$wallet_directory_root = '/var/www/html/wallet2';
		require($wallet_directory_root.'/includes/web3/vendor/autoload.php');
		//use Web3\Web3;
		//use Web3\Contract;

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
		$adminFee = 0;

		$tokenArr = $contractAddressArr[$token];
		$tokenAbi = $tokenArr['abi'];
		$tokenContractAddress = $tokenArr['contractAddress'];
		$decimalDigit = $tokenArr['decimal'];

		$adminAddress =	$n_master_wallet_address;
		$adminPassword =	$n_master_wallet_pass;

		$walletAddress = $userData['wallet_address'];
		$fromAccount = $walletAddress;
		$fromAccountPass = $userData['email'].$n_wallet_pass_key;
		
		$fee_type = '';
		if($userData['admin_type']=='admin' || $userData['transfer_approved'] != 'C'){
			$fee_type = 'ETH';
		} else {
			$fee_type = 'CTC';
		}

		// Coin 잔액 조회
		$getNewCoinBalance = $wi_wallet_process->wi_get_balance($token, $walletAddress, $contractAddressArr);
		if ( $getNewCoinBalance == -1 ) {
			jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
		}


		if ( $fee_type == 'ETH' ) {
			// ETH 잔액 조회
			$getNewBalance = $wi_wallet_process->wi_get_balance('eth', $walletAddress, $contractAddressArr);
			if ( $getNewBalance == -1 || $getNewBalance <= 0 ) {
				jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
			}

			
			if($getNewCoinBalance < trim($amount)){
				jsonReturn(array('code'=>603,'error'=>true,'msg'=>npro_err_message(603)));
			}

		} else {
						
			// 발송권한 체크
			$err_code = npro_send_approve_check($member_no, $token, $token);
			if ( $err_code != 200 ) {
				jsonReturn(array('code'=>$err_code,'error'=>true,'msg'=>npro_err_message($err_code)));
			}

			// 수수료 조회
			$getTokenFee = $db->where("module_name", 'send_token_fee')->getOne('settings');
			$adminFee = $getTokenFee['value'];

			// 잔액 조회
			if($getNewCoinBalance < trim($amount) + $adminFee){
				jsonReturn(array('code'=>603,'error'=>true,'msg'=>npro_err_message(603)));
			}


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

		$contract = new Contract($web3->provider, $testAbi);

		if ( $fee_type == 'ETH' ) {
			// 전송

			/*try {
				$contract->at($contractAddress)->send('transfer', $toAccount, $amountToSend, [
					'from' => $fromAccount,
					'gas' => '0x186A0',   //100000
					'gasprice'=>$gasPriceInWei
				], function ($err, $result) use ($contract, $fromAccount, $toAccount, &$transactionId) {
					if ($err !== null) {
						throw new Exception($err->getMessage(), 4);
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

				//$last_id_dts = new_set_send_err_log ('send', $token, $member_no, $toAccount, 'error', 'send'.$send_error_msg);
				//new_fn_logSave( 'Message : (' . $member_no . ', ctc) ' . $e->getMessage() . ', Code : ' . $e->getCode() . ', File : ' . $e->getFile() . ' on line ' . $e->getLine());

			}*/
			
			if(!empty($transactionId)){
				$last_id = new_set_user_transactions($token, $member_no, $toAccount, $amount, 0, 0, 'completed', $transactionId);
			} else {
				$err_code = '681';
			}

			$status = !empty($transactionId) ? 'send' : 'fail';
			$last_id_sl = new_set_user_transactions_all('send', $token, $member_no, '', $fromAccount, $toAccount, $amount, 0, $transactionId, $status, '', '', '', '');
			
			if ( $err_code == '661' || $err_code == '681' ) {
				jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
			}



		} else {
			// 전송
			$senderAccount = $n_master_wallet_address;
			$ownerAccount = $walletAddress;
			
			/*try {
				$contract->at($contractAddress)->send('transferFrom',$ownerAccount, $toAccount, $amountToSend, [
					'from' => $senderAccount,
					'gas' => '0x186A0',   //100000
					'gasprice'=>$gasPriceInWei
				], function ($err, $result) use ($contract, $ownerAccount, $toAccount, &$transactionId) {
					if ($err !== null) {
						throw new Exception($err->getMessage(), 5);
					}
					else {
						$transactionId = $result;
					}
				});
			
			} catch (Exception $e) {
				$err_code = '661';

				//$last_id_dts = new_set_send_err_log ('send', $token, $member_no, $toAccount, 'error', 'send'.$send_error_msg);
				//new_fn_logSave( 'Message : (' . $member_no . ', ctc, ' . $actualAmountToSendWithoutDecimal . ') ' . $e->getMessage() . ', Code : ' . $e->getCode() . ', File : ' . $e->getFile() . ' on line ' . $e->getLine());
			}
			*/

			$status = !empty($transactionId) ? 'send' : 'fail';
			$last_id_sl = new_set_user_transactions_all('send', $token, $member_no, '', $ownerAccount, $toAccount, $amount, $adminFee, $transactionId, $status, '', '', '', '');
					
			if(!empty($transactionId)) {
				$last_id = new_set_user_transactions($token, $member_no, $toAccount, $amount, 0, 0, 'completed', $transactionId);



				$adminTransactionId = '';
				$adminFeeInDecimal = bcmul($adminFee,1000000000000000000);
				$adminFeeInDecimal = dec2hex($adminFeeInDecimal);
				$adminFeeInDecimal = '0x'.$adminFeeInDecimal; // Must add 0x
				$senderAccount = $n_master_wallet_address;
				$toAccount2 = $n_master_wallet_address_fee;
				/*
				try {
					$contract->at($contractAddress)->send('transferFrom',$ownerAccount, $toAccount2, $adminFeeInDecimal, [
						'from' => $senderAccount,
						'gas' => '0x186A0',   //100000
						'gasprice'=>$gasPriceInWei
					], function ($err, $result) use ($contract, $ownerAccount,  &$adminTransactionId) {
						if ($err !== null) {
							$adminTransactionId = '';
							throw new Exception($err->getMessage(), 6);
						} else {
							$adminTransactionId = $result;
						}
					});
				} catch (Exception $e) {
					new_fn_logSave( 'Message : (' . $member_no . ', ctc, ' . $adminFee . ') ' . $e->getMessage() . ', Code : ' . $e->getCode() . ', File : ' . $e->getFile() . ' on line ' . $e->getLine());
				}
				*/

				if(!empty($adminTransactionId)) {
					$last_id = new_set_user_transactions('ctc', $member_no, $toAccount2, $adminFee, 0, 0, 'completed', $adminTransactionId);	
				//} else {
				//	$err_code = '681';
				}
					
				$status = !empty($adminTransactionId) ? 'send' : 'fail';
				$last_id_sl = new_set_user_transactions_all('send', 'ctc', $member_no, '', $ownerAccount, $toAccount2, $adminFee, 0, $adminTransactionId, $status, '', '', '', '');



			} else {
				
				$err_code = '681';
			}

			if ( $err_code == '661' || $err_code == '681' ) {
				jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
			}

		}


		break;

// Request : member_no, to_address, coin_type, amount
	case 'coin_other':

		$member_no = (isset($requestData['member_no'])) ? $requestData['member_no'] : '';
		$to_address = (isset($requestData['to_address'])) ? $requestData['to_address'] : '';
		$coin_type = (isset($requestData['coin_type'])) ? $requestData['coin_type'] : '';
		$amount = (isset($requestData['amount'])) ? $requestData['amount'] : '';

		
		// 필수값 체크
		if ( empty($member_no) || empty($to_address) || empty($coin_type) || empty($amount) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>wa_err_message(804)));
		}

		if ( !is_numeric($amount) || $amount <= 0 ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>wa_err_message(805)));
		}

		// From Check
		$db = getDbInstance();
		$db->where('id', $member_no);
		$userData = $db->getOne('admin_accounts', 'wallet_address, email');
		if ( empty($userData) || empty($userData['wallet_address']) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}

		// To Check
		$db = getDbInstance();
		$db->orwhere('wallet_address', $to_address);
		$db->orwhere('virtual_wallet_address', $to_address);
		$toData = $db->getOne('admin_accounts', 'wallet_address, email');
		if ( empty($toData) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}
		$toAccount = $to_address;

		$token = strtolower($coin_type);
		$transfer_passwd_check = 'N';
		$id_auth_check = 'N';
		$amountToSend = $amount;
		$err_code = '';
		$err = ''; // unlock
		$unlocked = ''; // unlock

		// From 권한 체크

		$wallet_directory_root = '/var/www/html/wallet2';
		require($wallet_directory_root.'/includes/web3/vendor/autoload.php');
		//use Web3\Web3;
		//use Web3\Contract;

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
		$getNewCtcBalance = 0;
		$adminFee = 0;

		$tokenArr = $contractAddressArr[$token];
		$tokenAbi = $tokenArr['abi'];
		$tokenContractAddress = $tokenArr['contractAddress'];
		$decimalDigit = $tokenArr['decimal'];

		$adminAddress =	$n_master_wallet_address;
		$adminPassword =	$n_master_wallet_pass;

		$walletAddress = $userData['wallet_address'];
		$fromAccount = $walletAddress;
		$fromAccountPass = $userData['email'].$n_wallet_pass_key;
		
		$fee_type = '';
		if($userData['admin_type']=='admin' || $userData['transfer_approved'] != 'C'){
			$fee_type = 'ETH';
		} else {
			$fee_type = 'CTC';
		}

		// Coin 잔액 조회
		$getNewCoinBalance = $wi_wallet_process->wi_get_balance($token, $walletAddress, $contractAddressArr);
		if ( $getNewCoinBalance == -1 ) {
			jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
		}

		if($getNewCoinBalance < trim($amount)){
			jsonReturn(array('code'=>603,'error'=>true,'msg'=>npro_err_message(603)));
		}


		if ( $fee_type == 'ETH' ) {
			// ETH 잔액 조회
			$getNewBalance = $wi_wallet_process->wi_get_balance('eth', $walletAddress, $contractAddressArr);
			if ( $getNewBalance == -1 || $getNewBalance <= 0 ) {
				jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
			}
		} else {


			// CTC 잔액 조회
			$getNewBalance = $wi_wallet_process->wi_get_balance('ctc', $walletAddress, $contractAddressArr);
			if ( $getNewBalance == -1 ) {
				jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
			}
			
			// 발송권한 체크
			$err_code = npro_send_approve_check($member_no, 'ctc', $token);
			if ( $err_code != 200 ) {
				jsonReturn(array('code'=>$err_code,'error'=>true,'msg'=>npro_err_message($err_code)));
			}
			$err_code = npro_send_approve_check($member_no, $token, $token);
			if ( $err_code != 200 ) {
				jsonReturn(array('code'=>$err_code,'error'=>true,'msg'=>npro_err_message($err_code)));
			}

			// 수수료 조회
			$getTokenFee = $db->where("module_name", 'send_token_fee')->getOne('settings');
			$adminFee = $getTokenFee['value'];

			// 잔액체크 - 수수료
			if($getNewBalance < $adminFee){
				jsonReturn(array('code'=>602,'error'=>true,'msg'=>npro_err_message(602)));
			}


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

		if ( $fee_type == 'ETH' ) {

			/*try {
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

				//$last_id_dts = new_set_send_err_log ('send', $token, $member_no, $toAccount, 'error', 'send'.$send_error_msg);
				//nproc_fn_logSave($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $err_code, array('User'=>$member_no, 'Coin'=>$token));
				
				
			}
			*/

			if(!empty($transactionId)){
				$last_id = new_set_user_transactions($token, $member_no, $toAccount, $amount, 0, 0, 'completed', $transactionId);
			} else {
				$err_code = '681';
			}

			$status = !empty($transactionId) ? 'send' : 'fail';
			$last_id_sl = new_set_user_transactions_all('send', $token, $member_no, '', $fromAccount, $toAccount, $amount, 0, $transactionId, $status, '', '', '', '');
			
			if ( $err_code == '661' || $err_code == '681' ) {
				jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
			}



		} else {
			$senderAccount = $n_master_wallet_address;
			$ownerAccount = $walletAddress;
			$nonce = "";
			$eth->getTransactionCount($senderAccount,'pending', function ($err, $getNonce) use (&$nonce) {
				if ($err !== null) {
					/* echo 'Error: ' . $err->getMessage();
					return; */
					$nonce = "";
				}
				else {
					$nonce = $getNonce->toString();
					$nonce = (int)$nonce+1;
				}
			});



			/*try {
				// send CTC Token to destination Address
				//$contract->at($contractAddress)->send('transfer',$toAccount, $amountToSend, [
				$otherTokenContract->at($tokenContractAddress)->send('transferFrom',$ownerAccount, $toAccount, $amountToSend, [
					'from' => $senderAccount,
					'nonce' => '0x'.dechex($nonce),
					'gasprice'=>$gasPriceInWei
				], function ($err, $result) use ($contract, $ownerAccount, $toAccount, &$transactionId) {
					if ($err !== null) {
						throw new Exception($err->getMessage(), 7);
					} else {
						$transactionId = $result;
					}
				});
			} catch (Exception $e) {
				//$send_error_msg = '';
				//if(stristr($e->getMessage(), 'gas required exceeds allowance') == TRUE) {
				//	$send_error_msg = '(gas required exceeds allowance)';
				//} else if(stristr($e->getMessage(), 'insufficient funds') == TRUE) {
				//	$send_error_msg = '(insufficient funds)';
				//}
				$err_code = '661';

				//$last_id_dts = new_set_send_err_log ('send', $token, $member_no, $toAccount, 'error', 'send'.$send_error_msg);
				//nproc_fn_logSave($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $err_code, array('User'=>$member_no, 'Coin'=>$token));

			}
			*/


			$status = !empty($transactionId) ? 'send' : 'fail';
			$last_id_sl = new_set_user_transactions_all('send', $token, $member_no, '', $ownerAccount, $toAccount, $amount, $adminFee, $transactionId, $status, '', '', '', '');
					
			if(!empty($transactionId)) {
				$last_id = new_set_user_transactions($token, $member_no, $toAccount, $amount, 0, 0, 'completed', $transactionId);


				$adminTransactionId = '';
				//$adminFeeInDecimal = $adminFee*1000000000000000000;
				$adminFeeInDecimal = bcmul($adminFee,1000000000000000000); // 201112
				$adminFeeInDecimal = dec2hex($adminFeeInDecimal);
				$adminFeeInDecimal = '0x'.$adminFeeInDecimal; // Must add 0x
				$senderAccount = $n_master_wallet_address;
				$toAccount2 = $n_master_wallet_address_fee;
				/*try {
					$contract->at($contractAddress)->send('transferFrom',$ownerAccount, $toAccount2, $adminFeeInDecimal, [
						'from' => $senderAccount,
						'gas' => '0x'.dechex(100000),   //100000
						'gasprice'=>$gasPriceInWei
					], function ($err, $result) use ($contract, $ownerAccount,  &$adminTransactionId) {
						if ($err !== null) {
							$adminTransactionId = '';
							throw new Exception($err->getMessage(), 8);
						} else {
							$adminTransactionId = $result;
						}
					});
				} catch (Exception $e) {
					new_fn_logSave( 'Message : (' . $member_no . ', ctc, ' . $adminFee . ') ' . $e->getMessage() . ', Code : ' . $e->getCode() . ', File : ' . $e->getFile() . ' on line ' . $e->getLine());
				}
				*/

				if(!empty($adminTransactionId)) {
					$last_id = new_set_user_transactions('ctc', $member_no, $toAccount2, $adminFee, 0, 0, 'completed', $adminTransactionId);	
				//} else {
				//	$err_code = '681';
				}
					
				$status = !empty($adminTransactionId) ? 'send' : 'fail';
				$last_id_sl = new_set_user_transactions_all('send', 'ctc', $member_no, '', $ownerAccount, $toAccount2, $adminFee, 0, $adminTransactionId, $status, '', '', '', '');



			} else {
				$err_code = '681';
			}
			
			if ( $err_code == '661' || $err_code == '681' ) {
				jsonReturn(array('code'=>809,'error'=>true,'msg'=>err_message(809)));
			}



		}
		
		

		break;



}


?>