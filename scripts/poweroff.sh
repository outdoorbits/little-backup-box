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

CONFIG_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
CONFIG="${CONFIG_DIR}/config.cfg"
dos2unix "$CONFIG"
source "$CONFIG"

# Configuration
FILE_OLED_OLD="/root/oled_old.txt"
FILE_LOG="/home/pi/little-backup-box/scripts/tmp/little-backup-box.log"
FSCK_LOG="/home/pi/little-backup-box/scripts/tmp/fsck.log"
# Load LCD library
. "${CONFIG_DIR}/lib-lcd.sh"

#Arguments
MODE="$1"

# Power off
if [ "$POWER_OFF" = "true" ] || [ "$MODE" = "force" ]; then
    # umount
    umount "${STORAGE_MOUNT_POINT}"
    umount "${SOURCE_MOUNT_POINT}"

    # If display support is enabled, notify that the backup is complete
    if [ "$DISP" = "true" ]; then
        if [ "$MODE" = "force" ];
        then
            lcd_message "+Power off." "+Do not unplug" "+while the ACT" "+LED is on. Bye!"
        else
            lcd_message "+Backup complete." "+Do not unplug" "+while the ACT" "+LED is on. Bye!"
        fi
    fi

    echo "" > "${FILE_OLED_OLD}"
    rm "${FILE_LOG}"
    rm "${FSCK_LOG}"

    poweroff
else
    # If display support is enabled, notify that the backup is complete
    if [ "$DISP" = "true" ]; then
        lcd_message "+Backup complete." "-Do not unplug!" "+Power down via" "+web UI"
    fi
fi
