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
    lcd_message "Ready" "Connect camera"
fi

# Wait for camera
DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
while [ -z "${DEVICE}" ]; do
    sleep 1
    DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
done

# If display support is enabled, notify that the camera is detected
if [ $DISP = true ]; then
    lcd_message "Camera OK" "Working..."
fi

# Obtain camera model
# Create the target directory with the camera model as its name
CAMERA=$(gphoto2 --summary | grep "Model" | cut -d: -f2 | tr -d '[:space:]')
STORAGE_MOUNT_POINT="$BAK_DIR/$CAMERA"
mkdir -p "$STORAGE_MOUNT_POINT"

# Run the status-display.sh script
if [ $DISP = true ]; then
    # get number of files to sync
    FILES_TO_SYNC=$(gphoto2 --list-files | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+3) " " $(i+4) " " $(i+5)=="There are files in folder"){print $(i+2)}}' | sed s/,//g)

    source "${CONFIG_DIR}/status-display.sh" "${FILES_TO_SYNC}" "${STORAGE_MOUNT_POINT}" &
    PID=$!
fi

# Switch to STORAGE_MOUNT_POINT and transfer files from the camera
cd "$STORAGE_MOUNT_POINT"
GPHOTO_OUTPUT=$(gphoto2 --get-all-files --skip-existing --list-files)


#Display progress after finish
if [ $DISP = true ]; then
    sleep 4
fi

# Kill the status-display.sh script
kill $PID

# Check internet connection and send
# a notification if the NOTIFY option is enabled
check=$(wget -q --spider http://google.com/)
if [ $NOTIFY = true ] || [ ! -z "$check" ]; then
    send_email "Little Backup Box: Backup complete" "Type: Camera\nCamera:${CAMERA}\n\nBackup log:\n\n${GPHOTO_OUTPUT}"
fi

# Power off
source "${CONFIG_DIR}/poweroff.sh"
