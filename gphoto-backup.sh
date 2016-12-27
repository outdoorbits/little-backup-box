#!/usr/bin/env bash

# Specify a storage device and its mount point
STORAGE_DEV="sda1"
STORAGE_MOUNT_POINT="/media/storage"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Wait for a USB storage device (e.g., a USB flash drive)
STORAGE=$(ls /dev/* | grep $STORAGE_DEV | cut -d"/" -f3)
while [ -z ${STORAGE} ]
  do
  sleep 1
  STORAGE=$(ls /dev/* | grep $STORAGE_DEV | cut -d"/" -f3)
done

# When the USB storage device is detected, mount it
mount /dev/$STORAGE_DEV $STORAGE_MOUNT_POINT

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

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
mkdir "`date --iso-8601`" && cd $_
# Transfer new files to the USB storage device
#gphoto2 --get-all-files
gphoto2 --new
# Rename files using ExifTool based on EXIF date and time data
exiftool -r -d %Y%m%d-%H%M%S.%%e "-FileName<DateTimeOriginal" .
# Turn off the ACT LED to indicate that the backup is completed
sudo sh -c "echo 0 > /sys/class/leds/led0/brightness"
# Shutdown
shutdown -h now 
