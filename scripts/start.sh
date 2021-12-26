#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com

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
CONFIG="${WORKING_DIR}/config.cfg"
CONFIG_STANDARDS="${WORKING_DIR}/config-standards.cfg"

# Check and complete config.cfg
dos2unix "$CONFIG"

# delete IP_MAIL_SENT_MARKERFILE
IP_MAIL_SENT_MARKERFILE="${WORKING_DIR}/tmp/ip-sent.txt"
sudo rm "${IP_MAIL_SENT_MARKERFILE}"

# add missing config-parameters to config-file
while read CONFIG_STANDARD;
do
	IFS="="
	set -- $CONFIG_STANDARD
	VAR=$1
	VAL=$2

	if [ ! -z "${VAR}" ]; then
		if ! grep -q "${VAR}" "${CONFIG}"; then
			echo "${VAR}=${VAL}" >> "${CONFIG}"
		fi
	fi
done < "${CONFIG_STANDARDS}"

unset IFS

# Load config.cfg
source "$CONFIG"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"


# Hello
lcd_message "LittleBackupBox" "" "" ""

# Display IP
IP=$(hostname -I | cut -d' ' -f1)

until [ ! -z "$IP" ]; do
	sleep 1
	IP=$(hostname -I | cut -d' ' -f1)
done

ping -c1 google.com &>/dev/null
INTERNET_DISCONNECTED=$?

if ! grep -q "${IP}" "${FILE_OLED_OLD}"; then
	if [ "${INTERNET_DISCONNECTED}" = "0" ]; then
		lcd_message "IP (internet):" "${IP}"
	else
		lcd_message "IP (offline):" "${IP}"
	fi
fi

# Start default-backup
if [ "${conf_BACKUP_DEFAULT_SOURCE}" != "none" ] && [ "${conf_BACKUP_DEFAULT_TARGET}" != "none" ]; then
	. "${WORKING_DIR}/backup.sh" "${conf_BACKUP_DEFAULT_SOURCE}" "${conf_BACKUP_DEFAULT_TARGET}"
fi

