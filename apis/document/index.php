<!DOCTYPE html>
<html lang="en">
 <head>
<meta charset="UTF-8">
<title>API Document</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">

<link href="css/style.css?ver=1.1" rel="stylesheet" />
<script type="text/javascript" src="js/jquery-3.5.1.min.js"></script>
<script type="text/javascript" src="js/default.js"></script>

</head>
<body>

<?php

$kind = (isset($_GET['kind'])) ? $_GET['kind'] : '';
$lang = (isset($_GET['lang'])) ? $_GET['lang'] : 'en';
include 'vars.php';
?>
<script>
$(function() {
	$("nav #toggle").on('click', function () {
		nav_menu();
	});
	$("#langFrm select").on('change', function() {
		nav_lang();
	});
});
$(window).resize(function() {
	nav_menu_resize();
});

</script>

<nav>
	<div id="toggle" onclick="" >&#8801;</div>
	
	<div id="menu">
		<div id="menu_flex">
			<ul class="top">
				<?php foreach($nav_array as $val) {
					?><li class="<?php if ( $val == $kind ) echo 'on'; ?>"><a href="?kind=<?php echo $val; ?>&lang=<?php echo $lang; ?>" title="<?php echo $val; ?>"><?php echo $val; ?></a></li><?php
				} ?>
			</ul>
				
			<form method="get" id="langFrm" action="">
				<input type="hidden" name="kind" value="<?php echo $kind; ?>" />
				<select name="lang">
					<option value="en" <?php if ( $lang == 'en' ) echo  "selected"; ?> />English
					<option value="ko" <?php if ( $lang == 'ko' ) echo "selected"; ?> />Korean
				</select>

			</form>
		</div>
	</div>
</nav>

<div class="contents">
<?php
if ( !empty($kind) ) {
	include "main.php";
}
?>
</div>

</body>
</html>
