<?php

/*
 *
 *  by. OJT 2021.06.19 사용 중인 페이지 입니다.
 *  회원 탈퇴를 처리 하는 곳 입니다.
 *
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';
require_once '../config/config_wallet_original.php';

use wallet\common\Auth as walletAuth;
use wallet\common\Util as walletUtil;
use wallet\common\Valid as walletValid;
use wallet\ctcDbDriver\Driver as walletDb;
use wallet\common\Filter as walletFilter;
use wallet\common\Request as walletRequest;
use wallet\withdrwal\Release as walletWithdrwalRelease;

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
    $plainPathData = $request->getQueryParams();

    if(!$auth->sessionAuth()){
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
    if($filterPathData['type'] == 'passwordCheck'){
        $valid = $filter->apiHeaderCheck($w_api_key);
        if($valid !== true){
            //그럴 일은 없겠지만 익셉션 캐치를 못하는 경우 .. exit.
            exit();
        }

        $targetPostData = array(
            'memberId' => 'integerNotEmpty',
            'password' => 'stringNotEmpty',
        );
        $filterData = $filter->postDataFilter($plainData,$targetPostData);

        unset($targetPostData,$plainData);

        //비밀번호 구 체계 먼저 조회,
        $memberInfo = $walletDb->createQueryBuilder()
            ->select('id')
            ->from('admin_accounts')
            ->where('id = ?')
            ->andWhere('passwd = ?')
            ->andWhere('passwd_new is NULL')
            ->andWhere('passwd_datetime is NULL')
            ->setParameter(0,$filterData['memberId'])
            ->setParameter(1,$filterData['password'])
            ->execute()->fetch();

        //구 체계로 정보 조회에 실패 하였다면, 신 체계로 조회
        if(!$memberInfo){
            //salt 값을 가져 와야해서 email로 조회
            $memberInfoTemp = $walletDb->createQueryBuilder()
                ->select('passwd_new, passwd_salt, passwd_datetime')
                ->from('admin_accounts')
                ->where('id = ?')
                ->andWhere('passwd_new is not NULL')
                ->andWhere('passwd_datetime is not NULL')
                ->setParameter(0,$filterData['memberId'])
                ->execute()->fetch();

            if($memberInfoTemp){
                $hash = hash('sha512',trim($memberInfoTemp['passwd_salt'].$filterData['password']));
                if(hash_equals($hash,$memberInfoTemp['passwd_new'])){
                    $memberInfo = $walletDb->createQueryBuilder()
                        ->select('id, wallet_address, etoken_ectc, etoken_etp3, etoken_emc, etoken_ekrw, etoken_eeth, etoken_eusdt, etoken_ebtc, etoken_ebnb')
                        ->from('admin_accounts')
                        ->where('id = ?')
                        ->setParameter(0,$filterData['memberId'])
                        ->execute()->fetch();
                }
                unset($memberInfoTemp);
            }
        }
        if(!$memberInfo){
            throw new Exception($langArr['login_fail_msg2'],9999);
        }

        $withdrawalInfo = $walletDb->createQueryBuilder()
            ->select('wu_id')
            ->from('withdrawal_user')
            ->where('wu_accounts_id = ?')
            ->setParameter(0,$memberInfo['id'])
            ->execute()->fetch();
        if($withdrawalInfo){
            throw new Exception($langArr['withdrawalErrorString01'],9999);
        }

        $temp = true;
        /*
         * e-coin
         * real coin 둘다 조회 해야함.
         */
        $assetList = array(
            'eToken' => [
                'etokenEctc'=>$memberInfo['etoken_ectc'],
                'etokenEtp3'=>$memberInfo['etoken_etp3'],
                'etokenEmc'=>$memberInfo['etoken_emc'],
                'etokenEkrw'=>$memberInfo['etoken_ekrw'],
                'etokenEeth'=>$memberInfo['etoken_eeth'],
                'etokenEusdt'=>$memberInfo['etoken_eusdt'],
                'etokenEbtc'=>$memberInfo['etoken_ebtc'],
                'etokenEbnb'=>$memberInfo['etoken_ebnb'],
                'total' => 0
            ],

            'realToken' => [
                'total' => 0
            ]
        );

        foreach ($assetList['eToken'] as $key => $value){
            if($key != 'total'){
                $assetList['eToken']['total'] = $value + $assetList['eToken']['total'];
            }
        }

        //로컬에서는 사용 불가능...
//        $getbalances = array();
//        if (!empty($memberInfo['wallet_address']) ) {
//            require_once WALLET_PATH.'/lib/WalletInfos.php';
//            $wi_wallet_infos = new WalletInfos();
//            $getbalances = $wi_wallet_infos->wi_get_balance('', 'all', $memberInfo['wallet_address'], $contractAddressArr);
//        }

        //test
        $getbalances = ['tp3'=>0,'usdt'=>0,'eth'=>0];

        foreach ($getbalances as $key =>$value){
            $assetList['realToken']['total'] = (float)$value + $assetList['realToken']['total'];
            $assetList['realToken'][$key] = $value;
        }

        if($assetList['eToken']['total'] > 0 || $assetList['realToken']['total'] > 0){
            $assetStatus = true;
        }
        else{
            $assetStatus = false;
        }
        $assetBuilder = $walletDb->createQueryBuilder()
            ->insert('withdrawal_user')
            ->setValue('wu_accounts_id',$memberInfo['id'])
            ->setValue('wu_datetime', '?')
            ->setValue('wu_type', '?')
            ->setParameter(0,$util->getDateSql());

        //자산이 남았는지 조회가 필요 함
        if($assetStatus  ===  true){
            //자산 있음
            //자산이 있는 경우 어드민 페이지에서 처리 해줌.
            $type = 'asset';
            $insertProc = $assetBuilder
                ->setParameter(1,'asset')
                ->execute();
            if(!$insertProc){
                throw new Exception('탈퇴 신청 중 오류가 발생 하였습니다.',9999);
            }
            echo $util->success(['otherCode'=>10]);
        }
        else{
            //자산 없음
            //자산이 없는 경우는 바로 처리
            $insertProc = $assetBuilder
                ->setParameter(1,'empty')
                ->setValue('wu_status_datetime','?')
                ->setParameter(2,$util->getDateSql())
                ->execute();
            if(!$insertProc){
                throw new Exception('탈퇴 신청 중 오류가 발생 하였습니다.',9999);
            }
            $walletWithdrwalRelease = new walletWithdrwalRelease($memberInfo['id']);
            $releaseProc = $walletWithdrwalRelease->userRelease();
            if($releaseProc['code'] != 200){
                throw new Exception('탈퇴 처리 중 오류가 발생 하였습니다.',9999);
            }
            echo $util->success(['otherCode'=>20]);
        }


    }
    else if($filterPathData['type'] == 'upload'){
        $valid = $filter->apiHeaderCheckMultipart($w_api_key);
        if($valid !== true){
            //그럴 일은 없겠지만 익셉션 캐치를 못하는 경우 .. exit.
            exit();
        }
        $targetPostData = array(
            'memberId' => 'integerNotEmpty',
        );
        $filterData = $filter->postDataFilter($plainData,$targetPostData);
        $files = $request->getUploadedFiles();

        $uploadFileInfo = $util->slimApiMoveUploadedFile(WALLET_PATH.'/userfiles/withdrawal',$files['uploadFile'],'image');
        if(!$uploadFileInfo){
            throw new Exception($langArr['commonApiStringDanger04'],9999);
        }

        $memberInfo = $walletDb->createQueryBuilder()
            ->select('wu_accounts_id, wu_id')
            ->from('withdrawal_user')
            ->where('wu_accounts_id = ?')
            ->andWhere('wu_type = ?')
            ->andWhere('wu_status != ? OR wu_status is null')
            ->setParameter(0,$filterData['memberId'])
            ->setParameter(1,'asset')
            ->setParameter(2,'SUCCESS')
            ->execute()->fetch();
        if(!$memberInfo){
            throw new Exception($langArr['withdrawalUploadStringDanger01'],9999);
        }

        $nowDateTime = $util->getDateSql();
        $updateProc = $walletDb->createQueryBuilder()
            ->update('withdrawal_user')
            ->set('wu_status','?')
            ->set('wu_datetime','?')
            ->set('wu_source','?')
            ->set('wu_target','?')
            ->set('wu_width','?')
            ->set('wu_height','?')
            ->set('wu_image_type','?')
            ->set('wu_size','?')
            ->set('wu_image_datetime','?')
            ->where('wu_id = ?')
            ->setParameter(0,'PENDING')
            ->setParameter(1,$nowDateTime)
            ->setParameter(2,$uploadFileInfo['name'])
            ->setParameter(3,$uploadFileInfo['convertName'])
            ->setParameter(4,$uploadFileInfo['width'])
            ->setParameter(5,$uploadFileInfo['height'])
            ->setParameter(6,$uploadFileInfo['extension'])
            ->setParameter(7,$uploadFileInfo['size'])
            ->setParameter(8,$nowDateTime)
            ->setParameter(9,$memberInfo['wu_id'])
            ->execute();
        if(!$updateProc){
            throw new Exception($langArr['commonApiStringDanger04'],9999);
        }

        //탈퇴 심사 신청 시 강제로 세션을 파괴, REJECT 된 경우 로그인 가능.. 그 전엔 로그인 불가능.
        session_destroy();
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