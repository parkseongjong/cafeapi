<?php
require_once 'config.php';

$auth_key = (isset($_GET['auth_key'])) ? $_GET['auth_key'] : '';
$page = (isset($_GET['page'])) ? $_GET['page'] : '1';
$date1 = (isset($_GET['date1'])) ? $_GET['date1'] : '';


if ( empty($auth_key) ) {
	$err = '접근 권한이 없습니다.';

}

$sql = "select * from franchise where auth_key='".$auth_key."' and use_yn='Y'";
$res = $conn->query($sql);
$row = $res->fetch_assoc();

if (empty($row) || empty($row['wallet_address'])) {
	$err = '접근 권한이 없습니다.';
	exit;
}


if (empty($page)) $page = '1';

$limit_count = 10;



if (empty($date1)) $date1 = date("Y-m-d");
$addwhere = '';
if ( !empty($date1) ) {
	$addwhere = " and ok_date like '".$date1."%'";
}

$sql1 = "select count(*) as cnt from order_list_detail where fran_id='".$row['id']."'".$addwhere;
$res1 = $conn->query($sql1);
$row1 = $res1->fetch_array(MYSQLI_ASSOC);
$total_count = $row1['cnt'];
$last_page = ceil($total_count/$limit_count);

$limit_start = ($page-1)*$limit_count;

$sql2 = "select * from order_list_detail where fran_id='".$row['id']."'".$addwhere." order by id asc limit {$limit_start}, ".$limit_count;
$res2 = $conn->query($sql2);
$row2 = $res2->fetch_assoc();
$count2 = $res2->num_rows;

$sql3 = "select pay_type, status, sum(detail_count) as total_count, sum(detail_price) as total_price from order_list_detail where fran_id='".$row['id']."'".$addwhere." group by pay_type, status";
$res3 = $conn->query($sql3);
$row3 = $res3->fetch_assoc();
$count3 = $res2->num_rows;

?>

<!doctype html>
<html lang="en" ondragstart="return false" onresize="return false" onselectstart="return false" resize="none">
<head>
<meta charset="UTF-8">
<meta name="MobileOptimized" content="240" />
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<meta name="HandheldFriendly" content="true" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<meta http-equiv="page-exit"  content="blendtrans(duration=0.1)" />
<meta http-equiv="page-enter" content="blendtrans(duration=0.2)" />
<title> OneFamily </title>
<link rel="stylesheet" href="./css/style_list.css?<?=time()?>" type="text/css" media="screen, print" />
</head>
<body ondragstart="return false" onresize="return false" onselectstart="return false" resize="none">
	<?php
	if ( !empty($err) ) {
		echo 'ERROR : '.$err.'<br />';
} else {
	echo 'Welcome!<br />';
}?>

<form method="get" name="frm1" action="">
	<input type="hidden" name="auth_key" value="<?=$auth_key?>" />
	<input type="date" name="date1" value="<?=$date1?>" />
	<input type="hidden" name="page" value="<?=$page?>" />
	<input type="submit" value="검색" />
</form>

<?php
function status_change($val) {
	$result = $val;
	switch($val) {
		case 'OK':
			$result = '성공';
			break;
		case 'CANCEL':
			$result = '취소';
			break;
		default:
			$result = $val;
			break;
	}
	return $result;
}
if ( $count3 > 0 ) {
	?>
	<h3>* 통계</h3>
	<table class="total">
		<colgroup>
			<col width="25%" />
			<col width="25%" />
			<col width="25%" />
			<col width="25%" />
		</colgroup>
		<thead>
			<tr>
				<th>결제타입</th>
				<th>상태</th>
				<th>총수량</th>
				<th>총가격(단위:원)</th>
			</tr>
		</thead>
		<tbody>
		<?php
		foreach ($res3 as $row3) {
			$status = status_change($row3['status']);
			?><tr>
				<td><?=$row3['pay_type']?></td>
				<td><?=$status?></td>
				<td><?=$row3['total_count']?></td>
				<td><?=number_format($row3['total_price'])?></td>
			</tr><?php
		}
		?></tbody></table><?php
}
?>
<h3>* 리스트</h3>
<table class="list">
		<colgroup>
			<col width="8%" />
			<col width="8%" />
			<col width="15%" />

			<col width="5%" />
			<col width="10%" />
			<col width="18%" />

			<col width="8%" />
			<col width="10%" />
			<col width="18%" />
		</colgroup>
	<thead>
		<tr>
			<th>결제타입</th>
			<th>총결제금액</th>
			<th>결제승인번호</th>

			<th>상태</th>
			<th>시간</th>
			<th>상품명</th>

			<th>수량</th>
			<th>가격(단위:원)</th>
			<th>옵션</th>
		</tr>
	</thead>
	

	<?php
	if ( $count2 > 0 ) {
		//$before_approval_no = '';
		?><tbody><?php
		foreach ($res2 as $row2) {
			$status = '';
			$pay_type = '';
			$approval_no = '';
			$total_price = '';

			$status = status_change($row2['status']);
			$ok_date = explode(' ', $row2['ok_date']);
			$ok_date = $ok_date[1];

			$pay_type = $row2['pay_type'];

			$approval_no = $row2['approval_no'];

			$total_price = $row2['total_price'];

			//if ( $before_approval_no == $row2['approval_no'] ) {
				//$pay_type = '';
				//$approval_no = '';
				//$total_price = '';
				//$status = '';
			//}
			?><tr>
				<td><?=$pay_type?></td>
				<td><?=$total_price?></td>
				<td><?=$approval_no?></td>
				<td><?=$status?></td>
				<td><?=$ok_date?></td>
				<td><?=$row2['detail_name']?></td>
				<td><?=$row2['detail_count']?></td>
				<td><?=number_format($row2['detail_price'])?></td>
				<td><?=$row2['detail_option']?></td>
			</tr><?php
			//$before_approval_no = $row2['approval_no'];
		}
		?></tbody><?php
	}
	?>
	</table>

    <div class="navi_list">
        <ul>
            <li class="button">
				<?php
				if ( $last_page > 1 && $page > 1 ) { ?>
					<button name="prev" id="prev" class="button" onclick="goPage(1)">처음</button>
					<button name="prev" id="prev" class="button" onclick="goPage(<?=($page-1)?>)">이전</button>
				<?php } ?>
            </li>
<?php for($i=1; $i<=$last_page; $i++) { ?>
            <li>
                <div class="nav_point<?php if ($page==$i) echo ' nav_on'; ?>" onclick="goPage(<?=$i?>)">&nbsp;</div>
            </li>
<?php } ?>
            <li class="button">
			<?php
				if ( $last_page > 1 ) { 
					if ( $page < $last_page ) { ?>
		                <button name="next" id="next" class="button" <?php if ($page<$last_page) echo 'onclick="goPage('.($page+1).')"'; ?>>다음</button>
					<?php }
					if ( $page != $last_page ) { ?>
	                <button name="prev" id="prev" class="button" onclick="goPage(<?=$last_page?>)">마지막</button>
				<?php } } ?>
            </li>
        </ul>
    </div>

	<ul>
		<li>1회 결제시 주문한 옵션에 따라 1개가 보일 수도, 여러개가 보일 수도 있습니다.</li>
		<li>[총결제금액]은 1회 결제시 총 주문한 결제 금액이므로, [총결제금액]으로 합계를 내고자 할 경우 동일한 [결제승인번호]당 1번씩만 합산하여 계산해야 합니다.</li>
		<li>[총결제금액]의 표시 단위는 '원' 'eTP3', 'eMC' 중 1개입니다.</li>
	</ul>

</body>
</html>

<script>

function goPage(page) {
    document.location.href = "./list.php?auth_key=<?=$auth_key?>&date1=<?=$date1?>&page="+page;
}

</script>
