#!/usr/bin/env bash

# IMPORTANT:
# Run the install-little-backup-box.sh script first
# to install the required packages and configure the system.

# Specify a storage device and its mount point
STORAGE_MOUNT_POINT="/home/pi/BACKUP"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Create the target directory if it doesn't exist
if [ ! -d "$STORAGE_MOUNT_POINT" ]; then
  mkdir $STORAGE_MOUNT_POINT
fi

# Wait for camera
DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
while [ -z ${DEVICE} ]
	do
	sleep 1
	DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
done

# Set the ACT LED to blink at 500ms to indicate that the camera has been detected
sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"

# Switch to STORAGE_MOUNT_POINT and transfer files from the camera
# Rename the transferred files using the YYYYMMDD-HHMMSS format
cd $STORAGE_MOUNT_POINT
gphoto2 --get-all-files --skip-existing --filename=%Y%m%d-%H%M%S.%C
# Turn off the ACT LED to indicate that the backup is completed
sudo sh -c "echo 0 > /sys/class/leds/led0/brightness"

# Shutdown
shutdown -h now 
