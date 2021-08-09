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

# library expects from calling script:
# - source config.cfg
# - source lib-log.sh

function mount_device () {
# mounts the device, if WAIT_FOR_MOUNT=true, waits until the device is available
# returns uuid ("ios" for iOS) or false (if not mounted), "na" for not available
# checks and remounts all by UUID_USBX given devices

# Takes up to 2 arguments
# DEVICE: one of ("usb_1" "usb_2" "ios")
# WAIT_FOR_MOUNT: true or false
# [ DEVICE = "usb_2" ]: UUID_USB_1 of mounted usb_1

# call for USB: mount_device MOUNT_DEVICE WAIT_FOR_MOUNT UUID_USB_1 UUID_USB_2 # example: mount_device usb_1 true "" ""
# call for

    # Arguments
    MOUNT_DEVICE="${1}"
    WAIT_FOR_MOUNT=${2}

    if [ "${MOUNT_DEVICE}" = "usb_1" ] || [ "${MOUNT_DEVICE}" = "usb_2" ]; then
        UUID_USB_1="${3}"
        UUID_USB_2="${4}"
    fi

    # Definitions
    local SUCCESS=true
    local RESULT=""
    local USB_DEVICES=()
    local USB_DEVICE=""
    local USB_DEVICE_LUM=""
    local USB_DEVICE_LUM_ALPHA=""
    local USB_1_LUM=""
    local USB_1_LUM_ALPHA=""
    local RETRY_TO_MOUNT=true

    # USB-Storage-devices
    if [ "${MOUNT_DEVICE}" = "usb_1" ] || [ "${MOUNT_DEVICE}" = "usb_2" ]; then
        # [ -z UUID_USB_1 ]: check for mountable devices and mount first one
        # check for mountable devices and mount the other (not UUID_USB_1)

        while [ ${RETRY_TO_MOUNT} = true ];
        do
            unset USB_DEVICES
            readarray USB_DEVICES <<< "$(sudo blkid | grep "^/dev/${STORAGE_DEV_MASK}" | awk '{print $1" "$2}')"

            # USB 1
            if [ "${MOUNT_DEVICE}" = "usb_1" ] && [ -z "${UUID_USB_1}" ]; then
                for USB_DEVICE in "${USB_DEVICES[@]}"
                do
                    USB_DEVICE_LUM=$(echo ${USB_DEVICE} | awk '{print $1}')
                    USB_DEVICE_LUM=${USB_DEVICE_LUM//\:}

                    USB_DEVICE_UUID=$(echo ${USB_DEVICE} | awk '{print $2}' | awk -F[\"] '{print $2}')
                    USB_DEVICE_UUID=${USB_DEVICE_UUID//[$'\t\r\n ']}

                    if [ -z "${UUID_USB_1}" ] && [ ! "${USB_DEVICE_UUID}" = "${UUID_USB_2}" ]; then
                        UUID_USB_1=${USB_DEVICE_UUID}
                        log_to_file "UUID_USB_1=${UUID_USB_1} (${USB_DEVICE_LUM})"

                    fi
                done
            fi

            # USB 2
            if [ "${MOUNT_DEVICE}" = "usb_2" ] && [ -z "${UUID_USB_2}" ]; then
                #get USB_1_LUM_ALPHA to prevent mounting another partition on the same drive
                if [ ! -z "${UUID_USB_1}" ]; then
                    USB_1_LUM="$(sudo blkid | grep "${UUID_USB_1}" | awk '{print $1}')"
                    USB_1_LUM_ALPHA=${USB_1_LUM//[0-9]}
                    USB_1_LUM_ALPHA=${USB_1_LUM_ALPHA//\:}
                fi

                for USB_DEVICE in "${USB_DEVICES[@]}"
                do
                    USB_DEVICE_LUM=$(echo ${USB_DEVICE} | awk '{print $1}')
                    USB_DEVICE_LUM_ALPHA=${USB_DEVICE_LUM//[0-9]}
                    USB_DEVICE_LUM_ALPHA=${USB_DEVICE_LUM_ALPHA//\:}

                    USB_DEVICE_UUID=$(echo ${USB_DEVICE} | awk '{print $2}' | awk -F[\"] '{print $2}')
                    USB_DEVICE_UUID=${USB_DEVICE_UUID//[$'\t\r\n ']}

                    if [ -z "${UUID_USB_2}" ] && [ ! "${USB_DEVICE_UUID}" = "${UUID_USB_1}" ] && [ ! "${USB_DEVICE_LUM_ALPHA}" = "${USB_1_LUM_ALPHA}" ]; then
                        UUID_USB_2=${USB_DEVICE_UUID}
                        log_to_file "UUID_USB_2=${UUID_USB_2}"
                    fi
                done
            fi

            # Job done?
            if [ "${MOUNT_DEVICE}" = "usb_1" ] && [ ! -z "${UUID_USB_1}" ]; then
                RETRY_TO_MOUNT=false
            fi

            if [ "${MOUNT_DEVICE}" = "usb_2" ] && [ ! -z "${UUID_USB_2}" ]; then
                RETRY_TO_MOUNT=false
            fi

            # retry? (if job is not done)
            if [ ${WAIT_FOR_MOUNT} = false ]; then
                RETRY_TO_MOUNT=false
            fi

            if [ ${RETRY_TO_MOUNT} = true ]; then
                sleep 1
            fi
        done

        # (re-) mount all known USB-devices
        if [ ! -z "${UUID_USB_1}" ]; then
            if [ -z "$(device_mounted usb_1)" ]; then
                sudo mount --uuid ${UUID_USB_1} "${STORAGE_MOUNT_POINT}"
                log_to_file "mounted USB_1"
            fi
        fi

        if [ ! -z "${UUID_USB_2}" ]; then
            if [ -z "$(device_mounted usb_2)" ]; then
                sudo mount --uuid ${UUID_USB_2} "${SOURCE_MOUNT_POINT}"
                log_to_file "mounted USB_2"
            fi
        fi

        # check finally for success
        if [ ! -z "${UUID_USB_1}" ]; then
            if [ -z "$(device_mounted usb_1)" ]; then SUCCESS=false; fi
        fi
        if [ ! -z "${UUID_USB_2}" ]; then
            if [ -z "$(device_mounted usb_2)" ]; then SUCCESS=false; fi
        fi

        # Result
        if [ $SUCCESS ]; then
            if [ "${MOUNT_DEVICE}" = "usb_1" ]; then
                RESULT=${UUID_USB_1}
            fi
            if [ "${MOUNT_DEVICE}" = "usb_2" ]; then
                RESULT=${UUID_USB_2}
            fi
        fi
    fi

    echo ${RESULT}
}

function device_mounted () {
# returns true, if device is mounted
# Takes 1 argument
# SEARCH_FOR: one of ("usb_1" "usb_2") or mount-point or UUID
# returns uuid ("ios" for iOS) or false (if not mounted), "na" for not available

    # Arguments
    local SEARCH_FOR="${1}"

    # Definitions
    local RESULT=""

    if [ "${SEARCH_FOR}" = "usb_1" ]; then
        SEARCH_FOR="${STORAGE_MOUNT_POINT}"
    elif [ "${SEARCH_FOR}" = "usb_2" ]; then
        SEARCH_FOR="${SOURCE_MOUNT_POINT}"
    elif [ "${SEARCH_FOR}" = "ios" ]; then
        SEARCH_FOR="${IOS_MOUNT_POINT}"
    fi

    if [ ! -z "${SEARCH_FOR}" ]; then
        RESULT=$(sudo lsblk -o +UUID | grep "${SEARCH_FOR}" | awk '{print $8}')
    fi

    echo ${RESULT}
}

function umount_device () {
# Takes 1 argument
# UMOUNT: one of ("usb_1" "usb_2") or mount-point or UUID
# returns uuid ("ios" for iOS) or false (if not mounted), "na" for not available

    # Arguments
    local DEVICE="${1}"

    # Definitions
    local RESULT=""

    if [ ! -z "$(device_mounted ${DEVICE})" ]; then

        UMOUNT=""
        if [ "${DEVICE}" = "usb_1" ]; then
            UMOUNT="${STORAGE_MOUNT_POINT}"
        elif [ "${DEVICE}" = "usb_2" ]; then
            UMOUNT="${SOURCE_MOUNT_POINT}"
        elif [ "${DEVICE}" = "ios" ]; then
            UMOUNT="${IOS_MOUNT_POINT}"
        fi

        if [ ! -z "${UMOUNT}" ]; then
            RESULT=$(sudo umount "${UMOUNT}")
        fi
    fi

    echo ${RESULT}
}

