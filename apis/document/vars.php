<?php
include '/var/www/html/apis/config/config_for.php';
$nav_array = array('latoken');

switch($kind) {
	case 'latoken':
		$version = 'v1.0 (2021-01-15)';
		$auth_key = 'TESTCODE123456';
		$to_currency = 'CTC';
		$from_currency = 'USDT';
		$from_ex_rate = '1123.78';
		$to_ex_rate = '181.00';
		$order_id = '16106923818082';
		
		$overview = array(
			'create_invoice' => array('name' => 'Create Invoice', 'explanation'=>array('en'=>'Create Invoice : You will be given a number for inquiry and payment.', 'ko'=>'송장 생성 : 조회 및 결제에 필요한 번호를 부여받습니다.')),
			'get_invoice' => array('name' => 'Get list Invoice', 'explanation'=>array('en'=>'Get list Invoice : You can get the generated invoice information', 'ko'=>'송장 조회 : 생성된 송장 정보를 조회할 수 있습니다.')),
			//'resolve_invoice' => array('name' => 'Resolve Invoice', 'explanation'=>array('en'=>'Resolve Invoice', 'ko'=>'미해결 송장 처리')),
			//'refund_invoice' => array('name' => 'Refund Invoice', 'explanation'=>array('en'=>'Refund Invoice', 'ko'=>'송장 환불')),
			'get_ex_rate' => array('name' => 'Get current exchange rate', 'explanation'=>array('en'=>'Get current exchange rate', 'ko'=>'환율 조회')),
			'withdrawal' => array('name' => 'withdrawal', 'explanation'=>array('en'=>'withdrawal : You can request payment by invoice number.', 'ko'=>'출금요청 : 송장번호로 결제를 요청할 수 있습니다.')),
			'get_withdrawal' => array('name' => 'Get withdrawal', 'explanation'=>array('en'=>'Get withdrawal info : Withdrawal information can be obtained', 'ko'=>'출금정보 조회 : 출금정보를 조회할 수 있습니다.'))
		);

		$overview_detail = array(
			'Output format : JSON',
			'HTTP Request',
			'A unique code value must be sent to every request.'
		);


		$request_title = array('Parameter', 'Type', 'Required', 'Description', 'Ex (Sample)', 'Etc');
		$response_title = array('Parameter', 'Type', 'Description', 'Ex (Sample)', 'Etc');
		$err_title = array('Code', 'Error', 'Message', 'Etc');


		$request_method = 'POST';
		$request_url = 'https://cybertronchain.com/apis/for/apis.php';

		$sample_url_test = 'https://cybertronchain.com/apis/for/latoken.php?code='.$auth_key.'&kind=';

		$sample_url = array(
			'create_invoice' => $sample_url_test.'create_invoice&amp;userId=5137&amp;currency='.$from_currency.'&amp;to_currency='.$to_currency.'&amp;amount=1.0&amp;email=aaaaa@cybertronchain.com&amp;phone=01011111111',
			'get_invoice' => $sample_url_test.'get_invoice&amp;order_id='.$order_id,
			'resolve_invoice' => $sample_url_test.'resolve_invoice&amp;order_id='.$order_id,
			'refund_invoice' => $sample_url_test.'refund_invoice&amp;order_id='.$order_id,
			'get_ex_rate' => $sample_url_test.'get_ex_rate&amp;from_currency='.$from_currency.'&amp;to_currency='.$to_currency,
			'withdrawal' => $sample_url_test.'withdrawal&amp;order_id='.$order_id.'&amp;userId=5137',
			'get_withdrawal' => $sample_url_test.'get_withdrawal&amp;order_id='.$order_id,
		);

		$flow = array(
			'en' => array(
				'When a member clicks the [latoken] button in CTC Wallet, member information is transmitted to latoken.',
				'Request invoice from latoken to CTC Wallet (Send information such as member information and payment amount to CTC Wallet)',
				'Save the information received from latoken and return the invoice number to latoken',
				'Request payment from latoken to CTC Wallet with invoice number and member information',
				'Return result after payment in CTC Wallet'				
			),
			'ko' => array(
				'CTC Wallet에서 회원이 [latoken] 버튼 클릭하면, 회원 정보를 latoken에 전송',
				'latoken에서 CTC Wallet에 송장 요청 (회원정보, 결제금액 등의 정보를 CTC  Wallet에 전송)',
				'latoken에서 전달받은 정보를 저장 후 송장번호를 latoken으로 리턴',
				'latoken에서 송장번호 및 회원정보로 CTC Wallet에 결제요청',
				'CTC Wallet에서 결제 후 결과 리턴'
			)
		);


		$request_info['create_invoice'] = array(
			array('code' => 'code', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Requester identification code', 'ko'=>'고유코드'), 'ex' => $auth_key, 'etc' => ''),
			array('code' => 'kind', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Request Category', 'ko'=>'구분'), 'ex' => 'create_invoice', 'etc' => ''),
			array('code' => 'userId', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'A unique id for the user', 'ko'=>'사용자 고유 ID'), 'ex' => '', 'etc' => ''),
			array('code' => 'currency', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Currency', 'ko'=>'통화'), 'ex' => $from_currency, 'etc' => ''),
			array('code' => 'amount', 'type' => 'number', 'required' => 'TRUE', 'explanation' => array('en'=>'Currency Amount', 'ko'=>'\'currency\'의 수량'), 'ex' => '', 'etc' => ''),
			array('code' => 'to_currency', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Crypto Currency', 'ko'=>'가상화폐'), 'ex' => $to_currency, 'etc' => ''),
			array('code' => 'description', 'type' => 'string', 'required' => 'FALSE', 'explanation' => array('en'=>'Description', 'ko'=>'설명'), 'ex' => '', 'etc' => 'Max length : 150'),
			//array('code' => 'return_url', 'type' => 'string', 'required' => 'FALSE', 'explanation' => array('en'=>'URL to redirect customer from payment page when the payment is completed', 'ko'=>'결제 페이지에서 고객을 리디렉션하는 URL'), 'ex' => 'https://cybertronchain.com/wallet2/...', 'etc' => ''),
			array('code' => 'email', 'type' => 'string', 'required' => 'FALSE', 'explanation' => array('en'=>'Email address', 'ko'=>'사용자 이메일주소'), 'ex' => '', 'etc' => ''),
			array('code' => 'phone', 'type' => 'string', 'required' => 'FALSE', 'explanation' => array('en'=>'Phone number', 'ko'=>'휴대폰번호'), 'ex' => '', 'etc' => 'Without hyphen(-)')
		);
		$response_info['create_invoice'] = array(
			array('code' => 'code', 'type' => 'number', 'explanation' => array('en'=>'Error code if exists', 'ko'=>'존재하는 경우 오류 코드'), 'ex' => '200', 'etc' => ''),
			array('code' => 'error', 'type' => 'boolean', 'explanation' => array('en'=>'Boolean value representing if it is an error response or not.', 'ko'=>'오류 응답인지 여부를 나타내는 값'), 'ex' => 'FALSE', 'etc' => ''),
			array('code' => 'msg', 'type' => 'string', 'explanation' => array('en'=>'Success or error message if exists.', 'ko'=>'존재하는 경우 성공 또는 오류 메시지'), 'ex' => 'Success', 'etc' => ''),
			array('code' => 'order_id', 'type' => 'string', 'explanation' => array('en'=>'ID used for inquiry and payment request', 'ko'=>'조회 및 결제요청시 사용되는 id'), 'ex' => '', 'etc' => ''),
			array('code' => 'currency', 'type' => 'string', 'explanation' => array('en'=>'Currency', 'ko'=>'통화'), 'ex' => 'USDT', 'etc' => ''),
			array('code' => 'amount', 'type' => 'number', 'explanation' => array('en'=>'Currency Amount', 'ko'=>'\'currency\'의 수량'), 'ex' => '', 'etc' => ''),
			array('code' => 'status', 'type' => 'string', 'explanation' => array('en'=>'Status', 'ko'=>'상태'), 'ex' => 'waiting', 'etc' => ''),
			//array('code' => 'return_url', 'type' => 'string', 'explanation' => array('en'=>'', 'ko'=>''), 'ex' => '', 'etc' => ''),
			//array('code' => 'hosted_url', 'type' => 'string', 'explanation' => array('en'=>'', 'ko'=>''), 'ex' => '', 'etc' => ''),
			array('code' => 'created_at', 'type' => 'string', 'explanation' => array('en'=>'Invoice creation date', 'ko'=>'송장 생성일'), 'ex' => '', 'etc' => ''),
			array('code' => 'expired_at', 'type' => 'string', 'explanation' => array('en'=>'Invoice expiration date', 'ko'=>'송장 만료일'), 'ex' => '', 'etc' => ''),
		);
		$error_code['create_invoice'] = array(
			'200' => array('error' => 'FALSE', 'msg' => err_message(200)),
			'801' => array('error' => 'TRUE', 'msg' => err_message(801)),
			'802' => array('error' => 'TRUE', 'msg' => err_message(802)),
			'804' => array('error' => 'TRUE', 'msg' => err_message(804)),
			'805' => array('error' => 'TRUE', 'msg' => err_message(805)),
			'806' => array('error' => 'TRUE', 'msg' => err_message(806)),
			'811' => array('error' => 'TRUE', 'msg' => err_message(811))
		);




		$request_info['get_invoice'] = array(
			array('code' => 'code', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Requester identification code', 'ko'=>'고유코드'), 'ex' => $auth_key, 'etc' => ''),
			array('code' => 'kind', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Request Category', 'ko'=>'구분'), 'ex' => 'get_invoice', 'etc' => ''),
			array('code' => 'order_id', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'ID used for inquiry and payment request', 'ko'=>'조회 및 결제요청시 사용되는 id'), 'ex' => '', 'etc' => '')
		);
		$response_info['get_invoice'] = array(
			array('code' => 'code', 'type' => 'number', 'explanation' => array('en'=>'Error code if exists', 'ko'=>'존재하는 경우 오류 코드'), 'ex' => '200', 'etc' => ''),
			array('code' => 'error', 'type' => 'boolean', 'explanation' => array('en'=>'Boolean value representing if it is an error response or not.', 'ko'=>'오류 응답인지 여부를 나타내는 값'), 'ex' => 'FALSE', 'etc' => ''),
			array('code' => 'msg', 'type' => 'string', 'explanation' => array('en'=>'Success or error message if exists.', 'ko'=>'존재하는 경우 성공 또는 오류 메시지'), 'ex' => 'Success', 'etc' => ''),

			array('code' => 'order_id', 'type' => 'string', 'explanation' => array('en'=>'ID used for inquiry and payment request', 'ko'=>'조회 및 결제요청시 사용되는 id'), 'ex' => '', 'etc' => ''),
			array('code' => 'currency', 'type' => 'string', 'explanation' => array('en'=>'Currency', 'ko'=>'통화'), 'ex' => $from_currency, 'etc' => ''),
			array('code' => 'amount', 'type' => 'number', 'explanation' => array('en'=>'Currency Amount', 'ko'=>'\'currency\'의 수량'), 'ex' => $from_ex_rate, 'etc' => ''),
			array('code' => 'status', 'type' => 'string', 'explanation' => array('en'=>'Status', 'ko'=>'상태'), 'ex' => '', 'etc' => ''),
			//array('code' => 'return_url', 'type' => 'string', 'explanation' => array('en'=>'', 'ko'=>''), 'ex' => '', 'etc' => ''),
			//array('code' => 'hosted_url', 'type' => 'string', 'explanation' => array('en'=>'', 'ko'=>''), 'ex' => '', 'etc' => ''),
			array('code' => 'created_at', 'type' => 'string', 'explanation' => array('en'=>'Invoice creation date', 'ko'=>'송장 생성일'), 'ex' => '', 'etc' => ''),
			array('code' => 'expired_at', 'type' => 'string', 'explanation' => array('en'=>'Invoice expiration date', 'ko'=>'송장 만료일'), 'ex' => '', 'etc' => ''),
			array('code' => 'details', 'type' => '[array]', 'explanation' => array(
				array('code' => 'currency', 'type' => 'string', 'exp' => array('en'=>'Crypto Currency', 'ko'=>'가상화폐'), 'ex' => $to_currency),
				array('code' => 'amount', 'type' => 'number', 'exp' => array('en'=>'\'details &gt; currency\' Amount', 'ko'=>'\'details &gt; currency\' 수량'), 'ex' => $to_ex_rate),
				array('code' => 'address', 'type' => 'string', 'exp' => array('en'=>'Address', 'ko'=>'주소'), 'ex' => ''),
				array('code' => 'txid', 'type' => 'string', 'exp' => array('en'=>'Transaction Hash', 'ko'=>'Transaction Hash'), 'ex' => ''),
				array('code' => 'status', 'type' => 'string', 'exp' => array('en'=>'Status', 'ko'=>'상태'), 'ex' => 'success/failed/pending'),
				array('code' => 'created_at', 'type' => 'string', 'exp' => array('en'=>'Payment date', 'ko'=>'결제일'), 'ex' => '1528726662')
			), 'ex' => '', 'etc' => ''),
		);
		$error_code['get_invoice'] = array(
			'200' => array('error' => 'FALSE', 'msg' => err_message(200)),
			'801' => array('error' => 'TRUE', 'msg' => err_message(801)),
			'802' => array('error' => 'TRUE', 'msg' => err_message(802)),
			'804' => array('error' => 'TRUE', 'msg' => err_message(804)),
			'806' => array('error' => 'TRUE', 'msg' => err_message(806))
		);



		$request_info['resolve_invoice'] = array(
			array('code' => 'code', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Requester identification code', 'ko'=>'고유코드'), 'ex' => $auth_key, 'etc' => ''),
			array('code' => 'kind', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Request Category', 'ko'=>'구분'), 'ex' => 'resolve_invoice', 'etc' => ''),
			array('code' => 'order_id', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'ID used for inquiry and payment request', 'ko'=>'조회 및 결제요청시 사용되는 id'), 'ex' => '', 'etc' => '')
		);
		$response_info['resolve_invoice'] = array(
			array('code' => 'code', 'type' => 'number', 'explanation' => array('en'=>'Error code if exists', 'ko'=>'존재하는 경우 오류 코드'), 'ex' => '200', 'etc' => ''),
			array('code' => 'error', 'type' => 'boolean', 'explanation' => array('en'=>'Boolean value representing if it is an error response or not.', 'ko'=>'오류 응답인지 여부를 나타내는 값'), 'ex' => 'FALSE', 'etc' => ''),
			array('code' => 'msg', 'type' => 'string', 'explanation' => array('en'=>'Success or error message if exists.', 'ko'=>'존재하는 경우 성공 또는 오류 메시지'), 'ex' => 'Success', 'etc' => '')
		);
		$error_code['resolve_invoice'] = array(
			'200' => array('error' => 'FALSE', 'msg' => err_message(200)),
			'801' => array('error' => 'TRUE', 'msg' => err_message(801)),
			'802' => array('error' => 'TRUE', 'msg' => err_message(802)),
			'804' => array('error' => 'TRUE', 'msg' => err_message(804)),
			'806' => array('error' => 'TRUE', 'msg' => err_message(806))
		);



		$request_info['refund_invoice'] = array(
			array('code' => 'code', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Requester identification code', 'ko'=>'고유코드'), 'ex' => $auth_key, 'etc' => ''),
			array('code' => 'kind', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Request Category', 'ko'=>'구분'), 'ex' => 'refund_invoice', 'etc' => ''),
			array('code' => 'order_id', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'ID used for inquiry and payment request', 'ko'=>'조회 및 결제요청시 사용되는 id'), 'ex' => '', 'etc' => '')
		);
		$response_info['refund_invoice'] = array(
			array('code' => 'code', 'type' => 'number', 'explanation' => array('en'=>'Error code if exists', 'ko'=>'존재하는 경우 오류 코드'), 'ex' => '200', 'etc' => ''),
			array('code' => 'error', 'type' => 'boolean', 'explanation' => array('en'=>'Boolean value representing if it is an error response or not.', 'ko'=>'오류 응답인지 여부를 나타내는 값'), 'ex' => 'FALSE', 'etc' => ''),
			array('code' => 'msg', 'type' => 'string', 'explanation' => array('en'=>'Success or error message if exists.', 'ko'=>'존재하는 경우 성공 또는 오류 메시지'), 'ex' => 'Success', 'etc' => '')
		);
		$error_code['refund_invoice'] = array(
			'200' => array('error' => 'FALSE', 'msg' => err_message(200)),
			'801' => array('error' => 'TRUE', 'msg' => err_message(801)),
			'802' => array('error' => 'TRUE', 'msg' => err_message(802)),
			'804' => array('error' => 'TRUE', 'msg' => err_message(804)),
			'806' => array('error' => 'TRUE', 'msg' => err_message(806))
		);




		$request_info['get_ex_rate'] = array(
			array('code' => 'code', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Requester identification code', 'ko'=>'고유코드'), 'ex' => $auth_key, 'etc' => ''),
			array('code' => 'kind', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Request Category', 'ko'=>'구분'), 'ex' => 'get_ex_rate', 'etc' => ''),
			array('code' => 'from_currency', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'From Currency', 'ko'=>'From Currency'), 'ex' => $from_currency, 'etc' => ''),
			array('code' => 'to_currency', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'To Currency', 'ko'=>'To Currency'), 'ex' => $to_currency, 'etc' => '')
		);
		$response_info['get_ex_rate'] = array(
			array('code' => 'code', 'type' => 'number', 'explanation' => array('en'=>'Error code if exists', 'ko'=>'존재하는 경우 오류 코드'), 'ex' => '200', 'etc' => ''),
			array('code' => 'error', 'type' => 'boolean', 'explanation' => array('en'=>'Boolean value representing if it is an error response or not.', 'ko'=>'오류 응답인지 여부를 나타내는 값'), 'ex' => 'FALSE', 'etc' => ''),
			array('code' => 'msg', 'type' => 'string', 'explanation' => array('en'=>'Success or error message if exists.', 'ko'=>'존재하는 경우 성공 또는 오류 메시지'), 'ex' => 'Success', 'etc' => ''),
			array('code' => 'from_currency', 'type' => 'string', 'explanation' => array('en'=>'From Currency', 'ko'=>'From Currency'), 'ex' => $from_currency, 'etc' => ''),
			array('code' => 'to_currency', 'type' => 'string', 'explanation' => array('en'=>'To Currency', 'ko'=>'To Currency'), 'ex' => $to_currency, 'etc' => ''),
			array('code' => 'from_amount', 'type' => 'number', 'explanation' => array('en'=>'Amount per 1'.$from_currency.' (WON)', 'ko'=>'1'.$from_currency.'당 금액(원)'), 'ex' => $from_ex_rate, 'etc' => ''),
			array('code' => 'to_amount', 'type' => 'number', 'explanation' => array('en'=>'Amount per 1'.$to_currency.' (WON)', 'ko'=>'1'.$to_currency.'당 금액(원)'), 'ex' => $to_ex_rate, 'etc' => ''),
		);
		$error_code['get_ex_rate'] = array(
			'200' => array('error' => 'FALSE', 'msg' => err_message(200)),
			'801' => array('error' => 'TRUE', 'msg' => err_message(801)),
			'802' => array('error' => 'TRUE', 'msg' => err_message(802)),
			'804' => array('error' => 'TRUE', 'msg' => err_message(804)),
			'805' => array('error' => 'TRUE', 'msg' => err_message(805)),
			'806' => array('error' => 'TRUE', 'msg' => err_message(806))
		);


		$request_info['withdrawal'] = array(
			array('code' => 'code', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Requester identification code', 'ko'=>'고유코드'), 'ex' => $auth_key, 'etc' => ''),
			array('code' => 'kind', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Request Category', 'ko'=>'구분'), 'ex' => 'withdrawal', 'etc' => ''),
			array('code' => 'order_id', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'ID used for inquiry and payment request', 'ko'=>'조회 및 결제요청시 사용되는 id'), 'ex' => '', 'etc' => ''),
			array('code' => 'userId', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'A unique id for the user', 'ko'=>'사용자 고유 ID'), 'ex' => '', 'etc' => '')
		);
		$response_info['withdrawal'] = array(
			array('code' => 'code', 'type' => 'number', 'explanation' => array('en'=>'Error code if exists', 'ko'=>'존재하는 경우 오류 코드'), 'ex' => '200', 'etc' => ''),
			array('code' => 'error', 'type' => 'boolean', 'explanation' => array('en'=>'Boolean value representing if it is an error response or not.', 'ko'=>'오류 응답인지 여부를 나타내는 값'), 'ex' => 'FALSE', 'etc' => ''),
			array('code' => 'msg', 'type' => 'string', 'explanation' => array('en'=>'Success or error message if exists.', 'ko'=>'존재하는 경우 성공 또는 오류 메시지'), 'ex' => 'Success', 'etc' => ''),
			array('code' => 'txid', 'type' => 'string', 'explanation' => array('en'=>'Transaction Hash', 'ko'=>'Transaction Hash'), 'ex' => '', 'etc' => ''),
			array('code' => 'status', 'type' => 'string', 'explanation' => array('en'=>'Status', 'ko'=>'상태'), 'ex' => 'success/failed/pending', 'etc' => '')
		);
		$error_code['withdrawal'] = array(
			'200' => array('error' => 'FALSE', 'msg' => err_message(200)),
			'801' => array('error' => 'TRUE', 'msg' => err_message(801)),
			'802' => array('error' => 'TRUE', 'msg' => err_message(802)),
			'804' => array('error' => 'TRUE', 'msg' => err_message(804)),
			'806' => array('error' => 'TRUE', 'msg' => err_message(806)),
			'807' => array('error' => 'TRUE', 'msg' => err_message(807)),
			'809' => array('error' => 'TRUE', 'msg' => err_message(809))
		);



		$request_info['get_withdrawal'] = array(
			array('code' => 'code', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Requester identification code', 'ko'=>'고유코드'), 'ex' => $auth_key, 'etc' => ''),
			array('code' => 'kind', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'Request Category', 'ko'=>'구분'), 'ex' => 'get_withdrawal', 'etc' => ''),
			array('code' => 'order_id', 'type' => 'string', 'required' => 'TRUE', 'explanation' => array('en'=>'ID used for inquiry and payment request', 'ko'=>'조회 및 결제요청시 사용되는 id'), 'ex' => '', 'etc' => '')
		);
		$response_info['get_withdrawal'] = array(
			array('code' => 'code', 'type' => 'number', 'explanation' => array('en'=>'Error code if exists', 'ko'=>'존재하는 경우 오류 코드'), 'ex' => '200', 'etc' => ''),
			array('code' => 'error', 'type' => 'boolean', 'explanation' => array('en'=>'Boolean value representing if it is an error response or not.', 'ko'=>'오류 응답인지 여부를 나타내는 값'), 'ex' => 'FALSE', 'etc' => ''),
			array('code' => 'msg', 'type' => 'string', 'explanation' => array('en'=>'Success or error message if exists.', 'ko'=>'존재하는 경우 성공 또는 오류 메시지'), 'ex' => 'Success', 'etc' => ''),
			array('code' => 'status', 'type' => 'string', 'explanation' => array('en'=>'Status', 'ko'=>'상태'), 'ex' => 'success/failed/pending', 'etc' => ''),
			array('code' => 'details', 'type' => '[array]', 'required'=>'FALSE', 'explanation' => array(
				array('code' => 'txid', 'type' => 'string', 'exp' => array('en'=>'Transaction Hash', 'ko'=>'Transaction Hash'), 'ex' => ''),
				array('code' => 'address', 'type' => 'string', 'exp' => array('en'=>'address', 'ko'=>'주소'), 'ex' => ''),
				array('code' => 'amount', 'type' => 'number', 'exp' => array('en'=>'amount', 'ko'=>'수량'), 'ex' => '')
			), 'ex' => '', 'etc' => '')
		);
		$error_code['get_withdrawal'] = array(
			'200' => array('error' => 'FALSE', 'msg' => err_message(200)),
			'801' => array('error' => 'TRUE', 'msg' => err_message(801)),
			'802' => array('error' => 'TRUE', 'msg' => err_message(802)),
			'804' => array('error' => 'TRUE', 'msg' => err_message(804)),
			'806' => array('error' => 'TRUE', 'msg' => err_message(806))
		);



		break;


}
?>
