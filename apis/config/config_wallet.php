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
define('WALLET_URL', 'https://cybertronchain.com/wallet2');
define('WALLET_PATH', '/var/www/html/wallet2');
$w_api_key = 'BE14273125KL';
$w_api_admin_key = 'ABS521!^6ec44(*';

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

function addr_text_block($addr) {
	$tmp = substr($addr, 6, 30);
	$addr = str_replace($tmp, '********', $addr);
	//$addr = str_pad(substr($addr, 0, 10), strlen($addr), "*");
	return $addr;
}

$wallet_directory_root = '/var/www/html/wallet2';
if(!isset($_SESSION['lang']) || empty($_SESSION['lang'])) {
    $_SESSION['lang'] = "ko";
}
$langFolderPath = file_get_contents($wallet_directory_root."/lang/".$_SESSION['lang']."/index.json");
$langArr = json_decode($langFolderPath,true);
require_once $wallet_directory_root.'/config/new_config.php';



?>