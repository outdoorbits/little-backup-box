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

CONFIG_DIR=$(dirname "$0")
CONFIG="${CONFIG_DIR}/config.cfg"
dos2unix "$CONFIG"
source "$CONFIG"

# Load Mail library
. "${CONFIG_DIR}/lib-mail.sh"

# Load LCD library
. "${CONFIG_DIR}/lib-lcd.sh"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# If display support is enabled, display the "Ready. Connect camera" message
if [ $DISP = true ]; then
    oled_message "Ready" "Connect camera"
fi

# Wait for camera
DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
while [ -z "${DEVICE}" ]; do
    sleep 1
    DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
done

# If display support is enabled, notify that the camera is detected
if [ $DISP = true ]; then
    oled_message "Camera OK" "Working..."
fi

# Obtain camera model
# Create the target directory with the camera model as its name
CAMERA=$(gphoto2 --summary | grep "Model" | cut -d: -f2 | tr -d '[:space:]')
STORAGE_MOUNT_POINT="$BAK_DIR/$CAMERA"
mkdir -p "$STORAGE_MOUNT_POINT"

# Switch to STORAGE_MOUNT_POINT and transfer files from the camera
cd "$STORAGE_MOUNT_POINT"
gphoto2 --get-all-files --skip-existing

# If display support is enabled, notify that the backup is complete
if [ $DISP = true ]; then
    oled_message "Backup complete" "Power off"
fi

# Check internet connection and send
# a notification if the NOTIFY option is enabled
check=$(wget -q --spider http://google.com/)
    send_email "Little Backup Box" "Backup complete."
fi

# Power off
source "${CONFIG_DIR}/poweroff.sh"
