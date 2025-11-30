// refreshLogMonitor
// Refresh the log monitor iframe every 2 seconds
// needs:
// in <head>: <script type="text/javascript" src="js/logmonitor.js"></script>
// '<body onload="refreshLogMonitor('true/false')">'
// and '<iframe id="logmonitor" ...'

let LogMonitorInterval = 'undefined';

function refreshLogMonitor() {
	var logmonitor = document.getElementById("logmonitor");

	if (!(logmonitor === document.activeElement)) {

		logmonitor.contentWindow.location.reload();

		if (LogMonitorInterval === 'undefined') {
			LogMonitorInterval = setInterval('logmonitor.contentWindow.scrollTo(0, 999999)',200);
		}

	} else {
		clearIntervalLogMonitor();
	}

	var t = setTimeout(refreshLogMonitor, 2000);
}

function clearIntervalLogMonitor() {
	if (LogMonitorInterval !== 'undefined') {
		clearInterval(LogMonitorInterval);
		LogMonitorInterval = 'undefined'
	}
}
