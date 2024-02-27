<?php

use wallet\common\Util as walletUtil;

require '/var/www/html/wallet2/vendor/autoload.php';
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

$w_api_key = 'BE14273125KL';
$con_exchange_type_value = 'CoinIBT'; // /wallet2/config/new_config.php와 같아야 함

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
	
	//2021-08-03 log wirte add by.ojt

	$util = walletUtil::singletonMethod();
	$util->logFileWrite($util->jsonDecode(file_get_contents('php://input')),$arr,'coinibt','/var/www/ctc/wallet/logs/coinIbtAPI');

	
    exit();
}

$coin_lists = array('TP3', 'MC', 'CTC', 'KRW', 'ETH', 'USDT'); // , 'ETH', 'USDT', 'ETH', 'BTC'

// wallet2/config/new_config.php
$n_master_etoken_id = 45;
$n_master_etoken_wallet_address = "0x1125a7156dc34ABC463E35Bc7703B3287c41FD60";
/*
function coin_e_pay_change($coin_type) {
	$result = 'E-'.$coin_type;
	return $result;
}
*/


function err_message ($err_code) {
	$msg = '';
	switch($err_code) {
		case 200:
			$msg = 'Success';
			break;

        case 406:
            $msg = 'Not Acceptable';
            break;

		case 801:
			// 허용되지 않은 사용자입니다. (code값 불일치)
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
			$msg = 'Processing failure';
			break;
		case 811:
			// 저장 중 오류가 발생했습니다.
			$msg = 'Error during saving';
			break;
		case 999:
			$msg = 'Error';
			break;
	}
	return $msg;
}


?>