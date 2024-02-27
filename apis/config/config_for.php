<?php

function logWrite($arr='') {
    $fname = "/var/www/html/apis/for/logs/" . date('Y-m-d') . ".txt";
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
}


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


function err_message ($err_code) {
	$msg = '';
	switch($err_code) {
		case 200:
			$msg = 'Success';
			break;

		case 801:
			// ������ ���� ������Դϴ�. (code�� ����ġ)
			$msg = 'Disallowed user';
			break;
		case 802:
			// �߸��� ��û�Դϴ�. (kind�� ����ġ)
			$msg = 'Bad request';
			break;

		case 804:
			// �ʼ����� �����Ǿ����ϴ�.
			$msg = 'Missing required value';
			break;
		case 805:
			// ������ ���� ���� �ԷµǾ����ϴ�.
			$msg = 'Unacceptable value entered';
			break;
		case 806:
			// ������ �������� �ʽ��ϴ�.
			$msg = 'No information';
			break;
		case 807:
			// ���� ��ȿ�Ⱓ�� ����Ǿ����ϴ�.
			$msg = 'Expired and unavailable';
			break;
		case 808:
			// ��ȸ ����
			$msg = 'Lookup failure';
			break;
		case 809:
			// ó�� ����
			$msg = 'Processing failure'; // usdt ���۽� ������ ���
			break;
		case 811:
			// ���� �� ������ �߻��߽��ϴ�.
			$msg = 'Error during saving';
			break;
		case 999:
			$msg = 'Error';
			break;
	}
	return $msg;
}


function create_invoice_number() {
	$r = date('d').strrev(time()).rand(1000,9999);
	$count = get_for_transaction_list('order_num', $r, 'count');
	if ( $count > 0 ) {
		$r = create_invoice_number();
	}
	return $r;
}

function get_for_transaction_list($type, $val, $res_type) {
	if ( $type == 'order_num' ) {
		$db = getDbInstance();
		$db->where('order_num', $val);
		if ( $res_type == 'count' ) {
			$result = $db->getValue('for_transaction_list', 'count(*)');
		} else if ( $res_type == 'list' ) {
			$result = $db->getOne('for_transaction_list');
		}
	}
	return $result;
} //

/*
status

waiting �������
pending	������ ����. tx�� success�� �ƴ�
failed		���������� ������
expired	���� �� ������ ����� : �����Ұ�
success	�����Ϸ�

refunded	�Ǹ��ڰ� ȯ����


*/
?>