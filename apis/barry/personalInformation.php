<?php

/*
 *
 *  by. OJT 2021.06.02 사용 중인 페이지 입니다.
 *  barry 제 3개인정보 wallet 반영
 *
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';

use wallet\common\Util as walletUtil;
use wallet\common\Filter as walletFilter;
use wallet\common\Request as walletRequest;
use \League\Plates\Extension\Asset as plateTemplateAsset;

require(BASE_PATH . '/../wallet2/vendor/autoload.php');

//에러 상수는 나중에 따로 분리?
const NOT_SEARCH_MSG = '찾을 수 없는 정보 입니다.';

try {
    $util = walletUtil::singletonMethod();
    $filter = walletFilter::singletonMethod();
    $request = walletRequest::singletonMethod();
    /*
        유입 데이터 필터 START
    */
    $request = $request->getRequest();
    $plainData = $request->getParsedBody();

    $targetPostData = array(
        'signature' => 'stringNotEmpty',
        'timestamp' => 'integerNotEmpty',
        'value' => 'stringNotEmpty'
    );
    $filterData = $filter->postDataFilter($plainData,$targetPostData);
    unset($targetPostData,$plainData);

    if(!$util->serverCommunicationAuth('walletadmin',$filterData['value'],$filterData['timestamp'],$filterData['signature'])){
        throw new Exception('NOT FOUND REQUEST',9999);
    }
    /*
        유입 데이터 필터 END
    */


    $db = getDbInstance();

    $db->where('id',$filterData['value']);
    $userInfo = $db->getOne('admin_accounts','id');
    if(!$userInfo){
        throw new Exception('존재하는 user가 아닙니다.',9999);
    }

    $db->where('id', $userInfo['id']);
    $updateProc = $db->update('admin_accounts', ['barry_personal_information'=>1,'barry_personal_information_datetime'=>$util->getDateSql()]);
    if(!$updateProc){
        throw new Exception('wallet 반영에 실패 하였습니다.',9999);
    }

    echo $util->success();

} catch (Exception $e) {
    echo $util->fail(['data' => ['msg' => $e->getMessage()]]);
    exit();
}

?>