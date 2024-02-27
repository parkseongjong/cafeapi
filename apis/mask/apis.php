<?php
require_once '../config/config.php';
require_once '../config/config_mask.php';

## 공통
$auth_key = (isset($_GET['auth_key'])) ? $_GET['auth_key'] : '';
$kind = (isset($_GET['kind'])) ? $_GET['kind'] : '';


## 공용상수
$http_root = 'https://cybertronchain.com/apis/mask/imgs';
$adv_root = $http_root.'/adv/';
$img_root = $http_root.'/prod/';
$logo_root = $http_root.'/logo/';

$get_fran_id = '';

if (empty($kind)) {
    jsonReturn(array('code'=>'88','msg'=>'알수없는 요청입니다.'));
}
if (empty($auth_key)) {
    if ($kind != 'regist') {
        jsonReturn(array('code'=>'99','msg'=>'잘못된 사용자입니다.'));
    }
}

## 매장 인증
if ( $kind != 'regist' ) {
	$db = getDbInstance_k2();
	$db->where('auth_key', $auth_key);
	$db->where('use_yn', 'Y');
	$fran_row = $db->getOne('franchise');
	if (empty($fran_row) || empty($fran_row['wallet_address']) ) {
		jsonReturn(array('code'=>'99','msg'=>'잘못된 사용자입니다.'));
	} else {
		$get_fran_id = $fran_row['id'];
	}
}

/*
https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=payment_unit

https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=intro

https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=goods

https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=exchange&price=10000&target_unit=E-TP3
https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=exchange&price=10000&target_unit=E-MC

https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=qrcode&price=10000&unit=etp3

https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=check&payment_no=1606121873_100003_5109

https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=payment&payment_no=1606121873_100003_5109

https://cybertronchain.com/apis/mask/apis.php?auth_key=MASK_V_01&kind=payment&pay_type=etp3&payment_no=1606121873_100003_5109&total_price=10000
*/

$ok_json = array('code'=>'00','msg'=>'ok');


## API 요청 처리

switch($kind) {
	
	// 초기화 작업. 장비 전원을 On 시킬때 호출됨.
	case 'init':
		jsonReturn($ok_json);
		break;

	// 기기등록
	case 'regist':
		// code 값이 있고, Mac 값이 있으면 신규등록
		// code 값이 없고, Mac 값만 있으면 기존정보
		$code = (isset($_GET['code'])) ? $_GET['code'] : '';
		$mac_addr = (isset($_GET['mac_addr'])) ? $_GET['mac_addr'] : '';                // 생략가능

		if (empty($code) || empty($mac_addr)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}
		
		$db = getDbInstance_k2();
		$db->where('code', $code);
		$db->where('use_yn', 'Y');
		$row = $db->getOne('franchise');

		if (empty($row)) {
			jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
		}

		## 코드는 있는데 mac_addr이 다르면, 최초 입력이므로 update 기존 정보 정지후 신규 등록
		if ($row['mac_addr'] != $mac_addr) {

			$db = getDbInstance_k2();
			$updateArr = [];
			$updateArr['mac_addr'] = $mac_addr;
			$updateArr['use_yn']= 'Y';
			$updateArr['reg_date'] = date("Y-m-d H:i:s");
			$db->where('id', $row['id']);
			$last_id = $db->update('franchise', $updateArr);

			//$sql = "update franchise set mac_addr='".$mac_addr."', use_yn='Y', reg_date='".date('Y-m-d H:i:s')."' where id='".$row['id']."'";

			$ok_json['auth_key'] = $row['auth_key'];
			$ok_json['name'] = $row['name'];

		} else {

			## 코드는 있는데 mac_addr도 같으면, 정보 확인

			$ok_json['auth_key'] = $row['auth_key'];
			$ok_json['name'] = $row['name'];
		}

		jsonReturn($ok_json);

		break;

	// 가상화폐 결제 단위
	case 'payment_unit':

		$db = getDbInstance_k2();
		$db->where('use_yn', 'Y');
		$db->orderBy('disp_cnt', 'asc');
		$res = $db->get('payment_unit');
		// $sql = "select * from payment_unit where use_yn='Y' order by disp_cnt asc";

		$items = array();
		if ( $db->count > 0 ) {
			foreach ($res as $row) {
				$items[] = array(
					'unit' => $row['unit'],
					'btn_logo' => $logo_root.$row['btn_logo']
				);
			}
			$ok_json['items'] = $items;
		}

		jsonReturn($ok_json);

		break;
	
	// 손님이 없는 경우 광고로 보여질 풀사이즈 이미지 리턴
	case 'intro':

		$db = getDbInstance_k2();
		$db->where('fran_id', $get_fran_id);
		$db->where('use_yn', 'Y');
		$db->orderBy('disp_cnt', 'asc');
		$res = $db->get('adver');

		$items = array();
		if ( $db->count > 0 ) {
			foreach ($res as $row) {
				$items[] = array(
					'name' => $row['name'],
					'img_url' => $adv_root.$row['img_name'],
					'kind' => $row['link_type'],
					'time' => $row['delay_time'],
				);
			}
			$ok_json['items'] = $items;
		}

		jsonReturn($ok_json);
	
		break;

	// 상품 리스트
	case 'goods':

		$unit = (isset($_GET['unit'])) ? $_GET['unit'] : '';                // 생략가능

		$db = getDbInstance_k2();
		$db->where('fran_id', $get_fran_id);
		$db->where('use_yn', 'Y');
		$db->orderBy('disp_cnt', 'asc');
		$res = $db->get('goods_m');

		$items = array();
		if ( $db->count > 0 ) {
			foreach ($res as $row) {
				$items[] = array(
					'code' => $row['code'],
					'name' => $row['name'],
					'explanation' => $row['explanation'],
					'price' => $row['price'],
					'img_url' => $img_root.$row['img_name'],
				);
			}
			$ok_json['items'] = $items;
		}

		jsonReturn($ok_json);

		break;


	// 화폐 변환
	case 'exchange':

		$price = (isset($_GET['price'])) ? $_GET['price'] : '';
		$unit = (isset($_GET['unit'])) ? $_GET['unit'] : 'KRW';                         // 생략시 KRW
		$target_unit = (isset($_GET['target_unit'])) ? $_GET['target_unit'] : '';

		if (empty($price)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. price'));
		}
		if (empty($target_unit)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. target_unit'));
		}

		if (empty($unit)) $unit = 'KRW';

		if ($target_unit != 'E-TP3' && $target_unit != 'E-MC') {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}
	
		$module_name = 'krw_per_tp3_kiosk';
		$module_name2 = 'exchange_etp3_per_tp3';
		$coin_per_rate = 60;         // TP3 당 원화
		$coin_minimum = 50;
		if ( $target_unit == 'E-MC' ) {
			$module_name = 'krw_per_mc_kiosk';
			$module_name2 = 'exchange_emc_per_mc';
			$coin_per_rate = 60;         // MC 당 원화
			$coin_minimum = 50;
		}
		
		// 비율값 가져오기
		$db = getDbInstance();
		$db->where('module_name', $module_name);
		$row = $db->getOne('settings');
			
		if (!empty($row) && !empty($row['value'])) {
			$tmp = round($row['value']);
			if ($tmp >=$coin_minimum) $coin_per_rate = $tmp;
		}
		$price = floor($price);     // 소숫점 버림
		$coin = $price / $coin_per_rate;

		$e_coin_rate = 1;

		
		// 1 Coin당 몇 E-Pay 인지
		$db = getDbInstance();
		$db->where('module_name', $module_name2);
		$row2 = $db->getOne('settings');
		if (!empty($row2) && !empty($row2['value'])) {
			$e_coin_rate = $row2['value'];
		}
		$coin = $coin*$e_coin_rate;
	
		// 소숫점 버림
		$coin = floor($coin);

		$ok_json['price'] = $coin;
		$ok_json['unit'] = $target_unit;

		jsonReturn($ok_json);

		break;


	// QR 이미지 생성
	case 'qrcode':


		$price = (isset($_GET['price'])) ? $_GET['price'] : '';
		$unit = (isset($_GET['unit'])) ? $_GET['unit'] : '';

		if (empty($price)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}
		if (empty($unit)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}

		$unit = unit_change_unit($unit); // E-TP3 -> etp3

		if ($unit != 'etp3' && $unit != 'emc') {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}

		if ( empty($fran_row['wallet_address']) ) {
			jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
		}

		$payment_no = create_payment_no($get_fran_id, $fran_row['code']);
		$chl = $fran_row['wallet_address'] . "?amount=" . $price . "|" . strtolower($unit) . "_" . $payment_no;
		$ok_json['payment_no'] = $payment_no;
		$ok_json['img_url'] = 'https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=' . urlencode($chl);
		jsonReturn($ok_json);

		break;

	
	// 가상화폐 결제 결과 확인
	case 'check':

		$payment_no = (isset($_GET['payment_no'])) ? $_GET['payment_no'] : '';

		if (empty($payment_no)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}
		
		$db = getDbInstance();
		$db->where('kiosk_payment_no', $payment_no);
		$row2 = $db->getOne('etoken_logs');

		if ( empty($row2) ) {
			jsonReturn(array('code'=>'11','msg'=>'처리중입니다.'));
		} else {
			jsonReturn($ok_json);
		}

		break;


	// 결제 결과 저장
	case 'payment':

		$payment_no = (isset($_GET['payment_no'])) ? $_GET['payment_no'] : '';
		$pay_type = (isset($_GET['pay_type'])) ? $_GET['pay_type'] : '';
		$total_price = (isset($_GET['total_price'])) ? $_GET['total_price'] : '';
		$ok_date = (isset($_GET['ok_date'])) ? $_GET['ok_date'] : '';
		$detail = (isset($_GET['detail'])) ? $_GET['detail'] : '';

		if (empty($payment_no)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (payment_no)'));
		}
		if (empty($pay_type)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (pay_type)'));
		}
		if (empty($total_price) || round($total_price) <= 0) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (total_price)'));
		}
		if ($pay_type != 'E-TP3' && $pay_type != 'E-MC' && $pay_type != 'CARD') {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}

        if (empty($ok_date)) $ok_date = date('Y-m-d H:i:s');

        if (empty($detail)) $detail = '';

		$db = getDbInstance_k2();
		$insertArr = [];
		$insertArr['fran_id'] = $get_fran_id;
		$insertArr['pay_type'] = $pay_type;
		$insertArr['total_price'] = $total_price;
		$insertArr['payment_no'] = $payment_no;
		$insertArr['ok_date'] = $ok_date;
		$insertArr['detail'] = $detail;

		$order_list_logs1 = $db->insert('order_list', $insertArr);

		if ( empty($order_list_logs1) ) {
			jsonReturn(array('code'=>'55','msg'=>'저장중 오류.'));
		}


		if ( $detail == '' ) {

			
			$db = getDbInstance_k2();
			$insertArr2 = [];
			$insertArr2['fran_id'] = $get_fran_id;
			$insertArr2['pay_type'] = $pay_type;
			$insertArr2['total_price'] = $total_price;
			$insertArr2['payment_no'] = $payment_no;
			$insertArr2['ok_date'] = $ok_date;
			$order_list_logs2 = $db->insert('order_list_detail', $insertArr2);
			if ( empty($order_list_logs2) ) {
				jsonReturn(array('code'=>'55','msg'=>'저장중 오류.'));
			}
			
		} else {
			$detail_array =json_decode($detail, true);
			foreach($detail_array as $k1=>$v1) {
				$detail_code = !empty($v1['code']) ? $v1['code'] : '';
				$detail_name = !empty($v1['name']) ? $v1['name'] : '';
				$detail_count = !empty($v1['count']) ? $v1['count'] : '';
				$detail_price = !empty($v1['price']) ? $v1['price'] : '';
				//$detail_option = !empty($v1['option']) ? $v1['option'] : '';


					
				$db = getDbInstance_k2();
				$insertArr2 = [];
				$insertArr2['fran_id'] = $get_fran_id;
				$insertArr2['pay_type'] = $pay_type;
				$insertArr2['total_price'] = $total_price;
				$insertArr2['payment_no'] = $payment_no;
				$insertArr2['ok_date'] = $ok_date;
				
				$insertArr2['detail_code'] = $detail_code;
				$insertArr2['detail_name'] = $detail_name;
				$insertArr2['detail_count'] = $detail_count;
				$insertArr2['detail_price'] = $detail_price;
				//$insertArr2['detail_option'] = $detail_option;

				$order_list_logs2 = $db->insert('order_list_detail', $insertArr2);
				if ( empty($order_list_logs2) ) {
					jsonReturn(array('code'=>'55','msg'=>'저장중 오류.'));
				}
			}
		}

		jsonReturn($ok_json);

		break;

	case 'inventory':

		$code = (isset($_GET['code'])) ? $_GET['code'] : '';

		if (empty($code)) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}





		jsonReturn($ok_json);

		break;
	
	// 정의되지 않은 요청 구분 코드
	default:
		jsonReturn(array('code'=>'88','msg'=>'알수없는 요청입니다.'));
		break;
} // switch


?>
