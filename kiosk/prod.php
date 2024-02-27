<?php
require_once 'config.php';

$page = (isset($_GET['page'])) ? $_GET['page'] : '1';
$cate = (isset($_GET['cate'])) ? $_GET['cate'] : 'C01';

if (empty($page)) $page = '1';
if (empty($cate)) $cate = 'C01';

$sql = "select count(*) as cnt from goods where use_yn='Y' and category_code='".$cate."'";
$res = $conn->query($sql);

$row = $res->fetch_array(MYSQLI_ASSOC);

$total_count = $row['cnt'];
$last_page = ceil($total_count/9);


$limit_start = ($page-1)*9;

$sql = "select * from goods where use_yn='Y' and category_code='".$cate."' order by disp_cnt asc limit {$limit_start}, 9";
$res = $conn->query($sql);

$count = $res->num_rows;
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
<link rel="stylesheet" href="./css/style.css?<?=time()?>" type="text/css" media="screen, print" />
</head>
<body ondragstart="return false" onresize="return false" onselectstart="return false" resize="none">

    <div class="prod_list">
        <ul>
<?php foreach ($res as $row) {
        $opt_yn = (trim($row['options'])=='') ? 'N' : 'Y';
?>
            <li class="prod" onclick="setData('<?=$row['category_code']?>', '<?=$row['code']?>', '<?=$row['name']?>', <?=$row['price']?>, '<?=$opt_yn?>')">
                <div class="goods_img">
                    <img src="https://www.cybertronchain.com/kiosk/prod_img/<?=(empty($row['img_name'])) ? 'default.png':$row['img_name']?>" />
                </div>
                <div class="goods_name">
                    <?=$row['name']?>
                </div>
                <div class="goods_price">
                    <span class="unit">￦</span> <?=number_format($row['price'])?>원
                </div>
            </li>
<?php } ?>
<?php for ($i=$count; $i<9; $i++) { ?>
            <li class="prod"></li>
<?php } ?>
        </ul>
    </div>

    <div class="navi_list">
        <ul>
            <li class="button">
                <button name="prev" id="prev" class="button" <?php if ($page>1) echo 'onclick="goPage('.($page-1).')"'; ?>>이전</button>
            </li>
<?php for($i=1; $i<=$last_page; $i++) { ?>
            <li>
                <div class="nav_point<?php if ($page==$i) echo ' nav_on'; ?>" onclick="goPage(<?=$i?>)">&nbsp;</div>
            </li>
<?php } ?>
            <li class="button">
                <button name="next" id="next" class="button" <?php if ($page<$last_page) echo 'onclick="goPage('.($page+1).')"'; ?>>다음</button>
            </li>
        </ul>
    </div>

</body>
</html>

<script>
var product = {
    'cate_code' : '',
    'code' : '',
    'name' : '',
    'price' : 0,
    'opt_yn' : 'N',
};

function goPage(page) {
    document.location.href = "./prod.php?cate=<?=$cate?>&page="+page;
}

function setData(p1, p2, p3, p4, p5) {
    product.cate_code = p1;
    product.code = p2;
    product.name = p3;
    product.price = p4;
    product.opt_yn = p5;
//    return false;
/*
    res = JSON.stringify(product);
    console.log(res);
    //alert(''+res);
*/
}

function getChoiceProduct() {
    res = JSON.stringify(product);
    product = {
        'cate_code' : '',
        'code' : '',
        'name' : '',
        'price' : 0,
        'opt_yn' : 'N',
    };
    return res;
}

function init() {
    document.addEventListener("touchmove", onStart, false);
    document.addEventListener("gesturestart", onStart, false);
    document.addEventListener("gesturechange", onStart, false);
    document.addEventListener("gestureend", onStart, false);
    document.addEventListener("onselectstart", onStart, false);
    document.addEventListener("ondragstart", onStart, false);

    document.addEventListener("drag", onStart, false);
    document.addEventListener("drop", onStart, false);
    document.addEventListener("dragstart", onStart, false);
    document.addEventListener("dragend", onStart, false);
    document.addEventListener("dragenter", onStart, false);
    document.addEventListener("dragleave", onStart, false);
    document.addEventListener("dragover", onStart, false);
    document.addEventListener("resize", onStart, false);
    window.addEventListener("resize", onStart, false);
}

function onStart() {
    return false;
}

init();
</script>
