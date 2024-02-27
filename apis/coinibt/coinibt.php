<?php
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/config_coinibt.php';

$requestData = file_get_contents('php://input');
$requestData = json_decode($requestData, true);

$auth_key = !empty($requestData['auth_key']) ? $requestData['auth_key'] : '';
$kind = !empty($requestData['kind']) ? $requestData['kind'] : '';

// cybertronchain.com/apis/coinibt/coinibt.php?auth_key=BE14273125KL


if (empty($kind) || empty($auth_key) || $auth_key != $w_api_key ) {
   jsonReturn(array('code'=>801,'error'=>true,'msg'=>err_message(801)));
}

$ok_json = array('code'=>200,'error'=>false, 'msg'=>err_message(200));


// API 요청 처리

switch($kind) {

	case 'get_user':
		
		$wallet_address = !empty($requestData['wallet_address']) ? $requestData['wallet_address'] : '';

		// 필수값 체크
		if ( empty($wallet_address) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}

		$db = getDbInstance();
//		if($_SERVER['REMOTE_ADDR'] == '112.171.120.140') {
			//휴면 회원 쪽 조회 START 2021.06.16 by.OJT 휴면 회원은 조회가 안되어야 함.
			$db->where('A.wallet_address', $wallet_address);
			$db->where('A.account_type2', 'wallet');
			$db->join('admin_accounts_sleep B', 'A.id = B.id', 'INNER');
			$userData = $db->getOne('admin_accounts A');
			//휴면 회원 쪽 조회 END
			//$userData = $db->get('admin_accounts A');
			if($userData){
				jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
			}
			else{
				$db->where('wallet_address', $wallet_address);
				$db->where('account_type2', 'wallet');
				$userData = $db->getOne('admin_accounts', array('email, name, lname, auth_name, auth_phone, phone, n_phone'));
			}
//		}
//		else{
//			$db->where('wallet_address', $wallet_address);
//			$db->where('account_type2', 'wallet');
//			$userData = $db->getOne('admin_accounts', array('email, name, lname, auth_name, auth_phone, phone, n_phone'));
//		}

		if ( empty($userData) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		} else {
			if ( !empty($userData['auth_name']) ) {
				$name = $userData['auth_name'];
			} else {
				$name = $userData['name'];
				if ( !empty($userData['lname']) ) {
					$name = $userData['lname'].$userData['name'];
				}
			}

			$phone = '';
			if ( !empty($userData['auth_phone']) ) {
				$phone = $userData['auth_phone'];
			} else if ( !empty($userData['n_phone']) ) {
				$phone = $userData['n_phone'];
			} else if ( !empty($userData['phone']) ) {
				$phone = $userData['phone'];
			}
			
			$ok_json['name'] = $name;
			$ok_json['phone'] = $phone;

			jsonReturn($ok_json);
		}

		break;
	
	case 'get_user2':
		
		$wallet_address = !empty($requestData['wallet_address']) ? $requestData['wallet_address'] : '';
		$phone_number = !empty($requestData['phone_number']) ? $requestData['phone_number'] : '';

		// 필수값 체크
		if ( empty($wallet_address) || empty($phone_number) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}

		$db = getDbInstance();
		//휴면 회원 쪽 조회 START 2021.06.16 by.OJT 휴면 회원은 조회가 안되어야 함.
		$db->where('A.wallet_address', $wallet_address);
		$db->where('A.account_type2', 'wallet');
		$db->join('admin_accounts_sleep B', 'A.id = B.id', 'INNER');
		$userData = $db->getOne('admin_accounts A');
		//휴면 회원 쪽 조회 END
		//$userData = $db->get('admin_accounts A');
		if($userData){
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		}
		else{
			$db->where('wallet_address', $wallet_address);
			$db->where('account_type2', 'wallet');
			$userData = $db->getOne('admin_accounts', array('email, name, lname, auth_name, auth_phone, phone, n_phone'));
		}
//		$db->where('wallet_address', $wallet_address);
//		$db->where('account_type2', 'wallet');
//		$userData = $db->getOne('admin_accounts', array('email, name, lname, auth_name, auth_phone, phone, n_phone'));
		
		if ( empty($userData) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		} else {
			$name = '';
			$phone = '';
			$auth_yn = 'N';

			if ( $phone_number == $userData['auth_phone'] ) {
				$auth_yn = 'Y';
			}

			if ( !empty($userData['auth_name']) ) {
				$name = $userData['auth_name'];
			} else {
				$name = $userData['name'];
				if ( !empty($userData['lname']) ) {
					$name = $userData['lname'].$userData['name'];
				}
			}

			if ( !empty($userData['auth_phone']) ) {
				$phone = $userData['auth_phone'];
			} else if ( !empty($userData['n_phone']) ) {
				$phone = $userData['n_phone'];
			} else if ( !empty($userData['phone']) ) {
				$phone = $userData['phone'];
			}
			
			$ok_json['auth_yn'] = $auth_yn;
			$ok_json['name'] = $name;
			$ok_json['phone'] = $phone;

			jsonReturn($ok_json);
		}

		break;
	
	case 'withdrawal_epay': // 출금하면, 지갑에 E-Pay로 입금처리됨
		$coin_type = !empty($requestData['coin_type']) ? $requestData['coin_type'] : '';
		$wallet_address = !empty($requestData['wallet_address']) ? $requestData['wallet_address'] : ''; // CTC Wallet.wallet_address
		$address = !empty($requestData['address']) ? $requestData['address'] : ''; // CoinIBT.users.eth_address / users.btc_address / ...
		$users_id = !empty($requestData['users_id']) ? $requestData['users_id'] : ''; // CoinIBT.users.id
		$amount = !empty($requestData['amount']) ? $requestData['amount'] : '';

		// 필수값 체크
		if ( empty($coin_type) || empty($wallet_address) || empty($amount) || empty($address) || empty($users_id) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
		}
		$token = strtolower($coin_type); // TP3 -> tp3
	
		if ( !is_numeric($amount) ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
		}

		if ( !in_array($coin_type, $coin_lists) ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
		}

		$from_id = '';
		$from_address = '';
		$to_id = '';
		$to_wallet_address = '';


		
		$db = getDbInstance();
		//if($_SERVER['REMOTE_ADDR'] == '112.171.120.140') {
			//휴면 회원 쪽 조회 START 2021.06.16 by.OJT 휴면 회원은 출금이 안되어야 함.
			$db->where('A.wallet_address', $wallet_address);
			$db->join('admin_accounts_sleep B', 'A.id = B.id', 'INNER');
			$userData = $db->getOne('admin_accounts A');
			if($userData){
				jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
			}
			//휴면 회원 쪽 조회 END
		//}
		$db->where('wallet_address', $wallet_address);
		$toData = $db->getOne('admin_accounts');

		if ( empty($toData) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
		} else {
			// 사용자에게 Coin이 들어감
			// 보내는사람 : account_type2='CoinIBT' 
			
			// 보내는사람 체크
			$db = getDbInstance();
			$db->where('wallet_address', $address);
			$fromData = $db->getOne('admin_accounts');
			if ( empty($fromData) || $fromData['account_type2'] != $con_exchange_type_value || $fromData['external_id'] != $users_id ) {
				jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
			}
			$from_id = $fromData['id'];
			$from_address = $fromData['wallet_address'];

			$to_id = $toData['id'];
			$to_wallet_address = $toData['wallet_address'];

			

			$db = getDbInstance();
			$db->where("id", $to_id);
			$updateArr = [];
			$updateArr['etoken_e'.$token] = $db->inc($amount);
			$last_id1 = $db->update('admin_accounts', $updateArr);

			if ( $last_id1 ) {
				$data_to_send_logs = [];
				$data_to_send_logs['user_id'] = $to_id;
				$data_to_send_logs['wallet_address'] = $to_wallet_address;
				$data_to_send_logs['coin_type'] = 'e'.$token;
				$data_to_send_logs['points'] = $amount;
				$data_to_send_logs['in_out'] = 'in';
				$data_to_send_logs['send_type'] = 'coinibt_withdrawal';
				$data_to_send_logs['send_user_id'] = $from_id;
				$data_to_send_logs['send_wallet_address'] = $from_address;
				$data_to_send_logs['send_fee'] = '0';
				$data_to_send_logs['created_at'] = date("Y-m-d H:i:s");
				
				$db = getDbInstance();
				$last_id_sl = $db->insert('etoken_logs', $data_to_send_logs);
			}



			$db = getDbInstance();
			$db->where("id", $from_id);
			$updateArr = [];
			$updateArr['etoken_e'.$token] = $db->dec($amount);
			$last_id2 = $db->update('admin_accounts', $updateArr);

			if ( $last_id2 ) {
				$data_to_send_logs = [];
				$data_to_send_logs['user_id'] = $from_id;
				$data_to_send_logs['wallet_address'] = $from_address;
				$data_to_send_logs['coin_type'] = 'e'.$token;
				$data_to_send_logs['points'] = '-'.$amount;
				$data_to_send_logs['in_out'] = 'out';
				$data_to_send_logs['send_type'] = 'coinibt_withdrawal';
				$data_to_send_logs['send_user_id'] = $to_id;
				$data_to_send_logs['send_wallet_address'] = $to_wallet_address;
				$data_to_send_logs['send_fee'] = '0';
				$data_to_send_logs['created_at'] = date("Y-m-d H:i:s");
				
				$db = getDbInstance();
				$last_id_sl2 = $db->insert('etoken_logs', $data_to_send_logs);
			}

		}

		jsonReturn($ok_json);

		break;	
		
	case 'withdrawal_epay_only_my_account': // 출금하면, 지갑에 E-Pay로 입금처리됨 (오직 내 지갑 주소로만)
        // Coin IBT Main 에서는 자기 지갑에만 출금 가능
        $coin_type = !empty($requestData['coin_type']) ? $requestData['coin_type'] : '';
        $wallet_address = !empty($requestData['wallet_address']) ? $requestData['wallet_address'] : ''; // CTC Wallet.wallet_address
        $address = !empty($requestData['address']) ? $requestData['address'] : ''; // CoinIBT.users.eth_address / users.btc_address / ...
        $users_id = !empty($requestData['users_id']) ? $requestData['users_id'] : ''; // CoinIBT.users.id
        $amount = !empty($requestData['amount']) ? $requestData['amount'] : '';

        // 필수값 체크
        if ( empty($coin_type) || empty($wallet_address) || empty($amount) || empty($address) || empty($users_id) ) {
            jsonReturn(array('code'=>804,'error'=>true,'msg'=>err_message(804)));
        }
        $token = strtolower($coin_type); // TP3 -> tp3

        if ( !is_numeric($amount) ) {
            jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
        }

        if ( !in_array($coin_type, $coin_lists) ) {
            jsonReturn(array('code'=>805,'error'=>true,'msg'=>err_message(805)));
        }

        $from_id = '';
        $from_address = '';
        $to_id = '';
        $to_wallet_address = '';

        $db = getDbInstance();
        //if($_SERVER['REMOTE_ADDR'] == '112.171.120.140') {
        //휴면 회원 쪽 조회 START 2021.06.16 by.OJT 휴면 회원은 출금이 안되어야 함.
        $db->where('A.wallet_address', $wallet_address);
        $db->join('admin_accounts_sleep B', 'A.id = B.id', 'INNER');
        $userData = $db->getOne('admin_accounts A');
        if($userData){
            jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
        }
        //휴면 회원 쪽 조회 END
        //}

        $db->where('wallet_address', $wallet_address);
        $toData = $db->getOne('admin_accounts');
        if ( empty($toData) ) {
            jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
        }
        else {
            // 사용자에게 Coin이 들어감
            // 보내는사람 : account_type2='CoinIBT'

            // 보내는사람 체크
            $fromData = $db->where('wallet_address', $address)
                ->where('external_id', $users_id)
                ->where('account_type2', $con_exchange_type_value)
                ->getOne('admin_accounts','id, wallet_address, external_id, external_phone, account_type2');

            $memberInfo = $db->where('n_phone',$fromData['external_phone'])
                ->getOne('admin_accounts','id');
            if(!$memberInfo){
                jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(406)));
            }
            if ( empty($fromData)) {
                jsonReturn(array('code'=>806,'error'=>true,'msg'=>err_message(806)));
            }
            $from_id = $fromData['id'];
            $from_address = $fromData['wallet_address'];

            $to_id = $toData['id'];
            $to_wallet_address = $toData['wallet_address'];

            $db->where("id", $to_id);
            $updateArr = [];
            $updateArr['etoken_e'.$token] = $db->inc($amount);
            $last_id1 = $db->update('admin_accounts', $updateArr);

            if ( $last_id1 ) {
                $data_to_send_logs = [];
                $data_to_send_logs['user_id'] = $to_id;
                $data_to_send_logs['wallet_address'] = $to_wallet_address;
                $data_to_send_logs['coin_type'] = 'e'.$token;
                $data_to_send_logs['points'] = $amount;
                $data_to_send_logs['in_out'] = 'in';
                $data_to_send_logs['send_type'] = 'coinibt_withdrawal';
                $data_to_send_logs['send_user_id'] = $from_id;
                $data_to_send_logs['send_wallet_address'] = $from_address;
                $data_to_send_logs['send_fee'] = '0';
                $data_to_send_logs['created_at'] = date("Y-m-d H:i:s");

                $db = getDbInstance();
                $last_id_sl = $db->insert('etoken_logs', $data_to_send_logs);
            }

            $db = getDbInstance();
            $db->where("id", $from_id);
            $updateArr = [];
            $updateArr['etoken_e'.$token] = $db->dec($amount);
            $last_id2 = $db->update('admin_accounts', $updateArr);

            if ( $last_id2 ) {
                $data_to_send_logs = [];
                $data_to_send_logs['user_id'] = $from_id;
                $data_to_send_logs['wallet_address'] = $from_address;
                $data_to_send_logs['coin_type'] = 'e'.$token;
                $data_to_send_logs['points'] = '-'.$amount;
                $data_to_send_logs['in_out'] = 'out';
                $data_to_send_logs['send_type'] = 'coinibt_withdrawal';
                $data_to_send_logs['send_user_id'] = $to_id;
                $data_to_send_logs['send_wallet_address'] = $to_wallet_address;
                $data_to_send_logs['send_fee'] = '0';
                $data_to_send_logs['created_at'] = date("Y-m-d H:i:s");

                $db = getDbInstance();
                $last_id_sl2 = $db->insert('etoken_logs', $data_to_send_logs);
            }
        }

        jsonReturn($ok_json);

        break;
		
	// 정의되지 않은 요청 구분 코드
	default:
		jsonReturn(array('code'=>802,'error'=>true,'msg'=>err_message(802)));
		break;
} // switch



?>
