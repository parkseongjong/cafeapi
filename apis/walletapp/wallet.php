<?php
header('Content-Type: application/json');
// https://cybertronchain.com/apis/walletapp/wallet.php
require_once '../config/config.php';
require_once '../config/config_walletapp.php';
require_once '../config/config_wallet_original.php';

//$requestData = file_get_contents('php://input');
//$requestData = json_decode($requestData, true);
$requestData = $_POST;

$kind = (isset($requestData['kind'])) ? $requestData['kind'] : '';

$ok_json = array('code'=>200,'error'=>false, 'msg'=>wa_err_message(200));

$get_balance_coin_type_arr = array('bee', 'coin', 'epay');

switch($kind) {

	case 'get_balance':

		$member_no = (isset($requestData['member_no'])) ? $requestData['member_no'] : '';
		$coin_type = (isset($requestData['coin_type'])) ? $requestData['coin_type'] : '';

		// 필수값 체크
		if ( empty($member_no) || empty($coin_type) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>wa_err_message(804)));
		}

		// DB에 있는 회원인지 체크
		$db = getDbinstance();
		$db->where('id', $member_no);
		$userData = $db->getOne('admin_accounts');
		if ( empty($userData) || empty($userData['wallet_address']) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>wa_err_message(806)));
		}

		// coin_type 체크
		if ( !in_array($coin_type, $new_walletapp_coin_list) && !in_array($coin_type, $new_walletapp_epay_list) && !in_array($coin_type, $get_balance_coin_type_arr) ) { // $new_walletapp_coin_list, $new_walletapp_epay_list : /wallet2/config/new_config.php
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>wa_err_message(805)));
		}

		require_once $wallet_directory_root.'/lib/WalletProcess.php';
		$wi_wallet_process = new WalletProcess();
		$items = [];

		switch($coin_type) {
			case 'bee':
				$bee_points = 0;
				$db->where("user_id", $member_no);
				$bee_points = $db->getValue("store_transactions", "sum(points)");
				$items[$coin_type] = $bee_points;

				break;

			case 'coin':
				foreach($new_walletapp_coin_list as $row) {
					$getbalance = $wi_wallet_process->wi_get_balance($row, $userData['wallet_address'], $contractAddressArr);
					if ( is_numeric($getbalance) ) {
						$items[$row] = $getbalance;
					} else {
						$items[$row] = -1;
					}
				}
				break;

			case 'epay':
				foreach($new_walletapp_epay_list as $row) {
					$items[$row] = isset($userData['etoken_'.$row]) ? $userData['etoken_'.$row] : -1;
				}

				break;

			default:
				if ( in_array($coin_type, $new_walletapp_coin_list) ) {
					$getbalance = $wi_wallet_process->wi_get_balance($coin_type, $userData['wallet_address'], $contractAddressArr);
					if ( is_numeric($getbalance) ) {
						$items[$coin_type] = $getbalance;
					} else {
						$items[$coin_type] = -1;
					}
				}
				if ( in_array($coin_type, $new_walletapp_epay_list) ) {
					$items[$coin_type] = isset($userData['etoken_'.$coin_type]) ? $userData['etoken_'.$coin_type] : -1;
				}
				break;

		} // switch

		$ok_json['items'] = $items;


		jsonReturn($ok_json);

		break;

	case 'login':

		$login_type_arr = array('email', 'phone');
		$app_name_arr = array('wallet', 'barrybarries', 'coinibt');

		$country_code = (isset($requestData['country_code'])) ? $requestData['country_code'] : '';
		$login_type = (isset($requestData['login_type'])) ? $requestData['login_type'] : '';
		$user_id = (isset($requestData['user_id'])) ? $requestData['user_id'] : '';
		$user_pw = (isset($requestData['user_pw'])) ? $requestData['user_pw'] : '';
		$user_ip = (isset($requestData['user_ip'])) ? $requestData['user_ip'] : '';
		$device_id = (isset($requestData['device_id'])) ? $requestData['device_id'] : '';
		$onesignal_id = (isset($requestData['onesignal_id'])) ? $requestData['onesignal_id'] : '';
		$app_name = (isset($requestData['app_name'])) ? $requestData['app_name'] : '';

		// 필수값 체크
		if ( empty($login_type) || empty($user_id) || empty($user_pw) || empty($user_ip) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>wa_err_message(804)));
		}

		if ( !in_array($login_type, $login_type_arr) ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>wa_err_message(805)));
		}
		if ( !empty($app_name) && !in_array($app_name, $app_name_arr) ) {
			jsonReturn(array('code'=>805,'error'=>true,'msg'=>wa_err_message(805)));
		}

		$db = getDbInstance();

		// 차단된 IP인지 체크
		if ( !empty($user_ip) ) {
			$blocked_ip_count = 0;
			$db->where("ip_name", $user_ip);
			$blocked_ip_count = $db->getValue('blocked_ips', 'count(*)');
			if ($blocked_ip_count > 0) { 
				jsonReturn(array('code'=>701,'error'=>true,'msg'=>wa_err_message(701)));
			}
		}

		// 등록된 기기 확인 -> 721

		$login_id = '';
		if ( $login_type == 'phone' ) {
			if ( empty($country_code) ) {
				jsonReturn(array('code'=>804,'error'=>true,'msg'=>wa_err_message(804)));
			}

			if ( $country_code == '82' ) {
				$tmp = substr($user_id, 1);
				$login_id = '+'.$country_code.$tmp;
			} else {
				$login_id = '+'.$country_code.$user_id;
			}
		} else {
			$login_id = $user_id;
		}
		$passwd5 = md5($user_pw);
		
		$db->where ("email", $login_id);
		$db->where ("passwd", $passwd5);
		$row = $db->get('admin_accounts');
		
		if ($db->count >= 1) {

			$device = '';
			$post_dev_id = '';
			$app_name = 'wallet';
			$field_name = 'devId';

			if ( $app_name == 'wallet' ) {
				$field_name = 'devId';
			} else if ( $app_name == 'barrybarries' ) {
				$field_name = 'devId2';
			} else if ( $app_name == 'coinibt' ) {
				$field_name = 'devId3';
			}
			$post_dev_id = $device_id;
			$device = new_get_device($device_id); // wallet2/config/new_config
			

			
			if ( $row[0]['admin_type'] != 'admin' ) {

				// devId 중복체크 20.10.12
				// app에서 device정보가 넘어왔는데, db에 저장이 되어 있지 않은 경우 => 중복체크
				// wallet app에서 실행시 : admin_accounts.devId 값이 없는 경우
				// barrybarries app에서 실행시 : admin_accounts.devId2 값이 없는 경우
				// coinibt app에서 실행시 : admin_accounts.devId3 값이 없는 경우
				if ( !empty($post_dev_id) && empty($row[0][$field_name]) ) {

					// 본인인증된 경우 => DeviceId 같으면 다른 기기에 등록되어 있더라도 로그인 가능하게 할 것!
					if ( $row[0]['id_auth'] != 'Y' || $row[0]['auth_phone'] != $post_dev_id ) {


						// 중복체크
						// 나를 제외한 누군가에게 device 정보가 등록되어 있는 경우
						$dev_count = $db->rawQueryValue("SELECT count(*) from admin_accounts where id != '".$row[0]['id']."' and device='".$device."' and ( devId='".$post_dev_id."' or devId2='".$post_dev_id."' or devId3='".$post_dev_id."')");
						if ( $dev_count[0] > 0 ) {
							
							$logs_id = apis_wa_insert_login_device_logs($row[0]['id'], $login_id, $app_name, $device, $post_dev_id, 'Device already registered', $user_ip);
							jsonReturn(array('code'=>721,'error'=>true,'msg'=>wa_err_message(721)));
						}

						// 20.10.13
						// device가 안드로이드 && post로 넘어온 device 정보가 010으로 시작하는 핸드폰 번호인 경우 : 
						if (strlen($post_dev_id) == 11 && substr($post_dev_id, 0, 3) == '010' && $device == 'android' ) {
							$device_id_phone = substr($post_dev_id, 1);

							// 본인인증 한 경우 : 다르면 실패 / 같으면 넘어감
							//if ( $row[0]['id_auth'] == 'Y' && stristr($row[0]['auth_phone'], $device_id_phone) == FALSE ) {
							if ( $row[0]['id_auth'] == 'Y' ) {
								if ( $row[0]['auth_phone'] != $post_dev_id ) {

									$logs_id = apis_wa_insert_login_device_logs($row[0]['id'], $login_id, $app_name, $device, $post_dev_id, 'Trying to register with another device1', $user_ip);
									jsonReturn(array('code'=>721,'error'=>true,'msg'=>wa_err_message(721)));
								}

							// 본인인증 안한 경우 : 핸드폰가입자 중 번호 email에 없으면 실패 / 있으면 넘어감
							} else {
								if ( $row[0]['register_with'] == 'phone' && stristr($row[0]['email'], $device_id_phone) == FALSE ) {
									$logs_id = apis_wa_insert_login_device_logs($row[0]['id'], $login_id, $app_name, $device, $post_dev_id, 'Trying to register with another device2', $user_ip);
									jsonReturn(array('code'=>721,'error'=>true,'msg'=>wa_err_message(721)));
								}
							}

						}

					}

				}


				// add device id : 20.09.08 ----- 20.10.12
				// -----------------------------------------------------등록된 기기로만 로그인 가능
				//if ( !empty($row[0]['devId']) && !empty($row[0]['device']) && $row[0]['devId'] != $_POST['dev_id'] ) {
				if ( ( ( $app_name == 'wallet' && !empty($row[0]['devId']) ) || ( $app_name == 'barrybarries' && !empty($row[0]['devId2']) ) || ( $app_name == 'coinibt' && !empty($row[0]['devId3']) ) ) && !empty($row[0]['device']) ) { // DB devId, devId2, devId3중 하나라도 값이 있고 && DB device 값이 있는 경우
					if ( $row[0]['devId'] != $post_dev_id && $row[0]['devId2'] != $post_dev_id && $row[0]['devId3'] != $post_dev_id ) { // post로 넘어온 값이 DB에서 찾을수 없는 경우
						$logs_id = apis_wa_insert_login_device_logs($row[0]['id'], $login_id, $app_name, $device, $post_dev_id, 'Other Device Login', $user_ip);
						jsonReturn(array('code'=>722,'error'=>true,'msg'=>wa_err_message(722)));
						
					}
				}
			}
			

			// 로그인 불가 사용자 확인 -> 702

			if ( !empty($row[0]['login_or_not']) && $row[0]['login_or_not'] == 'N' ) {
				$login_logs_id = apis_wa_insert_login_logs($login_id, 'F', 'Accounts unable to log in', $user_ip);
				jsonReturn(array('code'=>702,'error'=>true,'msg'=>wa_err_message(702)));
			}

			if ( !empty($row[0]['email_verify']) && $row[0]['email_verify'] == 'N' ) {
				$login_logs_id = apis_wa_insert_login_logs($login_id, 'F', 'Unauthorized Id account(EmailVerify)', $user_ip);
				jsonReturn(array('code'=>702,'error'=>true,'msg'=>wa_err_message(702)));
			}

			
			$updateArr = [] ;
			
			if ( !empty($device) && empty($row[0]['device']) ) {
				$updateArr['device'] = $device;
			}
			if ( !empty($post_dev_id) && empty($row[0][$field_name]) ) {
				$updateArr[$field_name] = $post_dev_id;
			}
			
			if ( !empty($onesignal_id) ) {
				if ( empty($row[0]['onesignal_id']) ) {
					$updateArr['onesignal_id'] = $onesignal_id;
				}
				if (empty($row[0]['onesignal_id2']) ) {
					$updateArr['onesignal_id2'] = $onesignal_id;
				}
			}
			$db->where("id", $row[0]['id']);
			$updateArr['last_login_at'] =  date("Y-m-d H:i:s");
			$last_id = $db->update('admin_accounts', $updateArr);
			

			// wallet_address 없으면 생성 후 업데이트
			//$walletAddress = $row[0]['wallet_address'];
			//if ( empty($walletAddress) ) {
			//}


			// 로그인 성공시 처리하는 부분
			$ok_json['member_no'] = $row[0]['id'];
			jsonReturn($ok_json);



		} else {

			$db->where ("email", $login_id);
			$row = $db->get('admin_accounts');
			if ($db->count >= 1) {
				// 비밀번호 불일치
				$login_logs_id = apis_wa_insert_login_logs($login_id, 'F', 'Password mismatch', $user_ip);
				jsonReturn(array('code'=>704,'error'=>true,'msg'=>wa_err_message(704)));
			} else {
				// ID 불일치
				$login_logs_id = apis_wa_insert_login_logs($login_id, 'F', 'Id mismatch', $user_ip);
				jsonReturn(array('code'=>703,'error'=>true,'msg'=>wa_err_message(703)));
			}


		}
		

		break;
	

	
	case 'get_member':

		$member_no = (isset($requestData['member_no'])) ? $requestData['member_no'] : '';


		// 필수값 체크
		if ( empty($member_no) ) {
			jsonReturn(array('code'=>804,'error'=>true,'msg'=>wa_err_message(804)));
		}

		// DB에 있는 회원인지 체크
		$db = getDbinstance();
		$db->where('id', $member_no);
		$userData = $db->getOne('admin_accounts');
		if ( empty($userData) || empty($userData['wallet_address']) ) {
			jsonReturn(array('code'=>806,'error'=>true,'msg'=>wa_err_message(806)));
		}

		$ok_json['walletAddress'] = $userData['wallet_address'];
		$ok_json['user_name'] = get_user_real_name($userData['auth_name'], $userData['name'], $userData['lname']);
		$ok_json['name'] = $userData['name'];
		$ok_json['lname'] = !empty($userData['lname']) ? $userData['lname'] : '';

		if ( $userData['id_auth'] == 'Y' ) {
			$ok_json['number'] = $userData['auth_phone'];
			$ok_json['gender'] = $userData['auth_gender'];
			$ok_json['dob'] = $userData['auth_dob']; // YYYY-mm-dd
			$ok_json['local'] = $userData['auth_local_code']; // Kor, For
		} else {
			$ok_json['number'] = $userData['email'];
			$ok_json['gender'] = !empty($userData['gender']) ? $userData['gender'] : '';
			$ok_json['dob'] = !empty($userData['dob']) ? str_replace('/', '-', $userData['dob']) : ''; // YYYY/mm/dd -> YYYY-mm-dd
			$ok_json['local'] = '';
		}
		$ok_json['location'] = !empty($userData['location']) ? $userData['location'] : '';
		$ok_json['profile_img'] = !empty($userData['profile_img']) ? $userData['profile_img'] : '';
		$ok_json['auth'] = $userData['id_auth'];


		break;


	default:
		jsonReturn(array('code'=>802,'error'=>true,'msg'=>wa_err_message(802)));
		break;


}

?>