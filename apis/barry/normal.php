<?php

/*
 *
 *  by. OJT 2021.06.28 사용 중인 페이지 입니다.
 *  barry WALLET 개인정보 조회 API
 *
 */
session_start();
header('Content-Type: application/json; charset=UTF-8');
require_once '../config/config.php';
require_once '../config/config_wallet.php';

use wallet\common\Util as walletUtil;
use wallet\common\Filter as walletFilter;
use wallet\ctcDbDriver\Driver as walletDb;
use wallet\common\Request as walletRequest;
use barry\encrypt\Rsa as barryRsa;

use \League\Plates\Extension\Asset as plateTemplateAsset;




require(BASE_PATH . '/../wallet2/vendor/autoload.php');

//에러 상수는 나중에 따로 분리?
const NOT_SEARCH_MSG = '찾을 수 없는 정보 입니다.';

try {
    $util = walletUtil::singletonMethod();
    $walletDb = walletDb::singletonMethod();
    $walletDb = $walletDb->init();
    $filter = walletFilter::singletonMethod();
    $request = walletRequest::singletonMethod();
    $barryRsa = new barryRsa();

    /*
        유입 데이터 필터 START
    */
    $request = $request->getRequest();
    $plainData = $request->getParsedBody();
    $plainPathData = $request->getQueryParams();

    $targetPostData = array(
        'type' => 'stringNotEmpty' //wallet 으로 요청하는 barry 타입
    );
    $filterPathData = $filter->postDataFilter($plainPathData,$targetPostData);

    $targetPostData = array(
        'signature' => 'stringNotEmpty',
        'timestamp' => 'integerNotEmpty',
        'value' => 'stringNotEmpty', // wallet 으로 요청하는 고유 ID
    );
    $filterData = $filter->postDataFilter($plainData,$targetPostData);
    unset($plainPathData,$targetPostData);

    if(!$util->serverCommunicationAuth('walletadmin',$filterData['value'],$filterData['timestamp'],$filterData['signature'])){
        throw new Exception('NOT FOUND REQUEST',9999);
    }
    /*
        유입 데이터 필터 END
    */

    if($filterPathData['type'] == 'barryAuth'){
        unset($plainData);
        $memberInfo = $walletDb->createQueryBuilder()
            ->select('*')
            ->from('barry_auth')
            ->where('ctc_key = ?')
            ->setParameter(0, $filterData['value'])
            ->execute()->fetch();

        if(!$memberInfo){
            throw new Exception(NOT_SEARCH_MSG,9999);
        }

        //결과 값 암호화
        foreach ($memberInfo as $key => $value){
            $memberInfo[$key] = $barryRsa->encrypt($value);
        }

        echo $util->success(['data' => $memberInfo]);

    }
    else if($filterPathData['type'] == 'walletInfo'){
        unset($plainData);
        $memberInfo = $walletDb->createQueryBuilder()
            ->select('id, auth_name, lname, name, email, passwd, id_auth, auth_phone, n_phone, register_with')
            ->from('admin_accounts')
            ->where('id = ?')
            ->setParameter(0,$filterData['value'])
            ->execute()->fetch();
        if(!$memberInfo){
            throw new Exception(NOT_SEARCH_MSG,9999);
        }

        //본인 인증 회원 시 본인 인증 번호 리턴
        if($memberInfo['id_auth'] == 'Y'){
            $memberInfo['buildBarryId'] = $memberInfo['auth_phone'];
        }
        else{
            if($memberInfo['register_with'] == "phone"){
                //핸드폰 가입자 이지만, 번호가 없는 경우도 false 던져준다 이용못함.
                if($memberInfo['n_phone']){
                    $memberInfo['buildBarryId'] = $memberInfo['n_phone'];
                }
                else{
                    $memberInfo['buildBarryId'] = false;
                }
            }
            else{
                //핸드폰 번호가 없는 경우 베리몰 이용을 못하게 false 던져준다.
                $memberInfo['buildBarryId'] = false;
            }
        }

        //결과 값 암호화
        foreach ($memberInfo as $key => $value){
            $memberInfo[$key] = $barryRsa->encrypt($value);
        }


        echo $util->success(['data' => $memberInfo]);
    }
    else if($filterPathData['type'] == 'walletInfoFromVirtualWalletAddress'){
        unset($plainData);
        $memberInfo = $walletDb->createQueryBuilder()
            ->select('id, auth_name, lname, name, email, passwd, id_auth')
            ->from('admin_accounts')
            ->where('virtual_wallet_address = ?')
            ->setParameter(0,$filterData['value'])
            ->execute()->fetch();

        //결과 값 암호화
        foreach ($memberInfo as $key => $value){
            $memberInfo[$key] = $barryRsa->encrypt($value);
        }

        if(!$memberInfo){
            throw new Exception(NOT_SEARCH_MSG,9999);
        }

        echo $util->success(['data' => $memberInfo]);
    }
    else if($filterPathData['type'] == 'barrySellerInfo'){
        $targetPostData = array(
            'name' => 'stringNotEmpty',
        );
        foreach ($filter->postDataFilter($plainData,$targetPostData) as $key=>$value){
            $filterData[$key] = $value;
        }
        unset($plainData);
        $sellerInfo = $walletDb->createQueryBuilder()
            ->select('*')
            ->from('barry_seller_request')
            ->where('barry_id = ?')
            ->andWhere('confirm_yn = "N" OR confirm_yn ="Y"')
            ->setParameter(0,$filterData['value'])
            ->execute()->fetch();
        if($sellerInfo){
            throw new Exception(NOT_SEARCH_MSG,9999);
        }

        $insertProc = $walletDb->createQueryBuilder()
            ->insert('barry_seller_request')
            ->setValue('barry_id','?')
            ->setValue('barry_name','?')
            ->setParameter(0, $filterData['value'])
            ->setParameter(1, $filterData['name'])
            ->execute();
        if(!$insertProc){
            throw new Exception(NOT_SEARCH_MSG,9999);
        }

        echo $util->success();

    }
    else if($filterPathData['type'] == 'barryAuthExp'){
        unset($plainData);
        $memberInfo = $walletDb->createQueryBuilder()
            ->delete('barry_auth')
            ->where('ctc_key = ?')
            ->setParameter(0, $filterData['value'])
            ->execute();

        if(!$memberInfo){
            throw new Exception(NOT_SEARCH_MSG,9999);
        }

        echo $util->success();

    }
    else{
        throw new Exception(NOT_SEARCH_MSG,9999);
    }


} catch (Exception $e) {
    echo $util->fail(['data' => ['msg' => $e->getMessage()]]);
    exit();
}

?>