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
FILE_OLED_OLD="${WORKING_DIR}/tmp/oled_old.txt"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load Mail library
. "${WORKING_DIR}/lib-mail.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

#load language library
. "${WORKING_DIR}/lib-language.sh"

# argruments
FORCE_DISPLAY=${1}

ping -c1 google.com &>/dev/null
INTERNET_DISCONNECTED=$?

IP=$(hostname -I | cut -d' ' -f1)

if [ $conf_conf_DISP_IP_REPEAT = true ] || [ ! -z "${FORCE_DISPLAY}" ]; then
	if ! grep -q "${IP}" "${FILE_OLED_OLD}"; then
		if [ "${INTERNET_DISCONNECTED}" = "0" ]; then
			lcd_message "IP ($(l 'box_cronip_online')):" "${IP}"
		else
			lcd_message "IP ($(l 'box_cronip_offline')):" "${IP}"
		fi
	fi
fi

TRIES_MAX=5
TRIES_DONE=0
while [[ "${TRIES_MAX}" -gt "${TRIES_DONE}" ]] && [[ "${INTERNET_DISCONNECTED}" != "0" ]]; do
	sleep 2
	ping -c1 google.com &>/dev/null
	INTERNET_DISCONNECTED=$?

	TRIES_DONE=$((TRIES_DONE+1))
done

if [ "${INTERNET_DISCONNECTED}" != "0" ]; then
	exit 0
fi

# Internet
IP=$(hostname -I | cut -d' ' -f1)

if [ $conf_DISP = true ] && [ $conf_conf_DISP_IP_REPEAT = true ]; then
	if ! grep -q "${IP}" "${FILE_OLED_OLD}"; then
		lcd_message "IP ($(l 'box_cronip_online')):" "${IP}"
	fi
fi

UPTIME=$(awk '{print int($1)}' /proc/uptime)

# Mail
if [ ! -z $conf_SMTP_SERVER ] && [ ! -f "${IP_MAIL_SENT_MARKERFILE}" ]; then

	touch "${IP_MAIL_SENT_MARKERFILE}"

	MEJIRO_HTTP_PLAIN=""
	MEJIRO_HTTPS_PLAIN=""
	MEJIRO_HTTP_HTML=""
	MEJIRO_HTTPS_HTML=""

	if [ -f "${WORKING_DIR}/../mejiro/index.php" ]; then
		MEJIRO_HTTP_PLAIN="Mejiro: http://${IP}:8000/mejiro\n"
		MEJIRO_HTTPS_PLAIN="Mejiro: https://${IP}/mejiro\n"
		MEJIRO_HTTP_HTML="Mejiro: <a href='http://${IP}:8000/mejiro/'>http://${IP}:8000/mejiro/</a><br>\n"
		MEJIRO_HTTPS_HTML="Mejiro: <a href='https://${IP}/mejiro/'>http://${IP}/mejiro/</a><br>\n"
	fi

	TEXT_PLAIN="
$(l 'box_cronip_mail_description_https'):
$(l 'box_cronip_mail_main'): https://${IP}
$(l 'mainmenue_filebrowser'): https://${IP}/files
${MEJIRO_HTTPS_PLAIN}

$(l 'box_cronip_mail_desription_http'):
$(l 'box_cronip_mail_main'): http://${IP}:8000
$(l 'mainmenue_filebrowser'): http://${IP}:8000/files
${MEJIRO_HTTP_PLAIN}miniDLNA: http://${IP}:8200"

	TEXT_HTML="
<b>$(l 'box_cronip_mail_description_https'):</b><br>
$(l 'box_cronip_mail_main'): <a href='https://${IP}'>https://${IP}</a><br>
$(l 'mainmenue_filebrowser'): <a href='https://${IP}/files'>http://${IP}/files</a><br>
${MEJIRO_HTTPS_HTML}
<br>
<b>$(l 'box_cronip_mail_desription_http'):</b><br>
$(l 'box_cronip_mail_main'): <a href='http://${IP}:8000'>http://${IP}:8000</a><br>
$(l 'mainmenue_filebrowser'): <a href='http://${IP}:8000/files'>http://${IP}:8000/files</a><br>
${MEJIRO_HTTP_HTML}
miniDLNA: <a href='http://${IP}:8200'>http://${IP}:8200</a><br>"



	TEXT_PLAIN="${TEXT_PLAIN}

$(l 'box_cronip_mail_open_samba') 'smb://${IP}'"

		TEXT_HTML="${TEXT_HTML}
<br>
$(l 'box_cronip_mail_open_samba'): '<a href='smb://${IP}'>smb://${IP}</a>'<br>"
	send_email "$(l 'box_cronip_mail_info'): ${IP}" "${TEXT_PLAIN}" "${TEXT_HTML}"
fi
