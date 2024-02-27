<?php
session_start();
//var_dump(file_get_contents("php://input",true));
//var_dump(file_get_contents("php://input"));
////$aaa = file_get_contents('php://input');
//var_dump($aaa);
//var_dump($_REQUEST);
//var_dump($_GET);
//var_dump($_POST);
//var_dump(filter_input(INPUT_POST, 'gg', FILTER_SANITIZE_STRING));
//exit();  ????????????????????????모지...
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';

use wallet\common\Util as walletUtil;
use WalletLogger\Logger as walletLogger;

require (BASE_PATH .'/../wallet2/vendor/autoload.php');
include_once (BASE_PATH.'/../wallet2/lib/WalletLogger.php');

//에러 상수는 나중에 따로 분리?
const NOT_SEARCH_MSG = '찾을 수 없는 정보 입니다.';


//유입 데이터, header 확인은 추 후 공통 class로 빼야 함.
//지금은... 시간 관계상 + api 개수가 많지 않으니 ........ 당장은 빼지 않음 by.OJT 2021-05-26
/*
    유입 데이터 필터 START
*/
try{
    $headers = apache_request_headers();
    //$authKey,$contentType 는 가변 변수로 선언 됩니다.
    foreach (['authorization' => 'authKey','content-type' => 'contentType'] as $key => $value){
        if(array_key_exists($key,$headers)){
            $$value = explode(' ',$headers[$key]);
			$test = explode(' ',$headers[$key]);
        }
        else{
            $$value = false;
        }
    }
	
    $util = walletUtil::singletonMethod();
    $walletLoggerLoader = new walletLogger();
    $walletLogger = $walletLoggerLoader->init();
    $walletLoggerUtil = $walletLoggerLoader->initUtil();

    $rawData = $util->jsonDecode(file_get_contents("php://input",true));

    /*
     * userDataType 종류
     * W : 탈퇴 회원
     * S : 휴면 회원
     * N : 일반 회원
     */
    $postData = array(
        'headerAuthKey' => ['code'=>'88','msg'=>'알수없는 2 요청 입니다.'],
        'headerType' => ['code'=>'88','msg'=>'올바른 요청 컨텐츠 타입이 아닙니다.'],
        'kind' => ['code'=>'88','msg'=>'알수없는 1 요청 입니다.'],
        'id' => ['code'=>'88','msg'=>'ID가 누락 되었습니다.'],
        'userDataType' => ['code'=>'88','msg'=>'userDataType이 누락 되었습니다.'],
    );
    $filterData = array();
    foreach ($postData as $key => $value){
        //auth key는 header 값을 조회 해서 비교 합니다.
        if($key == 'headerAuthKey'){
            if($authKey[0] != 'walletKey' || $authKey[1] != $w_api_admin_key ){
                jsonReturn(array(
                        'code' => $value['code'],
                        'msg'=> $value['msg']
                    )
                );
            }
        }
        else if($key == 'headerType'){
            if($contentType[0] != 'application/json;'){
                jsonReturn(array(
                        'code' => $value['code'],
                        'msg'=> $value['msg']
                    )
                );
            }
        }
        else {
            $postValue = (isset($rawData[$key]) ? filter_var($rawData[$key], FILTER_SANITIZE_SPECIAL_CHARS) : '');

            if (empty($postValue)) {
                jsonReturn(array(
                        'code' => $value['code'],
                        'msg' => $value['msg']
                    )
                );
            }
            $filterData[$key] = $postValue;
        }
    }
    unset($postData,$postValue,$headers);
    /*
        유입 데이터 필터 END
    */

    $db = getDbInstance();
    $okJson = array('code'=>'00','msg'=>'ok');

    $db->where('A.id', $filterData['id']);

    $walletLogger->info('사용자 UNMASK 실행',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>0,'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

    switch($filterData['kind']) {
        case 'name':
            if($filterData['userDataType'] == 'S'){
                $db->join("admin_accounts_sleep B", "A.id = B.id", "INNER");
                $userInfo = $db->getOne('admin_accounts A', 'A.id, B.register_with as registerWith, B.name, B.lname, A.id_auth as idAuth, B.auth_name as authName');
            }
            else{
                $userInfo = $db->getOne('admin_accounts A', 'id, register_with as registerWith, name, lname, id_auth as idAuth, auth_name as authName');
            }

            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;

            $walletLogger->info('사용자 이름 UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            if(empty($userInfo['lname']) && empty($userInfo['name'])){
                $okJson['data']['unMaskData'] = $userInfo['authName'];
            }
            else{
                $okJson['data']['unMaskData'] = $userInfo['lname'].$userInfo['name'];
            }

            break;
        case 'phone':

            if($filterData['userDataType'] == 'S'){
                $db->join("admin_accounts_sleep B", "A.id = B.id", "INNER");
                $userInfo = $db->getOne('admin_accounts A', 'A.id, B.phone');
            }
            else{
                $userInfo = $db->getOne('admin_accounts A', 'id, phone');
            }

            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['phone'];
            $walletLogger->info('사용자 번호(phone) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            break;
        case 'externalPhone':
            if($filterData['userDataType'] == 'S'){
                $db->join("admin_accounts_sleep B", "A.id = B.id", "INNER");
                $userInfo = $db->getOne('admin_accounts A', 'A.id, B.external_phone as externalPhone');
            }
            else{
                $userInfo = $db->getOne('admin_accounts A', 'A.id, external_phone as externalPhone');
            }
            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['externalPhone'];

            $walletLogger->info('사용자 번호(externalPhone) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            break;
        case 'authPhone':
            if($filterData['userDataType'] == 'S'){
                $db->join("admin_accounts_sleep B", "A.id = B.id", "INNER");
                $userInfo = $db->getOne('admin_accounts A', 'A.id, B.id_auth as idAuth, B.auth_phone as authPhone');
            }
            else{
                $userInfo = $db->getOne('admin_accounts A', 'A.id, id_auth as idAuth, auth_phone as authPhone');
            }
            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['authPhone'];

            $walletLogger->info('사용자 번호(authPhone) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            break;
        case 'ip':
            if($filterData['userDataType'] == 'S'){
                $db->join("admin_accounts_sleep B", "A.id = B.id", "INNER");
                $userInfo = $db->getOne('admin_accounts A', 'A.id, B.user_ip as ip');
            }
            else{
                $userInfo = $db->getOne('admin_accounts A', 'A.id, user_ip as ip');
            }
            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['ip'];

            $walletLogger->info('사용자 아이피(ip) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);


            break;
        case 'email':
            if($filterData['userDataType'] == 'S'){
                $db->join("admin_accounts_sleep B", "A.id = B.id", "INNER");
                $userInfo = $db->getOne('admin_accounts A', 'A.id, B.register_with as registerWith, B.email');
            }
            else{
                $userInfo = $db->getOne('admin_accounts A', 'A.id, register_with as registerWith, email');
            }
            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['email'];

            $walletLogger->info('사용자 이메일(email) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            break;

        case 'store':
            $userInfo = $db->getOne('stores A', 'id, store_name as storeName');
            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['storeName'];

            $walletLogger->info('사용자 스토어(store) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            break;

        case 'storePhone':
            $userInfo = $db->getOne('stores A', 'id, store_phone as storePhone');
            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['storePhone'];

            $walletLogger->info('사용자 스토어(storePhone) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            break;

        case 'blockedIp':
            $userInfo = $db->getOne('blocked_ips A', 'id, ip_name as ip');
            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['ip'];

            $walletLogger->info('사용자 정지 ip(blockedIp) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            break;

        case 'blockedAdminIp':
            $userInfo = $db->getOne('blocked_admin_ips A', 'id, ip as ip');
            if(!$userInfo){
                throw new Exception(['code'=>'406','msg'=>NOT_SEARCH_MSG],406);
            }

            $okJson['data'] = $userInfo;
            $okJson['data']['unMaskData'] = $userInfo['ip'];

            $walletLogger->info('사용자 정지 ip(blockedAdminIp) UNMASK',['admin_id'=>$walletLoggerUtil->getAdminSession(),'user_id'=>$userInfo['id'],'url'=>$walletLoggerUtil->getUrl(),'action'=>'S']);

            break;

        default:
            jsonReturn(array('code'=>'88','msg'=>'알수없는 요청입니다.'));
            break;
    }

    echo $util->jsonEncode($okJson);
    exit();
}
catch (Exception $e){
    jsonReturn($e->getMessage());
    exit();
}
?>
