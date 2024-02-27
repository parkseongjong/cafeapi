<?php

/*
 *
 *  by. OJT 2021.06.04 사용 중인 페이지 입니다.
 *  password 해싱 방식 변경  md5 -> sha512
 *
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';

use wallet\common\Auth as walletAuth;
use wallet\common\Util as walletUtil;
use wallet\ctcDbDriver\Driver as walletDb;
use wallet\common\Filter as walletFilter;
use wallet\common\Request as walletRequest;

require(BASE_PATH . '/../wallet2/vendor/autoload.php');

try {
    $auth = walletAuth::singletonMethod();
    $util = walletUtil::singletonMethod();
    $walletDb = walletDb::singletonMethod();
    $walletDb = $walletDb->init();
    $filter = walletFilter::singletonMethod();
    $request = walletRequest::singletonMethod();

    $request = $request->getRequest();
    $plainData = $request->getParsedBody();

    if(!$auth->sessionAuth()){
        throw new Exception($langArr['commonApiStringDanger04'],9999);
    }
    else{
        $plainData['memberId'] = $auth->getSessionId();
    }

    $targetPostData = array(
        'memberId' => 'integerNotEmpty',
        'plainWord' => 'stringNotEmpty'
    );
    $filterData = $filter->postDataFilter($plainData,$targetPostData);
    unset($targetPostData,$plainData);

    $memberInfo = $walletDb->createQueryBuilder()
        ->select('id, passwd, passwd_new, passwd_datetime')
        ->from('admin_accounts')
        ->where('id = ?')
        ->setParameter(0, $filterData['memberId'])
        ->execute()->fetch();

    if(!$memberInfo){
        throw new Exception($langArr['commonApiStringDanger04'],9999);
    }

    $regex = "/((?=.*[a-z])(?=.*[0-9])(?=.*[$@$!%*#?&])(?=.*[^a-zA-Z0-9])(?!.*(admin|root)).{8,})/m";
    //영문 + 숫자 + 특문 , 8자리 이상이 아니면 fail
    if(!preg_match($regex, $filterData['plainWord'])){
        throw new Exception($langArr['passwordChangeStringDanger02'],9999);
    }

    //새 정책 passwd이 아닌 경우, 해시 값 생성 저장
    if(!empty($memberInfo['passwd_new'])){
        throw new Exception($langArr['passwordChangeStringDanger03'],9999);
    }
    if(empty($memberInfo['passwd_datetime'])){
        $salt = bin2hex(openssl_random_pseudo_bytes(8)).'.';
        $hash = hash('sha512',trim($salt.$filterData['plainWord']));
        $updateProc = $walletDb->createQueryBuilder()
            ->update('admin_accounts')
            ->set('passwd_new', '?')
            ->set('passwd_datetime', '?')
            ->set('passwd_salt', '?')
            ->where('id = ?')
            ->setParameter(0,$hash)
            ->setParameter(1,$util->getDateSql())
            ->setParameter(2,$salt)
            ->setParameter(3,$memberInfo['id'])
            ->execute();

        session_destroy();

        if(!$updateProc){
            throw new Exception($langArr['commonApiStringDanger03'],9999);
        }
    }
    else{
        throw new Exception($langArr['passwordChangeStringDanger03'],9999);
    }
    echo $util->success();

}
catch (Exception $e) {
    echo $util->fail(['data' => ['msg' => $e->getMessage()]]);
    exit();
}

?>