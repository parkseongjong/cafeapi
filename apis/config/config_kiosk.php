<?php

function logWrite($arr='') {
    $fname = "/var/www/html/apis/kiosk/logs/" . date('Y-m-d') . ".txt";
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


// payment_no에는 _가 포함되어서는 안된다.
function create_payment_no($fran_id='', $code='') {
	$r = $fran_id.$code.time().rand(1000,9999);
	return $r;
}


// E-TP3 -> etp3
// E-MC -> emc
function unit_change_unit($unit) {
	$result = '';
	$result = str_replace('-', '', $unit);
	$result = strtolower($result);
	return $result;
}

?>