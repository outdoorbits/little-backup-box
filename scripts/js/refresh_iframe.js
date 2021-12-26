// refreshIFrame
// Refresh the log monitor iframe every 2 seconds
// needs '<body onload="refreshIFrame()">'
// and '<iframe id="logmonitor" ...'
function refreshIFrame() {
	var x = document.getElementById("logmonitor");
	x.contentWindow.location.reload();
	var t = setTimeout(refreshIFrame, 2000);
}
