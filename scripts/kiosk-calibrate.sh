#!/usr/bin/env bash

# Author: Stefan Saam, github@saams.de

#######################################################################
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#######################################################################

set -euo pipefail

# MUST be started as root
if [[ $EUID -ne 0 ]]; then
	echo "Run this script as root"
exit 1
fi

TARGET_USER="lbb-desktop"
TARGET_UID="$(id -u "$TARGET_USER")"

# Adjust paths/URLs
PROFILE="/home/$TARGET_USER/.mozilla/firefox-esr/kiosk.default"
CAL_URL="http://localhost:8088"
KIOSK_URL="http://localhost:8080"

# Calibration server (Python)
CAL_SERVER="/var/www/little-backup-box/touch_cal_web.py"
CAL_PORT="8088"

# Helper functions
die() { echo "ERROR: $*" >&2; exit 1; }

have_wayland() {
	[[ -S "/run/user/$TARGET_UID/wayland-0" ]]
	}

stop_firefox() {
	echo "Stopping firefox-esr for $TARGET_USER (if running)..."
	# first graceful, then forceful
	sudo -u "$TARGET_USER" pkill -TERM -x firefox-esr 2>/dev/null || true
	sleep 1
	sudo -u "$TARGET_USER" pkill -KILL -x firefox-esr 2>/dev/null || true
	}

start_firefox_kiosk() {
	local url="$1"
	echo "Starting firefox-esr kiosk as $TARGET_USER -> $url"
	sudo -iu "$TARGET_USER" env \
		XDG_RUNTIME_DIR="/run/user/$TARGET_UID" \
			WAYLAND_DISPLAY=wayland-0 \
				MOZ_ENABLE_WAYLAND=1 \
					firefox-esr --kiosk --private-window \
						--profile "$PROFILE" \
							"$url" >/tmp/firefox-kiosk.log 2>&1 &
						}

stop_old_cal_server() {
	echo 4
# 	sudo fuser -k -TERM 8088/tcp
	sleep 1
	echo 5
	}

start_cal_server() {
	echo "Starting calibration server on :$CAL_PORT ..."
	bash -c "
	nohup python3 '$CAL_SERVER' --device /dev/input/event5 --width 480 --height 320 --port $CAL_PORT \
		>/tmp/touch-cal-server.log 2>&1 &
	echo \$! > /tmp/touch-cal-server.pid
	"
	}

stop_cal_server() {
	if [[ -f /tmp/touch-cal-server.pid ]]; then
	local pid
	pid="$(cat /tmp/touch-cal-server.pid || true)"
	if [[ -n "${pid:-}" ]] && kill -0 "$pid" 2>/dev/null; then
	echo "Stopping calibration server (pid $pid)..."
	kill -TERM "$pid" 2>/dev/null || true
	sleep 1
	kill -KILL "$pid" 2>/dev/null || true
	fi
	rm -f /tmp/touch-cal-server.pid
	else
	echo "No calibration server pid file found; trying to stop by port..."
	fuser -k "${CAL_PORT}/tcp" 2>/dev/null || true
	fi
	}

wait_for_http() {
	local url="$1"
	local tries="${2:-60}"
	for _ in $(seq 1 "$tries"); do
	if curl -fsS "$url" >/dev/null 2>&1; then
	return 0
	fi
	sleep 1
	done
	return 1
	}

wait_for_cal_done() {
	echo "Waiting for calibration to complete (polling $CAL_URL/status)..."
	# does not require curl + jq; we match on "done": true
	for _ in $(seq 1 300); do  # max 5 minutes
	if curl -fsS "$CAL_URL/status" | grep -q '"done":[[:space:]]*true'; then
	echo "Calibration marked done."
	return 0
	fi
	sleep 1
	done
	die "Calibration did not complete within the timeout."
	}

main() {
	command -v curl >/dev/null || die "curl is required: sudo apt install curl"
	[[ -f "$CAL_SERVER" ]] || die "Calibration server not found at $CAL_SERVER (set CAL_SERVER path correctly)."
	[[ -d "$PROFILE" ]] || die "Firefox profile dir not found: $PROFILE"

	have_wayland || die "Wayland socket not found: /run/user/$TARGET_UID/wayland-0 (is labwc session running for $TARGET_USER?)"

	stop_firefox

	stop_old_cal_server

	start_cal_server
	wait_for_http "$CAL_URL" 30 || die "Calibration server did not come up on $CAL_URL (see /tmp/touch-cal-server.log)."

	start_firefox_kiosk "$CAL_URL"

	wait_for_cal_done

	python3 /var/www/little-backup-box/create_screen_config.py

	stop_firefox
	stop_cal_server

	start_firefox_kiosk "$KIOSK_URL"
	echo "Done. Logs: /tmp/firefox-kiosk.log and /tmp/touch-cal-server.log"
	}

main "$@"
