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
# [ DEVICE = "usb_2" ]: DEVICE_IDENT_1 of mounted usb_1

# call for USB: mount_device MOUNT_DEVICE WAIT_FOR_MOUNT DEVICE_IDENT_1 DEVICE_IDENT_2 # example: mount_device usb_1 true "" ""
# call for

    # Arguments
    MOUNT_DEVICE="${1}"
    WAIT_FOR_MOUNT=${2}

    if [ "${MOUNT_DEVICE}" = "usb_1" ] || [ "${MOUNT_DEVICE}" = "usb_2" ]; then
        DEVICE_IDENT_1="${3}"
        DEVICE_IDENT_2="${4}"
    fi

    # Definitions
    local DEVICE_IDENT=""
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
        # [ -z DEVICE_IDENT_1 ]: check for mountable devices and mount first one
        # check for mountable devices and mount the other (not DEVICE_IDENT_1)

        while [ ${RETRY_TO_MOUNT} = true ];
        do
            unset USB_DEVICES
            readarray USB_DEVICES <<< "$(sudo blkid | grep "^/dev/${STORAGE_DEV_MASK}")"

            # USB 1
            if [ "${MOUNT_DEVICE}" = "usb_1" ] && [ -z "${DEVICE_IDENT_1}" ]; then
                for USB_DEVICE in "${USB_DEVICES[@]}"
                do
                    USB_DEVICE_LUM=$(echo ${USB_DEVICE} | awk '{print $1}' | sed 's/[^0-9a-z\/]*//g')

                    USB_DEVICE_UUID=$(echo ${USB_DEVICE} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^UUID=" | cut -d'"' -f 2) #################XXX

                    if [ -z "${USB_DEVICE_UUID}" ]; then
                        DEVICE_IDENT="${USB_DEVICE_LUM}"
                    else
                        DEVICE_IDENT="--uuid ${USB_DEVICE_UUID}"
                    fi

                    if [ -z "${DEVICE_IDENT_1}" ] && [ ! "${DEVICE_IDENT}" = "${DEVICE_IDENT_2}" ]; then
                        DEVICE_IDENT_1=${DEVICE_IDENT}
                        log_to_file "DEVICE_IDENT_1=${DEVICE_IDENT_1} (${USB_DEVICE_LUM}) prepared to mount"

                    fi
                done
            fi

            # USB 2
            if [ "${MOUNT_DEVICE}" = "usb_2" ] && [ -z "${DEVICE_IDENT_2}" ]; then
                #get USB_1_LUM_ALPHA to prevent mounting another partition on the same drive
                if [ ! -z "${DEVICE_IDENT_1}" ]; then
                    USB_1_LUM="$(sudo blkid | grep "'${DEVICE_IDENT_1/ --uuid/}'" | awk '{print $1}' | sed 's/[^0-9a-z\/]*//g'))"
                    USB_1_LUM_ALPHA=${USB_1_LUM//[0-9]}
                fi

                for USB_DEVICE in "${USB_DEVICES[@]}"
                do

                    USB_DEVICE_LUM=$(echo ${USB_DEVICE} | awk '{print $1}' | sed 's/[^0-9a-z\/]*//g')
                    USB_DEVICE_LUM_ALPHA=${USB_DEVICE_LUM//[0-9]}

                    USB_DEVICE_UUID=$(echo ${USB_DEVICE} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^UUID=" | cut -d'"' -f 2) #################XXX

                    if [ -z "${USB_DEVICE_UUID}" ]; then
                        DEVICE_IDENT="${USB_DEVICE_LUM}"
                    else
                        DEVICE_IDENT="--uuid ${USB_DEVICE_UUID}"
                    fi

                    if [ -z "${DEVICE_IDENT_2}" ] && [ ! "${DEVICE_IDENT_1}" = "${DEVICE_IDENT}" ] && [ ! "${USB_DEVICE_LUM_ALPHA}" = "${USB_1_LUM_ALPHA}" ]; then
                        DEVICE_IDENT_2=${DEVICE_IDENT}
                        log_to_file "DEVICE_IDENT_2=${DEVICE_IDENT_2} prepared to mount"
                    fi
                done
            fi

            # Job done?
            if [ "${MOUNT_DEVICE}" = "usb_1" ] && [ ! -z "${DEVICE_IDENT_1}" ]; then
                RETRY_TO_MOUNT=false
            fi

            if [ "${MOUNT_DEVICE}" = "usb_2" ] && [ ! -z "${DEVICE_IDENT_2}" ]; then
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
        if [ ! -z "${DEVICE_IDENT_1}" ]; then
            if [ -z "$(device_mounted usb_1)" ]; then
                sleep 1 # wait for stabilisation after plug in
                RET=$(sudo mount ${DEVICE_IDENT_1} "${STORAGE_MOUNT_POINT}")
                log_to_file "mounted USB_1 ${DEVICE_IDENT_1} > ${STORAGE_MOUNT_POINT}: '${RET}'"
            fi
        fi

        if [ ! -z "${DEVICE_IDENT_2}" ]; then

            if [ -z "$(device_mounted usb_2)" ]; then
                sleep 1 # wait for stabilisation after plug in
                RET=$(sudo mount ${DEVICE_IDENT_2} "${SOURCE_MOUNT_POINT}")
                log_to_file "mounted USB_2 ${DEVICE_IDENT_2} > ${SOURCE_MOUNT_POINT}: '${RET}'"
            fi
        fi

        # check finally for success
        if [ ! -z "${DEVICE_IDENT_1}" ]; then
            if [ -z "$(device_mounted usb_1)" ]; then SUCCESS=false; fi
        fi
        if [ ! -z "${DEVICE_IDENT_2}" ]; then
            if [ -z "$(device_mounted usb_2)" ]; then SUCCESS=false; fi
        fi

        # Result
        if [ $SUCCESS ]; then
            if [ "${MOUNT_DEVICE}" = "usb_1" ]; then
                RESULT=${DEVICE_IDENT_1}
            fi
            if [ "${MOUNT_DEVICE}" = "usb_2" ]; then
                RESULT=${DEVICE_IDENT_2}
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
    local SEARCH_FOR=${1}
    SEARCH_FOR="${SEARCH_FOR/ --uuid/}"
    SEARCH_FOR=$(echo "${SEARCH_FOR}" | sed 's/--uuid//g' | sed -e 's/^[[:space:]]*//')

    # Definitions
    local RESULT=""
    local DEVICE_IDENT=""
    local USB_DEVICE=""
    local USB_DEVICE_LUM=""
    local USB_DEVICE_UUID=""


    if [ "${SEARCH_FOR}" = "usb_1" ]; then
        SEARCH_FOR="${STORAGE_MOUNT_POINT}"
    elif [ "${SEARCH_FOR}" = "usb_2" ]; then
        SEARCH_FOR="${SOURCE_MOUNT_POINT}"
    elif [ "${SEARCH_FOR}" = "ios" ]; then
        SEARCH_FOR="${IOS_MOUNT_POINT}"
    fi

    if [ ! -z "${SEARCH_FOR}" ]; then

        USB_DEVICE="$(sudo blkid | grep "${SEARCH_FOR}")"

        if [ -z "${USB_DEVICE}" ]; then
            USB_DEVICE="$(sudo cat /proc/mounts | grep "${SEARCH_FOR}")"
        fi

        if [ ! -z "${USB_DEVICE}" ]; then
            USB_DEVICE_LUM=$(echo ${USB_DEVICE} | awk '{print $1}' | sed 's/[^0-9a-z\/]*//g')

            USB_DEVICE=$(sudo blkid | grep "^${USB_DEVICE_LUM}")

            USB_DEVICE_UUID=$(echo ${USB_DEVICE} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^UUID" | cut -d'"' -f 2)

            if [ -z "${USB_DEVICE_UUID}" ]; then
                DEVICE_IDENT="${USB_DEVICE_LUM}"
            else
                DEVICE_IDENT="--uuid ${USB_DEVICE_UUID}"
            fi

            if grep -q "^${USB_DEVICE_LUM}" "/proc/mounts"; then
                RESULT=${DEVICE_IDENT}
            fi
        fi
    fi

    log_to_file "mounted '${SEARCH_FOR}'? '${RESULT}'"

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

    log_to_file "umount '${DEVICE}'? '${RESULT}'"

    echo ${RESULT}
}

