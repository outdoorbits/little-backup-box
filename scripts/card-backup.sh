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

CONFIG_DIR=$(dirname "$0")
CONFIG="${CONFIG_DIR}/config.cfg"

source "$CONFIG"

# st599 added debugging print outs
if [ $DEBUG = true ]; then
  echo "CARD BACKUP"
  echo "Config Parser"
  echo "  Storage device      $STORAGE_DEV"
  echo "  Storage mount point $STORAGE_MOUNT_POINT"
  echo "  Card device         $CARD_DEV"
  echo "  Card mount point    $CARD_MOUNT_POINT"
  echo "  Display             $DISP"
  echo "  Syncthing           $SYNCTHING"
fi

# If display support is enabled, state programme run
if [ $DISP = true ]; then
    oled r
    oled +a "Lit. Bac. Box"
    oled +b "Card Backup"
    sudo oled s
    sleep 1
    oled r
    oled +a "Card Backup"
    oled +b "Storage..."
    sudo oled s
fi



# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Shutdown after a specified period of time (in minutes) if no device is connected.
#
if [ $DEBUG = true ]; then
  echo "Activating Shutdown"
fi
sudo shutdown -h $SHUTD "Shutdown is activated. To cancel: sudo shutdown -c"

# Wait for a USB storage device (e.g., a USB flash drive)
if [ $DEBUG = true ]; then
  echo "Awaiting Storage"
fi
STORAGE=$(ls /dev/* | grep "$STORAGE_DEV" | cut -d"/" -f3)
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

# If display support is enabled, notify that the storage device has been mounted
if [ $DISP = true ]; then
    oled r
    oled +a "Storage OK"
    oled +b "Card reader..."
    sudo oled s
fi

# Wait for a card reader or a camera
# takes first device found
if [ $DEBUG = true ]; then
  echo "Awaiting Card Reader"
fi
CARD_READER=($(ls /dev/* | grep "$CARD_DEV" | cut -d"/" -f3))
until [ ! -z "${CARD_READER[0]}" ]
  do
  sleep 1
  CARD_READER=($(ls /dev/* | grep "$CARD_DEV" | cut -d"/" -f3))
done

# If the card reader is detected, mount it and obtain its UUID
if [ ! -z "${CARD_READER[0]}" ]; then
  mount /dev"/${CARD_READER[0]}" "$CARD_MOUNT_POINT"

  # Set the ACT LED to blink at 500ms to indicate that the card has been mounted
  sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"

  # If display support is enabled, notify that the card has been mounted
  if [ $DISP = true ]; then
      oled r
      oled +a "Card reader OK"
      oled +b "Backup start"
      sudo oled s
  fi

  # Create  a .id random identifier file if doesn't exist
  if [ $DEBUG = true ]; then
    echo "Creating ID File"
  fi
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


  # Perform backup using rsync
  if [ $DEBUG = true ]; then
    echo "Perform Backup"
  fi
  if [ $DISP = true ]; then
    rsync -avh --info=progress2 --exclude "*.id" "$CARD_MOUNT_POINT"/ "$BACKUP_PATH" | ./oled-rsync-progress.sh exclude-file.txt
  else
    rsync -avh --info=progress2 --exclude "*.id" "$CARD_MOUNT_POINT"/ "$BACKUP_PATH"
  fi
fi
if [ $DEBUG = true ]; then
  echo "Backup Complete"
fi


# If display support is enabled, notify that the backup is complete
if [ $DISP = true ]; then
    oled r
    oled +a "Backup complete"
    oled +b "Shutdown"
    sudo oled s
    sleep 5
    oled r
    sudo oled s
fi
# Shutdown

if [ $DEBUG = true ]; then
  echo "Shutdown"
fi
sync
shutdown -h now
