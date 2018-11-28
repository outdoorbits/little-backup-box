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

# Specify devices and their their mount points
# as well as other settings
STORAGE_DEV="sda1" # Name of the storage device
STORAGE_MOUNT_POINT="/media/storage" # Mount point of the storage device
CARD_DEV="sdb1" # Name of the storage card
CARD_MOUNT_POINT="/media/card" # Mount point of the storage card
SHUTD="5" # Minutes to wait before shutdown due to inactivity

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Shutdown after a specified period of time (in minutes) if no device is connected.
sudo shutdown -h $SHUTD "Shutdown is activated. To cancel: sudo shutdown -c"

# Wait for a USB storage device (e.g., a USB flash drive)
STORAGE=$(ls /dev/* | grep "$STORAGE_DEV" | cut -d"/" -f3)
#STORAGE=$(lsblk -x SIZE | grep sd[a-z]1  | awk '{print $1}' | sort | head -n 1)
while [ -z "${STORAGE}" ]
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

# Wait for a card reader or a camera
# takes first device found
CARD_READER=($(ls /dev/* | grep "$CARD_DEV" | cut -d"/" -f3))
until [ ! -z "${CARD_READER[0]}" ]
  do
  sleep 1
  CARD_READER=($(ls /dev/* | grep "$CARD_DEV" | cut -d"/" -f3))
done

# If the card reader is detected, mount it and obtain its UUID
if [ ! -z "${CARD_READER[0]}" ]; then
  mount /dev"/${CARD_READER[0]}" "$CARD_MOUNT_POINT"

  CARD_COUNT=$(find $CARD_MOUNT_POINT/ -type f | wc -l)
  # # Set the ACT LED to blink at 500ms to indicate that the card has been mounted
  sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"

  # Create  a .id random identifier file if doesn't exist
  cd "$CARD_MOUNT_POINT"
  if [ ! -f *.id ]; then
    random=$(echo $RANDOM)
    touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
  fi
  ID_FILE=$(ls *.id)
  ID="${ID_FILE%.*}"
  cd

  # Set the backup path
  BACKUP_PATH="$STORAGE_MOUNT_POINT"/"$ID"
  STORAGE_COUNT=$(find $BACKUP_PATH/ -type f | wc -l)
  # Perform backup using rsync
  rsync -avh --info=progress2 --exclude "*.id" "$CARD_MOUNT_POINT"/ "$BACKUP_PATH" &
  pid=$!

  while kill -0 $pid 2> /dev/null
    do
    STORAGE_COUNT=$(find $BACKUP_PATH/ -type f | wc -l)
    PERCENT=$(expr 100 \* $STORAGE_COUNT / $CARD_COUNT)
    sudo sh -c "echo $PERCENT"
    #IF STATEMENTS HERE FOR LEDS
    if [ $PERCENT -gt 25 ] && [ $PERCENT -lt 49 ]; then
      sudo sh -c "echo 300 > /sys/class/leds/led0/delay_on"
    elif [ $PERCENT -gt 50 ] && [ $PERCENT -lt 74 ]; then
      sudo sh -c "echo 200 > /sys/class/leds/led0/delay_on"
    elif [ $PERCENT -gt 75 ] && [ $PERCENT -lt 100 ]; then
      sudo sh -c "echo 100 > /sys/class/leds/led0/delay_on"
    fi
    # then
    #LEDS
    #fi
    sleep 1
  done
  sudo sh -c "echo 1 > /sys/class/leds/led0/brightness"
  # Turn off the POWER LED to indicate that the backup is completed
  sudo sh -c "echo 0 > /sys/class/leds/led1/brightness"
fi

# Shutdown
sync
shutdown -h now
