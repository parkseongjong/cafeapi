<?php
/*
function logWrite($arr='') {
    $fname = "/var/www/html/apis/mask/logs/" . date('Y-m-d') . ".txt";
    $f = fopen($fname, "a");
    fwrite($f, "[".date('Y-m-d H:i:s')."] : ".$_SERVER['REMOTE_ADDR']."\n");
    fwrite($f, "[REQ] ---------------\n");
    foreach($_GET as $k => $v) {
        fwrite($f, '    '.$k.'='.$v."\n");
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
}*/


function jsonReturn($arr='') {
    if (empty($arr)) {
        $arr = array('code'=>'999', 'error'=>true, 'msg'=>'Error');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    } else {
        if (is_array($arr)) {
            echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('code'=>'999', 'error'=>true, 'msg'=>$arr), JSON_UNESCAPED_UNICODE);
        }
    }
    //logWrite($arr);
    exit();
}


function wa_err_message ($err_code) {
	$msg = '';
	switch($err_code) {
		case 200:
			$msg = 'Success';
			break;

		case 801:
			// 허용되지 않은 사용자입니다. (auth_key값 불일치)
			$msg = 'Disallowed user';
			break;
		case 802:
			// 잘못된 요청입니다. (kind값 불일치)
			$msg = 'Bad request';
			break;

		case 804:
			// 필수값이 누락되었습니다.
			$msg = 'Missing required value';
			break;
		case 805:
			// 허용되지 않은 값이 입력되었습니다.
			$msg = 'Unacceptable value entered';
			break;
		case 806:
			// 정보가 존재하지 않습니다.
			$msg = 'No information';
			break;

		case 808:
			// 조회 실패
			$msg = 'Lookup failure';
			break;
		case 809:
			// 처리 실패
			$msg = 'Processing failure'; // usdt 전송시 실패한 경우
			break;
		case 811:
			// 저장 중 오류가 발생했습니다.
			$msg = 'Error during saving';
			break;


		case 701:
			// 차단된 IP
			$msg = 'Blocked IP Address';
			break;
		case 702:
			// 로그인 불가 ID
			$msg = 'Login blocked ID';
			break;
		case 703:
			// 없는 ID
			$msg = 'Id mismatch';
			break;
		case 704:
			// 비밀번호 불일치
			$msg = 'Password mismatch';
			break;


		case 721:
			// 다른 Device ID
			$msg = 'Device ID mismatch';
			break;
		case 722:
			// 등록된 기기로 로그인해주세요
			$msg = 'Log in with the registered device';
			break;

		case 999:
			$msg = 'Error';
			break;
	}
	return $msg;
}



$wallet_directory_root = '/var/www/html/wallet2';
require_once $wallet_directory_root.'/config/new_config.php';







function apis_wa_insert_login_device_logs($user_id, $email, $app_name, $device, $devId, $msg, $ip) {
	$insertArr = [];
	$insertArr['user_id'] = $user_id;
	$insertArr['email'] = $email;
	$insertArr['app_name'] = $app_name;
	if ( !empty($device) ) {
		$insertArr['device'] = $device;
	}
	if ( !empty($devId) ) {
		$insertArr['devId'] = $devId;
	}
	$insertArr['msg'] = $msg;
	$insertArr['ip'] = $ip;
	$insertArr['created_at'] = date("Y-m-d H:i:s");
	
	$db = getDbInstance();
	$logs_id = $db->insert('login_device_logs', $insertArr);
	
	return $logs_id;
}

function apis_wa_insert_login_logs($email, $login_result, $msg, $ip) {
	$insertArr = [];
	$insertArr['email'] = $email;
	$insertArr['login_result'] = $login_result;
	$insertArr['msg'] = $msg;
	$insertArr['ip'] = $ip;
	$insertArr['login_at'] = date("Y-m-d H:i:s");

	$db = getDbInstance();
	$logs_id = $db->insert('login_logs', $insertArr);

	return $logs_id;
}



?>