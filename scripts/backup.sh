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

# get arguments: SOURCE_MODE DEST_MODE
SOURCE_MODE="storage"
if [ "${1}" = "camera" ];
then
    SOURCE_MODE="camera"
fi

DEST_MODE="external"
if [ "${2}" = "internal" ];
then
    DEST_MODE="internal"
fi

# overwrite logfile
echo "Source: ${SOURCE_MODE}" > little-backup-box.log
echo "Destination:: ${DEST_MODE}" >> little-backup-box.log

# Load Mail library
. "${CONFIG_DIR}/lib-mail.sh"

# Load LCD library
. "${CONFIG_DIR}/lib-lcd.sh"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# umount devices
umount "${STORAGE_MOUNT_POINT}" || /bin/true
umount "${SOURCE_MOUNT_POINT}" || /bin/true

# Storage device
if [ "${DEST_MODE}" = "external" ];
then
    # external mode
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
    mount "/dev/${STORAGE_DEV}" "${STORAGE_MOUNT_POINT}"
    
    STORAGE_PATH="${STORAGE_MOUNT_POINT}"
    
    # If display support is enabled, notify that the storage device has been mounted
    if [ $DISP = true ]; then
        oled_message "Ext.Storage OK" "Insert source"
    fi
else
    # internal mode
    STORAGE_PATH="${BAK_DIR}"
    
    # If display support is enabled, notify that the storage device has been mounted
    if [ $DISP = true ]; then
        oled_message "Int.Storage OK" "Insert source"
    fi
fi

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# Wait for a source device
# takes first device found
if [ "${SOURCE_MODE}" = "storage" ];
then
    # Source=storage
    # If display support is enabled, display the "Ready. Connect source" message
    if [ $DISP = true ]; then
        oled_message "Ready" "Insert source"
    fi
    
    # Source device
    if [ "${SOURCE_MODE}" = "storage" ];
    then
        if [ "${DEST_MODE}" = "external" ];
        then
            SOURCE_DEVICE="${SOURCE_DEV}" # the second integrated device
        else
            SOURCE_DEVICE="${STORAGE_DEV}" # the first integrated device
        fi
    fi
    
    SRC=($(ls /dev/* | grep "${SOURCE_DEVICE}" | cut -d"/" -f3))
    until [ ! -z "${SRC[0]}" ]; do
        sleep 1
        SRC=($(ls /dev/* | grep "$SOURCE_DEVICE" | cut -d"/" -f3))
    done
    
    # If the source device is detected, mount it and obtain its UUID
    mount /dev"/${SRC[0]}" "${SOURCE_MOUNT_POINT}"
    
    # If display support is enabled, notify that the source device has been mounted
    if [ $DISP = true ]; then
        oled_message "Source OK" "Working..."
    fi
    
    # Create  a .id random identifier file if doesn't exist
    cd "${SOURCE_MOUNT_POINT}"
    if [ ! -f *.id ]; then
        random=$(echo $RANDOM)
        touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
    fi
    ID_FILE=$(ls -t *.id | head -n1)
    ID="${ID_FILE%.*}"
    cd
    
    # Set the backup path
    BACKUP_PATH="${STORAGE_PATH}"/"${ID}"
    
    # SOURCE_IDENTIFIER
    SOURCE_IDENTIFIER="Source-ID: ${ID}"
else
    # Source=camera
    # If display support is enabled, display the "Ready. Connect camera" message
    if [ $DISP = true ]; then
        oled_message "Ready" "Connect camera"
    fi

    # Wait for camera
    DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
    while [ -z "${DEVICE}" ]; do
        sleep 1
        DEVICE=$(gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
    done

    # If display support is enabled, notify that the camera is detected
    if [ $DISP = true ]; then
        oled_message "Camera OK" "Working..."
    fi

    # Obtain camera model
    # Create the target directory with the camera model as its name
    CAMERA=$(gphoto2 --summary | grep "Model" | cut -d: -f2 | tr -d '[:space:]')
    BACKUP_PATH="${STORAGE_PATH}/${CAMERA}"
    
    # SOURCE_IDENTIFIER
    SOURCE_IDENTIFIER="Camera: ${CAMERA}"
fi


# Set the ACT LED to blink at 500ms to indicate that the source device has been mounted
sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"

# Run the status-display.sh script
if [ $DISP = true ]; then
    # get number of files to sync
    if [ "${SOURCE_MODE}" = "storage" ];
    then
        # Source=storage
        FILES_TO_SYNC=$(rsync -avh --stats --exclude "*.id" --dry-run "${SOURCE_MOUNT_POINT}"/ "${BACKUP_PATH}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3)=="Number of created files:"){print $(i+4)}}' | sed s/,//g)
    else
        # Source=camera
        mkdir -p "${BACKUP_PATH}"
        cd "${BACKUP_PATH}"
        FILES_TO_SYNC=$(gphoto2 --list-files | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+3) " " $(i+4) " " $(i+5)=="There are files in folder"){print $(i+2)}}' | sed s/,//g)
        cd
    fi
    
    source "${CONFIG_DIR}/status-display.sh" "${FILES_TO_SYNC}" "${BACKUP_PATH}" &
    PID=$!
fi

# Perform backup using rsync
if [ "${SOURCE_MODE}" = "storage" ];
then
    # Source=storage
    if [ $LOG = true ]; then
        SYNC_OUTPUT=$(rsync -avh --stats --exclude "*.id" --log-file=little-backup-box.log "$SOURCE_MOUNT_POINT"/ "$BACKUP_PATH")
    else
        SYNC_OUTPUT=$(rsync -avh --stats --exclude "*.id" "$SOURCE_MOUNT_POINT"/ "$BACKUP_PATH")
    fi
else
    # Source=camera
    # Switch to STORAGE_MOUNT_POINT and transfer files from the camera
    mkdir -p "${BACKUP_PATH}"
    cd "${BACKUP_PATH}"
    if [ $LOG = true ]; then
        SYNC_OUTPUT=$(gphoto2 --get-all-files --skip-existing --list-files --debug-logfile little-backup-box.log)
    else
        SYNC_OUTPUT=$(gphoto2 --get-all-files --skip-existing --list-files)
    fi
    cd
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
    send_email "Little Backup Box: Backup complete" "Type: ${SOURCE_MODE} to ${DEST_MODE}\n${SOURCE_IDENTIFIER}\n\nBackup log:\n\n${SYNC_OUTPUT}"
fi

# Power off
source "${CONFIG_DIR}/poweroff.sh"
