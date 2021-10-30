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

# Display IP
IP=$(hostname -I | cut -d' ' -f1)

until [ ! -z "$IP" ]; do
sleep 1
IP=$(hostname -I | cut -d' ' -f1)
done

lcd_message "LittleBackupBox" "${IP}"

# Start default-backup
if [ "${BACKUP_DEFAULT_SOURCE}" != "none" ] && [ "${BACKUP_DEFAULT_TARGET}" != "none" ]; then
	. "${WORKING_DIR}/backup.sh" "${BACKUP_DEFAULT_SOURCE}" "${BACKUP_DEFAULT_TARGET}"
fi

