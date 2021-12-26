#!/usr/bin/env bash

# Author: Stefan Saam github@saams.de

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
# - source constants.sh
# - source lib-log.sh

function mount_device() {
	# mounts the device, if WAIT_FOR_MOUNT=true, waits until the device is available
	# returns uuid ("ios" for iOS) or false (if not mounted), "na" for not available
	# checks and remounts all by UUID_USBX given devices

	# Takes up to 4 arguments
	# DEVICE: one of ("usb_1" "usb_2")
	# WAIT_FOR_MOUNT: true or false
	# DEVICE_IDENT_1 or DEVICE_IDENT_2 (optional) LUM or UUID-Identification (like returned from mount_device before or device_mounted)


	# call to mount USB: mount_device MOUNT_DEVICE WAIT_FOR_MOUNT DEVICE_IDENT_PRESET_1 DEVICE_IDENT_PRESET_2 # example: mount_device usb_1 true "" ""

	# Arguments
	MOUNT_DEVICE="${1}"
	WAIT_FOR_MOUNT=${2}

	if [ "${MOUNT_DEVICE}" = "usb_1" ] || [ "${MOUNT_DEVICE}" = "usb_2" ]; then
		DEVICE_IDENT_PRESET_1="${3}"
		DEVICE_IDENT_PRESET_2="${4}"
	fi

	log_message "mount_device: MOUNT_DEVICE=${MOUNT_DEVICE}; WAIT_FOR_MOUNT=${WAIT_FOR_MOUNT}; DEVICE_IDENT_PRESET_1=${DEVICE_IDENT_PRESET_1}; DEVICE_IDENT_PRESET_2=${DEVICE_IDENT_PRESET_2}" 3

	# Definitions
	local DEVICE_IDENT=""
	local DEVICE_IDENT_THIS=""
	local SUCCESS=true
	local RESULT=""
	local USB_DEVICES=()
	local USB_DEVICE=""
	local USB_DEVICE_LUM=""
	local USB_DEVICE_LUM_ALPHA=""
	local USB_DEVICE_OTHER_LUM_ALPHA=""
	local RETRY_TO_MOUNT=true

	local MOUNT_USER="www-data"
	local MOUNT_GROUP="www-data"
	local MOUNT_UID=$(id -u ${MOUNT_USER})
	local MOUNT_GID=$(id -g ${MOUNT_GROUP})

	if [ "${MOUNT_DEVICE}" = "usb_1" ]; then
		DEVICE_IDENT_PRESET_THIS="${DEVICE_IDENT_PRESET_1}"
		DEVICE_IDENT_PRESET_OTHER="${DEVICE_IDENT_PRESET_2}"
		MOUNT_POINT="${const_STORAGE_MOUNT_POINT}"
	fi
	if [ "${MOUNT_DEVICE}" = "usb_2" ]; then
		DEVICE_IDENT_PRESET_THIS="${DEVICE_IDENT_PRESET_2}"
		DEVICE_IDENT_PRESET_OTHER="${DEVICE_IDENT_PRESET_1}"
		MOUNT_POINT="${const_SOURCE_MOUNT_POINT}"
	fi

	# USB storage devices
	if [ "${MOUNT_DEVICE}" = "usb_1" ] || [ "${MOUNT_DEVICE}" = "usb_2" ]; then

		# identify device, wait for it if WAIT_FOR_MOUNT is true
		USB_DEVICES_OLD=""
		while [ ${RETRY_TO_MOUNT} = true ]; do

			# force to re-scan usb-devices
			sudo udevadm trigger

			# get all devices starting with "PATH=\"...
			unset USB_DEVICES
			readarray USB_DEVICES <<< "$(sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep "^PATH=\"/dev/${const_STORAGE_DEV_MASK}")"

			# log if list of devices changed
			if [ ! "${USB_DEVICES_OLD[*]}" == "${USB_DEVICES[*]}" ]; then
				log_exec "pre mount ${MOUNT_DEVICE}" "sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE" 3
			fi

			USB_DEVICES_OLD=${USB_DEVICES[@]}

			# find USB

				#get LUM-Alpha (like sda for sda1) to exclude another partition on the same device
				if [ ! -z "${DEVICE_IDENT_PRESET_OTHER}" ]; then
					USB_DEVICE_OTHER_LUM_ALPHA="$(sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID | grep "${DEVICE_IDENT_PRESET_OTHER/--uuid\ }\"" | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^PATH=" | cut -d'"' -f 2 | sed 's/[0-9]//g')"
				fi

				for USB_DEVICE in "${USB_DEVICES[@]}"; do

					USB_DEVICE_LUM=$(echo ${USB_DEVICE} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^PATH=" | cut -d'"' -f 2)
					USB_DEVICE_LUM_ALPHA=${USB_DEVICE_LUM//[0-9]/}

					USB_DEVICE_UUID=$(echo ${USB_DEVICE} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^UUID=" | cut -d'"' -f 2)

					USB_DEVICE_FSTYPE=$(echo ${USB_DEVICE} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^FSTYPE=" | cut -d'"' -f 2)

					if [ -z "${USB_DEVICE_UUID}" ]; then
						DEVICE_IDENT="${USB_DEVICE_LUM}"
					else
						DEVICE_IDENT="--uuid ${USB_DEVICE_UUID}"
					fi

					if [ -z "${DEVICE_IDENT_THIS}" ] && [ ! -z "${USB_DEVICE_FSTYPE}" ] && [ "${DEVICE_IDENT_PRESET_OTHER}" != "${DEVICE_IDENT}" ] && [ "${USB_DEVICE_LUM_ALPHA}" != "${USB_DEVICE_OTHER_LUM_ALPHA}" ]; then
						if [ -z "${DEVICE_IDENT_PRESET_THIS}" ] || [ "${DEVICE_IDENT}" = "${DEVICE_IDENT_PRESET_THIS}" ]; then
							DEVICE_IDENT_THIS=${DEVICE_IDENT}
							log_message "DEVICE_IDENT_THIS='${DEVICE_IDENT_THIS}' ('${USB_DEVICE_LUM}') prepared to mount at '${MOUNT_POINT}'"
						fi
					fi
				done

			# Check if device is identified
			if [ ! -z "${DEVICE_IDENT_THIS}" ]; then
				# success: device to mount identified
				RETRY_TO_MOUNT=false
			fi

			# Retry if mount is not done
			if [ ${WAIT_FOR_MOUNT} = false ]; then
				# only one try
				RETRY_TO_MOUNT=false
			fi

			if [ ${RETRY_TO_MOUNT} = true ]; then
				sleep 1
			fi

		done

		# Mount USB device
		if [ ! -z "${DEVICE_IDENT_THIS}" ]; then
			if [ -z "$(device_mounted "${MOUNT_DEVICE}")" ]; then
				# device not mounted

				# clean mountpoint
				sudo rm -R "${MOUNT_POINT}"/*  > /dev/null 2>&1

				sleep 1
				RET=$(sudo mount ${DEVICE_IDENT_THIS} "${MOUNT_POINT}" -o umask=0 2>&1)
				log_message "mounted ${MOUNT_DEVICE} '${DEVICE_IDENT_THIS}' at '${MOUNT_POINT}': Msg.='${RET}'" 2
			else
				log_message "${MOUNT_DEVICE} already mounted, nothing to do." 3
			fi

			# Check finally for success

			if [ -z "$(device_mounted "${MOUNT_DEVICE}")" ]; then
				SUCCESS=false
			fi

			# Result
			if [ $SUCCESS ]; then
				RESULT=${DEVICE_IDENT_THIS}
			fi
		fi
	fi

	log_message "mount_device RESULT=${RESULT}"
	echo ${RESULT}
}

function device_mounted() {
	# returns true, if device is mounted
	# Takes 1 argument
	# SEARCH_FOR: one of ("usb_1" "usb_2") or mount-point or UUID
	# returns uuid ("ios" for iOS) or false (if not mounted), "na" for not available

	# Arguments
	local SEARCH_FOR=${1}

	SEARCH_FOR=$(echo "${SEARCH_FOR}" | sed 's/--uuid//g' | sed -e 's/^[[:space:]]*//')

	# Definitions
	local RESULT=""
	local DEVICE_IDENT=""
	local USB_DEVICE=""
	local USB_DEVICE_LUM=""
	local USB_DEVICE_UUID=""

	if [ "${SEARCH_FOR}" = "usb_1" ]; then
		SEARCH_FOR="${const_STORAGE_MOUNT_POINT}"
	elif [ "${SEARCH_FOR}" = "usb_2" ]; then
		SEARCH_FOR="${const_SOURCE_MOUNT_POINT}"
	elif [ "${SEARCH_FOR}" = "ios" ]; then
		SEARCH_FOR="${const_IOS_MOUNT_POINT}"
	fi

	if [ ! -z "${SEARCH_FOR}" ]; then

		USB_DEVICE_MOUNTINFO="$(sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID | grep "${SEARCH_FOR}\"")"

		if [ ! -z "${USB_DEVICE_MOUNTINFO}" ]; then
			USB_DEVICE_LUM=$(echo ${USB_DEVICE_MOUNTINFO} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^PATH=" | cut -d'"' -f 2)
			USB_DEVICE_UUID=$(echo ${USB_DEVICE_MOUNTINFO} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^UUID=" | cut -d'"' -f 2)
			USB_DEVICE_MOUNTPOINT=$(echo ${USB_DEVICE_MOUNTINFO} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^MOUNTPOINT=" | cut -d'"' -f 2)

			if [ -z "${USB_DEVICE_UUID}" ]; then
				DEVICE_IDENT="${USB_DEVICE_LUM}"
			else
				DEVICE_IDENT="--uuid ${USB_DEVICE_UUID}"
			fi

			if [ -z "${USB_DEVICE_MOUNTPOINT}" ]; then
				RESULT=""
			else
				RESULT="${DEVICE_IDENT}"
			fi
		fi
	fi

	log_message "device_mounted?: '${SEARCH_FOR}' = '${RESULT}'" 2

	echo ${RESULT}
}

function umount_device() {
	# Takes 1 argument
	# UMOUNT: one of ("usb_1" "usb_2") or mount-point or UUID
	# returns uuid ("ios" for iOS) or false (if not mounted), "na" for not available

	# Arguments
	local DEVICE="${1}"

	# Definitions
	local RESULT=""

	UMOUNT="${DEVICE}"
	if [ "${DEVICE}" = "usb_1" ]; then
		UMOUNT="${const_STORAGE_MOUNT_POINT}"
	elif [ "${DEVICE}" = "usb_2" ]; then
		UMOUNT="${const_SOURCE_MOUNT_POINT}"
	elif [ "${DEVICE}" = "ios" ]; then
		UMOUNT="${const_IOS_MOUNT_POINT}"
	fi

	if [ ! -z "${UMOUNT}" ]; then
		RESULT=$(sudo umount "${UMOUNT}")
	fi


	log_message "umount_device: '${DEVICE}'? '${RESULT}'" 2

	echo ${RESULT}
}

function get_storage_spaces() {
    local DEVICE=$1

    local storsize=$(df "${DEVICE}" -h --output=size | sed '1d' | tr -d ' ')
    local storused=$(df "${DEVICE}" -h --output=pcent | sed '1d' | tr -d ' ')
    local storfree=$(df "${DEVICE}" -h --output=avail | sed '1d' | tr -d ' ')

    echo "${storsize}|${storused}|${storfree}"
}
