#!/usr/bin/env python3

# Author: Stefan Saam, github@saams.de

#######################################################################
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program. If not, see <http://www.gnu.org/licenses/>.
#######################################################################

"""
# start server
sudo python3 /var/www/little-backup-box/touch_cal_web.py --device /dev/input/event5 --width 480 --height 320 --port 8088 &

# start firefox
TARGET_USER=lbb-desktop
TARGET_UID=$(id -u "$TARGET_USER")

sudo -iu "$TARGET_USER" env \
		XDG_RUNTIME_DIR="/run/user/$TARGET_UID" \
		WAYLAND_DISPLAY=wayland-0 \
		MOZ_ENABLE_WAYLAND=1 \
		firefox-esr --kiosk --private-window \
		--profile "/home/$TARGET_USER/.mozilla/firefox-esr/kiosk.default" \
		"http://localhost:8088"
"""

import argparse
import threading
import time
from dataclasses import dataclass, field

from evdev import InputDevice, ecodes
from flask import Flask, jsonify

import lib_setup
setup	= lib_setup.setup()

app		= Flask(__name__)

@dataclass
class State:
	device_path: str
	width: int
	height: int
	points: list = field(default_factory=list) # collected raw (x, y)
	step: int = 0
	done: bool = False
	error: str | None	= None
	last_raw: tuple[int, int] | None = None
	abs_x_min: int | None = None
	abs_x_max: int | None = None
	abs_y_min: int | None = None
	abs_y_max: int | None = None
	reading: bool = False

STATE: State

TARGETS	= [
	("top-left", 20, 20),
	("top-right", -20, 20),
	("bottom-left", 20, -20),
	("bottom-right",-20, -20),
]

def norm_targets(width, height):
	# Target positions in pixels
	def tx(xoff): return xoff if xoff >= 0 else width + xoff
	def ty(yoff): return yoff if yoff >= 0 else height + yoff
	return [(name, tx(x), ty(y)) for name, x, y in TARGETS]

def _norm(v: float, vmin: int, vmax: int) -> float:
	# Normalize absolute coordinates into [0..1] using device ABS ranges
	den	= float(max(1, vmax - vmin))
	return (float(v) - float(vmin)) / den

def compute_matrix_from_points_norm(points_norm):
	"""
	Compute libinput calibration matrix from normalized (0..1) points.

	Point order:
	 0: top-left, 1: top-right, 2: bottom-left, 3: bottom-right

	Axis inversion is handled implicitly via the sign of dx/dy (directed differences).
	"""
	if len(points_norm) < 4:
		raise ValueError("Need 4 calibration points")

	(tlx, tly)	= points_norm[0]
	(trx, try_)	= points_norm[1]
	(blx, bly)	= points_norm[2]
	(brx, bry)	= points_norm[3]

	# Use midpoints to reduce noise
	x_left		= (tlx + blx) / 2.0
	x_right		= (trx + brx) / 2.0
	y_top		= (tly + try_) / 2.0
	y_bottom	= (bly + bry) / 2.0

	dx			= x_right - x_left
	dy			= y_bottom - y_top

	if abs(dx) < 1e-9 or abs(dy) < 1e-9:
		raise ValueError(f"Degenerate calibration (dx={dx}, dy={dy})")

	# Map effective rectangle -> full [0..1]:
	# x'	= (x - x_left) / dx and y'	= (y - y_top) / dy
	sx		= 1.0 / dx
	tx		= -x_left * sx
	sy		= 1.0 / dy
	ty		= -y_top * sy

	return [
		sx, 0.0, tx,
		0.0, sy, ty,
		0.0, 0.0, 1.0
	]

def reader_thread():
	try:
		dev	= InputDevice(STATE.device_path)
	except Exception as e:
		STATE.error	= f"Cannot open {STATE.device_path}: {e}"
		return

	# Read ABS ranges once (required to compute a correct libinput matrix)
	try:
		ax	= dev.absinfo(ecodes.ABS_X)
		ay	= dev.absinfo(ecodes.ABS_Y)
		if ax is None or ay is None:
			raise RuntimeError("ABS_X/ABS_Y absinfo not available")
		STATE.abs_x_min, STATE.abs_x_max	= int(ax.min), int(ax.max)
		STATE.abs_y_min, STATE.abs_y_max	= int(ay.min), int(ay.max)
	except Exception as e:
		STATE.error	= f"Cannot read ABS ranges: {e}"
		return

	STATE.reading	= True
	touch_down		= False
	x			= y = None
	need_fresh_xy	= False

	# Collect one point per target
	for step in range(len(TARGETS)):
		if STATE.error:
			break

		STATE.step		= step
		touch_down		= False
		x				= y = None
		need_fresh_xy	= False

		# Wait until we see touch press and fresh coordinates
		while True:
			if STATE.error:
				break

			ev	= dev.read_one()
			if ev is None:
				time.sleep(0.005)
				continue

			if ev.type == ecodes.EV_ABS:
				if ev.code == ecodes.ABS_X:
					x	= int(ev.value)
					if touch_down and x is not None and y is not None:
						need_fresh_xy	= False
				elif ev.code == ecodes.ABS_Y:
					y	= int(ev.value)
					if touch_down and x is not None and y is not None:
						need_fresh_xy	= False

			elif ev.type == ecodes.EV_KEY and ev.code == ecodes.BTN_TOUCH:
				if ev.value == 1: # press
					touch_down	= True
					# Discard any stale coordinates from a previous touch
					x	= None
					y	= None
					need_fresh_xy	= True
				elif ev.value == 0: # release
					touch_down	= False

			# Accept the point only after we have fresh ABS_X and ABS_Y following the press
			if touch_down and (not need_fresh_xy) and x is not None and y is not None:
				STATE.last_raw	= (x, y)
				STATE.points.append((x, y))
				break

	STATE.done		= True
	STATE.reading	= False

@app.get("/")
def index():
	return """
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Touch Calibration</title>
<style>
html, body { margin:0; height:100%; background:#000; color:#fff; font-family:sans-serif; }
#info { position:fixed; top:0; left:0; right:0; padding:10px; background:rgba(0,0,0,0.6); }
#target {
 position: fixed;
 width: 60px;
 height: 60px;
 border: 6px solid #0f0;
 border-radius: 50%;
 transform: translate(-50%,-50%);
}
#target::before {
 content: "";
 position: absolute;
 left: 50%;
 top: 6px;
 width: 2px;
 height: calc(100% - 12px);
 background: #0f0;
 transform: translateX(-50%);
}
#target::after {
 content: "";
 position: absolute;
 top: 50%;
 left: 6px;
 height: 2px;
 width: calc(100% - 12px);
 background: #0f0;
 transform: translateY(-50%);
}
#hint { font-size:14px; opacity:0.9; }
pre { white-space:pre-wrap; }
</style>
</head>
<body>
<div id="info">
<div><b>Touch Calibration</b></div>
<div id="hint">Loading status…</div>
<pre id="result"></pre>
</div>
<div id="target" hidden></div>
<script>
async function poll(){
const r = await fetch('/status');
const s = await r.json();
const hint = document.getElementById('hint');
const tgt = document.getElementById('target');
const res = document.getElementById('result');

if(s.error){
	hint.textContent = "Error: " + s.error;
	tgt.hidden = true;
	return;
}
if(!s.started){
	hint.textContent = "Backend running, waiting for reader…";
	tgt.hidden = true;
} else if(!s.done){
	hint.textContent = "Touch the green circle: " + s.target_name + " (raw: " + (s.last_raw||"-") + ")";
	tgt.hidden = false;
	tgt.style.left = s.target_x + "px";
	tgt.style.top = s.target_y + "px";
} else {
	hint.textContent = "Done. Results below.";
	tgt.hidden = true;
	res.textContent = s.output || "";
}
setTimeout(poll, 250);
}
poll();
</script>
</body>
</html>
"""

@app.get("/status")
def status():
	targets	= norm_targets(STATE.width, STATE.height)
	started	= STATE.reading or STATE.done
	tgt_name, tx, ty	= targets[min(STATE.step, len(targets) - 1)]
	out	= None

	if STATE.done and not STATE.error:
		out	= build_output()
		if out:
			matrix_line	= out.split("\n")[0]
			vals	= matrix_line.split("=")[1].split(" ")

			try:
				_	= [float(v) for v in vals]
				all_numeric	= True
			except Exception:
				all_numeric	= False

			if len(vals) == 9 and all_numeric:
				matrix_x	= " ".join(vals[0:3])
				matrix_y	= " ".join(vals[3:6])

				setup.set_val("conf_TOUCH_MATRIX_X", matrix_x)
				setup.set_val("conf_TOUCH_MATRIX_Y", matrix_y)
				setup.rewrite_configfile()

	return jsonify({
		"started": started,
		"done": STATE.done,
		"error": STATE.error,
		"step": STATE.step,
		"target_name": tgt_name,
		"target_x": tx,
		"target_y": ty,
		"last_raw": f"{STATE.last_raw[0]}/{STATE.last_raw[1]}" if STATE.last_raw else None,
		"output": out
	})

def build_output():
	if len(STATE.points) < 4:
		return None
	if None in (STATE.abs_x_min, STATE.abs_x_max, STATE.abs_y_min, STATE.abs_y_max):
		return f"Error: ABS ranges missing.\nCollected raw points: {STATE.points}\n"

	# Normalize raw points into [0..1] using real device ABS ranges (libinput uses this space)
	points_norm	= [
		(
			_norm(x, STATE.abs_x_min, STATE.abs_x_max),
			_norm(y, STATE.abs_y_min, STATE.abs_y_max),
		)
		for (x, y) in STATE.points[:4]
	]

	m	= compute_matrix_from_points_norm(points_norm)
	m_str	= " ".join(f"{v:.6f}" for v in m)

	return (
		f"LIBINPUT_CALIBRATION_MATRIX={m_str}\n"
		f"Collected raw points: {STATE.points}\n"
		f"ABS ranges: X {STATE.abs_x_min}..{STATE.abs_x_max}, Y {STATE.abs_y_min}..{STATE.abs_y_max}\n"
		f"Collected normalized points: {[(round(px,6), round(py,6)) for (px,py) in points_norm]}\n"
	)

if __name__ == "__main__":
	p	= argparse.ArgumentParser()
	p.add_argument("--device", default="/dev/input/event5")
	p.add_argument("--width", type=int, default=480)
	p.add_argument("--height", type=int, default=320)
	p.add_argument("--listen", default="0.0.0.0")
	p.add_argument("--port", type=int, default=8088)

	args	= p.parse_args()

	STATE	= State(device_path=args.device, width=args.width, height=args.height)

	t	= threading.Thread(target=reader_thread, daemon=True)
	t.start()

	# Run web server
	app.run(host=args.listen, port=args.port, debug=False)
