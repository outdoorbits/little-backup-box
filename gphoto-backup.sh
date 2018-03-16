#!/usr/bin/env bash

# Specify a storage device and its mount point
STORAGE_MOUNT_POINT="/home/pi/BACKUP"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

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

# Switch to STORAGE_MOUNT_POINT and create a directory with current date as its name
cd $STORAGE_MOUNT_POINT
gphoto2 --new
# Rename files using ExifTool based on EXIF date and time data
exiftool -r -d %Y%m%d-%H%M%S.%%e "-FileName<DateTimeOriginal" .
# Turn off the ACT LED to indicate that the backup is completed
sudo sh -c "echo 0 > /sys/class/leds/led0/brightness"
# Shutdown
shutdown -h now 
