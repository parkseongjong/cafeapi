<?php

$wallet_directory_root = '/var/www/html/wallet2';
$barry_encrypt_root = '/var/www/ctc/apis/barry';

require_once $wallet_directory_root.'/config/new_config.php';

// apis - kind=finish���� �����
$barry_beepoint_cashback = 20; // KRW�� ��ȯ�� �ݾ��� 20%�� ������Ʈ�� ����
$barry_payback_amount = 98; // �Ǹűݾ��� 98%�� �Ǹ����ּҷ� ������

/*
$n_transfer_pw_count = 10;


$n_master_id_fee = 40;
$n_master_wallet_address_fee = "0xB124556aCb6703cbF9b1244A18B72091734025c4";

$n_master_etoken_ctc_fee_id = $n_master_id_fee;
$n_master_etoken_ctc_fee_wallet_address = $n_master_wallet_address_fee;



function get_user_real_name($auth_name, $name, $lname)
{
	$user_name = '';

	if ( !empty($auth_name) ) { // �������� �Ϸ��� ��� �Ǹ� ǥ��, Real name indication when self-certification is complete
		$user_name = $auth_name;
	} else if ( !empty($name) ) { // ����� �Է��� �̸�, Show user-populated names
		$user_name = $name;
		if ( !empty($lname) ) {
			$user_name = $lname.$name;
		}
	}
	$user_name = $user_name != '' ? $user_name : '';
	return $user_name;
}

function new_number_format($value, $leng) {
	$result = '';
	$result = number_format($value, $leng);
	$result = rtrim($result, 0);
	$result = rtrim($result, '.');
	return $result;
}
$n_decimal_point_array2 = array(
	'ectc' => 8,
	'etp3' => 8,
	'emc' => 8,
	'ekrw' => 8
);
*/


// 21.03.22
// barry_prod_list ���̺� �ߺ�Ȯ�� �� insert
function barryapi_set_barry_prod($tbl_id, $tbl_name, $prod_subject) {

	$last_id = '';
	if ( !empty($tbl_id) && !empty($tbl_name) && !empty($prod_subject) ) {
		$db = getDbInstance();

		$db->where('tbl_id', $tbl_id);
		$db->where('tbl_name', $tbl_name);
		$id = $db->getValue('barry_prod_list', 'id');
		
		if ( !empty($id) ) {
			// �̹� ����
			$last_id = $id;
		} else {
			$insertArr = [];
			$insertArr['tbl_id'] = $tbl_id;
			$insertArr['tbl_name'] = $tbl_name;
			$insertArr['prod_subject'] = $prod_subject;
			$last_id = $db->insert('barry_prod_list', $insertArr);
		}
	}
	return $last_id;
}

// 21.03.22
// ���ڹ߼۽� 90byte�� ���� �ʾƾ� ��. ���� �ڸ���
function barryapi_cut_product_name($prod_name) {
	// Abababab ���� ������۷�¡����... ���ź������ 22,222eTP3 �����Ͽ����ϴ�. => 73 byte
	// Abababab sent ������۷�¡����... 22,222eTP3 as a purchase fee. => 63 byte
	$maxChars = 7;
	$textLength = mb_strlen($prod_name);
	if ( $textLength > $maxChars ) {
		$prod_name = mb_substr($prod_name , 0, $maxChars, 'utf-8').'...';
	}
	return $prod_name;
}


function barryapi_coin_type_change($coin) {
	$result = '';
	$type = '';
	if( stristr($coin, 'E-') == TRUE || stristr($coin, 'e-') == TRUE ) {
		$result = str_replace('-', '', $coin);
		$result = strtolower($result);
		$type = 'epay';
	} else {
		$result = strtolower($coin);
		$type = 'coin';
	}
	return array($type, $result);
}

// ??? => E-TP3
function barryapi_coin_type_change2($coin) {
	$result = '';
	if( stristr($coin, 'E-') == TRUE || stristr($coin, 'e-') == TRUE ) {
		$result = strtoupper($coin);
	}
	else {
		$result = 'E-'.substr($coin, 1);
	}

	return $result;
}


// $e_pay : etp3, emc
function barryapi_set_module_name($e_pay) {
	$mod1 = '';
	$mod2 = '';
	$coin = barryapi_name_ecoin_to_coin($e_pay);

	$mod1 = 'krw_per_'.$coin.'_kiosk';
	$mod2 = 'exchange_'.$e_pay.'_per_'.$coin;
	/*switch($e_pay) {
		case 'etp3':
			$mod1 = 'krw_per_tp3_kiosk';
			$mod2 = 'exchange_etp3_per_tp3';
			break;
		case 'emc':
			$mod1 = 'krw_per_mc_kiosk';
			$mod2 = 'exchange_emc_per_mc';
			break;
	}*/
	return array($mod1, $mod2);
}
// etp3 -> tp3
function barryapi_name_ecoin_to_coin($e_pay) {
	$coin = $e_pay;
	if ( substr($e_pay, 0, 1) == 'e' ) {
		$coin = substr($coin, 1);
	}
	return $coin;
}
?>
