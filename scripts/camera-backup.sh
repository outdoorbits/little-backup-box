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
source "$CONFIG"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Shutdown after a specified period of time (in minutes) if no device is connected.
sudo shutdown -h $SHUTD "Shutdown is activated. To cancel: sudo shutdown -c"
# If display support is enabled, notify that shutdown is activated
if [ $DISP = true ]; then
    oled r
    oled +b "Shutdown active"
    oled +c "Connect camera"
    sudo oled s
fi

# Wait for camera
DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
while [ -z "${DEVICE}" ]; do
    sleep 1
    DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
done

# Cancel shutdown
sudo shutdown -c

# If display support is enabled, notify that the camera is detected
if [ $DISP = true ]; then
    oled r
    oled +b "Camera OK"
    oled +c "Working..."
    sudo oled s
fi

# Obtain camera model
# Create the target directory with the camera model as its name
CAMERA=$(gphoto2 --summary | grep "Model" | cut -d: -f2 | tr -d '[:space:]')
STORAGE_MOUNT_POINT="$BAK_DIR/$CAMERA"
mkdir -p "$STORAGE_MOUNT_POINT"

# Switch to STORAGE_MOUNT_POINT and transfer files from the camera
# Rename the transferred files using the YYYYMMDD-HHMMSS format
cd "$STORAGE_MOUNT_POINT"
gphoto2 --get-all-files --skip-existing

# If display support is enabled, notify that the backup is complete
if [ $DISP = true ]; then
    oled r
    oled +b "Backup complete"
    oled +c "Shutdown"
    sudo oled s
fi
# Shutdown
if [ $DISP = true ]; then
    oled r
fi
shutdown -h now
