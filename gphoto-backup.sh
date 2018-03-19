#!/usr/bin/env bash

# IMPORTANT:
# Run the install-little-backup-box.sh script first
# to install the required packages and configure the system.

# Specify a storage device and its mount point
HOME_DIR="/home/pi"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Wait for camera
DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
while [ -z ${DEVICE} ]
	do
	sleep 1
	DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
done

# Obtain camera model
# Create the target directory with the camera model as its name
CAMERA=$(gphoto2 --summary | grep "Model" | cut -d: -f2 | tr -d '[:space:]')
STORAGE_MOUNT_POINT="$HOME_DIR/$CAMERA"
mkdir -p $STORAGE_MOUNT_POINT

# Switch to STORAGE_MOUNT_POINT and transfer files from the camera
# Rename the transferred files using the YYYYMMDD-HHMMSS format
cd $STORAGE_MOUNT_POINT
gphoto2 --get-all-files --skip-existing --filename=%Y%m%d-%H%M%S.%C

# Shutdown
shutdown -h now 
