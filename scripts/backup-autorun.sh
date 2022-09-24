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
CONFIG_STANDARDS="${WORKING_DIR}/config-standards.cfg"
source "$CONFIG"

# Check and complete config.cfg
dos2unix "$CONFIG"

#load language library
. "${WORKING_DIR}/lib-language.sh"

# delete IP_MAIL_SENT_MARKERFILE
IP_MAIL_SENT_MARKERFILE="${WORKING_DIR}/tmp/ip-sent.txt"
sudo rm "${IP_MAIL_SENT_MARKERFILE}"

echo "" | sudo tee "${FILE_OLED_OLD}"
echo "Little Backup Box" | sudo tee "${const_LOGFILE}"
echo "" | sudo tee "${const_LOGFILE_SYNC}"

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

# wipe lockfile
sudo rm "$const_CMD_RUNNER_LOCKFILE" > /dev/null 2>&1

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"


# Hello
lcd_message "IMAGE:${WORKING_DIR}/little-backup-box.bmp:Little" "Backup" "Box"
sleep 1

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
		lcd_message "IP ($(l 'box_cronip_online')):" "${IP}"
	else
		lcd_message "IP ($(l 'box_cronip_offline')):" "${IP}"
	fi
fi


# Default backup

RUN_SECONDARY_BACKUP="false"
if [ "${conf_BACKUP_DEFAULT_SOURCE2}" != "none" ] && [ "${conf_BACKUP_DEFAULT_TARGET2}" != "none" ]; then
	RUN_SECONDARY_BACKUP="true"
fi

# default backup 1
if [ "${conf_BACKUP_DEFAULT_SOURCE}" != "none" ] && [ "${conf_BACKUP_DEFAULT_TARGET}" != "none" ]; then
	if [ "${RUN_SECONDARY_BACKUP}" == "true" ]; then
		lcd_message "$(l 'box_backup_primary')"
		sleep 1
	fi

	. "${WORKING_DIR}/backup.sh" "${conf_BACKUP_DEFAULT_SOURCE}" "${conf_BACKUP_DEFAULT_TARGET}" "${RUN_SECONDARY_BACKUP}"
fi

# default-backup 2
if [ "${RUN_SECONDARY_BACKUP}" == "true" ]; then
	lcd_message "$(l 'box_backup_secondary')"
	sleep 1

	. "${WORKING_DIR}/backup.sh" "${conf_BACKUP_DEFAULT_SOURCE2}" "${conf_BACKUP_DEFAULT_TARGET2}"
fi

