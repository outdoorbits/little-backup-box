#!/usr/bin/env bash

# Author: Stefan Saam, github@saams.de
# Original author: Dmitri Popov, dmpop@linux.com

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

ADDON_ID="kiosk-keyboard@little-backup-box"
ADDON_NAME="LBB Kiosk Keyboard"
ADDON_VERSION="1.0.0"

BASE_DIR="/opt/little-backup-box/firefox-keyboard"
SRC_DIR="${BASE_DIR}/extension"
XPI_FILE="${BASE_DIR}/lbb-kiosk-keyboard.xpi"

KIOSKBOARD_VERSION="2.3.0"

VENDOR_DIR="${SRC_DIR}/vendor/kioskboard"
CONTENT_DIR="${SRC_DIR}/content"

require_tools() {
	sudo apt-get update
	sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q \
			curl \
			ca-certificates \
			zip
}

download_with_fallback() {
	local output="$1"
	shift

	local url
	for url in "$@"; do
		if curl -fsSL "$url" -o "$output"; then
			return 0
		fi
	done

	echo "Download failed for ${output}" >&2
	exit 1
}

prepare_dirs() {
	rm -rf "${SRC_DIR}"

	mkdir -p "${VENDOR_DIR}"
	mkdir -p "${CONTENT_DIR}"
}

download_kioskboard() {
	download_with_fallback \
		"${VENDOR_DIR}/kioskboard-aio.min.js" \
		"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/dist/kioskboard-aio-${KIOSKBOARD_VERSION}.min.js" \
		"https://cdn.jsdelivr.net/npm/kioskboard@${KIOSKBOARD_VERSION}/dist/kioskboard-aio-${KIOSKBOARD_VERSION}.min.js" \
		"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/src/all-in-one/kioskboard-aio.js"

	download_with_fallback \
		"${VENDOR_DIR}/kioskboard.min.css" \
		"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/dist/kioskboard-${KIOSKBOARD_VERSION}.min.css" \
		"https://cdn.jsdelivr.net/npm/kioskboard@${KIOSKBOARD_VERSION}/dist/kioskboard-${KIOSKBOARD_VERSION}.min.css" \
		"https://unpkg.com/kioskboard@${KIOSKBOARD_VERSION}/src/kioskboard.css"

	cat > "${VENDOR_DIR}/THIRD_PARTY_LICENSES.txt" <<EOF
KioskBoard ${KIOSKBOARD_VERSION}
https://github.com/furcan/KioskBoard
License: MIT

The KioskBoard package is bundled here only for local/offline use inside
the Little Backup Box Firefox kiosk extension.
EOF
}

write_manifest() {
	cat > "${SRC_DIR}/manifest.json" <<EOF
{
  "manifest_version": 2,
  "name": "${ADDON_NAME}",
  "version": "${ADDON_VERSION}",
  "description": "Injects KioskBoard into external web forms for Little Backup Box kiosk mode.",
  "browser_specific_settings": {
    "gecko": {
      "id": "${ADDON_ID}"
    }
  },
  "permissions": [
    "http://*/*",
    "https://*/*"
  ],
  "content_scripts": [
    {
      "matches": [
        "http://*/*",
        "https://*/*"
      ],
      "js": [
        "vendor/kioskboard/kioskboard-aio.min.js",
        "content/lbb-kioskboard-init.js"
      ],
      "css": [
        "vendor/kioskboard/kioskboard.min.css",
        "content/lbb-kioskboard.css"
      ],
      "run_at": "document_idle",
      "all_frames": true,
      "match_about_blank": true
    }
  ]
}
EOF
}
