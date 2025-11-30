// refreshDisplay
// Refresh the display iframe every 2 seconds
// needs:
// in <head>: <script type="text/javascript" src="js/display.js"></script>
// '<body onload="refreshDisplay('true/false')">'
// and '<iframe id="display" ...'

let DisplayInterval = 'undefined';

function refreshDisplay() {
	var display = document.getElementById("display");

	// Get the original src without query parameters
	let originalSrc = display.src.split('?')[0];

	// Append a timestamp to bust the cache
	display.src = originalSrc + '?t=' + new Date().getTime();

	if (DisplayInterval === 'undefined') {
		// Schedule next refresh
		setTimeout(refreshDisplay, 2000);
	}
}

