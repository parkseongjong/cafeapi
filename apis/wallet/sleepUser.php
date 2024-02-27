<?php

/*
 *
 *  by. OJT 2021.06.14 사용 중인 페이지 입니다.
 *  본인 인증 kor외 email, phone 코드를 처리하는 페이지 입니다.
 *
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';

use wallet\common\Auth as walletAuth;
use wallet\common\Util as walletUtil;
use wallet\sleep\Restore as walletSleepRestore;
use wallet\common\Valid as walletValid;
use wallet\common\Push as walletPush;
use wallet\ctcDbDriver\Driver as walletDb;
use wallet\common\Filter as walletFilter;
use wallet\common\Request as walletRequest;
use League\Plates\Engine as plateTemplate;
use League\Plates\Extension\Asset as plateTemplateAsset;

require(BASE_PATH . '/../wallet2/vendor/autoload.php');

try {
    $auth = walletAuth::singletonMethod();
    $util = walletUtil::singletonMethod();
    $valid = walletValid::singletonMethod();
    $walletDb = walletDb::singletonMethod();
    $walletDb = $walletDb->init();
    $filter = walletFilter::singletonMethod();
    $request = walletRequest::singletonMethod();

    $request = $request->getRequest();
    $plainData = $request->getParsedBody();
    
    error_log($auth->sessionAuthTemp(),0);
    error_log(print_r($_SESSION,true),0);
    if(!$auth->sessionAuthTemp()){
        throw new Exception($langArr['commonApiStringDanger04'],9999);
    }
    else{
        $plainData['memberId'] = $auth->getSessionIdTemp();
    }
    $targetPostData = array(
        'memberId' => 'integerNotEmpty',
        'type' => 'stringNotEmpty',
        'authInfoType' => 'stringNotEmpty',
        'target'=> 'stringNotEmpty',
        'verifyCode' => 'stringNotEmpty',
        'localeCode' => 'stringNotEmpty'
    );
    $filterData = $filter->postDataFilter($plainData,$targetPostData);

    unset($targetPostData,$plainData);
    $memberInfo = $walletDb->createQueryBuilder()
        ->select('A.id, A.passwd, A.passwd_new, A.passwd_datetime')
        ->from('admin_accounts_sleep','A')
        ->innerJoin('A', 'sleep_user_email', 'B', 'A.id = B.sue_accounts_id')
        ->where('A.id = ?')
        ->andWhere('B.sue_transfer = ?')
        ->setParameter(0, $filterData['memberId'])
        ->setParameter(1, 'SUCCESS') //이미 이관 완료 된 SUCCESS만 ...
        ->execute()->fetch();
    if(!$memberInfo){
        throw new Exception($langArr['commonApiStringDanger04'],9999);
    }

    if($filterData['type'] == 'generateCode'){

        $pushDriver = new walletPush();
        $code = rand(100000,999999);
        $activationDatetime = $util->getDateSql();

        if($filterData['authInfoType'] == 'emailCode'){
            if(!$valid->emailRegex($filterData['target'])){
                throw new Exception($langArr['emailCollectionJsString01']);
            }

            $updateProc = $walletDb->createQueryBuilder()
                ->update('admin_accounts')
                ->set('vcode','?')
                ->where('id = ?')
                ->setParameter(0,$code)
                ->setParameter(1,$memberInfo['id'])
                ->execute();
            if(!$updateProc){
                throw new Exception($langArr['commonApiStringDanger03'],9999);
            }

            $mailData = array(
                'senderTitle' => 'CYBERTRON',
                'font' => "'Malgun Gothic',Apple SD Gothic Neo,sans-serif,'맑은고딕',Malgun Gothic,'굴림',gulim",
                'logoImgUrl' => 'https://cybertronchain.com/beta/images/logo.png',
                'datetime' => $activationDatetime,
                'code' => $code,
            );
            $templates = new plateTemplate(BASE_PATH.'/wallet/skin', 'html');
            $mailHtml = $templates->render('mailCollectionForm', ['data' => $mailData]);

            $pushDriver->sendMail('CyberTron 메일 인증 코드 입니다.',$filterData['target'],$mailHtml);
        }
        else if($filterData['authInfoType'] == 'phoneCode'){
            //해외 유효성 체크 필요...... js에서는 area별로 처리 되고 있음.
            if(!$valid->intRegex($filterData['target'])){
                throw new Exception($langArr['phoneValidFail']);
            }

            $updateProc = $walletDb->createQueryBuilder()
                ->update('admin_accounts')
                ->set('vcode','?')
                ->where('id = ?')
                ->setParameter(0,$code)
                ->setParameter(1,$memberInfo['id'])
                ->execute();
            if(!$updateProc){
                throw new Exception($langArr['commonApiStringDanger03'],9999);
            }

            $pushDriver->sendMessage($filterData['target'],$filterData['localeCode'],'[CTC 월렛]인증번호는['.$code.']입니다.','SMS');
        }
        else{
            throw new Exception($langArr['commonApiStringDanger04'],9999);
        }

        echo $util->success();
    }
    else if($filterData['type'] == 'upload'){

        $memberVcodeInfo = $walletDb->createQueryBuilder()
            ->select('vcode')
            ->from('admin_accounts')
            ->where('id = ?')
            ->setParameter(0,$memberInfo['id'])
            ->execute()->fetch();
        if($memberVcodeInfo['vcode'] != $filterData['verifyCode']) {
            throw new Exception($langArr['emailCollectionApiStringDanger09'],9999);
        }

        $updateProc = $walletDb->createQueryBuilder()
            ->update('admin_accounts')
            ->set('vcode','?')
            ->where('id = ?')
            ->setParameter(0,null)
            ->setParameter(1,$memberInfo['id'])
            ->execute();
         if(!$updateProc){
             throw new Exception($langArr['commonApiStringDanger03'],9999);
         }

         $walletSleepRestore = new walletSleepRestore($memberInfo['id']);
         if($walletSleepRestore->userRetore()){
             $bufferData['header'] = false;
             $bufferData['footer'] = false;

             $templates = new plateTemplate( WALLET_PATH.'/skin/sleepUser', 'html');
             $templates->loadExtension(new plateTemplateAsset(WALLET_PATH.'/skin/common/assets', false));
             $randerData = $templates->render('sleepRestoreFormComplete', [
                 'info' => [
                     'htmlHeader' => $bufferData['header'],
                     'htmlFooter' => $bufferData['footer'],
                     'lang' => $langArr,
                     'asstsUrl' => WALLET_URL.'/skin/common/assets',
                 ]
             ]);
         }
        echo $util->success();
    }
    else{
        throw new Exception($langArr['commonApiStringDanger04'],9999);
    }
}
catch (Exception $e) {
    echo $util->fail(['data' => ['msg' => $e->getMessage()]]);
    exit();
}

?>