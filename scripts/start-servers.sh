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
source "$CONFIG"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

#load DEVICES library
. "${WORKING_DIR}/lib-devices.sh"

## start minidlna
sudo minidlnad -R
sudo service minidlna start

##start rclone web-gui
if [ ! -f "${const_RCLONE_CONFIG_FILE}" ]; then
	sudo touch "${const_RCLONE_CONFIG_FILE}"
fi
sudo chmod 777 "${const_RCLONE_CONFIG_FILE}"

if [ -z "${conf_PASSWORD}" ]; then
	sudo rclone rcd --rc-web-gui --rc-web-gui-no-open-browser --rc-addr :5572 --config "${const_RCLONE_CONFIG_FILE}" --rc-user lbb --rc-pass "lbb" &
else
	sudo rclone rcd --rc-web-gui --rc-web-gui-no-open-browser --rc-addr :5572 --config "${const_RCLONE_CONFIG_FILE}" --rc-user lbb --rc-pass "${conf_PASSWORD}" &
fi

# ## Mount USB 1
# # Wait for a USB storage device (USB 1) (e.g., a USB flash drive)
# mount_device "usb_1" true "$(device_mounted usb_1)" "$(device_mounted usb_2)"
#
# # Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
# sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
# sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# ret="$(get_storage_spaces ${const_STORAGE_MOUNT_POINT})"

# IFS="|"
# set -- $ret
#
# STOR_SIZE="Size: $1"
# STOR_FREE="free: $3"
#
# unset IFS
#
# lcd_message "Ext. storage OK" "${STOR_SIZE}" "${STOR_FREE}" ""
# sleep 4
