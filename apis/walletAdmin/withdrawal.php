<?php

/*
 *
 *  by. OJT 2021.06.22 사용 중인 페이지 입니다.
 *  ADMIN, 자산이 있는 회원 탈퇴를 처리 하는 곳 입니다.
 *
 */
session_start();
//header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';

use wallet\common\Auth as walletAuth;
use wallet\common\Util as walletUtil;
use wallet\common\Valid as walletValid;
use wallet\common\Push as walletPush;
use wallet\ctcDbDriver\Driver as walletDb;
use wallet\common\Filter as walletFilter;
use wallet\common\Request as walletRequest;
use wallet\withdrwal\Release as walletWithdrwalRelease;

use \League\Plates\Engine as plateTemplate;
//use \League\Plates\Extension\Asset as plateTemplateAsset;

require(BASE_PATH . '/../wallet2/vendor/autoload.php');

try {
    $auth = walletAuth::singletonMethod();
    $util = walletUtil::singletonMethod();
    $valid = walletValid::singletonMethod();
    $pushDriver = new walletPush();
    $walletDb = walletDb::singletonMethod();
    $walletDb = $walletDb->init();
    $filter = walletFilter::singletonMethod();
    $request = walletRequest::singletonMethod();
    $request = $request->getRequest();
    $plainData = $request->getParsedBody();
    $plainPathData = $request->getQueryParams();

    $valid = $filter->apiHeaderCheck($w_api_admin_key);
    if($valid !== true){
        //그럴 일은 없겠지만 익셉션 캐치를 못하는 경우 .. exit.
        exit();
    }

    //어드민 체크,
    if(!$auth->sessionAuthAdminCheck()){
        throw new Exception($langArr['commonApiStringDanger04'],9999);
    }
    else{
        $plainData['memberId'] = $auth->getSessionId();
    }

    $targetPostData = array(
        'type' => 'stringNotEmpty'
    );
    $filterPathData = $filter->postDataFilter($plainPathData,$targetPostData);
    unset($plainPathData);

    $pushOption = array();
    $procDatetime = $util->getDateSql();

    if($filterPathData['type'] == 'reject'){
        $targetPostData = array(
            'memberId' => 'integerNotEmpty',
            'targetId' => 'integerNotEmpty',
            'rejectMsg' => 'stringNotEmpty',
        );
        $filterData = $filter->postDataFilter($plainData,$targetPostData);
        unset($targetPostData,$plainData);

        //2021.10.27 By.OJT
        //이미지를 안올렸을때도 반려 처리 가능하게..
        //asset이고 처리 상태가 null,PENDING경우에만 상태 값 변경 가능
        $targetInfo = $walletDb->createQueryBuilder()
            ->select('wu_id, wu_status, wu_accounts_id')
            ->from('withdrawal_user')
            ->where('wu_id = ?')
            ->andWhere('wu_type = ?')
            ->andWhere('wu_status IS NULL')
            ->orWhere('wu_status = ?')
            ->setParameter(0,$filterData['targetId'])
            ->setParameter(1,'asset')
            //->setParameter(2,['null','SUCCESS','REJECT'],\Doctrine\DBAL\Connection::PARAM_STR_ARRAY)
            ->setParameter(2,'PENDING')
            ->execute()->fetch();
        if(!$targetInfo){
            error_log('!!!',0);
            throw new Exception('이미 반려 처리 되었거나 상태를 변경 할 수 없는 회원 입니다.',9999);
        }

        $updateProc = $walletDb->createQueryBuilder()
            ->update('withdrawal_user')
            ->set('wu_status','?')
            ->set('wu_status_datetime','?')
            ->set('wu_description','?')
            ->where('wu_id = ?')
            ->setParameter(0,'REJECT')
            ->setParameter(1,$util->getDateSql())
            ->setParameter(2,$filterData['rejectMsg'])
            ->setParameter(3,$targetInfo['wu_id'])
            ->execute();
        if(!$updateProc){
            throw new Exception('반려 처리에 실패 하였습니다.',9999);
        }

        $pushOption['title'] = 'CyberTron 탈퇴 요청이 반려 처리 되었습니다.';
        $pushOption['contetns'] = '회원 탈퇴 요청 심사 결과 반려 처리 되었으니 로그인 후 내 정보 관리 > 자산포기각서 제출을 통해 다시 요청해 주세요.';
        $pushOption['dateTime'] = $procDatetime;
        $pushOption['dateTimeTitle'] = '회원 탈퇴 반려 일시';
        //echo $util->success();

    }
    else if($filterPathData['type'] == 'withdrawal'){
        //탈퇴 처리
        $targetPostData = array(
            'memberId' => 'integerNotEmpty',
            'targetId' => 'integerNotEmpty',
        );
        $filterData = $filter->postDataFilter($plainData,$targetPostData);
        unset($targetPostData,$plainData);

        $targetInfo = $walletDb->createQueryBuilder()
            ->select('wu_id, wu_status, wu_accounts_id')
            ->from('withdrawal_user')
            ->where('wu_id = ?')
            ->andWhere('wu_type = ?')
            ->andWhere('wu_status = ?')
            ->setParameter(0,$filterData['targetId'])
            ->setParameter(1,'asset')
            ->setParameter(2,'PENDING')
            ->execute()->fetch();
        if(!$targetInfo){
            throw new Exception('이미 탈퇴 처리 되었거나 상태를 변경 할 수 없는 회원 입니다.',9999);
        }

        $walletWithdrwalRelease = new walletWithdrwalRelease($targetInfo['wu_accounts_id']);
        $releaseProc = $walletWithdrwalRelease->userRelease();
        if($releaseProc['code'] != 200){
            throw new Exception('탈퇴 처리 중 오류가 발생 하였습니다.',9999);
        }

        $updateProc = $walletDb->createQueryBuilder()
            ->update('withdrawal_user')
            ->set('wu_status','?')
            ->set('wu_status_datetime','?')
            ->where('wu_id = ?')
            ->setParameter(0,'SUCCESS')
            ->setParameter(1,$procDatetime)
            ->setParameter(2,$targetInfo['wu_id'])
            ->execute();
        if(!$updateProc){
            throw new Exception('반려 처리에 실패 하였습니다.',9999);
        }

        $pushOption['title'] = 'CyberTron 탈퇴 요청이 정상 처리 되었습니다.';
        $pushOption['contetns'] = '회원 탈퇴 요청 심사 결과 승인 처리 되었습니다. 이용해 주셔서 감사드립니다.';
        $pushOption['dateTime'] = $procDatetime;
        $pushOption['dateTimeTitle'] = '회원 탈퇴 승인 일시';
        //echo $util->success();

    }
    else{
        throw new Exception($langArr['commonApiStringDanger04'],9999);
    }

    //처리 후 안내 PUSH( emial , sms )
    $targetMemberInfo = $walletDb->createQueryBuilder()
        ->select('register_with, email, wallet_phone_email, wallet_phone_email_auth_key, n_phone, n_country, id_auth,auth_phone')
        ->from('admin_accounts')
        ->where('id = ?')
        ->setParameter(0,$targetInfo['wu_accounts_id'])
        ->execute()->fetch();
    if(!$targetMemberInfo){
        throw new Exception('발송 정보를 불러오는데 실패 하였습니다.',9999);
    }

    $mailData = array(
        'senderTitle' => 'CYBERTRON',
        'font' => "'Malgun Gothic',Apple SD Gothic Neo,sans-serif,'맑은고딕',Malgun Gothic,'굴림',gulim",
        'logoImgUrl' => 'https://cybertronchain.com/beta/images/logo.png',
        'pushInfo' => $pushOption
    );
    $templates = new plateTemplate(WALLET_PATH.'/skin/withdrawal', 'html');

    if($targetMemberInfo['register_with'] == 'email'){
        //email 유저인 경우 발송.
        $mailHtml = $templates->render('mailPushForm', ['data' => $mailData]);
        $pushDriver->sendMail($pushOption['title'],$targetMemberInfo['email'],$mailHtml);
    }
    else if($targetMemberInfo['register_with'] == 'phone'){
        //phone 유저인 경우 발송
        //phone 유저이나 email이 등록 되어 있다면 email 우선 발송을 한다.
        if (!empty($targetMemberInfo['wallet_phone_email'] && $targetMemberInfo['wallet_phone_email_auth_key'] == 'OK')) {
            $mailHtml = $templates->render('mailPushForm', ['data' => $mailData]);
            $pushDriver->sendMail($pushOption['title'],$targetMemberInfo['wallet_phone_email'],$mailHtml);
        }
        else{
            $pushDriver->sendMessage($targetMemberInfo['n_phone'],$targetMemberInfo['n_country'],$pushOption['title'],'SMS');

            $inserProc = $walletDb->createQueryBuilder()
                ->insert('send_sms_logs')
                ->setValue('country_code', '?')
                ->setValue('phone_number', '?')
                ->setValue('contents', '?')
                ->setValue('created', '?')
                ->setParameter(0, $targetMemberInfo['n_country'])
                ->setParameter(1, $targetMemberInfo['n_phone'])
                ->setParameter(2, $pushOption['title'])
                ->setParameter(3, $procDatetime)
                ->execute();
            if (!$inserProc) {
                throw new Exception('SMS 발송로그 반영을 실패 하였습니다.', 9999);
            }
        }
    }

    echo $util->success();
}
catch (Exception $e) {
    echo $util->fail(['data' => ['msg' => $e->getMessage()]]);
    exit();
}

?>