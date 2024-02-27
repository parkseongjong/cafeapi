<?php
/*
	apisTEST_201231 버전 적용

// 20.12.31

// 21.02.09
// $coin_list_arr 추가
// kind=getprice 추가


// 21.03.25
1. if문을 switch-case로 변경
2. barryapi_err_message 추가
3. 불필요한 주석 제거
3. 코드 중간중간에 있던 $db = getDbInstance();를 맨 위에 하나만 둠
4. $_POST => $requestData
5. $coin_list_arr_low 추가
6. 배송완료(finish)에서 모듈 이름 가져오는 부분변경
6-1. /apis/config/config_barrry.php에 추가 : barryapi_set_module_name, barryapi_name_ecoin_to_coin
6-2. list($module_name, $module_name2) = barryapi_set_module_name($coin_unit);
7. getprice, getprice2 
 - 리턴되는 unit : E-TP3 형식으로 리턴됨 (eTP3, ETP3, e-TP3, eTP3 아님)
8. 결제요청(payment)시 unit를 ETP3 뿐만 아니라 E-TP3로도 결제가능하도록 수정!




// 21.03.22 수정된 내용 : [주석 참고 : 21.03.22]
// 결제요청시 베리베리 상품정보 저장
// 베리베리 상품정보 저장 테이블					wallet.barry_prod_list
// 거래 데이터에 베리베리 상품정보 추가			wallet.etoken_logs.barry_prod_id : wallet.barry_prod_list.id
//  /apis/config/config_barry.php			barryapi_set_barry_prod(), barryapi_cut_product_name() 추가

*** 테스트 내용
1. 동일 상품을 결제했을 때 : barry_prod_list 테이블에 2번 들어가지 않아야 함																	=> 확인완료
2. 결제시 etoken_logs 테이블의 barry_prod_id에 값이( barry_prod_list 테이블의 id값) 제대로 들어가나 확인					=> 확인완료
3. 상품 정보가 넘어오지 않을 경우
    3-1. 문자발송 : OOO 님이 상품구매비용으로 2,272eTP3 전송하였습니다.																		=> 확인완료
	3-2. wallet.barry_prod_list에 insert되지 않아야 함																									=> 확인완료
	3-3. wallet.etoken.logs.barry_prod_id = NULL																									=> 확인완료
4. 상품 정보가 넘어온 경우
	4-1. 문자발송 : OOO 님이 골드폼클렌징... 구매비용으로 2,272eTP3 전송하였습니다.														=> 확인완료
	4-2. wallet.barry_prod_list 테이블에 insert됨																											=> 확인완료
	4-3. wallet.etoken.logs.barry_prod_id = barry_prod_list.id																				=> 확인완료

*/

/*
21.04.26
1) $cancel_percentage 추가
2) barryapi_err_message : 443 추가
3) DB : wallet.etoken_logs.barry_proc_id 추가
4) $kind == cancel 추가
*/


//2021.09.02 By.OJT 민호님 LOCAL TEST용 pw 평문


header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../config/config_barry.php';

include_once($barry_encrypt_root.'/barryEncrypt/Rsa.php');
use barry\encrypt\Rsa as barryRsa;

$encc = new barryRsa;

$requestData = $_POST;
$db = getDbInstance();

// 공통
$ckey = (isset($requestData['ckey'])) ? $requestData['ckey'] : '';
$kind = (isset($requestData['kind'])) ? $requestData['kind'] : '';

// 결제 허용하는 코인 리스트 추가, 21.02.09
$coin_list_arr = array('E-TP3', 'E-MC');
$coin_list_arr_low = array('etp3', 'emc');


if (empty($kind)) {
	jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
}
if (empty($ckey)) {
	jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
}

$ok_json = array('code'=>'00','msg'=>barryapi_err_message('00'));

$cancel_percentage = 0.05; // cancel


switch($kind) {
	
	// 회원 인증
	case 'check':

		$user_id = (isset($requestData['user_id'])) ? $requestData['user_id'] : '';

		if ( empty($user_id) ) {
			jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
		}

		$db->where('id', $user_id);
		$db->where('ckey', $ckey);
		$count = $db->getValue('admin_accounts', 'count(*)');
		
		if ( $count == 0 ) {
			jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
		} else {
			jsonReturn($ok_json);
		}

		break;
		

	// 비밀번호 인증
	case 'passwd':

		$user_id = (isset($requestData['user_id'])) ? $requestData['user_id'] : '';
		$user_pw = (isset($requestData['user_pw'])) ? $requestData['user_pw'] : '';

		if ( empty($user_id) || empty($user_pw) ) {
			jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
		}
		//$user_pw = $encc->decrypt($user_pw);
		$user_pw = $user_pw; //암호화 없이.. 
		
		$db->where('id', $user_id);
		$db->where('ckey', $ckey);
		$userData = $db->getOne('admin_accounts');
		
		if ( empty($userData) ) {
			jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
		} else {
			
			$user_id_auth = 'N';
			if ( !empty($userData['id_auth']) && $userData['id_auth'] == 'Y' ) {
				$user_id_auth = 'Y';
			}
			$ip_kor = '';
			$ip_kor = trim(new_ipinfo_ip_chk('2'));
			if ($ip_kor == 'KR' && $user_id_auth != 'Y') {
				jsonReturn(array('code'=>'44','msg'=>barryapi_err_message('441')));
			}

			if ( empty($userData['transfer_passwd'])) {
				jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('662')));
			} else {
				if ( password_verify($user_pw, $userData['transfer_passwd'])) {
					// 비밀번호 일치시 처리 시작

					$stf_count = !empty($userData['transfer_pw_count']) ? $userData['transfer_pw_count'] : '0';
					$stf_date = $userData['transfer_pw_date'];
					if ( !empty($stf_date) && $stf_date != date("Y-m-d") ) { // 날짜 다르면 초기화
						$stf_count = 0;
						
						$db->where ("id", $userData['id']);
						$updateArr = [] ;
						$updateArr['transfer_pw_count'] =  NULL;
						$updateArr['transfer_pw_date'] =  NULL;
						$last_id = $db->update('admin_accounts', $updateArr);
					}
					if ($stf_count >= $n_transfer_pw_count ) {
						// 횟수 초과시
						jsonReturn(array('code'=>'55','msg'=>barryapi_err_message('551')));
					} else {
						jsonReturn($ok_json);
					}

					// 비밀번호 일치시 처리 종료
				} else {
					jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
				}
			}
		}
		
		//jsonReturn($ok_json);

		break;


	// 결제요청 : 코인 결제시 여기에 추가하지 말고 분리할것!
	case 'payment':

		$seller_user_id = (isset($requestData['seller_user_id'])) ? $requestData['seller_user_id'] : '';
		$seller_address = (isset($requestData['seller_address'])) ? $requestData['seller_address'] : '';
		$buyer_user_id = (isset($requestData['buyer_user_id'])) ? $requestData['buyer_user_id'] : '';
		$amount = (isset($requestData['amount'])) ? $requestData['amount'] : '';
		$unit = (isset($requestData['unit'])) ? $requestData['unit'] : '';

		// 21.03.22
		$product_id = (isset($requestData['product_id'])) ? $requestData['product_id'] : '';
		$product_tbl = (isset($requestData['product_tbl'])) ? $requestData['product_tbl'] : '';
		$product_name = (isset($requestData['product_name'])) ? $requestData['product_name'] : '';
		$barry_prod_id = 0;
		
		// 필수값 체크
		if ( empty($seller_user_id) || empty($seller_address) || empty($buyer_user_id) || empty($amount) || empty($unit) ) {
			jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
		}
		
		if ( $amount <= 0 ) {
			jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
		}

		list($coin_type_a, $coin_unit_a) = barryapi_coin_type_change($unit); // coin_type_a 사용하면 안됨!
		if ( !in_array($coin_unit_a, $coin_list_arr_low)) {
			jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
		}
		$token = strtolower($coin_unit_a);

				
		$db->where('id', $buyer_user_id);
		$db->where('ckey', $ckey);
		$userData = $db->getOne('admin_accounts');
		
		if ( empty($userData) ) {
			jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
		} else {
			
			$db->where('id', $seller_user_id);
			$seller_userData = $db->getOne('admin_accounts');
			if ( empty($seller_userData) || $seller_userData['virtual_wallet_address'] != $seller_address ) {
				jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
			} else {
				// 성공시

				if ( $userData['etoken_use'] == 'N' ) {
					jsonReturn(array('code'=>'12','msg'=>barryapi_err_message('22')));
				}

				$ectc_balance = $userData['etoken_ectc'];
				$coin_balance = $userData['etoken_'.$token];
				
				
				$module_name = 'send_etoken_fee';
				if ( $userData['transfer_approved'] != 'C' ) {
					$module_name = 'send_etoken_fee_eth';
				}
				if ( $userData['transfer_fee_type'] == 'H' ) {
					$module_name = 'send_etoken_fee_h';
				}

				$getTokenFee = $db->where("module_name", $module_name)->getOne('settings');
				$getTokenFeeVal = $getTokenFee['value'];

				$getMinAmount = $db->where("module_name", 'min_send_amount_'.$token)->getOne('settings');
				$getMinAmountVal = $getMinAmount['value'];

				// 최소전송금액 체크
				if ( !empty($getMinAmountVal) && $getMinAmountVal > $amount ) {
					jsonReturn(array('code'=>'33','msg'=>barryapi_err_message('33')));
				}
				
				// 구매자 잔액 체크
				if ( $amount > $coin_balance ) {
					jsonReturn(array('code'=>'55','msg'=>barryapi_err_message('552')));
				}

				// 구매자 수수료 체크
				if ( $getTokenFeeVal > 0 && $getTokenFeeVal > $ectc_balance ) {
					jsonReturn(array('code'=>'44','msg'=>barryapi_err_message('442')));
				}

				// eCTC 수수료 받는사람 설정
				if ( $getTokenFeeVal > 0 ) {
					$receive_fee_id = $n_master_etoken_ctc_fee_id;
					$receiver_fee_address = $n_master_etoken_ctc_fee_wallet_address;
				}
				
				$send_type = 'barry';
				
				
				$barry_prod_id = barryapi_set_barry_prod($product_id, $product_tbl, $product_name); // 21.03.22
				
				// 구매자 차감
				
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
					if ( !empty($barry_prod_id) ) { // 21.03.22
						$data_to_log['barry_prod_id'] = $barry_prod_id;
					}
					$data_to_log['created_at'] = date("Y-m-d H:i:s");
					
					$last_id_sl = $db->insert('etoken_logs', $data_to_log);

					// 수수료 있을 경우 수수료 차감
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
						if ( !empty($barry_prod_id) ) { // 21.03.22
							$data_to_log2['barry_prod_id'] = $barry_prod_id;
						}
						$data_to_log2['created_at'] = date("Y-m-d H:i:s");
						
						$last_id_sl2 = $db->insert('etoken_logs', $data_to_log2);
					}
				}

				// 판매자 +

				if ( $send_type != 'barry' ) { // 가상주소가 받을 때에는 합계를 내지 않음
					
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
				if ( !empty($barry_prod_id) ) { // 21.03.22
					$data_to_log['barry_prod_id'] = $barry_prod_id;
				}
				$data_to_log['created_at'] = date("Y-m-d H:i:s");
				
				$last_id_sl3 = $db->insert('etoken_logs', $data_to_log);
				
				
				// eCTC in

				if ( $getTokenFeeVal > 0 ) {
					$updateArr = [];
					
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
						if ( !empty($barry_prod_id) ) { // 21.03.22
							$data_to_log2['barry_prod_id'] = $barry_prod_id;
						}
						$data_to_log2['created_at'] = date("Y-m-d H:i:s");
						
						$last_id_sl4 = $db->insert('etoken_logs', $data_to_log2);
					}

				}
				
				// 문자&메일 발송
				if ( $last_id_sl ) {

					$ok_json['payment_no'] = $last_id_sl; // 배송완료 처리를 위한 결제고유번호(구매자 기준) 추가 - 20.12.31, YMJ

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
						if ( !empty($barry_prod_id) ) { // 21.03.22 ( if문 추가)
							$send_sms_message1 = !empty($langArr['send_sms_message1']) ? $langArr['send_sms_message1'] : ' sent ';
							$send_sms_vertual_message3 = isset($langArr['send_sms_vertual_message3']) ? $langArr['send_sms_vertual_message3'] : '';
							$send_sms_vertual_message4 = !empty($langArr['send_sms_vertual_message4']) ? $langArr['send_sms_vertual_message4'] : ' as a purchase fee.';
							$alert_msg = $from_name.$send_sms_message1.barryapi_cut_product_name($product_name).$send_sms_vertual_message3.new_number_format($amount, $n_decimal_point_array2[$coin_type]).$coin_type2.$send_sms_vertual_message4;
						} else {
							$send_sms_vertual_message1= !empty($langArr['send_sms_vertual_message1']) ? $langArr['send_sms_vertual_message1'] : " sent ";
							$send_sms_vertual_message2 = !empty($langArr['send_sms_vertual_message2']) ? $langArr['send_sms_vertual_message2'] : " for the purchase of goods.";
							$alert_msg = $from_name.$send_sms_vertual_message1.new_number_format($amount, $n_decimal_point_array2[$coin_type]).$coin_type2.$send_sms_vertual_message2;
						}
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
				
				//2021.07.26 getTokenFeeVal 결제 된 수수료 리턴By.OJT
				$ok_json['payment_fee'] = $getTokenFeeVal;// apis_test3 후 확인..
			}		
		}
		
		jsonReturn($ok_json);


		break;


	// 배송완료 : 20.12.31
	case 'finish':

		$seller_user_id = (isset($requestData['seller_user_id'])) ? $requestData['seller_user_id'] : '';
		$payment_no = (isset($requestData['payment_no'])) ? $requestData['payment_no'] : '';
		$buyer_user_id = (isset($requestData['buyer_user_id'])) ? $requestData['buyer_user_id'] : '';
		$coin_type = (isset($requestData['coin_type'])) ? $requestData['coin_type'] : '';

		if ( empty($seller_user_id) || empty($buyer_user_id) || empty($payment_no) || empty($coin_type) ) {
			jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
		}
		
		list($coin_type2, $coin_unit) = barryapi_coin_type_change($coin_type);
		if ( !in_array(strtoupper($coin_type), $coin_list_arr)) {
			jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
		}

		// 1. 구매자에게 20% 캐시백(비포인트)
		// => 20% : 1TP3 = 50원이라면 => 100TP3 * 50 = 5000 KRW의 20%가 구매자에게 전송됨
		// 2. 판매자 가상계좌에서 판매자 지갑주소로 이동 (98%만)
				
		$db->where('id', $buyer_user_id);
		$db->where('ckey', $ckey);
		$userData = $db->getOne('admin_accounts');
		
		if ( empty($userData) ) {
			jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
		} else {
			// 구매자 정보가 일치할 경우 시작
			
			
			$db->where('id', $seller_user_id);
			$sellerData = $db->getOne('admin_accounts');
			if ( empty($sellerData) ) {
				jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
			} else {
				// 판매자 정보가 일치할 경우 시작
				
				$db->where('id', $payment_no);
				$amount = '';
				if ( $coin_type2 == 'coin' ) {
					$payData = $db->getOne('user_transactions_all');
				} else if ( $coin_type2 == 'epay' ) {
					$payData = $db->getOne('etoken_logs');
				}
				if ( empty($payData) ) {
					jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
				} else {
					// 결제번호가 일치할 경우 시작
					if ( $coin_type2 == 'coin' ) {
						$amount = $payData['amount'];
					} else if ( $coin_type2 == 'epay' ) {
						$amount = str_replace('-', '', $payData['points']);
					}

					if ( empty($amount) ) {
						jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
					} else {
						// coin / epay 원화 변환 시작
						$krw_price = '';
						$price = $amount;
						$price_cashback = '';

						$coin_per_rate = 60; // Coin당 원화 / 기본값
						list($module_name, $module_name2) = barryapi_set_module_name($coin_unit);


						if ( $coin_type2 == 'epay' ) {
							$e_coin_rate = 1;
							// 1 Coin당 몇 E-Pay 인지
							
							$db->where('module_name', $module_name2);
							$row2 = $db->getOne('settings');
							if (!empty($row2) && !empty($row2['value'])) {
								$e_coin_rate = $row2['value'];
							}
							$price = $price * $e_coin_rate; // 100 Coin이 몇 E-Pay인지
						}

						// 비율값 가져오기 : 1 Coin당 몇 원인지
						
						$db->where('module_name', $module_name);
						$row = $db->getOne('settings');

						if (!empty($row) && !empty($row['value'])) {
							$tmp = round($row['value']);
							$coin_per_rate = $tmp; // 1 Coin당 몇 원인지
						}
						$price = $price * $coin_per_rate;


						// coin / epay 원화 변환 종료

						// 20% 캐시백
						$price_cashback = $price * $barry_beepoint_cashback / 100;
						
						// 구매자에게 20% 캐시백 시작 => Beepoint로
						$insertArr = [];
						$insertArr['user_id'] = $userData['id'];
						$insertArr['user_wallet_address'] = $userData['wallet_address'];
						$insertArr['store_id'] = $sellerData['id'];
						$insertArr['store_wallet_address'] = $sellerData['virtual_wallet_address'];
						$insertArr['points'] = $price_cashback;
						$insertArr['amount'] = $amount;
						$insertArr['krw'] = $price;
						$insertArr['ex_rate'] = $row['value'];
						$insertArr['description'] = 'barrybarries';
						$insertArr['coin_type'] = $coin_unit;
						$insertArr['log_id'] = $payment_no;
						$insertArr['created_at'] = date("Y-m-d H:i:s");
						$bee_point_id = $db->insert('store_transactions', $insertArr);
						
						// 캐시백 종료

						// 판매자 가상주소->주소(98%만 옮김) 시작

						if ( $coin_type2 == 'coin' ) {
						} else if ( $coin_type2 == 'epay' ) {
							
							$amount2 = $amount * $barry_payback_amount / 100;
							$amount2 = round($amount2);
							$send_type = 'barry';

							// 판매자 가상주소에서 차감
							// 판매자 가상주소가 받을 때는 합계를 내지 않았기 때문에 admin_accounts에서 차감하지 않음
							//
							//$db->where("id", $sellerData['id']);
							//$updateArr = [];
							//$updateArr['etoken_'.$coin_unit] = $db->dec($amount2);
							//$last_id = $db->update('admin_accounts', $updateArr);
							
							// 로그만 기록
							$data_to_log = [];
							$data_to_log['user_id'] = $sellerData['id'];
							$data_to_log['wallet_address'] = $sellerData['virtual_wallet_address'];
							$data_to_log['coin_type'] = $coin_unit;
							$data_to_log['points'] = '-'.$amount2;
							$data_to_log['in_out'] = 'out';
							if ( !empty($send_type) ) {
								$data_to_log['send_type'] = $send_type;
							}
							$data_to_log['send_user_id'] = $sellerData['id'];
							$data_to_log['send_wallet_address'] = $sellerData['wallet_address'];
							$data_to_log['send_fee'] = 0;
							$data_to_log['created_at'] = date("Y-m-d H:i:s");
							
							$last_id_sl = $db->insert('etoken_logs', $data_to_log);


							// 판매자 주소로 IN
							
							$db->where("id", $sellerData['id']);
							$updateArr = [];
							$updateArr['etoken_'.$coin_unit] = $db->inc($amount2);
							$last_id2 = $db->update('admin_accounts', $updateArr);

							$data_to_log = [];
							$data_to_log['user_id'] = $sellerData['id'];
							$data_to_log['wallet_address'] = $sellerData['wallet_address'];
							$data_to_log['coin_type'] = $coin_unit;
							$data_to_log['points'] = '+'.$amount2;
							$data_to_log['in_out'] = 'in';
							if ( !empty($send_type) ) {
								$data_to_log['send_type'] = $send_type;
							}
							// send_user_id, send_wallet_address에 구매자 정보를 적게 되면, 판매자 지갑주소에서 빠져나가고, 들어온게 모두 표시가 되기 때문에
							// 가상주소에서 빠져나가고, 지갑주소로 들어오게 하려면 여기에 판매자 가상주소 정보가 들어가야 한다.
							//$data_to_log['send_user_id'] = $buyer_user_id;
							//$data_to_log['send_wallet_address'] = $userData['wallet_address'];
							$data_to_log['send_user_id'] = $sellerData['id'];
							$data_to_log['send_wallet_address'] = $sellerData['virtual_wallet_address'];
							$data_to_log['send_fee'] = '0';
							$data_to_log['created_at'] = date("Y-m-d H:i:s");
							
							$last_id_sl2 = $db->insert('etoken_logs', $data_to_log);


						}
						// 판매자 가상주소->주소(98%만 옮김) 종료

						
					}

				} // 결제번호가 일치할 경우 종료
				
			} // 판매자 정보가 일치할 경우 종료
		} // 구매자 정보가 일치할 경우 종료

		jsonReturn($ok_json);


		break;

	
	case 'getprice':

		$coin_type = (isset($requestData['coin_type'])) ? $requestData['coin_type'] : ''; // TP3, e-TP3, ...
		$price = (isset($requestData['price'])) ? $requestData['price'] : '';

		if (empty($coin_type) || empty($price)) {
			jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
		}
		$coin_type = strtoupper($coin_type);
		list($coin_type2, $coin_unit) = barryapi_coin_type_change($coin_type);
			
		if ( !in_array($coin_type, $coin_list_arr)) {
			jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
		}
		
		$coin_type_tmp = barryapi_coin_type_change2($coin_type);
		$coin = new_coin_price_change_won($coin_type_tmp, $price, $coin_type2); // wallet2/config/new_config

		if ( $coin > 0 ) {
			$ok_json['price'] = $coin;
			$ok_json['unit'] = $coin_type_tmp;
		} else {
			jsonReturn(array('code'=>'22','msg'=>barryapi_err_message('22')));
		}

		jsonReturn($ok_json);

		break;

	
	case 'getprice2':

		$coin_type = (isset($requestData['coin_type'])) ? $requestData['coin_type'] : ''; // TP3, e-TP3, ...
		
		if (empty($coin_type)) {
			jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
		}
		$coin_type = strtoupper($coin_type);
		list($coin_type2, $coin_unit) = barryapi_coin_type_change($coin_type);
		
		if ( !in_array($coin_type, $coin_list_arr)) {
			jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
		}

		list($module_name, $module_name2) = barryapi_set_module_name($coin_unit);
		$epay_per_coin = 0; // Coin일 경우 무조건 0

		
		$db->where('module_name', $module_name); // krw_per_ctc_kiosk
		$coinData = $db->getOne('settings');
		
		if ( $coin_type2 == 'epay' ) {
			
			$db->where('module_name', $module_name2); // exchange_ectc_per_ctc
			$coinData2 = $db->getOne('settings');
			if ( empty($coinData2)) {
				jsonReturn(array('code'=>'22','msg'=>barryapi_err_message('22')));
			}
			$epay_per_coin = $coinData2['value'];
		}

		if ( empty($coinData) ) {
			jsonReturn(array('code'=>'22','msg'=>barryapi_err_message('22')));
		} else {
			$ok_json['ex_rate'] = $coinData['value']; // 1 Coin = ? 원
			$ok_json['epay_per_coin'] = $epay_per_coin; // 1 Coin = ? E-Pay
			$ok_json['unit'] = barryapi_coin_type_change2($coin_type);
		}
		jsonReturn($ok_json);


		break;
		
	//2021-05-10 test2->apis로 이전 추가 by.OJT
	case 'cancel':

		$seller_user_id = (isset($requestData['seller_user_id'])) ? $requestData['seller_user_id'] : '';
		$seller_address = (isset($requestData['seller_address'])) ? $requestData['seller_address'] : '';
		$buyer_user_id = (isset($requestData['buyer_user_id'])) ? $requestData['buyer_user_id'] : '';
		$payment_no = (isset($requestData['payment_no'])) ? $requestData['payment_no'] : '';
		$coin_type = (isset($requestData['coin_type'])) ? $requestData['coin_type'] : '';

		if (empty($seller_user_id) || empty($seller_address) || empty($buyer_user_id) || empty($payment_no) || empty($coin_type)) {
			jsonReturn(array('code'=>'99','msg'=>barryapi_err_message('99')));
		}

		$coin_type = strtoupper($coin_type);
		list($coin_type2, $coin_unit) = barryapi_coin_type_change($coin_type);
			
		if ( !in_array($coin_type, $coin_list_arr)) {
			jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
		}
		

		
		$db->where('id', $seller_user_id);
		//2021-05-10 user 단에서 처리 할려 헀으나 admin 단에서 처리로 스펙이 변경 되어 Ckey로 비교는 제외 함.
		//$db->where('ckey', $ckey);
		$userData = $db->getOne('admin_accounts', 'id, wallet_address, virtual_wallet_address');
		
		if ( empty($userData) || $userData['virtual_wallet_address'] != $seller_address ) {
		//if ( empty($userData) ) {
			jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
		} else {
			$db->where('id', $buyer_user_id);
			$buyer_userData = $db->getOne('admin_accounts', 'id, wallet_address');
			if ( empty($buyer_userData) ) {
				jsonReturn(array('code'=>'77','msg'=>barryapi_err_message('77')));
			} else {
				
				$db->where('id', $payment_no); // 여기서 조회되는 데이터는 구매자에게서 차감되는 로그 id
				$amount = '';
				if ( $coin_type2 == 'coin' ) {
					$payData = $db->getOne('user_transactions_all');
				} else if ( $coin_type2 == 'epay' ) {
					$payData = $db->getOne('etoken_logs', 'id, points, barry_prod_id, barry_proc_id');
				}
				if ( empty($payData) ) {
					jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
				} else {
					// 결제번호가 일치할 경우 시작
					if ( $coin_type2 == 'coin' ) {
						$amount = $payData['amount'];
					} else if ( $coin_type2 == 'epay' ) {
						$amount = str_replace('-', '', $payData['points']);
					}
					$barry_prod_id = $payData['barry_prod_id'];

					if ( empty($amount) ) {
						jsonReturn(array('code'=>'66','msg'=>barryapi_err_message('66')));
					}
					if ( !empty($payData['barry_proc_id']) ) { // 이미 쥐소처리됨
						jsonReturn(array('code'=>'44','msg'=>barryapi_err_message('443')));
					}


					// 현재 상태 : 구매자 지갑 -100% / 판매자 가상지갑 +100%
					// 구매자에서 판매자 가상주소로 들어갔는지 체크 => $payment_no로 체크
					// 판매자 가상주소에서 판매자 주소로 들어갔는지 체크 (들어갔으면 취소불가) => 베리베리에서 배송완료 처리를 하지 않으면, 판매자 가상주소에서 판매자 주소로 들어가지 않음

					// 처리
					// 구매자 +100%
					// 판매자 가상 -100%
					// 판매자 지갑 -5% -> 관리자 +5%
					// log 테이블에 취소처리 때문이라는 것을 기록 => send_type = 'barry_cancel, barry_cancel_p'
					// 구매자가 구입할 때 발생한 수수료는 무시

					// rollback......?

					$token = $coin_unit;

					$send_type = 'barry_cancel'; // 취소
					
					// 판매자 -5%
					$amount_5 = $amount * $cancel_percentage;
					// 베리베리에서 소수점 만들지 않음 -> * 0.05 해도 소수점 최대 2자리까지 나옴
					
					// 판매자 -5% 할 때, 받는사람 : 관리자
					$adminId = $n_master_etoken_id;
					$adminWalletAddress = $n_master_etoken_wallet_address;
					
					// 구매자 + 100%				
					$db->where("id", $buyer_user_id);
					$updateArr = [];
					$updateArr['etoken_'.$token] = $db->inc($amount);
					$last_id = $db->update('admin_accounts', $updateArr);

					$data_to_log = [];
					$data_to_log['user_id'] = $buyer_user_id;
					$data_to_log['wallet_address'] = $buyer_userData['wallet_address'];
					$data_to_log['coin_type'] = $token;
					$data_to_log['points'] = '+'.$amount;
					$data_to_log['in_out'] = 'in';
					if ( !empty($send_type) ) {
						$data_to_log['send_type'] = $send_type;
					}
					$data_to_log['send_user_id'] = $seller_user_id;
					$data_to_log['send_wallet_address'] = $seller_address;
					$data_to_log['send_fee'] = '0';
					if ( !empty($barry_prod_id) ) {
						$data_to_log['barry_prod_id'] = $barry_prod_id;
					}
					$data_to_log['barry_proc_id'] = $payment_no;
					$data_to_log['created_at'] = date("Y-m-d H:i:s");
					
					$last_id_sl2 = $db->insert('etoken_logs', $data_to_log);



					// 판매자 가상 -100%
					if ( $send_type != 'barry_cancel' ) { // 가상주소가 받을 때에는 합계를 내지 않았기 때문에 가상주소에서 차감할 때도 합계를 내지 않는다.
						$db->where("id", $seller_user_id);
						$updateArr = [];
						$updateArr['etoken_'.$token] = $db->dec($amount);
						$last_id3 = $db->update('admin_accounts', $updateArr);
					}

					$data_to_log = [];
					$data_to_log['user_id'] = $seller_user_id;
					$data_to_log['wallet_address'] = $seller_address;
					$data_to_log['coin_type'] = $token;
					$data_to_log['points'] = '-'.$amount;
					$data_to_log['in_out'] = 'out';
					if ( !empty($send_type) ) {
						$data_to_log['send_type'] = $send_type;
					}
					$data_to_log['send_user_id'] = $buyer_user_id;
					$data_to_log['send_wallet_address'] = $buyer_userData['wallet_address'];
					$data_to_log['send_fee'] = '0';
					if ( !empty($barry_prod_id) ) {
						$data_to_log['barry_prod_id'] = $barry_prod_id;
					}
					$data_to_log['barry_proc_id'] = $payment_no;
					$data_to_log['created_at'] = date("Y-m-d H:i:s");
					
					$last_id_sl3 = $db->insert('etoken_logs', $data_to_log);
					

					$send_type = 'barry_cancel_p'; // 취소 패널티 구분

					// 판매자 -5%
					$db->where("id", $seller_user_id);
					$updateArr = [];
					$updateArr['etoken_'.$token] = $db->dec($amount_5);
					$last_id5 = $db->update('admin_accounts', $updateArr);

					$data_to_log = [];
					$data_to_log['user_id'] = $seller_user_id;
					$data_to_log['wallet_address'] = $userData['wallet_address'];
					$data_to_log['coin_type'] = $token;
					$data_to_log['points'] = '-'.$amount_5;
					$data_to_log['in_out'] = 'out';
					if ( !empty($send_type) ) {
						$data_to_log['send_type'] = $send_type;
					}
					$data_to_log['send_user_id'] = $adminId;
					$data_to_log['send_wallet_address'] = $adminWalletAddress;
					$data_to_log['send_fee'] = '0';
					if ( !empty($barry_prod_id) ) {
						$data_to_log['barry_prod_id'] = $barry_prod_id;
					}
					$data_to_log['barry_proc_id'] = $payment_no;
					$data_to_log['created_at'] = date("Y-m-d H:i:s");
					
					$last_id_sl5 = $db->insert('etoken_logs', $data_to_log);
					

					// 판매자 주소에서 -5%하면, 받을 관리자
					$db->where("id", $adminId);
					$updateArr = [];
					$updateArr['etoken_'.$token] = $db->inc($amount_5);
					$last_id6 = $db->update('admin_accounts', $updateArr);

					$data_to_log = [];
					$data_to_log['user_id'] = $adminId;
					$data_to_log['wallet_address'] = $adminWalletAddress;
					$data_to_log['coin_type'] = $token;
					$data_to_log['points'] = '+'.$amount_5;
					$data_to_log['in_out'] = 'in';
					if ( !empty($send_type) ) {
						$data_to_log['send_type'] = $send_type;
					}
					$data_to_log['send_user_id'] = $seller_user_id;
					$data_to_log['send_wallet_address'] = $userData['wallet_address'];
					$data_to_log['send_fee'] = '0';
					if ( !empty($barry_prod_id) ) {
						$data_to_log['barry_prod_id'] = $barry_prod_id;
					}
					$data_to_log['barry_proc_id'] = $payment_no;
					$data_to_log['created_at'] = date("Y-m-d H:i:s");
					
					$last_id_sl6 = $db->insert('etoken_logs', $data_to_log);

					
					$db->where('id', $payment_no);
					$updateArr = [];
					$updateArr['barry_proc_id'] = $last_id_sl2;
					if ( $coin_type2 == 'coin' ) {
						$last_id_7 = $db->update('user_transactions_all', $updateArr);
					} else if ( $coin_type2 == 'epay' ) {
						$last_id_7 = $db->update('etoken_logs', $updateArr);
					}
					
					jsonReturn($ok_json);

				
				}
			}
		}
	
		break;

	default:
		 jsonReturn(array('code'=>'88','msg'=>barryapi_err_message('88')));
		break;

} // switch




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
    //logWrite($arr);
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


// 21.03.25
function barryapi_err_message($err_code) {
	$msg = '';
	switch($err_code) {
		case "00":
			$msg = 'ok';
			break;

		case "99":
			$msg = '필수값이 누락되었습니다.';
			break;
		case "88":
			$msg = '알수없는 요청입니다.';
			break;
		case "77":
			$msg = '잘못된 사용자입니다.';
			break;
		case "66":
			$msg = '잘못된 요청입니다.';
			break;

		case "662":
			$msg = '전송비밀번호를 셋팅하지 않은 사용자입니다.';
			break;

		case "551":
			$msg = '결제비밀번호 입력 횟수가 초과되었습니다. 다음날 다시 시도해주세요.';
			break;
		case "552":
			$msg = '잔액이 부족합니다.';
			break;

		case "441":
			$msg = '본인인증 후 이용할 수 있습니다.';
			break;
		case "442":
			$msg = '수수료가 부족합니다.';
			break;
		case "443":
			$msg = '이미 취소처리되었습니다.';
			break;
		case "33":
			$msg = '전송에 실패하였습니다.(최소전송금액)';
			break;
		case "22":
			$msg = '처리에 실패하였습니다.';
			break;

		default:
			$msg = 'error';
			break;
	}
	return $msg;
}
?>
