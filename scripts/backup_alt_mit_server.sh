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

###############################################
# The commented examples in the script provide
# instructions on adding custom backup jobs
# To extend, just use the elif-section-examples
###############################################

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Config
LogFileSync="${WORKING_DIR}/tmp/sync.log"

MOUNTED_DEVICES=()
UUID_USB_1=""
UUID_USB_2=""

#####################################
# SOURCE AND DESTINATION DEFINTIONS #
#####################################

# START

# To add a new definition, specify the desired arguments to the list

# Source definition
if [[ " storage camera ios internal " =~ " ${1} " ]]; then
    SOURCE_MODE="${1}"
else
    SOURCE_MODE="storage"
fi

# Destination definition
if [[ " internal external " =~ " ${2} " ]]; then
    DEST_MODE="${2}"
else
    DEST_MODE="external"
fi


if [ "${SOURCE_MODE}" = "${DEST_MODE}" ]; then
    lcd_message "Invalid" "mode" "combination" ""
    exit 1
fi

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load Mail library
. "${WORKING_DIR}/lib-mail.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

#load DEVICES library
. "${WORKING_DIR}/lib-devices.sh"

# log
log_to_file "Source: ${SOURCE_MODE}"
log_to_file "Destination: ${DEST_MODE}"


function get_storage_spaces() {
    local DEVICE=$1

    local storsize=$(df "${DEVICE}" -h --output=size | sed '1d' | tr -d ' ')
    local storused=$(df "${DEVICE}" -h --output=pcent | sed '1d' | tr -d ' ')
    local storfree=$(df "${DEVICE}" -h --output=avail | sed '1d' | tr -d ' ')

    echo "${storsize}|${storused}|${storfree}"
}

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Unmount devices
sudo umount "${STORAGE_MOUNT_POINT}" || /bin/true
sudo umount "${SOURCE_MOUNT_POINT}" || /bin/true

#########################
# MANAGE STORAGE DEVICE #
#########################

# START

# To define a new method, add an elif block (example below)

if [ "${DEST_MODE}" = "external" ]; then
    # External mode
    # If display support is enabled, display the specified message

    lcd_message "Ready" "Insert storage" "" ""

    # Wait for a USB storage device (e.g., a USB flash drive)
    UUID_USB_1=$(mount_device "usb_1" true "${UUID_USB_1}" "${UUID_USB_2}")
    MOUNTED_DEVICES+=("${UUID_USB_1}")

    STORAGE_PATH="${STORAGE_MOUNT_POINT}"

    # notify that the storage device has been mounted
    ret="$(get_storage_spaces ${STORAGE_MOUNT_POINT})"

    IFS="|"
    set -- $ret

    STOR_SIZE="Size: $1"
    STOR_FREE="free: $3"

    unset IFS

    lcd_message "Ext. storage OK" "${STOR_SIZE}" "${STOR_FREE}" ""

    if [ $DISP = true ]; then
        sleep 2
    fi

elif [ "${DEST_MODE}" = "server" ]; then
        STORAGE_PATH="rsync://${RSYNC_USER}@${RSYNC_SERVER}:${RSYNC_PORT}${RSYNC_PATH}"

    # elif [ "${DEST_MODE}" = "NEW_STORAGE_DEFINITION" ]; then
    #         lcd_message "Ready" "Insert NEW_STORAGE_TYPE"
    #         ...
    #         # Set storage path
    #         STORAGE_PATH

    elif [ "${DEST_MODE}" = "internal" ]; then
        # Internal mode
        STORAGE_PATH="${INTERAL_BACKUP_DIR}"

        # If display support is enabled, notify that the storage device has been mounted
        lcd_message "Int. storage OK" "" "" ""
    else
        # no defined mode selected
        lcd_message "No valid" "destination" "mode defined" ""
        exit 1
fi

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

echo "STORAGE_PATH=${STORAGE_PATH}"

########################
# MANAGE SOURCE DEVICE #
########################

# START

# To define a new method, add an elif block (example below)

if [ "${SOURCE_MODE}" = "storage" ]; then

    # Source storage
    # If display support is enabled, display the specified message
    lcd_message "Ready" "Insert source" "" ""

    # Source device
    if [ "${SOURCE_MODE}" = "storage" ]; then
        if [ "${DEST_MODE}" = "external" ]; then
            UUID_USB_2=$(mount_device "usb_2" true "${UUID_USB_1}" "${UUID_USB_2}")
            MOUNTED_DEVICES+=("${UUID_USB_2}")

            # Set SOURCE_PATH
            SOURCE_PATH="${SOURCE_MOUNT_POINT}"
        else
            UUID_USB_1=$(mount_device "usb_1" true "${UUID_USB_1}" "${UUID_USB_2}")
            MOUNTED_DEVICES+=("${UUID_USB_1}")

            # Set SOURCE_PATH
            SOURCE_PATH="${STORAGE_MOUNT_POINT}"
        fi
    fi

    # notify that the source device has been mounted
    ret="$(get_storage_spaces ${SOURCE_PATH})"
    IFS="|"
    set -- $ret
    STOR_SIZE="Size: $1"
    STOR_USED="used: $2"

    unset IFS

    lcd_message "Source OK" "Working..." "${STOR_SIZE}" "${STOR_USED}"
    if [ $DISP = true ]; then
        sleep 2
    fi

    # Create  a .id random identifier file if doesn't exist
    cd "${SOURCE_PATH}"
    if [ ! -f *.id ]; then
        random=$(echo $RANDOM)
        sudo touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
    fi
    ID_FILE=$(ls -t *.id | head -n1)
    ID="${ID_FILE%.*}"
    cd

    # Set BACKUP_PATH
    BACKUP_PATH="${STORAGE_PATH}/${ID}"

    # Set SOURCE_IDENTIFIER
    SOURCE_IDENTIFIER="Source ID: ${ID}"

elif [ "${SOURCE_MODE}" = "ios" ]; then
    lcd_message "Ready" "Connect" "iOS device" ""

    # Try to mount the iOS device
    ifuse ${IOS_MOUNT_POINT} -o allow_other

    # Waiting for the iOS device to be mounted
    until [ ! -z "$(ls -A ${IOS_MOUNT_POINT})" ]; do
        lcd_message "No iOS device" "Waiting..." "" ""
        sleep 10
        sudo ifuse ${IOS_MOUNT_POINT} -o allow_other
    done

    # Mount iOS device
    SOURCE_PATH="${MOUNT_IOS_DIR}/DCIM"

    # Create  a .id random identifier file if doesn't exist
    cd "${SOURCE_PATH}"
    if [ ! -f *.id ]; then
        random=$(echo $RANDOM)
        sudo touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
    fi
    ID_FILE=$(ls -t *.id | head -n1)
    ID="${ID_FILE%.*}"
    cd

    # Set BACKUP_PATH
    BACKUP_PATH="${STORAGE_PATH}/iOS/${ID}"

    # Set SOURCE_IDENTIFIER
    SOURCE_IDENTIFIER="Source ID: iOS ${ID}"

elif [ "${SOURCE_MODE}" = "internal" ]; then
        lcd_message "Int. storage OK" "" "" ""

        # Set SOURCE_PATH
        SOURCE_PATH="${INTERAL_BACKUP_DIR}"

        # Set BACKUP_PATH
        BACKUP_PATH="${STORAGE_PATH}/internal"

        # Set SOURCE_IDENTIFIER
        SOURCE_IDENTIFIER="Internal memory"

# elif [ "${SOURCE_MODE}" = "NEW_SOURCE_DEFINITION" ]; then
#
#         lcd_message "Ready" "Insert NEW_SOURCE_TYPE"
#         ...
#         # Specify backup path and source identifier
#         SOURCE_PATH
#         BACKUP_PATH
#         SOURCE_IDENTIFIER

elif [ "${SOURCE_MODE}" = "camera" ]; then
    # Source camera
    # If display support is enabled, display the specified message
    lcd_message "Ready" "Connect camera" "" ""

    # Wait for camera
    DEVICE=$(sudo gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
    while [ -z "${DEVICE}" ]; do
        sleep 1
        DEVICE=$(sudo gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
    done

    # If display support is enabled, notify that the camera is detected
    lcd_message "Camera OK" "Working..." "" ""

    # Obtain camera model
    # Create the target directory with the camera model as its name
    CAMERA=$(sudo gphoto2 --summary | grep "Model" | cut -d: -f2 | tr -d '[:space:]')

    #Set SOURCE_PATH
    # not used

    # Set BACKUP_PATH
    BACKUP_PATH="${STORAGE_PATH}/${CAMERA}"

    # Set SOURCE_IDENTIFIER
    SOURCE_IDENTIFIER="Camera: ${CAMERA}"

else
    # no defined mode selected
    lcd_message "No valid" "source" "mode defined" "1"
fi

echo "SOURCE_PATH=${SOURCE_PATH}"
echo "BACKUP_PATH=${BACKUP_PATH}"
echo "SOURCE_IDENTIFIER=${SOURCE_IDENTIFIER}"

# Set the ACT LED to blink at 500ms to indicate that the source device has been mounted
sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"

########################################
# CALCULATE NUMBER OF FILES TO BACK UP #
########################################

# START

# To define a new method, add an elif block (example below)

if [[ " storage ios internal " =~ " ${SOURCE_MODE} " ]]; then
    # Source storage
    FILES_TO_SYNC=$(sudo sshpass -p "${RSYNC_PASSWORD}" rsync -avh --stats --exclude "*.id" --dry-run "${SOURCE_PATH}"/ "${BACKUP_PATH}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3)=="Number of created files:"){print $(i+4)}}' | sed s/,//g)

#     elif [ "${SOURCE_MODE}" = "NEW_SOURCE_DEFINITION" ];
#     then
#         FILES_TO_SYNC=...

elif [ "${SOURCE_MODE}" = "camera" ]; then
    # Source camera
    sudo mkdir -p "${BACKUP_PATH}"
    cd "${BACKUP_PATH}"
    FILES_TO_SYNC=$(sudo gphoto2 --list-files | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+3) " " $(i+4) " " $(i+5)=="There are files in folder"){print $(i+2)}}' | sed s/,//g)
    cd
else
    # no defined mode selected
    lcd_message "No valid" "source" "mode defined" "2"
    exit 1
fi

# END
source "${WORKING_DIR}/status-display.sh" &
PID=$!

##############
# RUN BACKUP #
##############

# START

# To define a new method, add an elif block (example below)

if [[ " storage ios internal " =~ " ${SOURCE_MODE} " ]]; then
    # If source is storage or ios

    if [ ${DEST_MODE} = "server" ]; then
        if [ $LOG = true ]; then
            SYNC_OUTPUT=$(sudo sshpass -p "${RSYNC_PASSWORD}" rsync -avh --rsync-path="mkdir -p ${RSYNC_PATH} && rsync" --stats --exclude "*.id" --log-file="${LogFileSync}" "$SOURCE_PATH"/ "$BACKUP_PATH") || true
        else
            SYNC_OUTPUT=$(sudo sshpass -p "${RSYNC_PASSWORD}" rsync -avh --rsync-path="mkdir -p ${RSYNC_PATH} && rsync" --stats --exclude "*.id" "$SOURCE_PATH"/ "$BACKUP_PATH") || true
        fi
    else
        sudo mkdir -p "${BACKUP_PATH}"
        if [ $LOG = true ]; then
            SYNC_OUTPUT=$(sudo rsync -avh --stats --exclude "*.id" --log-file="${LogFileSync}" "$SOURCE_PATH"/ "$BACKUP_PATH")
        else
            SYNC_OUTPUT=$(sudo rsync -avh --stats --exclude "*.id" "$SOURCE_PATH"/ "$BACKUP_PATH")
        fi
    fi

#     elif [ "${SOURCE_MODE}" = "NEW_SOURCE_DEFINITION" ];
#     then
#     if [ $LOG = true ]; then
#         SYNC_OUTPUT=$(...)
#     else
#         SYNC_OUTPUT=$(...)

elif [ "${SOURCE_MODE}" = "camera" ]; then
    # If source is camera
    # Switch to STORAGE_MOUNT_POINT and transfer files from the camera
    sudo mkdir -p "${BACKUP_PATH}"
    cd "${BACKUP_PATH}"
    if [ $LOG = true ]; then
        SYNC_OUTPUT=$(sudo gphoto2 --filename "%F/%f.%C" --get-all-files --skip-existing --list-files --debug-logfile "${LogFileSync}")
    else
        SYNC_OUTPUT=$(sudo gphoto2 --filename "%F/%f.%C" --get-all-files --skip-existing --list-files)
    fi
    cd
else
    # no defined mode selected
    lcd_message "No valid" "source" "mode defined" "3"
    exit 1
fi

# END

# Display progress after finish
if [ $DISP = true ]; then
    sleep 5
fi

# Kill the status-display.sh script
kill $PID

# Check for lost devices
SYNC_ERROR=""
for MOUNTED_DEVICE in "${MOUNTED_DEVICES[@]}"
do
    RESULT_DEVICE_MOUNTED=$(device_mounted "${MOUNTED_DEVICE}")
    if [ -z "${RESULT_DEVICE_MOUNTED}" ]; then
        SYNC_ERROR="Err.Lost device!"
        log_to_file "Lost device '${MOUNTED_DEVICE}': '${RESULT_DEVICE_MOUNTED}'"
    fi
done

# Check internet connection and send
# a notification if the NOTIFY option is enabled
check=$(wget -q --spider http://google.com/)
if [ $NOTIFY = true ] || [ ! -z "$check" ]; then

    if [ ! -z "${SYNC_ERROR}" ]; then
        SUBJ_MSG="${SYNC_ERROR}"
        BODY_MSG="${SYNC_ERROR}\n\n"
    else
        SUBJ_MSG="complete"
        BODY_MSG=""
    fi

    send_email "Little Backup Box: Backup ${SUBJ_MSG}" "${BODY_MSG}Type: ${SOURCE_MODE} to ${DEST_MODE}\n${SOURCE_IDENTIFIER}\n\nBackup log:\n\n${SYNC_OUTPUT}"
fi

# Power off
if [ -z "${SYNC_ERROR}" ]; then
    MESSAGE="Backup complete."
else
    MESSAGE="${SYNC_ERROR}"
fi

source "${WORKING_DIR}/poweroff.sh" "poweroff" "" "${MESSAGE}"
