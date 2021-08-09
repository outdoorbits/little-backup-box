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
source "$CONFIG"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

#load DEVICES library
. "${WORKING_DIR}/lib-devices.sh"

# Wait for a USB storage device (USB 1) (e.g., a USB flash drive)
mount_device "usb_1" true "$(device_mounted usb_1)" "$(device_mounted usb_2)"

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

function get_storage_spaces() {
  local device=$1

  local storsize=$(df ${STORAGE_MOUNT_POINT} -h --output=size | sed '1d' | tr -d ' ')
  local storused=$(df ${STORAGE_MOUNT_POINT} -h --output=pcent | sed '1d' | tr -d ' ')
  local storfree=$(df ${STORAGE_MOUNT_POINT} -h --output=avail | sed '1d' | tr -d ' ')

  echo "${storsize}|${storused}|${storfree}"
}

# notify that the storage device has been mounted
ret="$(get_storage_spaces ${STORAGE_MOUNT_POINT})"

IFS="|"
set -- $ret

STOR_SIZE="Size: $1"
STOR_FREE="free: $3"

unset IFS

lcd_message "Ext. storage OK" "${STOR_SIZE}" "${STOR_FREE}" ""
sleep 4

# Reload minidlna

sudo minidlnad -R
sudo service minidlna restart
