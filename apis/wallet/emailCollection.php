<?php
/*
 *
 *  by. OJT 2021.05.27 사용 중인 페이지 입니다.
 *
 *
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';
include_once (BASE_PATH.'/../wallet2/lib/WalletUtil.php');
include_once (BASE_PATH.'/../wallet2/lib/WalletFilter.php');
include_once (BASE_PATH.'/../wallet2/sendgrid-php/vendor/autoload.php');

use wallet\oldCommon\Util as walletUtil;
use wallet\oldCommon\Filter as walletFilter;
use \League\Plates\Engine as plateTemplate;
use \League\Plates\Extension\Asset as plateTemplateAsset;

require (BASE_PATH .'/../wallet2/vendor/autoload.php');

//에러 상수는 나중에 따로 분리?
const NOT_SEARCH_MSG = '찾을 수 없는 정보 입니다.';

try{
    /*
        유입 데이터 필터 START
    */
    $util = walletUtil::singletonMethod();
    $filter = walletFilter::singletonMethod();

    $valid = $filter->apiHeaderCheck($w_api_key);
    if($valid !== true){
        //그럴 일은 없겠지만 익셉션 캐치를 못하는 경우 .. exit.
        exit();
    }

    $rawData = $util->jsonDecode(file_get_contents("php://input",true));
    //wallet 에는 요청자 정보를 API에서 따로 받을 수 있게 준비 된게 없어, session으로 받는다.
    if(empty($_SESSION['user_id'])){
        $rawData['id'] = false;
    }
    else{
        $rawData['id'] = $_SESSION['user_id'];
    }


    $postData = array(
        'id' => ['code'=>'88','msg'=>$langArr['emailCollectionApiStringDanger01']],
        'email' => ['code'=>'88','msg'=>$langArr['emailCollectionApiStringDanger02']],
        'verifyCode' => ['code'=>'88','msg'=>$langArr['emailCollectionApiStringDanger03']],
        'type' => ['code'=>'88','msg'=>$langArr['emailCollectionApiStringDanger04']],
    );
    $filterData = $filter->postDataFilter($rawData,$postData);
    unset($postData,$rawData);
    /*
        유입 데이터 필터 END
    */


    $db = getDbInstance();

    //요청자가 본인인지 확인
    $userInfo = $db->where('id', $filterData['id'])->getOne('admin_accounts', 'id, email, wallet_phone_email, name, wallet_phone_email_auth_key, wallet_phone_email_auth_key_datetime');
    if(!$userInfo){
        throw new Exception($langArr['commonApiStringDanger01'],9999);
    }
    if($userInfo['id'] != $_SESSION['user_id']){
        throw new Exception($langArr['commonApiStringDanger02'],9999);
    }

    //코드 생성, 이메일 등록 ( 등록 시 생성된 코드와 비교)
    if($filterData['type'] == 'generateCode'){
        $code = rand(100000,999999);
        $activationDatetime = $util->getDateSql();


        //이미 발급한 코드가 있고, 10분이 지나지 않았는데 생성을 요청 하면 막는다.
        if($userInfo['wallet_phone_email_auth_key'] != 'OK'){
            if($userInfo['wallet_phone_email_auth_key_datetime'] != NULL){
                $deactivationDatetime = date("Y-m-d H:i:s", strtotime($userInfo['wallet_phone_email_auth_key_datetime'].'+10 minutes'));
                if(strtotime($activationDatetime) <= strtotime($deactivationDatetime)){
                    throw new Exception($langArr['emailCollectionApiStringDanger05'],9999);
                }
            }
        }
        else{
            throw new Exception($langArr['emailCollectionApiStringDanger06'],9999);
        }

        $updateProc = $db->where('id', $userInfo['id'])->update('admin_accounts',
            [
                'wallet_phone_email_auth_key'=>$code,
                'wallet_phone_email_auth_key_datetime'=>$util->getDateSql()
                //'wallet_phone_email_auth_key_datetime'=>'2021-05-27 11:00:44.0'
            ]
        );

        if(!$updateProc){
            throw new Exception($langArr['emailCollectionApiStringDanger07'],9999);
        }

        $templates = new plateTemplate(BASE_PATH.'/wallet/skin', 'html');
        $mailData = array(
            'senderTitle' => 'CYBERTRON',
            'font' => "'Malgun Gothic',Apple SD Gothic Neo,sans-serif,'맑은고딕',Malgun Gothic,'굴림',gulim",
            'logoImgUrl' => 'https://cybertronchain.com/beta/images/logo.png',
            'datetime' => $activationDatetime,
            'code' => $code,
        );
        $mailHtml = $templates->render('mailCollectionForm', ['data' => $mailData]);

        $emailObj = new \SendGrid\Mail\Mail();
        $emailObj->setFrom("michael@cybertronchain.com", "CyberTron Coin");
        $emailObj->setSubject("CyberTron 메일 인증 코드 입니다.");
        $emailObj->addTo($filterData['email']);
        $emailObj->addContent("text/html", $mailHtml);

        $sendgrid = new \SendGrid('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');

        $response = $sendgrid->send($emailObj);
        if($response->statusCode() != '202'){
            throw new Exception($langArr['emailCollectionApiStringDanger10'],9999);;
        }
        //print $response->statusCode() . "\n";
        //print_r($response->headers());
        //print $response->body() . "\n";die;

        echo $util->success(['data'=>['code'=>200, 'emailCode'=>$code]]);
    }
    else if($filterData['type'] == 'upload'){
        if($userInfo['wallet_phone_email_auth_key'] == NULL){
            throw new Exception($langArr['emailCollectionApiStringDanger08'],9999);
        }

        if($userInfo['wallet_phone_email_auth_key'] == 'OK'){
            throw new Exception($langArr['emailCollectionApiStringDanger06'],9999);
        }

        if($filterData['verifyCode'] != $userInfo['wallet_phone_email_auth_key']){
            throw new Exception($langArr['emailCollectionApiStringDanger09'],9999);
        }
        //원래 테이블 조회
        $userDuplicateInfo = $db->where('email', $filterData['email'])->orWhere('wallet_phone_email',$filterData['email'])
            ->getOne('admin_accounts', 'id');
        if($userDuplicateInfo){
            $db->where('id', $userInfo['id'])->update('admin_accounts',['wallet_phone_email_auth_key_datetime'=>null]);
            throw new Exception($langArr['email_already_rg'].'1',9999);
        }
        //휴면 계정 테이블 조회
        $userDuplicateInfo = $db->where('email', $filterData['email'])->orWhere('wallet_phone_email',$filterData['email'])
            ->getOne('admin_accounts_sleep', 'id');
        if($userDuplicateInfo){
            $db->where('id', $userInfo['id'])->update('admin_accounts',['wallet_phone_email_auth_key_datetime'=>null]);
            throw new Exception($langArr['email_already_rg'].'2',9999);
        }
        //회원 탈퇴 테이블 조회
        $userDuplicateInfo = $db->where('email', $filterData['email'])->orWhere('wallet_phone_email',$filterData['email'])
            ->getOne('admin_accounts_withdrawal', 'id');
        if($userDuplicateInfo){
            $db->where('id', $userInfo['id'])->update('admin_accounts',['wallet_phone_email_auth_key_datetime'=>null]);
            throw new Exception($langArr['email_already_rg'].'3',9999);
        }

        //인증 시 요청 시간이 인증 완료 시간 이 됨.
        $updateProc = $db->where('id', $filterData['id'])->update('admin_accounts',
            [
                'wallet_phone_email'=>$filterData['email'],
                'wallet_phone_email_auth_key'=>'OK',
                'wallet_phone_email_auth_key_datetime'=>$util->getDateSql()
            ]
        );
        echo $util->success();
    }
    else{
        throw new Exception($langArr['commonApiStringDanger02'],9999);;
    }




}
catch (Exception $e){
    echo $util->fail(['data'=>['msg'=>$e->getMessage()]]);
    exit();
}
?>
