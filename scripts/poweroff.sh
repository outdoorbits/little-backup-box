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
dos2unix "$CONFIG"
source "$CONFIG"

# Configuration
FILE_OLED_OLD="${WORKING_DIR}/tmp/oled_old.txt"
FILE_LOG="${WORKING_DIR}/tmp/little-backup-box.log"
FSCK_LOG="${WORKING_DIR}/tmp/fsck.log"
IP_MAIL_SENT_MARKERFILE="${WORKING_DIR}/tmp/ip-sent.txt"


# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

#Arguments
ACTION="${1}"
FORCE="${2}"

# Power off
if [ "$POWER_OFF" = "true" ] || [ "${FORCE}" = "force" ]; then
    # umount
    sudo umount "${STORAGE_MOUNT_POINT}"
    sudo umount "${SOURCE_MOUNT_POINT}"

    if [ "${FORCE}" = "force" ]; then
        if [ "${ACTION}" = "poweroff" ]; then
            lcd_message "+Power off." "+Do not unplug" "+while the ACT" "+LED is on. Bye!"
        elif [ "${ACTION}" = "reboot" ]; then
            lcd_message "+Rebooting..." "+Do not unplug!" "" ""
        fi
    else
        lcd_message "+Backup complete." "+Do not unplug" "+while the ACT" "+LED is on. Bye!"
    fi

    sudo echo "" >"${FILE_OLED_OLD}"
    sudo echo "" >"${FILE_LOG}"
    sudo echo "" >"${FSCK_LOG}"
    sudo rm "${IP_MAIL_SENT_MARKERFILE}"

    if [ "${ACTION}" = "poweroff" ]; then
        sudo poweroff
    elif [ "${ACTION}" = "reboot" ]; then
        sudo reboot
    fi

else
    # notify that the backup is complete
    lcd_message "+Backup complete." "-Do not unplug!" "+Power down via" "+web UI"
fi
