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

# Check and complete config.cfg
dos2unix "$CONFIG"

CONFIG_STANDARDS=( \
'STORAGE_DEV_MASK="sd" # Beginning of the name of storage devices'
'STORAGE_MOUNT_POINT="/media/storage" # Mount point of the storage device' \
'SOURCE_MOUNT_POINT="/media/source" # Mount point of the source device' \
'INTERAL_BACKUP_DIR="/media/internal"' \
'IOS_MOUNT_POINT="/media/iOS"' \
'POWER_OFF=false # Set to false to disable automatic power off after backup' \
'LOG=false # Set to true to enable logging' \
'NOTIFY=false # Set to true to enable email notifications' \
'SMTP_SERVER=""   # Mail settings (specify to receive notifications)' \
'SMTP_PORT=""     # If the mail settings are specified, Little Backup Box' \
'MAIL_USER=""     # sends an email with the devices IP address even when' \
'MAIL_PASSWORD="" # $NOTIFY is set to false' \
'MAIL_TO=""' \
'MAIL_HTML=true # Set to false to disable HTML-mails' \
'DISP=false' \
'DISP_IP_REPEAT=false' \
)

for CONFIG_STANDARD in "${CONFIG_STANDARDS[@]}"
do
        IFS="="
        set -- $CONFIG_STANDARD
        VAR=$1
        VAL=$2

    if ! grep -q "${VAR}" "${CONFIG}"; then
        echo "${VAR}=${VAL}" >> "${CONFIG}"
    fi
done

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


