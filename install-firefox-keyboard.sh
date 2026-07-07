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

ADDON_SLUG="on-screen-keyboard"
ADDON_XPI_URL="https://addons.mozilla.org/firefox/downloads/latest/${ADDON_SLUG}/latest.xpi"

ADDON_DIR="/opt/little-backup-box/firefox-addons"
XPI_FILE="${ADDON_DIR}/on-screen-keyboard.xpi"
ID_FILE="${ADDON_DIR}/on-screen-keyboard.id"

POLICY_DIR="/etc/firefox/policies"
POLICY_FILE="${POLICY_DIR}/policies.json"

ensure_tools() {
	sudo apt-get update
	sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
			curl \
			jq \
			unzip \
			ca-certificates
}

download_addon() {
	sudo install -d -m 0755 "${ADDON_DIR}"

	tmp="$(mktemp)"
	curl -fsSL "${ADDON_XPI_URL}" -o "${tmp}"
	sudo install -m 0644 "${tmp}" "${XPI_FILE}"
	rm -f "${tmp}"

	addon_id="$(
		unzip -p "${XPI_FILE}" manifest.json \
		| jq -r '.browser_specific_settings.gecko.id // .applications.gecko.id // empty'
	)"

	if [[ -z "${addon_id}" ]]; then
		echo "Could not determine Firefox add-on ID from manifest.json." >&2
		echo "Install once manually and check about:support, or inspect the XPI metadata." >&2
		exit 1
	fi

	printf '%s\n' "${addon_id}" | sudo tee "${ID_FILE}" >/dev/null
	sudo chmod 0644 "${ID_FILE}"

	echo "Downloaded add-on:"
	echo "  XPI: ${XPI_FILE}"
	echo "  ID : ${addon_id}"
}

ensure_policy_file() {
	sudo install -d -m 0755 "${POLICY_DIR}"

	if [[ ! -s "${POLICY_FILE}" ]]; then
		printf '{ "policies": {} }\n' | sudo tee "${POLICY_FILE}" >/dev/null
		sudo chmod 0644 "${POLICY_FILE}"
	fi

	if ! jq empty "${POLICY_FILE}" >/dev/null 2>&1; then
		echo "Invalid JSON in ${POLICY_FILE}" >&2
		exit 1
	fi
}

addon_id() {
	if [[ ! -s "${ID_FILE}" ]]; then
		echo "Missing ${ID_FILE}. Run: $0 install" >&2
		exit 1
	fi
	cat "${ID_FILE}"
}

enable_addon() {
	ensure_policy_file

	id="$(addon_id)"
	file_url="file://${XPI_FILE}"

	tmp="$(mktemp)"
	jq \
		--arg id "${id}" \
		--arg url "${file_url}" \
		'
		.policies.ExtensionSettings =
		((.policies.ExtensionSettings // {}) + {
			($id): {
			"installation_mode": "force_installed",
			"install_url": $url,
			"updates_disabled": true
			}
		})
		' "${POLICY_FILE}" > "${tmp}"

	sudo install -m 0644 "${tmp}" "${POLICY_FILE}"
	rm -f "${tmp}"

	echo "On-screen keyboard enabled by Firefox policy."
	echo "Restart Firefox for the change to take effect."
}

disable_addon() {
	ensure_policy_file

	id="$(addon_id)"

	tmp="$(mktemp)"
	jq \
		--arg id "${id}" \
		'
		.policies.ExtensionSettings =
		((.policies.ExtensionSettings // {}) + {
			($id): {
			"installation_mode": "blocked"
			}
		})
		' "${POLICY_FILE}" > "${tmp}"

	sudo install -m 0644 "${tmp}" "${POLICY_FILE}"
	rm -f "${tmp}"

	echo "On-screen keyboard blocked/disabled by Firefox policy."
	echo "Restart Firefox for the change to take effect."
}

restart_firefox_for_lbb_desktop() {
	# Falls Firefox durch Autostart/systemd wieder gestartet wird, reicht pkill.
	sudo pkill -u lbb-desktop -x firefox-esr 2>/dev/null || true
	sudo pkill -u lbb-desktop -x firefox 2>/dev/null || true
}

case "${1:-}" in
	install)
		ensure_tools
		download_addon
		;;
	enable)
		enable_addon
		;;
	disable)
		disable_addon
		;;
	restart)
		restart_firefox_for_lbb_desktop
		;;
	*)
		echo "Usage: $0 {install|enable|disable|restart}" >&2
		exit 1
		;;
esac
