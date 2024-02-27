<?php
/*
 *
 *  by. OJT 2021.05.27 사용 중인 페이지 입니다.
 *
 *
 */
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';
include_once (BASE_PATH.'/../wallet2/lib/WalletUtil.php');
use wallet\common\Util as walletUtil;

require (BASE_PATH .'/../wallet2/vendor/autoload.php');

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
        }
        else{
            $$value = false;
        }
    }

    $util = walletUtil::singletonMethod();

    $rawData = $util->jsonDecode(file_get_contents("php://input",true));

    $postData = array(
        'headerAuthKey' => ['code'=>'88','msg'=>'알수없는 2 요청 입니다.'],
        'headerType' => ['code'=>'88','msg'=>'올바른 요청 컨텐츠 타입이 아닙니다.'],
        'kind' => ['code'=>'88','msg'=>'알수없는 1 요청 입니다.'],
        'id' => ['code'=>'88','msg'=>'ID가 누락 되었습니다.'],
    );
    $filterData = array();
    foreach ($postData as $key => $value){
        //auth key는 header 값을 조회 해서 비교 합니다.
        if($key == 'headerAuthKey'){
            if($authKey[0] != 'walletKey' || $authKey[1] != $w_api_admin_key){
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

    $db->where('id', $filterData['id']);

    switch($filterData['kind']) {
        case 'statusModify':
            $blockedInfo = $db->where('id',$filterData['id'])
                ->getOne('blocked_admin_ips', '*');
            if(!$blockedInfo) {
                throw new Exception(['code' => '406', 'msg' => NOT_SEARCH_MSG],406);
            }

            if($blockedInfo['status'] == 0){
                $blockedInfoProc = $db->where('id',$filterData['id'])
                    ->update('blocked_admin_ips',['status'=>1]);
                if(!$blockedInfoProc){
                    throw new Exception(['code'=>'406','msg'=>'처리에 실패 하였습니다.'],406);
                }
                $okJson['data']['status'] = 1;
            }
            else{
                $blockedInfoProc = $db->where('id',$filterData['id'])
                    ->update('blocked_admin_ips',['status'=>0]);
                if(!$blockedInfoProc){
                    throw new Exception(['code'=>'406','msg'=>'처리에 실패 하였습니다.'],406);
                }
                $okJson['data']['status'] = 0;
            }

            break;
            
        default:
            throw new Exception(['code'=>'88','msg'=>'알수없는 요청입니다.'],88);
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
