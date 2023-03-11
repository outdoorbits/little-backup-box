#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de

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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Config
IP_MAIL_SENT_MARKERFILE="${WORKING_DIR}/tmp/ip-sent.txt"

#load time library
. "${WORKING_DIR}/lib-time.sh"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load Mail library
. "${WORKING_DIR}/lib-mail.sh"

# Load DISPLAY library
. "${WORKING_DIR}/lib-display.sh"

#load language library
. "${WORKING_DIR}/lib-language.sh"

#load network library
. "${WORKING_DIR}/lib-network.sh"

# argruments
FORCE_DISPLAY=${1}

#online?
INTERNET_STATUS=$(get_internet_status)

#display online-status and IP
IP=$(get_ip)

#in case of multiple IPs: Print IPs in lines, reversed sequence
IP_ARRAY=(${IP})
IP_LINES=$(printf '%s\n' "${IP_ARRAY[@]}" | tac)

if ([ $conf_DISP_IP_REPEAT = true ]  || [ ! -z "${FORCE_DISPLAY}" ]) &&  [ ! -z "${IP_LINES}" ]; then
	if ! grep -q "${IP}" "${const_DISPLAY_CONTENT_OLD_FILE}"; then
		if [ "${INTERNET_STATUS}" = "connected" ]; then
			disp_message ":IP ($(l 'box_cronip_online')):" ":${IP_LINES}"
		else
			disp_message ":IP ($(l 'box_cronip_offline')):" ":${IP_LINES}"
		fi
	fi
fi

# Mail
if [ ! -z $conf_MAIL_NOTIFICATIONS ] && [ ! -f "${IP_MAIL_SENT_MARKERFILE}" ]; then

	#wait for internet if not connected
	TRIES_MAX=5
	TRIES_DONE=0
	INTERNET_STATUS_NEW=$(get_internet_status)
	while [[ "${TRIES_MAX}" -gt "${TRIES_DONE}" ]] && ([ -z "${IP}" ] || [ "${INTERNET_STATUS_NEW}" != "connected" ]); do
		sleep 2

		IP=$(get_ip)

		INTERNET_STATUS_NEW=$(get_internet_status)

		TRIES_DONE=$((TRIES_DONE+1))
	done

	if [ ! -z "${IP}" ] && [ "${INTERNET_STATUS_NEW}" = "connected" ]; then
		#online!

		IP_NEW=$(get_ip)

		if [ $conf_DISP = true ] && [ $conf_DISP_IP_REPEAT = true ]; then
			if [ "${IP}" != "${IP_NEW}" ] || [ "${INTERNET_STATUS}" != "${INTERNET_STATUS_NEW}" ]; then
				# IP changed
				IP="${IP_NEW}"
				disp_message ":IP ($(l 'box_cronip_online')):" ":${IP_LINES}"
			fi
		fi

		#lockfile
		if [ ! -f "${IP_MAIL_SENT_MARKERFILE}" ]; then
			touch "${IP_MAIL_SENT_MARKERFILE}"
		else
			#Lockfile has obviously been created by another process in the meantime, exit
			exit 0
		fi

		#create mail

		TEXT_PLAIN="
$(l 'box_cronip_mail_description_https'):
$(l 'box_cronip_mail_main'): https://${IP}

$(l 'box_cronip_mail_desription_http'):
$(l 'box_cronip_mail_main'): http://${IP}:8000"

	TEXT_HTML="
<b>$(l 'box_cronip_mail_description_https'):</b><br>
$(l 'box_cronip_mail_main'): <a href='https://${IP}'>https://${IP}</a><br>
<br>
<b>$(l 'box_cronip_mail_desription_http'):</b><br>
$(l 'box_cronip_mail_main'): <a href='http://${IP}:8000'>http://${IP}:8000</a><br>"

		TEXT_PLAIN="${TEXT_PLAIN}

$(l 'box_cronip_mail_open_samba') smb://${IP}"

		TEXT_HTML="${TEXT_HTML}
<br>
$(l 'box_cronip_mail_open_samba'): <a href='smb://${IP}'>smb://${IP}</a>"

		send_email "$(l 'box_cronip_mail_info'): ${IP}" "${TEXT_PLAIN}" "${TEXT_HTML}"
	fi
fi
