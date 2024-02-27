<?php
// 사용안함
require_once '../config/config.php';
require_once '../config/config_wallet.php';

## 공통
$auth_key = (isset($_POST['auth_key'])) ? $_POST['auth_key'] : '';
$kind = (isset($_POST['kind'])) ? $_POST['kind'] : '';

if (empty($kind)) {
    jsonReturn(array('code'=>'88','msg'=>'알수없는1 요청입니다.'));
}

if (empty($auth_key)) {
    jsonReturn(array('code'=>'88','msg'=>'알수없는2 요청입니다.'));
}
if ( $auth_key != $w_api_key ) {
    jsonReturn(array('code'=>'88','msg'=>'알수없는3 요청입니다.'));
}

$ok_json = array('code'=>'00','msg'=>'ok');


## API 요청 처리

switch($kind) {

	/*
	request : auth_key, st, q(검색어), offset(pagelimit), page, filter_col, order_by
	response : code, msg, totalPages, items(from_name, from_address, to_name, to_address, coin, fee, fee_unit, date)
	*/
	/*
	case 'e_pay_list':
		$fee_unit = 'E-CTC'; // 수수료 단위


		$st = (isset($_POST['st'])) ? $_POST['st'] : '';
		$q = (isset($_POST['q'])) ? $_POST['q'] : '';
		$offset = (isset($_POST['offset'])) ? $_POST['offset'] : '';
		$page = (isset($_POST['page'])) ? $_POST['page'] : '';
		$filter_col = (isset($_POST['filter_col'])) ? $_POST['filter_col'] : '';
		$order_by = (isset($_POST['order_by'])) ? $_POST['order_by'] : '';
		
		if ( empty($st) || empty($q) ) {
			jsonReturn(array('code'=>'77','msg'=>'필수값이 누락되었습니다.'));
		}
		
		if ( $offset == '' ) {
			$offset = 10;
		}
		if ( $page == '' ) {
			$page = 1;
		}
		if ($filter_col == "") {
			$filter_col = "id";
		}
		if ($order_by == "") {
			$order_by = "desc";
		}
		
		$db = getDbInstance();
		if ( $st == 'addr' ) {
			$db->where('wallet_address', $q);
		}
		if ($order_by) {
			$db->orderBy($filter_col, $order_by);
		}
		$db->pageLimit = $offset;
		$resultData = $db->arraybuilder()->paginate("etoken_logs", $page);
		$total_pages = $db->totalPages;
		
		$ok_json['totalPages'] = $total_pages;

		$items = array();
		foreach($resultData as $row) {

			$coin = new_number_format($row['points'], $n_decimal_point_array2[$row['coin_type']]).' '.$n_epay_name_array[$row['coin_type']];

			$db = getDbInstance();
			$db->where('id', $row['user_id']);
			$getData = $db->getOne('admin_accounts');
			$name = '';
			$name = get_user_real_name($getData['auth_name'], $getData['name'], $getData['lname']);

			$db = getDbInstance();
			$db->where('id', $row['send_user_id']);
			$getTargetData = $db->getOne('admin_accounts');
			$target_name = '';
			$target_name = get_user_real_name($getTargetData['auth_name'], $getTargetData['name'], $getTargetData['lname']);


			$addr = $row['wallet_address']; // 거래당시 주소
			$addr = addr_text_block($addr);
			
			$target_addr = $row['send_wallet_address'];
			$target_addr = addr_text_block($target_addr);

			if ( $row['in_out'] == 'out' ) {
				// from : user_id, to : send_user_id
				$from_addr = $addr;
				$from_name = $name;
				$to_addr = $target_addr;
				$to_name = $target_name;
			} else {
				// from : send_user_id, to : user_id
				$from_addr = $target_addr;
				$from_name = $target_name;
				$to_addr = $addr;
				$to_name = $name;
			}
			$items[] = array(
				'from_name' => $from_name,
				'from_address' => $from_addr,
				'to_name' => $to_name,
				'to_address' => $to_addr,
				'coin' => $coin,
				'fee' => $row['send_fee'],
				'fee_unit' => $fee_unit,
				'date' => $row['created_at']
			);
		} // foreach

		$ok_json['items'] = $items;

		jsonReturn($ok_json);

		
		break;
		*/

	// 정의되지 않은 요청 구분 코드
	default:
		jsonReturn(array('code'=>'88','msg'=>'알수없는 요청입니다.'));
		break;
} // switch



?>
