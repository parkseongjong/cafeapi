<?php
require_once 'config.php';

## 공통
$auth_key = (isset($_GET['auth_key'])) ? $_GET['auth_key'] : '';
$kind = (isset($_GET['kind'])) ? $_GET['kind'] : '';


## 공용상수
$http_root = 'https://cybertronchain.com/kiosk2';
$adv_root = $http_root.'/adv/';
$img_root = $http_root.'/prod_img/';
$logo_root = $http_root.'/logo_img/';

$get_fran_id = '';

if (empty($kind)) {
    jsonReturn(array('code'=>'88','msg'=>'알수없는 요청입니다.'));
}
if (empty($auth_key)) {
    if ($kind != 'regist') {
        jsonReturn(array('code'=>'99','msg'=>'잘못된 사용자입니다.'));
    }
}

if ( $kind != 'regist' ) {
	$sql = "select * from franchise where auth_key='".$auth_key."' and use_yn='Y'";
	$res = $conn->query($sql);
	$row = $res->fetch_assoc();
	if (empty($row)) {
		jsonReturn(array('code'=>'99','msg'=>'잘못된 사용자입니다.'));
	} else {
		$get_fran_id = $row['id'];
	}
}

## 매장 인증
/*
if ($auth_key != 'VIXBER_ILSAN_01') {
    jsonReturn(array('code'=>'99','msg'=>'잘못된 사용자입니다.'));
}
*/
/*
https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=payment_unit

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=intro

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=category

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=goods&category=C01
https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=goods&category=9999

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=option&goods=C03-01&lang=
https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=option&goods=C03-01&lang=en

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=exchange&price=10000&target_unit=etp3
https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=exchange&price=10000&target_unit=emc

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=qrcode&price=10000&unit=etp3

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=check&payment_no=1606121873_100003_5109

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=payment&payment_no=1606121873_100003_5109

https://cybertronchain.com/kiosk2/apis.php?auth_key=VIXBER_ILSAN_VA_01&kind=payment&pay_type=etp3&payment_no=1606121873_100003_5109&total_price=10000
*/
$ok_json = array('code'=>'00','msg'=>'ok');



## API 요청 처리

if ($kind == 'init') {                                  // 초기화 작업. 장비 전원을 On 시킬때 호출됨.

    //$ok_json['server_time'] = date('Y-m-d H:i:s');

    jsonReturn($ok_json);

} else if ($kind == 'regist') {                         // 기기등록

    ## code 값이 있고, Mac 값이 있으면 신규등록
    ## code 값이 없고, Mac 값만 있으면 기존정보

    $code = (isset($_GET['code'])) ? $_GET['code'] : '';
    $mac_addr = (isset($_GET['mac_addr'])) ? $_GET['mac_addr'] : '';                // 생략가능

    if (empty($code) || empty($mac_addr)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }

    $sql = "select * from franchise where code='".$code."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    if (empty($row)) {
        jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
    }

    ## 코드는 있는데 mac_addr이 다르면, 최초 입력이므로 update 기존 정보 정지후 신규 등록
    if ($row['mac_addr'] != $mac_addr) {
        $sql = "update franchise set mac_addr='".$mac_addr."', use_yn='Y', reg_date='".date('Y-m-d H:i:s')."' where id='".$row['id']."'";
        $res = $conn->query($sql);

        $ok_json['auth_key'] = $row['auth_key'];
        $ok_json['name'] = $row['name'];

    } else {

        ## 코드는 있는데 mac_addr도 같으면, 정보 확인

        $ok_json['auth_key'] = $row['auth_key'];
        $ok_json['name'] = $row['name'];
    }

    jsonReturn($ok_json);

} else if ($kind == 'payment_unit') {                          // 가상화폐 결제 단위

    $sql = "select * from payment_unit where use_yn='Y' order by disp_cnt asc";
    $res = $conn->query($sql);

    $items = array();

    foreach ($res as $row) {
        $items[] = array(
            'unit' => $row['unit'],
            'btn_logo' => $logo_root.$row['btn_logo']
        );
    }
    $ok_json['items'] = $items;

    jsonReturn($ok_json);

} else if ($kind == 'intro') {                          // 손님이 없는 경우 광고로 보여질 풀사이즈 이미지 리턴

    $sql = "select * from adver where fran_id='".$get_fran_id."' and use_yn='Y' order by disp_cnt asc";
    $res = $conn->query($sql);

    $items = array();

    foreach ($res as $row) {
        $items[] = array(
            'name' => $row['name'],
            'img_url' => $adv_root.$row['img_name'],
            'kind' => 'IMG',
            'time' => $row['delay_time'],
        );
    }
    $ok_json['items'] = $items;

    jsonReturn($ok_json);

} else if ($kind == 'category') {                       // 화면 상단 카테고리 메뉴

    $sql = "select * from category where fran_id='".$get_fran_id."' and use_yn='Y' order by disp_cnt asc";
    $res = $conn->query($sql);

    $items = array();

    foreach ($res as $row) {
        $items[] = array(
            'code' => $row['code'],
            'name' => $row['name'],
            'name_en' => $row['name_en'],
        );
    }
    $ok_json['items'] = $items;

    jsonReturn($ok_json);

} else if ($kind == 'goods') {                        // 선택한 카테고리의 상품 리스트

    $category = (isset($_GET['category'])) ? $_GET['category'] : '';
    $unit = (isset($_GET['unit'])) ? $_GET['unit'] : '';                // 생략가능

    if (empty($category)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }

    if ($category=='9999') {
        $sql = "select * from goods where fran_id='".$get_fran_id."' and use_yn='Y' order by category_code asc, disp_cnt asc";
    } else {
        $sql = "select * from goods where fran_id='".$get_fran_id."' and use_yn='Y' and category_code='".$category."' order by disp_cnt asc";
    }

    $res = $conn->query($sql);

    $items = array();

    foreach ($res as $row) {
        $opt_yn = (trim($row['options'])=='') ? 'N' : 'Y';
        $items[] = array(
            'cate_code' => $row['category_code'],
            'code' => $row['code'],
            'name' => $row['name'],
            'name_en' => $row['name_en'],
            'price' => $row['price'],
            'pack_price' => $row['pack_price'],
            'opt_yn' => $opt_yn,
            'img_url' => $img_root.$row['img_name'],
        );
    }
    $ok_json['items'] = $items;

    jsonReturn($ok_json);

} else if ($kind == 'option') {                         // 선택한 상품의 옵션 리스트

    $category = (isset($_GET['category'])) ? $_GET['category'] : '';    // 생략가능
    $goods = (isset($_GET['goods'])) ? $_GET['goods'] : '';
    $unit = (isset($_GET['unit'])) ? $_GET['unit'] : '';                // 생략가능
    $lang = (isset($_GET['lang'])) ? $_GET['lang'] : '';                // 생략가능

    if (empty($goods)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($lang)) {
        $lang = 'ko';
    }
    if ($lang != 'ko' && $lang != 'en') {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }

    $sql = "select * from goods where fran_id='".$get_fran_id."' and code='".$goods."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    $items = array();

    $options = json_decode($row['options']);
	if ( $lang == 'en' ) {
		$options = json_decode($row['options_en']);
	}

    if (!empty($options) && $options > 0 ) {
        foreach($options as $k => $v) {

            $arr = array();
			$option_subject = '';
			$option_subject = change_option_subject($v->name, $lang);

            foreach($v->option as $k2 => $v2) {
				$arr[] = array(
					'name' => $v2->name,
					'price' => $v2->price
				);
            }
            $items[$k] = array(
                'name' => $v->name,
				'subject' => $option_subject,
                'option' => $arr
            );
        }
    }
    $ok_json['items'] = $items;




    jsonReturn($ok_json);

} else if ($kind == 'exchange') {                       // 화폐 변환. 원화 <--> TP3

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
	$target_unit = strtoupper($target_unit);

    if ($target_unit != 'ETP3' && $target_unit != 'EMC') {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }

	// 201016, 단위 추가
	$module_name = 'krw_per_tp3_kiosk';
    $coin_per_rate = 60;         // TP3 당 원화
	$coin_minimum = 50;
	if ( $target_unit == 'EMC' ) {
		$module_name = 'krw_per_mc_kiosk';
		$coin_per_rate = 60;         // MC 당 원화
		$coin_minimum = 50;
	}

    // 비율값 가져오기
    $sql = "select * from wallet.settings where module_name='".$module_name."'";
    $res = $connWallet->query($sql);
    $row = $res->fetch_assoc();

    if (!empty($row) && !empty($row['value'])) {
        $tmp = round($row['value']);
        if ($tmp >=$coin_minimum) $coin_per_rate = $tmp;
    }
    $price = floor($price);     // 소숫점 버림
    $coin = $price / $coin_per_rate;

	$e_coin_rate = 1;
	if ( $target_unit == 'ETP3' ) {
		$sql = "select * from wallet.settings where module_name='exchange_etp3_per_tp3'"; // 1TP3당 몇 eTP3인지
		$res = $connWallet->query($sql);
		$row2 = $res->fetch_assoc();
		if (!empty($row2) && !empty($row2['value'])) {
			$e_coin_rate = $row2['value'];
		}
		$coin = $coin*$e_coin_rate;
	}
	if ( $target_unit == 'EMC' ) {
		$sql = "select * from wallet.settings where module_name='exchange_emc_per_mc'"; // 1MC당 몇 eMC인지
		$res = $connWallet->query($sql);
		$row2 = $res->fetch_assoc();
		if (!empty($row2) && !empty($row2['value'])) {
			$e_coin_rate = $row2['value'];
		}
		$coin = $coin*$e_coin_rate;
	}
	
    // 소숫점 버림
    $coin = floor($coin);

    $ok_json['price'] = $coin;
    $ok_json['unit'] = $target_unit;

    jsonReturn($ok_json);

} else if ($kind == 'qrcode') {                         // QR 이미지 생성

    $price = (isset($_GET['price'])) ? $_GET['price'] : '';
    $unit = (isset($_GET['unit'])) ? $_GET['unit'] : '';

    if (empty($price)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($unit)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }
	
    $unit = strtolower($unit);

    if ($unit != 'etp3' && $unit != 'emc') {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }

    $sql = "select * from franchise where auth_key='".$auth_key."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    if (empty($row) || empty($row['wallet_address'])) {
        jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
    }

    //$chl = $row['wallet_address'] . "?amount=" . $price;
	$payment_no = create_payment_no($row['code']);
    $chl = $row['wallet_address'] . "?amount=" . $price . "|" . strtolower($unit) . "_" . $payment_no;
    $ok_json['payment_no'] = $payment_no;

    $ok_json['img_url'] = 'https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=' . urlencode($chl);

    jsonReturn($ok_json);

} else if ($kind == 'check') {                          // 가상화폐 결제 결과 확인

    $payment_no = (isset($_GET['payment_no'])) ? $_GET['payment_no'] : '';

    if (empty($payment_no)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }

    $sql = "select * from franchise where auth_key='".$auth_key."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    if (empty($row)) {
        jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
    }

    $wallet_address = $row['wallet_address'];       // 전송받을 가맹점의 주소
	


	$sql2 = "select * from wallet.etoken_logs where kiosk_payment_no = '".$payment_no."'";
	// $sql2 = "select * from wallet.etoken_logs where send_wallet_address='".$wallet_address."' and created_at >= '".$req_time."' and coin_type='".$unit."' and points='".(-1 * $price)."' and send_type='kiosk'";
	$res2 = $connWallet->query($sql2);
	$row2 = $res2->fetch_assoc();

	if (empty($row2)) {

		jsonReturn(array('code'=>'11','msg'=>'처리중입니다. '.$run_sec));

	} else {

		jsonReturn($ok_json);
	}



    jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (unit)'));

} else if ($kind == 'payment') {                        // 결제 결과 저장

    $pay_type = (isset($_GET['pay_type'])) ? $_GET['pay_type'] : '';
	$payment_no = (isset($_GET['payment_no'])) ? $_GET['payment_no'] : '';
    $total_price = (isset($_GET['total_price'])) ? $_GET['total_price'] : '';
    $ok_date = (isset($_GET['ok_date'])) ? $_GET['ok_date'] : '';
    $detail = (isset($_GET['detail'])) ? $_GET['detail'] : '';

    if (empty($pay_type)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (pay_type)'));
    }
    if (empty($payment_no)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (payment_no)'));
    }

    $sql = "select * from franchise where auth_key='".$auth_key."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    if (empty($row)) {
        jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
    }
	
	if ( strtoupper($pay_type) == 'ETP3' || strtoupper($pay_type) == 'EMC' ) {
		$pay_type_u = strtoupper($pay_type);
	}
	

    if ($pay_type == 'CARD' || $pay_type_u == 'ETP3' || $pay_type_u == 'EMC') { // 201016

        if (empty($total_price) || round($total_price) <= 0) {
            jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (total_price)'));
        }

        $code = $row['code'];
        $fran_id = $row['id'];

        if (empty($ok_date)) $ok_date = date('Y-m-d H:i:s');

        if (empty($detail)) $detail = '';

        $sql2 = "insert into order_list (fran_id, pay_type, total_price, payment_no, ok_date, detail) values ('".$fran_id."', '".$pay_type_u."', '".$total_price."', '".$payment_no."', '".$ok_date."', '".$detail."')";
        $res2 = $conn->query($sql2);

        if ($res2===false) jsonReturn(array('code'=>'55','msg'=>'저장중 오류.'));


		
		if ( $detail == '' ) {
			$sql3 = "insert into order_list_detail (fran_id, pay_type, total_price, payment_no, ok_date) values ('".$fran_id."', '".$pay_type_u."', '".$total_price."', '".$payment_no."', '".$ok_date."')";
			$res3 = $conn->query($sql3);
			 if ($res3===false) jsonReturn(array('code'=>'55','msg'=>'저장중 오류.'));
		} else {
			$detail_array =json_decode($detail, true);
			foreach($detail_array as $k1=>$v1) {
				$detail_code = !empty($v1['code']) ? $v1['code'] : '';
				$detail_name = !empty($v1['name']) ? $v1['name'] : '';
				$detail_count = !empty($v1['count']) ? $v1['count'] : '';
				$detail_price = !empty($v1['price']) ? $v1['price'] : '';
				$detail_option = !empty($v1['option']) ? $v1['option'] : '';
				$sql3 = "insert into order_list_detail (fran_id, pay_type, total_price, payment_no, ok_date, detail_code, detail_name, detail_count, detail_price, detail_option) values ('".$fran_id."', '".$pay_type_u."', '".$total_price."', '".$payment_no."', '".$ok_date."', '".$detail_code."', '".$detail_name."', '".$detail_count."', '".$detail_price."', '".$detail_option."')";
				$res3 = $conn->query($sql3);
				if ($res3===false) jsonReturn(array('code'=>'55','msg'=>'저장중 오류.'));
			}
		}




        jsonReturn($ok_json);

    } else if ($pay_type == 'CANCEL') {     // 결제 삭제 (데이터만 삭제하며, 실제 카드취소나 암호화폐 환불은 수동으로 해야함)

        $fran_address = $row['wallet_address']; // 가맹점 지값주소

        $sql2 = "select * from order_list where payment_no='".$payment_no."'";
        $res2 = $conn->query($sql2);
        $row2 = $res2->fetch_assoc();

        if (empty($row2)) {
            jsonReturn(array('code'=>'66','msg'=>'결제 정보가 없습니다.'));
        }

		// 이미 취소된 건인 경우
		if ( $row2['status'] == 'CANCEL' ) {
            jsonReturn(array('code'=>'44','msg'=>'이미 취소되었습니다.'));
		}

        ## 결제 정보

        $order_id = $row2['id'];        // order 테이블의 id 값
        //$from_id = $row2['from_id'];    // 결제한 회원의 id
        $total_price = $row2['total_price'];    // 결제금액
        //$ok_date = $row2['ok_date'];    // 날짜 시간

		//$pay_type_s = strtolower($row2['pay_type']);


        ## 삭제하지 않고, status='CANCEL' 로 바꾸는걸로 바꿈.
        //$sql = "update order_list set status='CANCEL' where id='".$row['id']."'";
        $sql = "update order_list set status='CANCEL' where id='".$order_id."'";
        $res = $conn->query($sql);

		// order_list_detail에도 적용할 것

        ## TP3이면 환불처리

        if ($row2['pay_type']=='CARD') {
		} else if ($row2['pay_type']=='ETP3' || $row2['pay_type']=='EMC') {
			
           // $now = date('Y-m-d H:i:s');
            //$start_date = date('Y-m-d H:i:s', strtotime($ok_date.'-2 minutes'));
            //$end_date = date('Y-m-d H:i:s', strtotime($ok_date.'+2 minutes'));
			
			$sql3 = "select * from wallet.etoken_logs ";
			$sql3.= "where kiosk_payment_no='".$payment_no."' and send_type='kiosk'  and points='".(-1 * $total_price)."'";
			//$sql3.= "where send_wallet_address='".$fran_address."' and coin_type='".$pay_type_s."' and points='".(-1 * $total_price)."' and send_type='kiosk' and created_at >= '".$start_date."' and created_at <= '".$end_date."'"; // 이거 사용하면 +-1분 시간이 안맞아서 2분으로 수정하고 아래결로 대체함. 2020-11-12
			//$sql3.= "where send_wallet_address='".$fran_address."' and coin_type='".$pay_type_s."' and points='".(-1 * $total_price)."' and send_type='kiosk' and created_at >= '".$start_date."' and created_at <= '".$end_date."' and user_id = '".$from_id."'";

			$res3 = $connWallet->query($sql3);
			$row3 = $res3->fetch_assoc();

			if (empty($row3)) {
				jsonReturn(array('code'=>'66','msg'=>'로그 정보를 찾을수 없습니다.'));

			} else {

				// ETP3 취소 작업 시작
				$fran_id = $row3['send_user_id'];
				//$fran_address = $row3['send_wallet_address']; // 가맹점 지값주소

				$user_id = $row3['user_id'];

				$sql4 = "select * from wallet.admin_accounts where id='".$user_id."'";
				$res4 = $connWallet->query($sql4);
				$row4 = $res4->fetch_assoc();

				if (empty($row4)) {
					jsonReturn(array('code'=>'66','msg'=>'회원 정보가 없습니다.'));
				}
				$user_address = $row4['wallet_address']; // 회원 지값주소

				// 가맹점 차감

				$sql4 = "insert into wallet.etoken_logs ";
				$sql4.= "(user_id, wallet_address, coin_type, points, in_out, send_type, send_user_id, send_wallet_address, send_fee, created_at) ";
				$sql4.= "values ";
				$sql4.= "('".$fran_id."', '".$fran_address."', '".$pay_type_s."', '".(-1 * $total_price)."', 'out', 'kiosk_cancel', '".$user_id."', '".$user_address."', '0', '".$now."')";

				$res4 = $connWallet->query($sql4);

				if ($res4===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다. (4)'));

				$sql5 = "update wallet.admin_accounts set etoken_".$pay_type_s." = etoken_".$pay_type_s." - ".$total_price." where id = '".$fran_id."'";
				$res5 = $connWallet->query($sql5);

				if ($res5===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다. (4-2)'));



				// 회원 적립

				$sql4 = "insert into wallet.etoken_logs ";
				$sql4.= "(user_id, wallet_address, coin_type, points, in_out, send_type, send_user_id, send_wallet_address, send_fee, created_at) ";
				$sql4.= "values ";
				$sql4.= "('".$user_id."', '".$user_address."', '".$pay_type_s."', '".$total_price."', 'in', 'kiosk_cancel', '".$fran_id."', '".$fran_address."', '0', '".$now."')";

				$res4 = $connWallet->query($sql4);

				if ($res4===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다. (5)'));

				$sql5 = "update wallet.admin_accounts set etoken_".$pay_type_s." = etoken_".$pay_type_s." + ".$total_price." where id = '".$user_id."'";
				$res5 = $connWallet->query($sql5);

				if ($res5===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다. (5-2)'));

			}



            jsonReturn($ok_json);

        } else {
            jsonReturn(array('code'=>'77','msg'=>'필수값이 잘못되었습니다.'));
        }

        jsonReturn($ok_json);

    } else {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (pay_type2)'));
    }

} else {                                                // 정의되지 않은 요청 구분 코드
    jsonReturn(array('code'=>'88','msg'=>'알수없는 요청입니다.'));
}



function jsonReturn($arr='') {
    if (empty($arr)) {
        $arr = array('code'=>'99','msg'=>'Error');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    } else {
        if (is_array($arr)) {
            echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode(array('code'=>'99','msg'=>$arr), JSON_UNESCAPED_UNICODE);
        }
    }
    //logWrite($arr);
    exit();
}

function logWrite($arr='') {
    $fname = "/var/www/html/kiosk2/logs/" . date('Y-m-d') . ".txt";
    $f = fopen($fname, "a");
    fwrite($f, "[".date('Y-m-d H:i:s')."] : ".$_SERVER['REMOTE_ADDR']."\n");
    fwrite($f, "[GET] ---------------\n");
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
}

// payment_no에는 _가 포함되어서는 안된다.
function create_payment_no($code) {
	if ( $code ) {
		$r = time().$code.rand(1000,9999);
	} else {
		$r = time().rand(1000,9999);
	}

	/*
	$sql = "select * from wallet.etoken_logs where kiosk_payment_no='".$payment_no."'";
	$res = $conn->query($sql);
	$row = $res->fetch_assoc();
	if (empty($row)) {
		return $r;
	} else {
		$r = create_payment_no($code);
	}
	*/


	return $r;
}



function change_option_subject ($val, $lang) {
	$result = '옵션을 선택해주세요.';
	switch($val) {
		case '사이즈':
		case 'Size':
			if ( $lang == 'en' ) {
				$result = 'Please select a size';
			} else {
				$result = '사이즈를 선택해주세요.';
			}
			break;
		case '옵션':
		case 'Option':
			if ( $lang == 'en' ) {
				$result = 'Please select a option';
			} else {
				$result = '옵션을 선택해주세요.';
			}
			break;
	}
	return $result;
}