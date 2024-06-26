let slideshow_timeout	= '';

function slideshow_stop() {
	document.getElementById("slideshow_timer").value	= '-';
	slideshow_run();
}

function slideshow_run() {
	var slideshow_sec	= document.getElementById("slideshow_timer").value
	var slideshow_stop_button	= document.getElementById("slideshow_stop_button")

	if (slideshow_sec == '-') {
		slideshow_stop_button.style.visibility	= 'hidden';
		if (slideshow_timeout != '') {clearTimeout(slideshow_timeout); slideshow_timeout = '';}
	} else {
		slideshow_stop_button.style.visibility = 'visible';
		if (slideshow_timeout != '') {clearTimeout(slideshow_timeout);}
		slideshow_timeout = setTimeout('slideshow_redirect(' + slideshow_sec + ')', Number(slideshow_sec) * 1000);
	}
}

function slideshow_redirect(slideshow_sec) {
	window.location = document.getElementById("slideshow_next_link").value + '&slideshow_timer=' + slideshow_sec
}
