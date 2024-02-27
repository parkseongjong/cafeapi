<?php
require_once 'config.php';

## 공통
$auth_key = (isset($_GET['auth_key'])) ? $_GET['auth_key'] : '';
$kind = (isset($_GET['kind'])) ? $_GET['kind'] : '';


## 공용상수
$http_root = 'https://cybertronchain.com/kiosk';
$adv_root = $http_root.'/adv/';
$img_root = $http_root.'/prod_img/';


if (empty($kind)) {
    jsonReturn(array('code'=>'88','msg'=>'알수없는 요청입니다.'));
}
if (empty($auth_key)) {
    if ($kind != 'regist') {
        jsonReturn(array('code'=>'99','msg'=>'잘못된 사용자입니다.'));
    }
}


## 매장 인증
/*
if ($auth_key != 'VIXBER_ILSAN_01') {
    jsonReturn(array('code'=>'99','msg'=>'잘못된 사용자입니다.'));
}
*/

$ok_json = array('code'=>'00','msg'=>'ok');


## API 요청 처리

if ($kind == 'init') {                                  // 초기화 작업. 장비 전원을 On 시킬때 호출됨.

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

} else if ($kind == 'intro') {                          // 손님이 없는 경우 광고로 보여질 풀사이즈 이미지 리턴

    $sql = "select * from adver where use_yn='Y' order by disp_cnt asc";
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

    $sql = "select * from category where use_yn='Y' order by disp_cnt asc";
    $res = $conn->query($sql);

    $items = array();

    foreach ($res as $row) {
        $items[] = array(
            'code' => $row['code'],
            'name' => $row['name'],
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
        $sql = "select * from goods where use_yn='Y' order by category_code asc, disp_cnt asc";
    } else {
        $sql = "select * from goods where use_yn='Y' and category_code='".$category."' order by disp_cnt asc";
    }

    $res = $conn->query($sql);

    $items = array();

    foreach ($res as $row) {
        $opt_yn = (trim($row['options'])=='') ? 'N' : 'Y';
        $items[] = array(
            'cate_code' => $row['category_code'],
            'code' => $row['code'],
            'name' => $row['name'],
            'price' => $row['price'],
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

    if (empty($goods)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }

    $sql = "select * from goods where code='".$goods."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    $items = array();

    $options = json_decode($row['options']);

    if (!empty($options) && $options > 0 ) {
        foreach($options as $k => $v) {

            $arr = array();

            foreach($v->option as $k2 => $v2) {
                $arr[] = array(
                    'name' => $v2->name,
                    'price' => $v2->price
                );
            }
            $items[$k] = array(
                'name' => $v->name,
                'option' => $arr
            );
        }
    }
    $ok_json['items'] = $items;

    jsonReturn($ok_json);

} else if ($kind == 'exchange') {                       // 화폐 변환. 원화 <--> TP3

    $price = (isset($_GET['price'])) ? $_GET['price'] : '';
    $unit = (isset($_GET['unit'])) ? $_GET['unit'] : 'KRW';                         // 생략시 KRW
    $target_unit = (isset($_GET['target_unit'])) ? $_GET['target_unit'] : 'TP3';    // 생략시 TP3

    if (empty($price)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. price'));
    }

    if (empty($unit)) $unit = 'KRW';
    if (empty($target_unit)) $target_unit = 'TP3';


    $tp3_per_rate = 60;         // TP3 당 원화

    // 비율값 가져오기
    $sql = "select * from wallet.settings where module_name='krw_per_tp3_kiosk'";
    $res = $connWallet->query($sql);
    $row = $res->fetch_assoc();

    if (!empty($row) && !empty($row['value'])) {
        $tmp = round($row['value']);
        if ($tmp >=50) $tp3_per_rate = $tmp;
    }

    $price = floor($price);     // 소숫점 버림
    $tp3 = $price / $tp3_per_rate;

    // 소숫점 버림
    $tp3 = floor($tp3);

    $ok_json['price'] = $tp3;
    $ok_json['unit'] = 'TP3';

    jsonReturn($ok_json);

} else if ($kind == 'qrcode') {                         // QR 이미지 생성

    $price = (isset($_GET['price'])) ? $_GET['price'] : '';
    $unit = (isset($_GET['unit'])) ? $_GET['unit'] : '';        // 생략시 TP3

    if (empty($price)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($unit)) $unit = 'tp3';

    $unit = strtolower($unit);

    if ($unit != 'tp3' && $unit != 'etp3') {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }

    $sql = "select * from franchise where auth_key='".$auth_key."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    if (empty($row) || empty($row['wallet_address'])) {
        jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
    }

    $chl = $row['wallet_address'] . "?amount=" . $price;
    //$chl = $row['wallet_address'] . "?amount=" . $price . "|" . strtolower($unit);

    $ok_json['img_url'] = 'https://chart.googleapis.com/chart?cht=qr&chs=400x400&chl=' . urlencode($chl);

    jsonReturn($ok_json);

} else if ($kind == 'check') {                          // 가상화폐 결제 결과 확인

    $price = (isset($_GET['price'])) ? $_GET['price'] : '';
    $unit = (isset($_GET['unit'])) ? $_GET['unit'] : '';
    $req_time = (isset($_GET['req_time'])) ? $_GET['req_time'] : '';    // 결제 시작 시간

    if (empty($price) || round($price) <= 0) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($req_time)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
    }
    if (empty($unit)) $unit = 'TP3';

    $unit = strtolower($unit);

    $sql = "select * from franchise where auth_key='".$auth_key."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    if (empty($row)) {
        jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
    }

    $wallet_address = $row['wallet_address'];       // 전송받을 가맹점의 주소

    $setContractAddr = $contractAddressArr[$unit]['contractAddress'];   // (=defined config.php)
    $page = 1;
    $offset = 10000;


    //$ethApiKey (=defined config.php)



    // 서버와 키오스크와의 시간차이가 있어서 일단 시간을 조금 앞당겨서 체크하고 있음.

    $req_time = date('Y-m-d H:i:s', strtotime($req_time.'-100 sec'));



    $req_millis = strtotime($req_time);     // 결제 시작시간

    //$now = date('Y-m-d H:i:s');
    $now_millis = time();                   // 현재 시간

    $run_sec = $now_millis - $req_millis;

    //echo "Pay start : {$req_time} <br />Now : {$now}<br />Pay start millis : {$req_millis}<br />Now millis : {$now_millis} <br />progress time(sec) : {$run_sec}<br />";
/*
    // 키오스크에서 5분 체크를 하기 때문에 서버에서 시간 체크는 안하는걸로 바뀜.
    if ($run_sec < 180) {

        $ethUrl = "http://api.etherscan.io/api?module=account&action=tokentx&contractaddress=".$setContractAddr."&address=".$wallet_address."&page=".$page."&offset=".$offset."&sort=desc&apikey=".$ethApiKey;
*/

    // 고객이 TP3나 eTP3 중 하나로 결제할 수 있기 때문에,
    // 두 개를 동시에 검사해야 함.

    if ($unit == 'tp3' || $unit == 'etp3') {

        $id= '0';   // 전송한 회원 id

        $sql = "select * from wallet.user_transactions_all where to_address='".$wallet_address."' and created_at >= '".$req_time."' and coin_type='tp3' and amount='".$price."'";    //store_name='vixber1' and 

        $res = $connWallet->query($sql);
        $row = $res->fetch_assoc();

        if (empty($row) || empty($row['store_result'])) {   // 토큰 (TP3)를 전송하지 않았으면, eTP3 를 전송했는지 한번 더 검사한다.

            $sql2 = "select * from wallet.etoken_logs where send_wallet_address='".$wallet_address."' and created_at >= '".$req_time."' and coin_type='etp3' and points='".(-1 * $price)."' and send_type='kiosk'";

            $res2 = $connWallet->query($sql2);
            $row2 = $res2->fetch_assoc();

            if (empty($row2)) {

                jsonReturn(array('code'=>'11','msg'=>'처리중입니다. '.$run_sec));

            } else {

                if (!empty($row2['user_id'])) $id = trim($row2['user_id']);

                $ok_json['approval_no'] = time()."_".rand(100,999);
                $ok_json['id'] = $id;

                jsonReturn($ok_json);
            }

        } else {
            if (isset($row['store_result'])) {
                switch($row['store_result']) {
                    case 'Y':   // 처리완료
                        break;
                    case 'N':   // 기본
                        break;
                    case 'F':   // 실패
                        break;
                    default:
                        break;
                }
            }

            if (!empty($row['from_id'])) $id = trim($row['from_id']);

            ## DB에 레코드가 생성되었다면 무조건 결제를 한것으로 보고 OK를 송신한다.

            $ok_json['approval_no'] = time()."_".rand(100,999);
            $ok_json['id'] = $id;

            jsonReturn($ok_json);
        }

    }

    jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (unit)'));
/*
    } else {
        jsonReturn(array('code'=>'22','msg'=>'취소되었습니다. '.$run_sec));
    }
*/
} else if ($kind == 'payment') {                        // 결제 결과 저장

    $pay_type = (isset($_GET['pay_type'])) ? $_GET['pay_type'] : '';
    $approval_no = (isset($_GET['approval_no'])) ? $_GET['approval_no'] : '';
    $total_price = (isset($_GET['total_price'])) ? $_GET['total_price'] : '';
    $from_id = (isset($_GET['from_id'])) ? $_GET['from_id'] : '';
    $ok_date = (isset($_GET['ok_date'])) ? $_GET['ok_date'] : '';
    $detail = (isset($_GET['detail'])) ? $_GET['detail'] : '';

    if (empty($pay_type)) {
        jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (pay_type)'));
    }
    if ($pay_type != 'CANCEL') {
        if ($from_id !== '0') {
            if (empty($from_id)) {
                jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (회원)'));
            }
        }
    }

    $sql = "select * from franchise where auth_key='".$auth_key."' and use_yn='Y'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();

    if (empty($row)) {
        jsonReturn(array('code'=>'66','msg'=>'정보가 없습니다.'));
    }

    if ($pay_type == 'CARD' || $pay_type == 'TP3' || $pay_type == 'ETP3') {

        if (empty($total_price) || round($total_price) <= 0) {
            jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다. (total_price)'));
        }

        $code = $row['code'];
        $fran_id = $row['id'];

        if (empty($ok_date)) $ok_date = date('Y-m-d H:i:s');

        if (empty($detail)) $detail = '';

        $sql2 = "insert into order_list (fran_id, pay_type, total_price, approval_no, from_id, ok_date, detail) values ('".$fran_id."', '".$pay_type."', '".$total_price."', '".$approval_no."', '".$from_id."', '".$ok_date."', '".$detail."')";
        $res2 = $conn->query($sql2);

        if ($res2===false) jsonReturn(array('code'=>'55','msg'=>'저장중 오류.'));

        jsonReturn($ok_json);

    } else if ($pay_type == 'CANCEL') {     // 걸재 삭제 (데이터만 삭제하며, 실제 카드취소나 암호화폐 환불은 수동으로 해야함)

        $fran_address = $row['wallet_address']; // 가맹점 지값주소

        $sql2 = "select * from order_list where approval_no='".$approval_no."'";
        $res2 = $conn->query($sql2);
        $row2 = $res2->fetch_assoc();

        if (empty($row2)) {
            jsonReturn(array('code'=>'66','msg'=>'결제 정보가 없습니다.'));
        }

        ## 결제 정보

        $order_id = $row2['id'];        // order 테이블의 id 값
        $from_id = $row2['from_id'];    // 결제한 회원의 id
        $total_price = $row2['total_price'];    // 결제금액
        $ok_date = $row2['ok_date'];    // 날짜 시간


        ## 삭제하지 않고, status='CANCEL' 로 바꾸는걸로 바꿈.
        //$sql = "update order_list set status='CANCEL' where id='".$row['id']."'";
        $sql = "update order_list set status='CANCEL' where id='".$order_id."'";
        $res = $conn->query($sql);

        ## TP3이면 환불처리

        if ($row2['pay_type']=='CARD') {
/*
            $sql2 = "delete from order_list where approval_no = '".$approval_no."'";
            $res2 = $conn->query($sql2);

            if ($res2===false) jsonReturn(array('code'=>'55','msg'=>'삭제중 오류.'));
*/
        } else if ($row2['pay_type']=='TP3') {

            ## 웹머니 환불 처리 시작

            $now = date('Y-m-d H:i:s');
            $start_date = date('Y-m-d H:i:s', strtotime($ok_date.'-1 minutes'));
            $end_date = date('Y-m-d H:i:s', strtotime($ok_date.'+1 minutes'));

            // 정보
            $sql3 = "select id, from_address, to_address, amount from wallet.user_transactions_all ";
            $sql3.= "where from_id='".$row2['from_id']."' and to_address='".$fran_address."' and send_type='send' and coin_type='".strtolower($row2['pay_type'])."' and amount='".$total_price."' and created_at >= '".$start_date."' and created_at <= '".$end_date."'";

            $res3 = $connWallet->query($sql3);
            $row3 = $res3->fetch_assoc();

            if (empty($row3)) {

                // 로그 정보가 없으면 ETP3 결제도 검사한다.
                $sql3 = "select * from wallet.etoken_logs ";
                $sql3.= "where send_wallet_address='".$fran_address."' and coin_type='etp3' and points='".(-1 * $total_price)."' and send_type='kiosk' and created_at >= '".$start_date."' and created_at <= '".$end_date."'";

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
/*
                    $sql4 = "insert into wallet.etoken_logs ";
                    $sql4.= "(user_id, coin_type, points, in_out, send_type, send_user_id, send_wallet_address, send_fee, created_at) ";
                    $sql4.= "values ";
                    $sql4.= "('".$fran_id."', 'etp3', '".(-1 * $total_price)."', 'out', 'kiosk_cancel', '".$user_id."', '".$user_address."', '0', '".$now."')";

                    $res4 = $conn->query($sql4);

                    if ($res4===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다. (4)'));

                    $sql5 = "update wallet.admin_accounts set etoken_etp3 = etoken_etp3 - ".$total_price." where id = '".$fran_id."'";
                    $res5 = $conn->query($sql5);

                    if ($res5===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다. (4-2)'));
*/


                    // 회원 적립
/*
                    $sql4 = "insert into wallet.etoken_logs ";
                    $sql4.= "(user_id, coin_type, points, in_out, send_type, send_user_id, send_wallet_address, send_fee, created_at) ";
                    $sql4.= "values ";
                    $sql4.= "('".$user_id."', 'etp3', '".$total_price."', 'in', 'kiosk_cancel', '".$fran_id."', '".$fran_address."', '0', '".$now."')";

                    $res4 = $conn->query($sql4);

                    if ($res4===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다. (5)'));

                    $sql5 = "update wallet.admin_accounts set etoken_etp3 = etoken_etp3 + ".$total_price." where id = '".$user_id."'";
                    $res5 = $conn->query($sql5);

                    if ($res5===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다. (5-2)'));
*/
                }

            } else {

                // TP3 취소 작업 시작

                $transaction_id = $row3['id'];
                $from_address = $row3['from_address'];
                $to_address = $row3['to_address'];
                $amount = $row3['amount'];

                $sql4 = "insert into wallet.kiosk_cancel_log ";
                $sql4.= "(user_transactions_all_id, approval_no, cancel_request_at, coin_type, from_address, to_address, amount) ";
                $sql4.= "values ";
                $sql4.= "('".$transaction_id."', '".$approval_no."', '".$now."', '".strtolower($row2['pay_type'])."', '".$to_address."', '".$from_address."', '".$amount."')";

                $res4 = $connWallet->query($sql4);

                if ($res4===false) jsonReturn(array('code'=>'55','msg'=>'취소 작업 도중 오류가 발생했습니다.'));
            }

            jsonReturn($ok_json);



        //
        // pay_type == ETP3 값은 들어오지 않으므로, 아래 작업은 실행되지 않는다.
        //
        } else if ($row2['pay_type']=='ETP3') {

            // 정보
            $sql3 = "select id, user_id, send_user_id, send_wallet_address from wallet.etoken_logs ";
            $sql3.= "where coin_type='etp3' and in_out='out' and points='-".$total_price."' and send_type='kiosk' and created_at >= '".$start_date."' and created_at <= '".$end_date."'";
            $res3 = $connWallet->query($sql3);
            $row3 = $res3->fetch_assoc();

            if (empty($row3)) {
                jsonReturn(array('code'=>'66','msg'=>'로그 정보를 찾을수 없습니다.'));
            }

            // 취소를 위한 작업 시작


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
    logWrite($arr);
    exit();
}

function logWrite($arr='') {
    $fname = "/var/www/html/kiosk/logs/" . date('Y-m-d') . ".txt";
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
