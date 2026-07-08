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

# expected from calling script
## INSTALLER_DIR
## SETUP

set -euo pipefail

USER="lbb-desktop"
PROFILE_DIR="/home/${USER}/.mozilla/firefox-esr/kiosk.default"
CHROME_DIR="${PROFILE_DIR}/chrome"
USER_JS="${PROFILE_DIR}/user.js"

sudo -u "${USER}"  mkdir -p "${CHROME_DIR}"

# Helper: set or replace a pref in user.js (idempotent)
set_pref() {
	local key="$1"
	local value="$2"
	if [[ -f "${USER_JS}" ]] && grep -qE "user_pref\\(\"${key}\"," "${USER_JS}"; then
		sudo -u "${USER}" sed -i -E "s|user_pref\\(\"${key}\",.*\\);|user_pref(\"${key}\", ${value});|g" "${USER_JS}"
	else
		echo "user_pref(\"${key}\", ${value});" | sudo -u "${USER}" tee -a "${USER_JS}" >/dev/null
	fi
}

# --- Required for profile CSS ---
set_pref "toolkit.legacyUserProfileCustomizations.stylesheets" "true"

# Prefer classic (non-overlay) scrollbars where supported
set_pref "widget.gtk.overlay-scrollbars.enabled" "false"

# Optional but recommended kiosk hardening (ESR-safe)
set_pref "browser.shell.checkDefaultBrowser" "false"
set_pref "browser.startup.homepage_override.mstone" "\"ignore\""
set_pref "browser.aboutwelcome.enabled" "false"
set_pref "browser.firstrun.didSkipDefaultBrowserCheck" "true"
set_pref "browser.firstrun.guide.enabled" "false"
set_pref "datareporting.policy.dataSubmissionEnabled" "false"
set_pref "toolkit.telemetry.enabled" "false"
set_pref "app.shield.optoutstudies.enabled" "false"
set_pref "toolkit.telemetry.enabled" "false"

# set_pref "gfx.webrender.software" "true"
# set_pref "layers.acceleration.disabled" "true"


# --- Scrollbar styling for web content (ESR-compatible) ---
cat <<'EOF' | sudo -u "${USER}" tee "${CHROME_DIR}/userContent.css" >/dev/null
/* Always-visible, very wide scrollbar with red thumb (Firefox ESR) */
:root, * {
  scrollbar-width: auto !important;
  scrollbar-color: #ff0000 #000000 !important;
  /*                ^ thumb   ^ track */
}

/* Width control (geometry only) */
scrollbar:vertical {
  -moz-appearance: none !important;
  width: 52px !important;
  min-width: 52px !important;
}
EOF

# Optional: UI-level scrollbar consistency
cat <<'EOF' | sudo -u "${USER}" tee "${CHROME_DIR}/userChrome.css" >/dev/null
:root {
  scrollbar-width: auto !important;
}
EOF

# install on-screen-keyboard
"$INSTALLER_DIR/install-firefox-keyboard.sh" install

if [[ "${conf_VIRTUAL_KEYBOARD_ENABLED:-}" == [Tt][Rr][Uu][Ee] ]]; then
	"$INSTALLER_DIR/install-firefox-keyboard.sh" enable
else
	"$INSTALLER_DIR/install-firefox-keyboard.sh" disable
fi

"$INSTALLER_DIR/install-firefox-keyboard.sh"

#finished
echo "Firefox ESR kiosk profile configured:"
echo "  Profile : ${PROFILE_DIR}"
