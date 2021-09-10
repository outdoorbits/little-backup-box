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

# If display support is enabled, display the "Ready. Insert storage" message
if [ $DISP = true ]; then
    oled r
    oled +b "Ready"
    oled +c "Insert storage"
    oled s
fi

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

# If display support is enabled, notify that the storage device has been mounted
if [ $DISP = true ]; then
    oled r
    oled +b "Storage OK"
    oled +c "Source..."
    oled s
fi

# Wait for a source device and
# pick first found device
SRC=($(ls /dev/* | grep "$SOURCE_DEV" | cut -d"/" -f3))
until [ ! -z "${SRC[0]}" ]; do
    sleep 1
    SRC=($(ls /dev/* | grep "$SOURCE_DEV" | cut -d"/" -f3))
done

# If the source device is detected, mount it
mount /dev"/${SRC[0]}" "$SOURCE_MOUNT_POINT"

# Set the ACT LED to blink at 500ms to indicate that the source device has been mounted
sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"

# If display support is enabled, notify that the source device has been mounted
if [ $DISP = true ]; then
    oled r
    oled +b "Source OK"
    oled s
fi

# Create  a .id random identifier file if doesn't exist
cd "$SOURCE_MOUNT_POINT"
if [ ! -f *.id ]; then
    random=$(echo $RANDOM)
    touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
fi
ID_FILE=$(ls -t *.id | head -n1)
ID="${ID_FILE%.*}"
cd

# Set the backup path
BACKUP_PATH="$STORAGE_MOUNT_POINT"/"$ID"

# Run the progress.sh script
if [ $DISP = true ]; then
    source ${WORKING_DIR}/progress.sh "${SOURCE_MOUNT_POINT}" "${BACKUP_PATH}" &
    PID=$!
fi

# Perform backup using rsync
sudo rm /var/log/little-backup-box.log
RSYNC_OUTPUT=$(rsync -avh --stats --exclude "*.id" --log-file=/var/log/little-backup-box.log "$SOURCE_MOUNT_POINT"/ "$BACKUP_PATH")

# Kill the progress.sh script
kill $PID
rm /tmp/progress

# If display support is enabled, notify that the backup is complete
if [ $DISP = true ]; then
    oled r
    oled +b "Backup completed"
    oled +c "Power off"
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
        -T <(echo -e "From: ${MAIL_USER}\nTo: ${MAIL_TO}\nSubject: Little Backup Box: Source backup completed\n\nBackup log:\n\n${RSYNC_OUTPUT}")
fi

# Power off
if [ $POWER_OFF = true ]; then
    poweroff
fi
