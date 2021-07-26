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

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

# Wait for a USB storage device (e.g., a USB flash drive)
STORAGE=$(ls /dev/* | grep "$STORAGE_DEV" | cut -d"/" -f3)
while [ -z "${STORAGE}" ]; do
  sleep 1
  STORAGE=$(ls /dev/* | grep "$STORAGE_DEV" | cut -d"/" -f3)
done

# When the USB storage device is detected, mount it
mount /dev/"$STORAGE_DEV" "$STORAGE_MOUNT_POINT"

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# Reload minidlna

sudo minidlnad -R
sudo service minidlna restart

# If display support is enabled, display storage space info
if [ $DISP = true ]; then
  storsize=$(df /dev/"$STORAGE_DEV" -h --output=size | sed '1d' | tr -d ' ')
  storused=$(df /dev/"$STORAGE_DEV" -h --output=pcent | sed '1d' | tr -d ' ')
  storfree=$(df /dev/"$STORAGE_DEV" -h --output=avail | sed '1d' | tr -d ' ')
  lcd_message "Free: $storfree"
fi
