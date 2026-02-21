// refresh_iframe.js
function startIframeRefresh(iframeId, src, intervalMs) {
	var iframe = document.getElementById(iframeId);
	var KEY = "iframe_scroll_" + iframeId;

	function getDoc() {
		return iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document) || null;
	}

	function getScrollEl(doc) {
		return doc ? (doc.scrollingElement || doc.documentElement || doc.body) : null;
	}

	function saveScroll() {
		try {
			var doc = getDoc();
			var se = getScrollEl(doc);
			if (!se) return;
			sessionStorage.setItem(KEY, String(se.scrollTop || 0));
		} catch (e) {
		}
	}

	function restoreScrollThenShow() {
		var safetyTimer = setTimeout(function () {
			iframe.style.opacity = "1";
		}, 1000);

		try {
			var raw = sessionStorage.getItem(KEY);
			if (raw == null) {
				iframe.style.opacity = "1";
				clearTimeout(safetyTimer);
				return;
			}

			var target = parseInt(raw, 10) || 0;
			var tries = 0;
			var maxTries = 30;

			function attempt() {
				tries++;

				try {
					var doc = getDoc();
					var se = getScrollEl(doc);
					var w = iframe.contentWindow;

					if (!doc || !se || !w) {
						if (tries >= maxTries) {
							iframe.style.opacity = "1";
							clearTimeout(safetyTimer);
							return;
						}
						requestAnimationFrame(attempt);
						return;
					}

					var maxY = Math.max(0, se.scrollHeight - w.innerHeight);
					var y = Math.min(target, maxY);

					se.scrollTop = y;

					var diff = Math.abs((se.scrollTop || 0) - y);
					if (diff <= 2 || tries >= maxTries) {
						iframe.style.opacity = "1";
						clearTimeout(safetyTimer);
						return;
					}

					requestAnimationFrame(attempt);
				} catch (e) {
					iframe.style.opacity = "1";
					clearTimeout(safetyTimer);
				}
			}

			requestAnimationFrame(attempt);
		} catch (e) {
			iframe.style.opacity = "1";
			clearTimeout(safetyTimer);
		}
	}

	function bindScrollListener() {
		try {
			var w = iframe.contentWindow;
			if (!w) return;

			var last = 0;
			w.addEventListener("scroll", function () {
				var now = Date.now();
				if (now - last < 150) return;
				last = now;
				saveScroll();
			}, { passive: true });

			w.addEventListener("pagehide", saveScroll);
		} catch (e) {}
	}

	iframe.addEventListener("load", function () {
		bindScrollListener();
		restoreScrollThenShow();
	});

	function refresh() {
		saveScroll();

		iframe.style.opacity = "0";

		iframe.src = src + "?t=" + Date.now();
	}

	refresh();

	return setInterval(refresh, intervalMs);
}
