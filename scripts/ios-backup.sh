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

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# # If display support is enabled, display the message
if [ $DISP = true ]; then
  oled r
  oled +a "Ready"
  oled +b "Insert storage"
  oled s
fi

# Wait for a USB storage device (e.g., a USB flash drive)
STORAGE=$(ls /dev/* | grep "$STORAGE_DEV" | cut -d"/" -f3)
while [ -z ${STORAGE} ]; do
  sleep 1
  STORAGE=$(ls /dev/* | grep "$STORAGE_DEV" | cut -d"/" -f3)
done

# When the storage device is detected, mount it
mount /dev/"$STORAGE_DEV" "$STORAGE_MOUNT_POINT"

# Set the ACT LED to blink at 500ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"

# If display support is enabled, notify that the storage device has been mounted
if [ $DISP = true ]; then
  oled r
  oled +a "Storage OK"
  oled +b "Connect"
  oled +c "iOS device"
  oled s
fi

# Try to mount iOS device
ifuse $IOS_MOUNT_POINT -o allow_other

# Waiting for the iOS device to be mounted
until [ ! -z "$(ls -A $IOS_MOUNT_POINT)" ]; do
  if [ $DISP = true ]; then
    oled r
    oled +a "No iOS device"
    oled +b "Waiting ..."
    oled s
    sleep 5
  fi
  ifuse $IOS_MOUNT_POINT -o allow_other
  oled r
  oled +b "iOS OK"
  oled s
done

# Define source and destination paths
SOURCE_DIR="$IOS_MOUNT_POINT/DCIM"

# Create  a .id random identifier file if doesn't exist
cd "$IOS_MOUNT_POINT"
if [ ! -f *.id ]; then
  random=$(echo $RANDOM)
  sudo touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
fi
ID_FILE=$(ls -t *.id | head -n1)
ID="${ID_FILE%.*}"
cd

mkdir -p "$STORAGE_MOUNT_POINT/$ID"
BACKUP_PATH="$STORAGE_MOUNT_POINT/$ID"

# Run the progress.sh script
if [ $DISP = true ]; then
    source ${WORKING_DIR}/progress.sh "${BACKUP_PATH}" &
    PID=$!
fi

# Set the ACT LED to blink at 1000ms to indicate that the iOS device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# Perform backup using rsync
if [ $LOG = true ]; then
  sudo rm /var/log/little-backup-box.log
  RSYNC_OUTPUT=$(rsync -avh --stats --exclude "*.id" --log-file=/var/log/little-backup-box.log "$SOURCE_DIR"/ "$BACKUP_PATH")
else
  RSYNC_OUTPUT=$(rsync -avh --stats --exclude "*.id" "$SOURCE_DIR"/ "$BACKUP_PATH")
fi

# Kill the progress.sh script
kill $PID

# If display support is enabled, notify that the backup is complete
if [ $DISP = true ]; then
  oled r
  oled +a "Backup completed"
  oled +b "Power off"
  oled s
fi

# Check internet connection and send
# a notification if the NOTIFY option is enabled
check=$(wget -q --spider http://google.com/)
if [ $NOTIFY = true ] || [ ! -z "$check" ]; then
  curl --url 'smtps://'$SMTP_SERVER':'$SMTP_PORT --ssl-reqd \
    --mail-from $MAIL_USER \
    --mail-rcpt $MAIL_TO \
    --user $MAIL_USER':'$MAIL_PASSWORD \
    -T <(echo -e "From: ${MAIL_USER}\nTo: ${MAIL_TO}\nSubject: Little Backup Box: iOS backup completed\n\nBackup log:\n\n${RSYNC_OUTPUT}")
fi

# Power off
if [ $POWER_OFF = true ]; then
  poweroff
fi
