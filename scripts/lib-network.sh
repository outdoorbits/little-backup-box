#!/usr/bin/env bash

# Author: Stefan Saam github@saams.de

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

# requires
# lib-time.sh

function get_ip() {
	hostname -I | awk '{$1=$1};1' # trims the result
}


function get_internet_status() {
	ping -c1 "google.com" &>/dev/null
	local STATUS=$?
	if [ "${STATUS}" == "0" ]; then
		echo "connected"
	else
		echo "disconnected"
	fi
}

function vpn_status() {
	local VPN_TYPE="${1}"
	local VPN_CONFIG_FILE="${2}"  # for WireGuard only

	local VPN_CONFIG_FILE_TRUNK=$(basename "${VPN_CONFIG_FILE}")
	VPN_CONFIG_FILE_TRUNK="${VPN_CONFIG_FILE_TRUNK%.*}"

	local VPN_STATUS="down"

	if [ "${VPN_TYPE}" = "OpenVPN" ]; then
		if [ "$(sudo ip tuntap show)" != "" ]; then VPN_STATUS="up"; fi
	elif [ "${VPN_TYPE}" = "WireGuard" ]; then
		if [[ "$(sudo wg show ${VPN_CONFIG_FILE_TRUNK})" =~ "${VPN_CONFIG_FILE_TRUNK}" ]]; then VPN_STATUS="up"; fi
	fi

	echo "${VPN_STATUS}"
}

function vpn_stop() {

	local VPN_TYPE="${1}"
	local VPN_CONFIG_FILE="${2}"  # for WireGuard only

	local VPN_CONFIG_FILE_TRUNK=$(basename "${VPN_CONFIG_FILE}")
	VPN_CONFIG_FILE_TRUNK="${VPN_CONFIG_FILE_TRUNK%.*}"

	if [ "${VPN_TYPE}" = "OpenVPN" ]; then
		if [ $(vpn_status "${VPN_TYPE}") = "up" ]; then
			sudo killall openvpn
		fi

	elif [ "${VPN_TYPE}" = "WireGuard" ]; then
		if [ $(vpn_status "${VPN_TYPE}" "${VPN_CONFIG_FILE}") = "up" ]; then
			sudo wg-quick down "${VPN_CONFIG_FILE_TRUNK}"
		fi
	fi
}

function vpn_start() {

	local VPN_TYPE="${1}"
	local VPN_CONFIG_FILE="${2}"
	local VPN_TIMEOUT="${3}"

	local VPN_CONFIG_FILE_TRUNK=$(basename "${VPN_CONFIG_FILE}")
	VPN_CONFIG_FILE_TRUNK="${VPN_CONFIG_FILE_TRUNK%.*}"

	local VPN_CONNECTED="0"

	if [ "$(sudo -- bash -c "if [ -f \"${VPN_CONFIG_FILE}\" ]; then echo 'true'; fi")" = "true" ]; then

		# remember IP before VPN-connection
		local IP="$(get_ip)"

		if [ "${VPN_TYPE}" = "OpenVPN" ]; then
			sudo bash -c "openvpn --config '${VPN_CONFIG_FILE}' 2>&1 &"
		elif [ "${VPN_TYPE}" = "WireGuard" ]; then
			sudo wg-quick up "${VPN_CONFIG_FILE}" 2>&1
		fi

		local VPN_START_TIME=$(get_uptime_seconds)
		local VPN_TIMEOUT_TIME=$((${VPN_START_TIME} + ${VPN_TIMEOUT}))

		while [ "${VPN_CONNECTED}" = "0" ] && [[ $(get_uptime_seconds) -lt ${VPN_TIMEOUT_TIME} ]]; do

			if [ "${VPN_TYPE}" = "OpenVPN" ]; then
				if [ "$(vpn_status "${VPN_TYPE}" "${VPN_CONFIG_FILE}")" != "" ] && [ "${IP}" != "$(get_ip)" ]; then VPN_CONNECTED="1"; fi
			elif [ "${VPN_TYPE}" = "WireGuard" ]; then
				if [[ "$(sudo wg show ${VPN_CONFIG_FILE_TRUNK})" =~ "${VPN_CONFIG_FILE_TRUNK}" ]] && [ "${IP}" != "$(get_ip)" ]; then VPN_CONNECTED="1"; fi
			fi

			sleep 1

		done

	fi

	return "${VPN_CONNECTED}"
}
