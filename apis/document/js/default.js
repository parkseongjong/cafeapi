
function nav_menu() {
	if ( $("nav #menu").is(":visible") ) {
		$("nav #menu").slideUp();
	} else{
		$("nav #menu").slideDown();
	}
}

function nav_menu_resize() {
	var windowWidth = $( window ).width();
	if (windowWidth <= 500 ) {
		$("nav div#menu").css('display', 'none');
		$("nav #toggle").css('display', 'block');
	} else {
		$("nav div#menu").css('display', 'block');
		$("nav #toggle").css('display', 'none');
	}
}

function nav_lang() {
	$("#langFrm").submit();
}

