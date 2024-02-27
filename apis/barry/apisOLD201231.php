<?php
//사용안함 apisTEST_201231 버전 입니다.
exit();
require_once '../config/config.php';
require_once '../config/config_barry.php';

include_once($barry_encrypt_root.'/barryEncrypt/Rsa.php');
use barry\encrypt\Rsa as barryRsa;

$encc = new barryRsa;


// https://cybertronchain.com/apis/barry/apis.php

## 공통
$ckey = (isset($_POST['ckey'])) ? $_POST['ckey'] : '';
$kind = (isset($_POST['kind'])) ? $_POST['kind'] : '';


//$tmp = '111111';
//$ee1 = $encc->encrypt($tmp);
//echo $ee1.'<br />';
//echo $encc->decrypt($ee1);
//echo '<Br />';
//echo $encc->decrypt('xscKZtsw6dUd9qQvV0TsZ9poSLMumdQMgwiIYsad1jfDk1fZj7zlcefiG6i+J1CChUo4KVFWSyptWtawJ54b9MlZ00kKz3H0h3oHudI/g3YwUUPGeoTj9P1E2lgNA2Cake7dwG/ZwOQ4SzqlEiRR/I2XaDCYSIrc3cgCF7i4MAU0ldI64psx4AOjJXy/iVJtVDTqxMEipXE8QD0apmtiHKU/pVOhS1VUxQV2ZLeGtirYIceTrx0y3RKpH3iqu8/AIpYiHkK9MKvPK4xIEaTDB6fbjtZGnhzJmt0QuQAVmp6RJ4f/dDi6No6dsJwYg028OghpPdMhjXm9rjqjdmbdEw==');

if (empty($kind)) {
	jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
}
if (empty($ckey)) {
	jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
}

$ok_json = array('code'=>'00','msg'=>'ok');

//
## API 요청 처리

if ($kind == 'check') {
	
    $user_id = (isset($_POST['user_id'])) ? $_POST['user_id'] : '';

    if (empty($user_id)) {
        jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
    }

	$db = getDbInstance();
	$db->where('id', $user_id);
	$db->where('ckey', $ckey);
	$count = $db->getValue('admin_accounts', 'count(*)');
	
	if ( $count == 0 ) {
		jsonReturn(array('code'=>'77','msg'=>'잘못된 사용자입니다.'));
	} else {
	    jsonReturn($ok_json);
	}

} else if ($kind == 'passwd') {
	
    $user_id = (isset($_POST['user_id'])) ? $_POST['user_id'] : '';
    $user_pw = (isset($_POST['user_pw'])) ? $_POST['user_pw'] : '';

    if (empty($user_id)) {
        jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($user_pw)) {
        jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
    }
	$user_pw = $encc->decrypt($user_pw);

	$db = getDbInstance();
	$db->where('id', $user_id);
	$db->where('ckey', $ckey);
	$userData = $db->getOne('admin_accounts');
	
	if ( empty($userData) ) {
		jsonReturn(array('code'=>'77','msg'=>'잘못된 사용자입니다.'));
	} else {
		
		$user_id_auth = 'N';
		if ( !empty($userData['id_auth']) && $userData['id_auth'] == 'Y' ) {
			$user_id_auth = 'Y';
		}
		$ip_kor = '';
		$ip_kor = trim(new_ipinfo_ip_chk('2'));
		if ($ip_kor == 'KR' && $user_id_auth != 'Y') {
			jsonReturn(array('code'=>'44','msg'=>'본인인증 후 이용할 수 있습니다.'));
		}

		if ( empty($userData['transfer_passwd'])) {
			jsonReturn(array('code'=>'66','msg'=>'전송비밀번호를 셋팅하지 않은 사용자입니다.'));
		} else {
			if ( password_verify($user_pw, $userData['transfer_passwd'])) { // 입력문자열, 해쉬
				// 비밀번호 일치시 처리 시작

				$stf_count = !empty($userData['transfer_pw_count']) ? $userData['transfer_pw_count'] : '0';
				$stf_date = $userData['transfer_pw_date'];
				if ( !empty($stf_date) && $stf_date != date("Y-m-d") ) { // 날짜 다르면 초기화
					$stf_count = 0;
					$db = getDbInstance();
					$db->where ("id", $userData['id']);
					$updateArr = [] ;
					$updateArr['transfer_pw_count'] =  NULL;
					$updateArr['transfer_pw_date'] =  NULL;
					$last_id = $db->update('admin_accounts', $updateArr);
				}
				if ($stf_count >= $n_transfer_pw_count ) {
					// 횟수 초과시
					jsonReturn(array('code'=>'55','msg'=>'결제비밀번호 입력 횟수가 초과되었습니다. 다음날 다시 시도해주세요.'));
				} else {
					jsonReturn($ok_json);
				}

				// 비밀번호 일치시 처리 종료
			} else {
				jsonReturn(array('code'=>'77','msg'=>'잘못된 사용자입니다.'));
			}
		}
	}
	
	//jsonReturn($ok_json);

} else if ($kind == 'payment') {
	
    $seller_user_id = (isset($_POST['seller_user_id'])) ? $_POST['seller_user_id'] : '';
    $seller_address = (isset($_POST['seller_address'])) ? $_POST['seller_address'] : '';
    $buyer_user_id = (isset($_POST['buyer_user_id'])) ? $_POST['buyer_user_id'] : '';
    $amount = (isset($_POST['amount'])) ? $_POST['amount'] : '';
    $unit = (isset($_POST['unit'])) ? $_POST['unit'] : '';

    if (empty($seller_user_id)) {
        jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($seller_address)) {
        jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($buyer_user_id)) {
        jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($amount)) {
        jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($unit)) {
        jsonReturn(array('code'=>'99','msg'=>'필수값이 누락되었습니다.'));
    }


	if ( $amount <= 0 ) {
        jsonReturn(array('code'=>'66','msg'=>'잘못된 요청입니다.'));
	}
	if ( $unit != 'ETP3' && $unit != 'EMC' ) {
        jsonReturn(array('code'=>'66','msg'=>'잘못된 요청입니다.'));
	}

	$token = strtolower($unit);



	$db = getDbInstance();
	$db->where('id', $buyer_user_id);
	$db->where('ckey', $ckey);
	$userData = $db->getOne('admin_accounts');
	
	if ( empty($userData) ) {
		jsonReturn(array('code'=>'77','msg'=>'잘못된 사용자입니다.'));
	} else {
		$db = getDbInstance();
		$db->where('id', $seller_user_id);
		$seller_userData = $db->getOne('admin_accounts');
		if ( empty($seller_userData) || $seller_userData['virtual_wallet_address'] != $seller_address ) {
			jsonReturn(array('code'=>'77','msg'=>'잘못된 사용자입니다.'));
		} else {
			// 성공시

			if ( $userData['etoken_use'] == 'N' ) {
				jsonReturn(array('code'=>'12','msg'=>'처리에 실패하였습니다.'));
			}

			$ectc_balance = $userData['etoken_ectc'];
			$coin_balance = $userData['etoken_'.strtolower($unit)];
			
			

			$db = getDbInstance();

			$module_name = 'send_etoken_fee';
			if ( $userData['transfer_approved'] != 'C' ) {
				$module_name = 'send_etoken_fee_eth';
			}
			if ( $userData['transfer_fee_type'] == 'H' ) {
				$module_name = 'send_etoken_fee_h';
			}

			$getTokenFee = $db->where("module_name", $module_name)->getOne('settings');
			$getTokenFeeVal = $getTokenFee['value'];

			$getMinAmount = $db->where("module_name", 'min_send_amount_'.strtolower($unit))->getOne('settings');
			$getMinAmountVal = $getMinAmount['value'];

			// 최소전송금액 체크
			if ( !empty($getMinAmountVal) && $getMinAmountVal > $amount ) {
				jsonReturn(array('code'=>'33','msg'=>'전송에 실패하였습니다.(최소전송금액)'));
			}
			
			// 구매자 잔액 체크
			if ( $amount > $coin_balance ) {
				jsonReturn(array('code'=>'55','msg'=>'잔액이 부족합니다.'));
			}


			// 구매자 수수료 체크
			if ( $getTokenFeeVal > 0 && $getTokenFeeVal > $ectc_balance ) {
				jsonReturn(array('code'=>'44','msg'=>'수수료가 부족합니다.'));
			}

			// eCTC 수수료 받는사람 설정
			if ( $getTokenFeeVal > 0 ) {
				$receive_fee_id = $n_master_etoken_ctc_fee_id;
				$receiver_fee_address = $n_master_etoken_ctc_fee_wallet_address;
			}
			
			$send_type = 'barry';
			
			// 구매자 차감
			$db = getDbInstance();
			$db->where("id", $buyer_user_id);
			$updateArr = [];
			if ( $token == 'ectc' ) {
				$tmp = $amount + $getTokenFeeVal;
				$updateArr['etoken_'.$token] = $db->dec($tmp);
			} else {
				if ( $getTokenFeeVal > 0 ) {
					$updateArr['etoken_ectc'] = $db->dec($getTokenFeeVal);
				}
				$updateArr['etoken_'.$token] = $db->dec($amount);
			}
			$last_id = $db->update('admin_accounts', $updateArr);

			if ( $last_id) {
				// e-Pay out
				$data_to_log = [];
				$data_to_log['user_id'] = $buyer_user_id;
				$data_to_log['wallet_address'] = $userData['wallet_address'];
				$data_to_log['coin_type'] = $token;
				$data_to_log['points'] = '-'.$amount;
				$data_to_log['in_out'] = 'out';
				if ( !empty($send_type) ) {
					$data_to_log['send_type'] = $send_type;
				}
				$data_to_log['send_user_id'] = $seller_user_id;
				$data_to_log['send_wallet_address'] = $seller_address;
				$data_to_log['send_fee'] = $getTokenFeeVal;
				$data_to_log['created_at'] = date("Y-m-d H:i:s");
				$db = getDbInstance();
				$last_id_sl = $db->insert('etoken_logs', $data_to_log);

				if ( $getTokenFeeVal > 0 ) {
					// eCTC out
					$data_to_log2 = [];
					$data_to_log2['user_id'] = $buyer_user_id;
					$data_to_log2['wallet_address'] = $userData['wallet_address'];
					$data_to_log2['coin_type'] = 'ectc';
					$data_to_log2['points'] = '-'.$getTokenFeeVal;
					$data_to_log2['in_out'] = 'out';
					if ( !empty($send_type) ) {
						$data_to_log2['send_type'] = $send_type;
					}
					$data_to_log2['send_user_id'] = $receive_fee_id;
					$data_to_log2['send_wallet_address'] = $receiver_fee_address;
					$data_to_log2['send_fee'] = '0';
					$data_to_log2['created_at'] = date("Y-m-d H:i:s");
					$db = getDbInstance();
					$last_id_sl2 = $db->insert('etoken_logs', $data_to_log2);
				}
			}

			// 판매자 +

			if ( $send_type != 'barry' ) { // 가상주소가 받을 때에는 합계를 내지 않음
				$db = getDbInstance();
				$db->where("id", $seller_user_id);
				$updateArr = [];
				$updateArr['etoken_'.$token] = $db->inc($amount);
				$last_id3 = $db->update('admin_accounts', $updateArr);
			}
			$data_to_log = [];
			$data_to_log['user_id'] = $seller_user_id;
			$data_to_log['wallet_address'] = $seller_address;
			$data_to_log['coin_type'] = $token;
			$data_to_log['points'] = '+'.$amount;
			$data_to_log['in_out'] = 'in';
			if ( !empty($send_type) ) {
				$data_to_log['send_type'] = $send_type;
			}
			$data_to_log['send_user_id'] = $buyer_user_id;
			$data_to_log['send_wallet_address'] = $userData['wallet_address'];
			$data_to_log['send_fee'] = '0';
			$data_to_log['created_at'] = date("Y-m-d H:i:s");
			$db = getDbInstance();
			$last_id_sl3 = $db->insert('etoken_logs', $data_to_log);
			
			
			// eCTC in

			if ( $getTokenFeeVal > 0 ) {
				$updateArr = [];
				$db = getDbInstance();
				$db->where("id", $receive_fee_id);
				$updateArr['etoken_ectc'] = $db->inc($getTokenFeeVal);
				$last_id4 = $db->update('admin_accounts', $updateArr);

				if ( $last_id4 ) {
					$data_to_log2 = [];
					$data_to_log2['user_id'] = $receive_fee_id;
					$data_to_log2['wallet_address'] = $receiver_fee_address;
					$data_to_log2['coin_type'] = 'ectc';
					$data_to_log2['points'] = '+'.$getTokenFeeVal;
					$data_to_log2['in_out'] = 'in';
					if ( !empty($send_type) ) {
						$data_to_log2['send_type'] = $send_type;
					}
					$data_to_log2['send_user_id'] = $buyer_user_id;
					$data_to_log2['send_wallet_address'] = $userData['wallet_address'];
					$data_to_log2['send_fee'] = '0';
					$data_to_log2['created_at'] = date("Y-m-d H:i:s");
					$db = getDbInstance();
					$last_id_sl4 = $db->insert('etoken_logs', $data_to_log2);
				}

			}
			

		//	$from_name = 'From Test';
		//	$subject = 'Subject Test';
			//$contents = 'Contents Test';
		//	$country = '82';
		//	$phone = '01049138089';
		//	$email = 'mjyoo09@onefamilymall.com';
			//$send_mail_result = $wi_send_mail->send_sms ($country, $phone, $contents);
			//if ( $send_mail_result ) {
			//	echo ' / sms : success / ';
			//}
		//	$contents[0] = 'Contents Test';
		//	$send_email_result = $wi_send_mail->send_email ($email, $subject, $contents);
		//	if ( $send_email_result ) {
		//		echo ' / email : success / ';
			//}

			if ( $last_id_sl ) {

				$_SESSION['lang'] = 'ko';
				require_once $wallet_directory_root.'/lib/SendMail.php';
				$wi_send_mail = new SendMail();

				$send_mail_result = '';

				$from_name = get_user_real_name($userData['auth_name'], $userData['name'], $userData['lname']);
				$coin_type = $token;
				$coin_type2 = lcfirst(strtoupper($coin_type));

				$langFolderPath = file_get_contents($wallet_directory_root."/lang/".$_SESSION['lang']."/index.json");
				$langArr = json_decode($langFolderPath,true);

				$subject = !empty($langArr['send_sms_message3']) ? $langArr['send_sms_message3'] : 'CyberTronChain : Coin has been sent.';
				$alert_msg = '';
				if ( $send_type != 'barry' ) {
					$send_sms_message1 = !empty($langArr['send_sms_message1']) ? $langArr['send_sms_message1'] : ' sent ';
					$alert_msg = $from_name.$send_sms_message1.new_number_format($amount, $n_decimal_point_array2[$coin_type]).$coin_type2;
					$alert_msg .= isset($langArr['send_sms_message2']) ? $langArr['send_sms_message2'] : '';
				} else {
					$send_sms_vertual_message1= !empty($langArr['send_sms_vertual_message1']) ? $langArr['send_sms_vertual_message1'] : " sent ";
					$send_sms_vertual_message2 = !empty($langArr['send_sms_vertual_message2']) ? $langArr['send_sms_vertual_message2'] : " for the purchase of goods.";
					$alert_msg = $from_name.$send_sms_vertual_message1.new_number_format($amount, $n_decimal_point_array2[$coin_type]).$coin_type2.$send_sms_vertual_message2;
				}
				if ( $seller_userData['register_with'] == 'phone' || ($seller_userData['id_auth'] == 'Y' && !empty($seller_userData['auth_phone']) ) ) {

					if ( $seller_userData['id_auth'] == 'Y' ) { // 본인인증한 경우
						if ( !empty($seller_userData['n_country']) ) {
							$country = $seller_userData['n_country'];
						} else{
							$country = '82';
						}
						$phone = $seller_userData['auth_phone'];
					} else {
						$country = $seller_userData['n_country'];
						$phone = $seller_userData['n_phone'];
					}
					$contents = $alert_msg;
					$send_mail_result = $wi_send_mail->send_sms ($country, $phone, $contents);

				} else {
					$contents[0] = $alert_msg;
					$send_mail_result = $wi_send_mail->send_email ($seller_userData['email'], $subject, $contents);
				}
				
			


			} // if
			

		}		
	}
	
	jsonReturn($ok_json);
} else {
    jsonReturn(array('code'=>'88','msg'=>'알수없는 요청입니다.'));
}



function jsonReturn($arr='') {
    if (empty($arr)) {
        $arr = array('code'=>'77','msg'=>'Error');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    } else {
        if (is_array($arr)) {
            echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('code'=>'77','msg'=>$arr), JSON_UNESCAPED_UNICODE);
        }
    }
    logWrite($arr);
    exit();
}

function logWrite($arr='') {
    $fname = "/var/www/html/apis/barry/logs/" . date('Y-m-d') . ".txt";
    $f = fopen($fname, "a");
    fwrite($f, "[".date('Y-m-d H:i:s')."] : ".$_SERVER['REMOTE_ADDR']."\n");
    fwrite($f, "[REQ] ---------------\n");
    foreach($_POST as $k => $v) {
		if ( $k == 'user_pw' || $k == 'ckey') {
	        fwrite($f, '    '.$k.'='."\n");
		} else {
	        fwrite($f, '    '.$k.'='.$v."\n");
		}
    }
    fwrite($f, "[RET] ---------------\n");
    if (is_array($arr)) {
        foreach($arr as $k => $v) {
            if (is_array($v)) {
                fwrite($f, '    '.$k.'='.print_r($v, true)."\n");
            } else {
                fwrite($f, '    '.$k.'='.$v."\n");
            }
        }
    } else {
        fwrite($f, '    '.$arr."\n");
    }
    fwrite($f, "\n");
    fwrite($f, "========================================\n\n");
    fclose($f);
}
