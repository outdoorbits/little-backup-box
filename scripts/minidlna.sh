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

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# Reload minidlna

sudo minidlnad -R
sudo service minidlna restart
