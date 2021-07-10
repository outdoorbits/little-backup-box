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
sudo dos2unix "$CONFIG"
source "$CONFIG"

# Configuration
FILE_OLED_OLD="/root/oled_old.txt"

# Load Mail library
. "${CONFIG_DIR}/lib-mail.sh"

# Load LCD library
. "${CONFIG_DIR}/lib-lcd.sh"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# If display support is enabled, display the "Ready. Connect camera" message
if [ $DISP = true ]; then
	oled_message "Connect" "iOS device"
fi

# Mount iOS device
ifuse $MOUNT_IOS_DIR -o allow_other
SOURCE_DIR="$MOUNT_IOS_DIR/DCIM/100APPLE"

# Exit with a message if iOS device is not mounted
if [ -z "$(ls -A $MOUNT_IOS_DIR)" ]; then
	if [ $DISP = true ]; then
		oled_message "No iOS device" "Try again"
		exit 1
	fi
fi

# Set the ACT LED to blink at 1000ms to indicate that the iOS device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# Run the status-display.sh script
if [ $DISP = true ]; then
	# Get number of files to sync
	FILES_TO_SYNC=$(rsync -avh --stats --dry-run "$SOURCE_DIR"/ "$BAK_DIR" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3)=="Number of created files:"){print $(i+4)}}' | sed s/,//g)

	source "${CONFIG_DIR}/status-display.sh" "${FILES_TO_SYNC}" "$BAK_DIR" &
	PID=$!
fi

# Perform backup using rsync
if [ $LOG = true ]; then
	sudo rm /root/little-backup-box.log
	RSYNC_OUTPUT=$(rsync -avh --stats --log-file=little-backup-box.log "$SOURCE_DIR"/ "$BAK_DIR")
else
	RSYNC_OUTPUT=$(rsync -avh --stats "$SOURCE_DIR"/ "$BAK_DIR")
fi

# Display progress after finish
if [ $DISP = true ]; then
	sleep 4
fi

# Kill the status-display.sh script
kill $PID

# Check internet connection and send
# a notification if the NOTIFY option is enabled
check=$(wget -q --spider http://google.com/)
if [ $NOTIFY = true ] || [ ! -z "$check" ]; then
	send_email "Little Backup Box: Backup complete" "Type: iOS device\n\nBackup log:\n\n${RSYNC_OUTPUT}"
fi

sudo umount $MOUNT_IOS_DIR

# Power off
source "${CONFIG_DIR}/poweroff.sh"
