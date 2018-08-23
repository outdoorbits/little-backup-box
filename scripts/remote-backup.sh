#!/usr/bin/env bash

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

# IMPORTANT:
# Run the install-little-backup-box.sh script first
# to install the required packages and configure the system.

# Create a new configuraion file if it doesn't exist
# and prompt for required values

CONFIG_DIR=$(dirname "$0")
CONFIG="${CONFIG_DIR}/remote-backup.cfg"

if [ ! -f "$CONFIG" ]; then
    echo "Enter remote user name and press [ENTER]:"
    read USER
    echo 'USER="'$USER'"' >> "$CONFIG"
    echo "Enter remote server IP address or domain name and press [ENTER]:"
    read REMOTE
    echo 'REMOTE="'$REMOTE'"' >> "$CONFIG"
    echo "Specify target backup directory (include the trailing slash) and press [ENTER]"
    read BACKUP_DIR
    echo 'BACKUP_DIR="'$BACKUP_DIR'"' >> "$CONFIG"
    echo "Enter your Notify token and press [ENTER]."
    echo "Skip to disable:"
    read NOTIFY_TOKEN
    echo 'NOTIFY_TOKEN="'$NOTIFY_TOKEN'"' >> "$CONFIG"
    fi
# Initialize the configuration file
source "$CONFIG"

# Specify backup device and its mount points,
# and other settings
STORAGE_DEV="sda1" # Name of the storage device
STORAGE_MOUNT_POINT="/media/storage" # Mount point of the storage device
SHUTD="5" # Minutes to wait before shutdown due to inactivity

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Shutdown after a specified period of time (in minutes) if no device is connected.
sudo shutdown -h $SHUTD "Shutdown is activated. To cancel: sudo shutdown -c"

# Wait for a USB storage device (e.g., a USB flash drive)
STORAGE=$(ls /dev/* | grep "$STORAGE_DEV" | cut -d"/" -f3)
while [ -z ${STORAGE} ]
  do
  sleep 1
  STORAGE=$(ls /dev/* | grep "$STORAGE_DEV" | cut -d"/" -f3)
done

# When the USB storage device is detected, mount it
mount /dev/"$STORAGE_DEV" "$STORAGE_MOUNT_POINT"

# Cancel shutdown
sudo shutdown -c

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# Perform backup using rsync
rsync -avhz -e ssh --delete --progress "$STORAGE_MOUNT_POINT"/ "$USER"@"$REMOTE":"$BACKUP_DIR"

if [ ! -z "$NOTIFY_TOKEN" ]; then
	TEXT=$(sed 's/ /%20/g' <<< "Remote backup completed.")
	curl -k \
"https://us-central1-notify-b7652.cloudfunctions.net/sendNotification?to=${NOTIFY_TOKEN}&text=${TEXT}" \
> /dev/null
fi

  # Turn off the ACT LED to indicate that the backup is completed
  sudo sh -c "echo 0 > /sys/class/leds/led0/brightness"

# Shutdown
sync
shutdown -h now
