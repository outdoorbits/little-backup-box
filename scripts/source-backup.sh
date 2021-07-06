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

#Config
FILE_OLED_OLD="/root/oled_old.txt"

#Libraries
. "${CONFIG_DIR}/lib_oled_message.sh"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# If display support is enabled, display the "Ready. Connect camera" message
if [ $DISP = true ]; then
    oled_message "Ready" "Insert storage"
fi

# Wait for a USB storage device (e.g., a USB flash drive)
STORAGE=$(ls /dev/* | grep "${STORAGE_DEV}" | cut -d"/" -f3)
while [ -z "${STORAGE}" ]; do
    sleep 1
    STORAGE=$(ls /dev/* | grep "${STORAGE_DEV}" | cut -d"/" -f3)
done

# When the USB storage device is detected, mount it
mount /dev/"${STORAGE_DEV}" "${STORAGE_MOUNT_POINT}"

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# If display support is enabled, notify that the storage device has been mounted
if [ $DISP = true ]; then
    oled_message "Storage OK" "Insert source"
fi

# Wait for a source device
# takes first device found
SRC=($(ls /dev/* | grep "$SOURCE_DEV" | cut -d"/" -f3))
until [ ! -z "${SRC[0]}" ]; do
    sleep 1
    SRC=($(ls /dev/* | grep "$SOURCE_DEV" | cut -d"/" -f3))
done

# If the source device is detected, mount it and obtain its UUID
mount /dev"/${SRC[0]}" "$SOURCE_MOUNT_POINT"

# Set the ACT LED to blink at 500ms to indicate that the source device has been mounted
sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"

# If display support is enabled, notify that the source device has been mounted
if [ $DISP = true ]; then
    oled_message "Source OK" "Working..."
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
BACKUP_PATH="${STORAGE_MOUNT_POINT}"/"${ID}"

# Run the status-display.sh script
if [ $DISP = true ]; then
    # get number of files to sync
    FILES_TO_SYNC=$(rsync -avh --stats --dry-run --exclude "*.id" "$SOURCE_MOUNT_POINT"/ "$BACKUP_PATH" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3)=="Number of created files:"){print $(i+4)}}' | sed s/,//g)

    source "${CONFIG_DIR}/status-display.sh" "${FILES_TO_SYNC}" "${BACKUP_PATH}" &
    PID=$!
fi

# Perform backup using rsync
if [ $LOG = true ]; then
    sudo rm /root/little-backup-box.log
    RSYNC_OUTPUT=$(rsync -avh --stats --exclude "*.id" --log-file=little-backup-box.log "$SOURCE_MOUNT_POINT"/ "$BACKUP_PATH")
else
    RSYNC_OUTPUT=$(rsync -avh --stats --exclude "*.id" "$SOURCE_MOUNT_POINT"/ "$BACKUP_PATH")
fi

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
	curl --url 'smtps://'$SMTP_SERVER':'$SMTP_PORT --ssl-reqd \
		--mail-from $MAIL_USER \
		--mail-rcpt $MAIL_TO \
		--user $MAIL_USER':'$MAIL_PASSWORD \
	-T <(echo -e "From: ${MAIL_USER}\nTo: ${MAIL_TO}\nSubject: Little Backup Box: Backup complete\n\nBackup log:\n\n${RSYNC_OUTPUT}")
fi

# Power off
source "${CONFIG_DIR}/poweroff.sh"
