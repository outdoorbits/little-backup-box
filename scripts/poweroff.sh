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
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
dos2unix "$CONFIG"
source "$CONFIG"

# Configuration
FILE_OLED_OLD="${WORKING_DIR}/tmp/oled_old.txt"
IP_MAIL_SENT_MARKERFILE="${WORKING_DIR}/tmp/ip-sent.txt"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

#Arguments
ACTION="${1}"
FORCE="${2}"
MESSAGE="${3}"

# Power off
if [ "$conf_POWER_OFF" = "true" ] || [ "${FORCE}" = "force" ]; then
    # umount
    sudo umount "${const_STORAGE_MOUNT_POINT}"
    sudo umount "${const_SOURCE_MOUNT_POINT}"
    sudo umount "${const_CLOUD_MOUNT_POINT="/media/cloud"}"
	sudo umount "${const_IOS_MOUNT_POINT="/media/iOS"}"

    if [ "${ACTION}" = "poweroff" ]; then
        if [ -z "${MESSAGE}" ]; then
            lcd_message "+Power off." "+Do not unplug" "+while the ACT" "+LED is on. Bye!"
        else
            lcd_message "+${MESSAGE}" "+Do not unplug" "+while the ACT" "+LED is on. Bye!"
        fi
    elif [ "${ACTION}" = "reboot" ]; then
        if [ -z "${MESSAGE}" ]; then
            lcd_message "+Rebooting..." "+Do not unplug!" "+" "+"
        else
            lcd_message "+${MESSAGE}" "+Rebooting..." "+Do not unplug!" "+"
        fi
    fi

    # cleanup
    echo "" | sudo tee "${FILE_OLED_OLD}"
    echo "" | sudo tee "${const_LOGFILE}"
    sudo rm "${IP_MAIL_SENT_MARKERFILE}" > /dev/null 2>&1

    if [ "${ACTION}" = "poweroff" ]; then
        sudo poweroff
    elif [ "${ACTION}" = "reboot" ]; then
        sudo reboot
    fi

else
    # notify the backup status
    if [ -z "${MESSAGE}" ]; then
            lcd_message "+Backup complete." "+Do not unplug!" "+Power down via" "+web UI"
        else
            lcd_message "+${MESSAGE}" "+Do not unplug!" "+Power down via" "+web UI"
        fi
fi
