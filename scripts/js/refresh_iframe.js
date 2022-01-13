// refreshIFrame
// Refresh the log monitor iframe every 2 seconds
// needs '<body onload="refreshIFrame()">'
// and '<iframe id="logmonitor" ...'
function refreshIFrame() {
	var logmonitor = document.getElementById("logmonitor");
	logmonitor.contentWindow.location.reload();
	setInterval('logmonitor.contentWindow.scrollTo( 0, 999999 )',200)
	var t = setTimeout(refreshIFrame, 2000);
}
