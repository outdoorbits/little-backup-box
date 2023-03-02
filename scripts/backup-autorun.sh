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
source "${CONFIG}"

# load language library
. "${WORKING_DIR}/lib-language.sh"

# delete IP_MAIL_SENT_MARKERFILE
IP_MAIL_SENT_MARKERFILE="${WORKING_DIR}/tmp/ip-sent.txt"
sudo rm "${IP_MAIL_SENT_MARKERFILE}"

# delete const_DISPLAY_CONTENT_FILE
sudo rm "${const_DISPLAY_CONTENT_FILE}"

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

sudo chown www-data:www-data "${CONFIG}"
sudo chmod 775 "${CONFIG}"

unset IFS

#rewrite config to new standards
if [ "${conf_BACKUP_DEFAULT_SOURCE}" = "storage" ]; then echo "conf_BACKUP_DEFAULT_SOURCE='usb'" | sudo tee -a "${CONFIG}"; fi
if [ "${conf_BACKUP_DEFAULT_TARGET}" = "external" ]; then echo "conf_BACKUP_DEFAULT_TARGET='usb'" | sudo tee -a "${CONFIG}"; fi
if [ "${conf_BACKUP_DEFAULT_SOURCE2}" = "storage" ]; then echo "conf_BACKUP_DEFAULT_SOURCE2='usb'" | sudo tee -a "${CONFIG}"; fi
if [ "${conf_BACKUP_DEFAULT_TARGET2}" = "external" ]; then echo "conf_BACKUP_DEFAULT_TARGET2='usb'" | sudo tee -a "${CONFIG}"; fi
if [ ! -z "${conf_MAIL_conf_PASSWORD}" ]; then echo "conf_MAIL_PASSWORD='${conf_MAIL_conf_PASSWORD}'" | sudo tee -a "${CONFIG}"; fi
if [ ! -z "${conf_RSYNC_conf_PASSWORD}" ]; then echo "conf_RSYNC_PASSWORD='${conf_RSYNC_conf_PASSWORD}'" | sudo tee -a "${CONFIG}"; fi

#rewrite config to new names
if [ -z "${conf_MAIL_FROM}" ]; then echo "conf_MAIL_FROM='${conf_MAIL_USER}'" | sudo tee -a "${CONFIG}"; fi
if [ -z "${conf_DISP_IP_REPEAT}" ]; then echo "conf_DISP_IP_REPEAT=${conf_conf_DISP_IP_REPEAT}" | sudo tee -a "${CONFIG}"; fi
if [ -z "${conf_MAIL_NOTIFICATIONS}" ]; then echo "conf_MAIL_NOTIFICATIONS=${conf_NOTIFY}" | sudo tee -a "${CONFIG}"; fi

# Load config.cfg
source "$CONFIG"

# wipe lockfile cmd-runner
sudo rm "${const_CMD_RUNNER_LOCKFILE}" > /dev/null 2>&1

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

# Hello
lcd_message "IMAGE:${WORKING_DIR}/little-backup-box.bmp"
sleep 1

# Display IP
source "${WORKING_DIR}/cron-ip.sh" "force_display" &

# Default backup
SECONDARY_BACKUP_FOLLOWS="false"
if [ "${conf_BACKUP_DEFAULT_SOURCE2}" != "none" ] && [ "${conf_BACKUP_DEFAULT_TARGET2}" != "none" ]; then
	SECONDARY_BACKUP_FOLLOWS="true"
fi

## default backup 1
if [ "${conf_BACKUP_DEFAULT_SOURCE}" != "none" ] && [ "${conf_BACKUP_DEFAULT_TARGET}" != "none" ]; then
	if [ "${SECONDARY_BACKUP_FOLLOWS}" == "true" ]; then
		lcd_message "$(l 'box_backup_primary')"
		sleep 1
	fi

	. "${WORKING_DIR}/backup.sh" "${conf_BACKUP_DEFAULT_SOURCE}" "${conf_BACKUP_DEFAULT_TARGET}" '' '' '' "${SECONDARY_BACKUP_FOLLOWS}"
fi

## default-backup 2
if [ "${SECONDARY_BACKUP_FOLLOWS}" == "true" ]; then
	lcd_message "$(l 'box_backup_secondary')"
	sleep 1

	. "${WORKING_DIR}/backup.sh" "${conf_BACKUP_DEFAULT_SOURCE2}" "${conf_BACKUP_DEFAULT_TARGET2}"
fi

