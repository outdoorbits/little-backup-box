// refreshLogMonitor
// Refresh the log monitor iframe every 2 seconds
// needs '<body onload="refreshLogMonitor()">'
// and '<iframe id="logmonitor" ...'

let i = 'undefined'

function refreshLogMonitor() {
	var logmonitor = document.getElementById("logmonitor");

	if (!(logmonitor === document.activeElement)) {
		logmonitor.contentWindow.location.reload();

		if (i == 'undefined') {
			i = setInterval('logmonitor.contentWindow.scrollTo(0, 999999)',200);
		}
	} else {
		clearIntervalLogMonitor();
	}

	var t = setTimeout(refreshLogMonitor, 2000);
}

function clearIntervalLogMonitor() {
	if (i !== 'undefined') {
		clearInterval(i);
		i = 'undefined'
	}
}
