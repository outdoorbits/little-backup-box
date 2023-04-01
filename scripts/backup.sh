#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de

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


# usage: backup.sh SOURCE TARGET [SECONDARY_BACKUP_FOLLOWS]
#			SOURCE: Can be usb *1, internal, camera or ios; database, thumbnails or exif (only in combination with usb or internal)
# 			TARGET: Can be usb *2, internal, rsyncserver or cloud_???
#			SECONDARY_BACKUP_FOLLOWS: otionally, if true another run follows, no power off
#	*1 formerly storage
#	*2 formerly external


WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Config
MOUNTED_DEVICES=()


CLOUDSERVICE=""

#####################################
# SOURCE AND DESTINATION DEFINTIONS #
#####################################

# START

# Get arguments
SOURCE_ARG="${1}"
TARGET_ARG="${2}"

GENERATE_THUMBNAILS="${3}" #  ['true'|'false'|empty]
UPDATE_EXIF="${4}" #  ['true'|'false'|empty]
POWER_OFF="${5}" #  ['true'|'false'|empty]

SECONDARY_BACKUP_FOLLOWS="${6}" #  ['true'|'false'|empty]

if [ "${GENERATE_THUMBNAILS}" = "" ]; then GENERATE_THUMBNAILS="${conf_BACKUP_GENERATE_THUMBNAILS}"; fi

if [ "${UPDATE_EXIF}" = "" ]; then UPDATE_EXIF="${conf_BACKUP_UPDATE_EXIF}"; fi

if [ "${POWER_OFF}" = "true" ]; then POWER_OFF_FORCE="force"; fi # force power off, if set by argument
if [ "${POWER_OFF}" = "" ]; then POWER_OFF="${conf_POWER_OFF}"; fi

# if SECONDARY_BACKUP_FOLLOWS = true: no power off
if [ "${SECONDARY_BACKUP_FOLLOWS}" != "true" ]; then
	SECONDARY_BACKUP_FOLLOWS="false"
fi

# Source definition
if [[ " usb internal camera ios thumbnails database exif " =~ " ${SOURCE_ARG} " ]]; then
	SOURCE_MODE="${SOURCE_ARG}"
else
	SOURCE_MODE="usb"
fi

# Target definition
if [[ "${TARGET_ARG}" =~ ^cloud_.* ]]; then
	TARGET_MODE="cloud"
	CLOUDSERVICE=${TARGET_ARG#"cloud_"}
	if [ -z "${CLOUDSERVICE}" ]; then
		TARGET_MODE=""
	fi
else
	if [[ " usb internal rsyncserver " =~ " ${TARGET_ARG} " ]]; then
		TARGET_MODE="${TARGET_ARG}"
	else
		TARGET_MODE="usb"
	fi
fi

if [ "${SOURCE_MODE}" = "${TARGET_MODE}" ] && [ "${SOURCE_MODE}" != "usb" ] && [ "${TARGET_MODE}:${SOURCE_MODE}" != "none:none" ]; then
	disp_message "$(l 'box_backup_invalid_mode_combination_1')" "$(l 'box_backup_invalid_mode_combination_2')" "$(l 'box_backup_invalid_mode_combination_3')"
	TARGET_MODE='none'
	SOURCE_MODE='none'
fi

# switch UUID_USB_1 and UUID_USB_2? (only relevant in secondary backup)
if [ ! -z "${UUID_USB_1}" ] && [[ " rsyncserver cloud " =~ " ${TARGET_MODE} " ]]; then
	# use target-usb of the last run as source usb now
	UUID_USB_temp="${UUID_USB_1}"
	UUID_USB_1="${UUID_USB_2}"
	UUID_USB_2="${UUID_USB_temp}"
fi

#load time library
. "${WORKING_DIR}/lib-time.sh"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load Mail library
. "${WORKING_DIR}/lib-mail.sh"

# Load DISPLAY library
. "${WORKING_DIR}/lib-display.sh"

#load DEVICES library
. "${WORKING_DIR}/lib-devices.sh"

#load CLOUDS library
. "${WORKING_DIR}/lib-clouds.sh"

#load Systeminfo library
. "${WORKING_DIR}/lib-systeminfo.sh"

#load language library
. "${WORKING_DIR}/lib-language.sh"

#load database library
. "${WORKING_DIR}/lib-db.sh"

#load network library
. "${WORKING_DIR}/lib-network.sh"

# log
disp_message "$(l "box_backup_mode_${SOURCE_MODE}")" " > $(l "box_backup_mode_${TARGET_MODE}")" "   ${CLOUDSERVICE}"

log_message "Source: ${SOURCE_MODE}"
log_message "Destination: ${TARGET_MODE} ${CLOUDSERVICE}"

function calculate_files_to_sync() {
	local FILES_TO_SYNC=0
	local FILES_TO_SYNC_PATH=0
	local SOURCE_PATH="${1}"
	local SOURCE_PATHS_ARRAY=()
	local i=0

	if [ -z "${SOURCE_PATH}" ]; then
		# copy SOURCE_PATHS into local SOURCE_PATHS_ARRAY
		for ((i=0; i < ${#SOURCE_PATHS[@]}; i++)); do
			SOURCE_PATHS_ARRAY+=(${SOURCE_PATHS[$i]})
		done
	else
		SOURCE_PATHS_ARRAY=("${SOURCE_PATH}")
	fi

	# To define a new method, add an elif block (example below)

	if [[ " usb internal ios " =~ " ${SOURCE_MODE} " ]]; then
		# Source usb ios internal

		for SOURCE_PATH in "${SOURCE_PATHS_ARRAY[@]}"; do

			if [ ${TARGET_MODE} = "rsyncserver" ]; then
				FILES_TO_SYNC_PATH=$(sudo sshpass -p "${conf_RSYNC_PASSWORD}" rsync -avh --stats --min-size=1 --exclude "*.id" --exclude "*tims/" --exclude "${const_IMAGE_DATABASE_FILENAME}" --dry-run "${SOURCE_PATH}"/ "${RSYNC_CONNECTION}/${BACKUP_PATH}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3) " " $(i+4)=="Number of regular files transferred:"){print $(i+5)}}' | sed s/,//g)
			else
				FILES_TO_SYNC_PATH=$(sudo rsync -avh --stats --min-size=1 --exclude "*.id" --exclude "*tims/" --exclude "${const_IMAGE_DATABASE_FILENAME}" --dry-run "${SOURCE_PATH}"/ "${BACKUP_PATH}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3) " " $(i+4)=="Number of regular files transferred:"){print $(i+5)}}' | sed s/,//g)
			fi

			if [ -z "${FILES_TO_SYNC_PATH}" ]; then
				FILES_TO_SYNC_PATH=0
			fi

			log_message "Files to sync from folder '${SOURCE_PATH}': ${FILES_TO_SYNC_PATH}"

			FILES_TO_SYNC=$(( ${FILES_TO_SYNC} + ${FILES_TO_SYNC_PATH} ))
		done

	#     elif [ "${SOURCE_MODE}" = "NEW_SOURCE_DEFINITION" ];
	#     then
	#         FILES_TO_SYNC=...

	elif [ "${SOURCE_MODE}" = "camera" ]; then
		# Source camera
		sudo mkdir -p "${BACKUP_PATH}"
		cd "${BACKUP_PATH}"

		for SOURCE_PATH in "${SOURCE_PATHS_ARRAY[@]}"; do
			GPHOTO=$(sudo gphoto2 --list-files --folder "${SOURCE_PATH}")
			log_message "gphoto2 --list-files --folder \"${SOURCE_PATH}\":\nexitcode=$?\n${GPHOTO}" 3

			FILES_TO_SYNC_PATH=$(echo "${GPHOTO}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+3) " " $(i+4) " " $(i+5)=="There are files in folder" || $i " " $(i+1) " " $(i+3) " " $(i+4) " " $(i+5)=="There is file in folder"){SUM+=$(i+2);}} END {print SUM}')

			if [ -z "${FILES_TO_SYNC_PATH}" ]; then
				FILES_TO_SYNC_PATH=0
			fi

			log_message "Files in folder '${SOURCE_PATH}': ${FILES_TO_SYNC_PATH}"

			FILES_TO_SYNC=$(( ${FILES_TO_SYNC} + ${FILES_TO_SYNC_PATH} ))
		done

		cd

	elif [ "${SOURCE_MODE}" = "thumbnails" ]; then
		echo "" # dummy action
	elif [ "${SOURCE_MODE}" = "database" ]; then
		echo "" # dummy action
	elif [ "${SOURCE_MODE}" = "exif" ]; then
		echo "" # dummy action
	else
		# no defined mode selected
		if [ "${TARGET_MODE}:${SOURCE_MODE}" != "none:none" ]; then
			disp_message "s=b:$(l 'box_backup_no_valid_source_mode_1')" "s=b:$(l 'box_backup_no_valid_source_mode_2')" "s=b:$(l 'box_backup_no_valid_source_mode_3')" "s=b:1"
			TARGET_MODE='none'
			SOURCE_MODE='none'
		fi
	fi

	if [ -z "${FILES_TO_SYNC}" ]; then
		FILES_TO_SYNC="0"
	fi

	echo "${FILES_TO_SYNC}"
}

function progressmonitor() {
	# usage: progressmonitor "${START_TIME}" "${PRGMON_ABS_COUNT}" "${PRGMON_PRG_COUNT}" "${LCD1}" "${LCD2}" "${SPEED}"

	PRGMON_START_TIME="${1}"
	PRGMON_ABS_COUNT="${2}"
	PRGMON_PRG_COUNT="${3}"
	PRGMON_LCD1="${4}"
	PRGMON_LCD2="${5}"
	PRGMON_SPEED="${6}"

	if [ -z "${PRGMON_LAST_MESSAGE_TIME}" ]; then
		PRGMON_LAST_MESSAGE_TIME=0
	fi

	if [ "${PRGMON_SPEED}" = "" ] || [ "${PRGMON_SPEED}" = "0.00kB/s" ]; then
		PRGMON_SPEED=""
	else
		PRGMON_SPEED=", ${PRGMON_SPEED}"
	fi
	PRGMON_LCD3="${PRGMON_PRG_COUNT} $(l 'box_backup_of') ${PRGMON_ABS_COUNT}${PRGMON_SPEED}"

	# calculate progress
	if [ "${PRGMON_ABS_COUNT}" -gt "0" ]; then
		if [ "${PRGMON_PRG_COUNT}" -gt "0" ]; then
			PRGMON_FINISHED_PERCENT=$(echo "scale=1; 100 * ${PRGMON_PRG_COUNT} / ${PRGMON_ABS_COUNT}" | bc)
			PRGMON_LCD5="PGBAR=${PRGMON_FINISHED_PERCENT}"
		else
			PRGMON_LCD5="$(l 'box_backup_checking_old_files')..."
		fi
	else
		PRGMON_FINISHED_PERCENT="?"
		PRGMON_LCD5="PGBAR=0"
	fi

	if ([ "${PRGMON_PRG_COUNT}" != "${PRGMON_PRG_COUNT_OLD}" ] || [ "${PRGMON_LCD1}" != "${PRGMON_LCD1_OLD}" ]) && ([ $(($(get_uptime_seconds) - ${PRGMON_LAST_MESSAGE_TIME})) -ge "${const_PROGRESS_DISPLAY_WAIT_SEC}" ] || [ "${PRGMON_PRG_COUNT}" == "0" ] || [ "${PRGMON_FINISHED_PERCENT}" = "100.0" ]); then

		# calculte remaining time
		if [ "${PRGMON_PRG_COUNT}" -gt "0" ]; then
			PRGMON_TIME_RUN=$(echo "$(get_uptime_seconds) - ${PRGMON_START_TIME}" | bc)
			PRGMON_TIME_REMAINING=$(echo "${PRGMON_TIME_RUN} * ( ${PRGMON_ABS_COUNT} - ${PRGMON_PRG_COUNT} ) / ${PRGMON_PRG_COUNT}" | bc)
			PRGMON_TIME_REMAINING_FORMATED=$(date -d@${PRGMON_TIME_REMAINING} -u +%H:%M:%S)
			PRGMON_DAYS_LEFT=$((PRGMON_TIME_REMAINING/86400))
			if [ "${PRGMON_DAYS_LEFT}" -gt "0" ]; then
				PRGMON_TIME_REMAINING_FORMATED="${PRGMON_DAYS_LEFT}d ${PRGMON_TIME_REMAINING_FORMATED}"
			fi
		else
			PRGMON_PRG_COUNT="0"
			PRGMON_TIME_REMAINING_FORMATED="?"
		fi

		PRGMON_LCD4="$(l "box_backup_time_remaining"): ${PRGMON_TIME_REMAINING_FORMATED}"
		disp_message "set:clear" "s=hc:${PRGMON_LCD1}" "s=hc:${PRGMON_LCD2}" "s=hc:${PRGMON_LCD3}" "s=hc:${PRGMON_LCD4}" "s=hc:${PRGMON_LCD5}"

		PRGMON_LAST_MESSAGE_TIME=$(get_uptime_seconds)
	fi

	PRGMON_PRG_COUNT_OLD="${PRGMON_PRG_COUNT}"
	PRGMON_LCD1_OLD="${PRGMON_LCD1}"
}

function syncprogress() {
	local MODE="${1}"
	local SOURCE_FOLDER_NUMBER="${2}"

	local SPEED=""
	local START_TIME=$(get_uptime_seconds)
	local TIME_RUN=0
	local TIME_REMAINING=0
	local TIME_REMAINING_FORMATED=""
	local DAYS_LEFT=0

	local LCD1="$(l "box_backup_mode_${SOURCE_MODE}") ${SOURCE_FOLDER_NUMBER}" # header1
	local LCD2=" > $(l "box_backup_mode_${TARGET_MODE}") ${CLOUDSERVICE}" # header2
	local LCD3="0 $(l 'box_backup_of') ${FILES_TO_SYNC}" # filescount, speed
	local LCD4="$(l "box_backup_time_remaining"): ?" # time remaining
	local LCD5="PGBAR=0" # progressbar

	# start screen
	disp_message "set:clear" "s=hc:${LCD1}" "s=hc:${LCD2}" "s=hc:${LCD3}" "s=hc:${LCD4}" "s=hc:${LCD5}"

	local LAST_MESSAGE_TIME=0
	local FILESCOUNT=0
	local FINISHED_PERCENT="?"
	local FILENAME=""

	if [ "${MODE}" = "gphoto2" ]; then
		touch "${const_LOGFILE_SYNC}"
	fi

	while read PIPE; do

		if [ "${MODE}" = "rsync" ]; then
			PIPE="$(echo "${PIPE}" | tr -cd '[:alnum:]\/\%\ ._-' | sed 's/   */ /g')"
			if  [ "${PIPE:0:1}" = " " ] && [ ! -z "${FILENAME}" ]; then
				if [ -f "${SOURCE_PATH}/${FILENAME}" ]; then
					FILESCOUNT=$((FILESCOUNT+1))
					SPEED="$(echo "${PIPE}" | cut -d ' ' -f4)"

					FILENAME=""

				fi
			elif  [ "${PIPE:0:1}" != " " ]; then
				FILENAME="${PIPE}";
			fi

		elif [ "${MODE}" = "gphoto2" ]; then
			if [ "${PIPE:0:6}" = "Saving" ] || [ "${PIPE:0:4}" = "Skip" ]; then
				FILESCOUNT=$((FILESCOUNT+1))

				echo "${PIPE}" | tee -a "${const_LOGFILE_SYNC}"
			fi
		fi

		progressmonitor "${START_TIME}" "${FILES_TO_SYNC}" "${FILESCOUNT}" "${LCD1}" "${LCD2}" "${SPEED}"
	done

	# no need to check again earlier
	if [ $conf_DISP = true ]; then
		sleep "${conf_DISP_FRAME_TIME}"
	fi

}

function sync_return_code_decoder() {
	local mode="${1}"
	local code="${2}"

	ERROR_TEXT=()

	if [ -z "${code}" ]; then
		code=-1
		ERROR_TEXT[$code]="-"
	fi

	if [ "${mode}" == "camera" ]; then
		#gphoto2-codes
		ERROR_TEXT[1]="Error: No camera found."
	else
		#rsync-codes
		ERROR_TEXT[0]="Success"
		ERROR_TEXT[1]="Syntax or usage error"
		ERROR_TEXT[2]="Protocol incompatibility"
		ERROR_TEXT[3]="Errors selecting input/output files, dirs"
		ERROR_TEXT[4]="Requested action not supported: an attempt was made to manipulate 64-bit files on a platform that cannot support them or an option was specified that is supported by the client and not by the server."
		ERROR_TEXT[5]="Error starting client-server protocol"
		ERROR_TEXT[6]="Daemon unable to append to log-file"
		ERROR_TEXT[10]="Error in socket I/O"
		ERROR_TEXT[11]="Error in file I/O"
		ERROR_TEXT[12]="Error in rsync protocol data stream"
		ERROR_TEXT[13]="Errors with program diagnostics"
		ERROR_TEXT[14]="Error in IPC code"
		ERROR_TEXT[20]="Received SIGUSR1 or SIGINT"
		ERROR_TEXT[21]="Some error returned by waitpid()"
		ERROR_TEXT[22]="Error allocating core memory buffers"
		ERROR_TEXT[23]="Partial transfer due to error"
		ERROR_TEXT[24]="Partial transfer due to vanished source files"
		ERROR_TEXT[25]="The --max-delete limit stopped deletions"
		ERROR_TEXT[30]="Timeout in data send/receive"
		ERROR_TEXT[35]="Timeout waiting for daemon connection"
	fi

	if [ ! -z "${ERROR_TEXT[$code]}" ]; then
		echo "$(l 'box_backup_error_code') ${code}, ${ERROR_TEXT[$code]}"
	else
		echo "$code"
	fi
}

# Set the ACT LED to heartbeat
	sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

#########################
# VPN START             #
#########################

	#stop old VPN connections
	if [ $(vpn_status "OpenVPN") = "up" ] || [ $(vpn_status "WireGuard" "${const_VPN_DIR_WireGuard}/$const_VPN_FILENAME_WireGuard") = "up" ]; then
		disp_message ":$(l 'box_backup_vpn_disconnecting')"
		vpn_stop "OpenVPN"
		vpn_stop "WireGuard" "${const_VPN_DIR_WireGuard}/$const_VPN_FILENAME_WireGuard"
	fi

	#start VPN
	VPN_TYPE="none"
	if [ "${TARGET_MODE}" = "rsyncserver"  ]; then VPN_TYPE=${conf_VPN_TYPE_RSYNC}; fi
	if [[ " ${TARGET_MODE} " =~ " cloud "  ]]; then VPN_TYPE=${conf_VPN_TYPE_CLOUD}; fi

	if [ "${VPN_TYPE}" != "none" ]; then
		VPN_CONFIG_FILE=$(eval echo "\${const_VPN_DIR_${VPN_TYPE}}/\$const_VPN_FILENAME_${VPN_TYPE}")

		disp_message ":$(l 'box_backup_vpn_connecting')" ":${VPN_TYPE}"
		vpn_start "${VPN_TYPE}" "${VPN_CONFIG_FILE}" "${conf_VPN_TIMEOUT}"
		VPN_CONNECTED=$?

		if [ "${VPN_CONNECTED}" != "1" ]; then
			disp_message ":$(l 'box_backup_vpn_connecting_failed')"
			TARGET_MODE='none'
			SOURCE_MODE='none'
		else
			disp_message ":$(l 'box_backup_vpn_connecting_success')" ":${VPN_TYPE}"
			sudo "${WORKING_DIR}/cron-ip.sh" 'force'
		fi

	fi


#########################
# Unmount devices       #
#########################

	umount_device "usb_1"
	umount_device "usb_2"
	umount_device "${const_IOS_MOUNT_POINT}"
	sudo fusermount -uz "${const_CLOUD_MOUNT_POINT}" 2>/dev/null

#########################
# MANAGE STORAGE DEVICE #
#########################

	# To define a new method, add an elif block (example below)

	if [ "${TARGET_MODE}" = "usb" ]; then
		# External mode
		# If display support is enabled, display the specified message

		disp_message ":$(l 'box_backup_insert_target_1')" ":$(l 'box_backup_insert_target_2')"

		# Wait for a USB usb device (e.g., a USB flash drive)
		UUID_USB_1=$(mount_device "usb_1" true "${UUID_USB_1}" "${UUID_USB_2}")
		MOUNTED_DEVICES+=("${UUID_USB_1}")

		TARGET_PATH="${const_USB_TARGET_MOUNT_POINT}"

		# notify that the usb device has been mounted
		ret="$(get_storage_properties ${TARGET_PATH})"

		IFS="|"
		set -- $ret

		STOR_SIZE="$(l 'box_backup_storage_size'): $1"
		STOR_USED="$(l 'box_backup_storage_used'): $2"
		STOR_FREE="$(l 'box_backup_storage_free'): $3"
		STOR_FSTYPE="$(l 'box_backup_storage_filesystem_short'): $4"

		unset IFS

		disp_message "set:clear" ":$(l 'box_backup_usb_target_ok')" ":${STOR_SIZE}" ":${STOR_USED}" ":${STOR_FREE}" ":${STOR_FSTYPE}"

	elif [ "${TARGET_MODE}" = "internal" ]; then
		# Internal mode
		TARGET_PATH="${const_INTERNAL_BACKUP_DIR}"

		ret="$(get_storage_properties ${TARGET_PATH})"

		IFS="|"
		set -- $ret

		STOR_SIZE="$(l 'box_backup_storage_size'): $1"
		STOR_USED="$(l 'box_backup_storage_used'): $2"
		STOR_FREE="$(l 'box_backup_storage_free'): $3"
		STOR_FSTYPE="$(l 'box_backup_storage_filesystem_short'): $4"

		unset IFS

		# If display support is enabled, notify that the usb device has been mounted
		disp_message "set:clear" ":$(l 'box_backup_int_storage_ok')" ":${STOR_SIZE}" ":${STOR_USED}" ":${STOR_FREE}" ":${STOR_FSTYPE}"

	elif [ "${TARGET_MODE}" = "rsyncserver" ]; then
			RSYNC_CONNECTION="rsync://${conf_RSYNC_USER}@${conf_RSYNC_SERVER}:${conf_RSYNC_PORT}/${conf_RSYNC_SERVER_MODULE}"
			TARGET_PATH="${conf_BACKUP_TARGET_BASEDIR_CLOUD}"

	elif [ "${TARGET_MODE}" = "cloud" ]; then
			disp_message ":$(l 'box_backup_waiting_for_cloud_1')" ":$(l 'box_backup_waiting_for_cloud_2')" ":${CLOUDSERVICE}"

			TARGET_PATH="${const_CLOUD_MOUNT_POINT}/${conf_BACKUP_TARGET_BASEDIR_CLOUD}"

			mount_cloud "${CLOUDSERVICE}" "${const_CLOUD_MOUNT_POINT}"

	# elif [ "${TARGET_MODE}" = "NEW_STORAGE_DEFINITION" ]; then
	#         disp_message ":$(l 'box_backup__1')" ":$(l 'box_backup__2')"
	#         ...
	#         # Set usb path
	#         TARGET_PATH

	else
		# no defined mode selected
		if [ "${TARGET_MODE}:${SOURCE_MODE}" != "none:none" ]; then
			disp_message ":$(l 'box_backup_no_valid_destination_mode_1')" ":$(l 'box_backup_no_valid_destination_mode_2')" ":$(l 'box_backup_no_valid_destination_mode_3')" "s=b:2"
			TARGET_MODE='none'
			SOURCE_MODE='none'
		fi
	fi

	# END

	# Set the ACT LED to blink at 1000ms to indicate that the usb device has been mounted
	sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
	sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

########################
# MANAGE SOURCE DEVICE #
########################

	# START

	# To define a new method, add an elif block (example below)

	if [ "${SOURCE_MODE}" = "usb" ]; then

		# If display support is enabled, display the specified message
		disp_message ":$(l 'box_backup_insert_source_1')" ":$(l 'box_backup_insert_source_2')"

		# Source device
		UUID_USB_2=$(mount_device "usb_2" true "${UUID_USB_1}" "${UUID_USB_2}")
		MOUNTED_DEVICES+=("${UUID_USB_2}")

		# Set SOURCE_PATH
		SOURCE_PATHS=("${const_USB_SOURCE_MOUNT_POINT}")

		# notify that the source device has been mounted
		ret="$(get_storage_properties ${SOURCE_PATHS[0]})"
		IFS="|"
		set -- $ret
		STOR_SIZE="$(l 'box_backup_storage_size'): $1"
		STOR_USED="$(l 'box_backup_storage_used'): $2"
		STOR_FSTYPE="$(l 'box_backup_storage_filesystem_short'): $4"

		unset IFS

		disp_message "set:clear" ":$(l 'box_backup_usb_source_ok')" ":$(l 'box_backup_working')..." ":${STOR_SIZE}" ":${STOR_USED}" ":${STOR_FSTYPE}"

		# Create  a .id random identifier file if doesn't exist
		cd "${SOURCE_PATHS[0]}"
		if [ ! -f *.id ]; then
			random=$(echo $RANDOM)
			sudo touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
		fi
		ID_FILE=$(ls -t *.id | head -n1)
		ID="${ID_FILE%.*}"
		cd

		# Set BACKUP_PATH
		BACKUP_PATH="${TARGET_PATH}/${ID}"

		# Set SOURCE_IDENTIFIER
		SOURCE_IDENTIFIER="Source ID: ${ID}"

	elif [ "${SOURCE_MODE}" = "ios" ]; then
		disp_message ":$(l 'box_backup_connect_ios_1')" ":$(l 'box_backup_connect_ios_2')" ":$(l 'box_backup_connect_ios_3')"

		# Try to mount the iOS device
		ifuse ${const_IOS_MOUNT_POINT} -o allow_other

		# Waiting for the iOS device to be mounted
		until [ ! -z "$(ls -A ${const_IOS_MOUNT_POINT})" ]; do
			disp_message ":$(l 'box_backup_no_ios_waiting_1')" ":$(l 'box_backup_no_ios_waiting_2')..."
			sleep 2
			sudo ifuse ${const_IOS_MOUNT_POINT} -o allow_other
		done

		# Mount iOS device
		SOURCE_PATHS=("${const_IOS_MOUNT_POINT}/DCIM")

		# Create  a .id random identifier file if doesn't exist
		cd "${SOURCE_PATHS[0]}"
		if [ ! -f *.id ]; then
			random=$(echo $RANDOM)
			sudo touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
		fi
		ID_FILE=$(ls -t *.id | head -n1)
		ID="${ID_FILE%.*}"
		cd

		# Set BACKUP_PATH
		BACKUP_PATH="${TARGET_PATH}/iOS/${ID}"

		# Set SOURCE_IDENTIFIER
		SOURCE_IDENTIFIER="Source ID: ${ID}"

	elif [ "${SOURCE_MODE}" = "internal" ]; then

		# Set SOURCE_PATH
		SOURCE_PATHS=("${const_INTERNAL_BACKUP_DIR}")

		# display device information
		ret="$(get_storage_properties ${SOURCE_PATHS[0]})"
		IFS="|"
		set -- $ret
		STOR_SIZE="$(l 'box_backup_storage_size'): $1"
		STOR_USED="$(l 'box_backup_storage_used'): $2"
		STOR_FSTYPE="$(l 'box_backup_storage_filesystem_short'): $4"

		unset IFS

		disp_message "set:clear" ":$(l 'box_backup_int_storage_ok')" ":$(l 'box_backup_working')..." ":${STOR_SIZE}" ":${STOR_USED}" ":${STOR_FSTYPE}"

		# Create  a .id random identifier file if doesn't exist
		cd "${SOURCE_PATHS[0]}"
		if [ ! -f *.id ]; then
			random=$(echo $RANDOM)
			sudo touch $(date -d "today" +"%Y%m%d%H%M")-$random.id
		fi
		ID_FILE=$(ls -t *.id | head -n1)
		ID="${ID_FILE%.*}"
		cd

		# Set BACKUP_PATH
		BACKUP_PATH="${TARGET_PATH}/internal/${ID}"

		# Set SOURCE_IDENTIFIER
		SOURCE_IDENTIFIER="Source ID: ${ID}"

	elif [ "${SOURCE_MODE}" = "camera" ]; then
		# Source camera
		# If display support is enabled, display the specified message
		disp_message ":$(l 'box_backup_connect_camera_1')" ":$(l 'box_backup_connect_camera_2')"

		# Wait for camera
		DEVICE=$(sudo gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
		while [ -z "${DEVICE}" ]; do
			sleep 1
			DEVICE=$(sudo gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
		done

		# If display support is enabled, notify that the camera is detected
		disp_message ":$(l 'box_backup_camera_ok')" ":$(l 'box_backup_working')..."

		# Obtain camera model
		# Create the target directory with the camera model as its name
		CAMERA=$(sudo gphoto2 --summary | grep "Model" | cut -d: -f2 | tr -d '[:space:]')
		CAMERA=${CAMERA//[^a-zA-Z0-9-_\.]/_}

		MANUFACTURER=$(sudo gphoto2 --summary | grep "Manufacturer" | cut -d: -f2 | tr -d '[:space:]')
		MANUFACTURER=${MANUFACTURER//[^a-zA-Z0-9-_\.]/_}

		CAMERA_SERIAL=$(sudo gphoto2 --summary | grep "Serial Number" | cut -d: -f2 | tr -d '[:space:]')
		CAMERA_SERIAL=${CAMERA_SERIAL//[^a-zA-Z0-9-_\.]/_} #replace unsupported letters
		CAMERA_SERIAL=$(echo $CAMERA_SERIAL | sed 's/^0*//') #remove leading "0"

		if [ ${#CAMERA_SERIAL} -le 13 ]; then
			CAMERA_SERIAL_FORMATED="${CAMERA_SERIAL}"
		else
			CAMERA_SERIAL_FORMATED="...${CAMERA_SERIAL: -10}"
		fi

		disp_message ":${CAMERA}" ":${MANUFACTURER}" ":SN: ${CAMERA_SERIAL_FORMATED}"

		CAMERA_SERIAL_PATH_EXTENSION=""
		if [ "${CAMERA_SERIAL}" != "" ]; then
			CAMERA_SERIAL_PATH_EXTENSION="_SN_${CAMERA_SERIAL}"
		fi

		CAMERA_STORAGES_STR=$(sudo gphoto2 --storage-info | grep 'basedir' | cut -d= -f2 | tr -d ' ')
		IFS=$'\n' read -rd '' -a CAMERA_STORAGES_ARRAY <<<"${CAMERA_STORAGES_STR}"
		unset IFS

		# Set BACKUP_PATH
		BACKUP_PATH="${TARGET_PATH}/${CAMERA}${CAMERA_SERIAL_PATH_EXTENSION}"

		# Set SOURCE_IDENTIFIER
		SOURCE_IDENTIFIER="Camera: ${CAMERA} Serial: ${CAMERA_SERIAL}"

		# Define source-folders
		Camera_Search_Folders=()
		SOURCE_PATHS=()

		# split config-entry by ";"
		if [ ! -z "${conf_BACKUP_CAMERA_FOLDER_MASK}" ]; then
			IFS=";"
				read -a Camera_Masks <<< "${conf_BACKUP_CAMERA_FOLDER_MASK}"
			unset IFS

			# check all entries for relevance
			for Camera_Mask in "${Camera_Masks[@]}"
			do
				MaskSetCamera="$(cut -d':' -f1 <<< "${Camera_Mask}")"
				MaskSetFolder="$(cut -d':' -f2 <<< "${Camera_Mask}")"

				if [ "${MaskSetCamera}" = "${CAMERA}" ] || [ "${MaskSetCamera}" = "*" ]; then
					if [ "${#MaskSetFolder}" -gt "1" ] && [[ ${MaskSetFolder:0:2} == '!/' ]]; then
						#static defined source folders (quick)

						#check if path exists in CAMERA_STORAGES_ARRAY
						CAMERA_STORAGE_EXISTS=false
						for CAMERA_STORAGES_PATH in "${CAMERA_STORAGES_ARRAY[@]}"; do
							if [[ "${MaskSetFolder:1}" =~ "${CAMERA_STORAGES_PATH}" ]]; then
								CAMERA_STORAGE_EXISTS=true
							fi
						done

						if [ $CAMERA_STORAGE_EXISTS = true ]; then
							SOURCE_PATHS+=("${MaskSetFolder:1}")
						else
							disp_message ":$(l 'box_backup_camera_storage_not_exists_1')" ":${MaskSetFolder:1}" ":$(l 'box_backup_camera_storage_not_exists_2')"
						fi

					elif [ ! -z "${MaskSetFolder}" ]; then
						#dynamic search for source folders (slow)
						Camera_Search_Folders+=("$MaskSetFolder")
					fi
				fi
			done
		fi

		# only if Camera_Search_Folders has no values yet
		if [ ${#SOURCE_PATHS[@]} -eq 0 ]; then
			disp_message ":$(l 'box_backup_camera_scanning_folders')"
			Camera_Folders=( $(sudo gphoto2 --list-folders | cut -d"'" -f2 | grep "^/") )

			for Camera_Folder in "${Camera_Folders[@]}"; do
				log_message "Found folder: ${Camera_Folder}" 3
				for Camera_Search_Folder in "${Camera_Search_Folders[@]}"
				do
					if [[ "${Camera_Folder}" =~ "${Camera_Search_Folder}" ]]; then

						known=false
						for SOURCE_PATH in "${SOURCE_PATHS[@]}"
						do
							if [[ ${Camera_Folder} = ${SOURCE_PATH}* ]]; then
								known=true
							fi
						done

						if [ $known = false ]; then
							SOURCE_PATHS+=("${Camera_Folder}")
						fi
					fi
				done
			done

			if [ ${#SOURCE_PATHS[@]} -eq 0 ]; then
				SOURCE_PATHS=("/")
			fi

		fi

		#log Camera_Search_Folders
		log_message "Folders to sync from camera '${CAMERA}':" 1
		for SOURCE_PATH in "${SOURCE_PATHS[@]}"
		do
			log_message "*** - ${SOURCE_PATH} - For use as pattern in Settings ('$(l 'config_backup_camera_folder_mask_header')'): '${CAMERA}:!${SOURCE_PATH}'" 1
		done

	elif [ "${SOURCE_MODE}" = "thumbnails" ]; then
		# no backup, generate thumbnails only
		echo "" # dummy action

	elif [ "${SOURCE_MODE}" = "database" ]; then
		# no backup, generate database only
		echo "" # dummy action

	elif [ "${SOURCE_MODE}" = "exif" ]; then
		# no backup, update exif only
		echo "" # dummy action

	# elif [ "${SOURCE_MODE}" = "NEW_SOURCE_DEFINITION" ]; then
	#
	#         disp_message ":Ready" ":Insert NEW_SOURCE_TYPE"
	#         ...
	#         # Specify backup path and source identifier
	#         SOURCE_PATH
	#         BACKUP_PATH
	#         SOURCE_IDENTIFIER

	else
		# no defined mode selected
		if [ "${TARGET_MODE}:${SOURCE_MODE}" != "none:none" ]; then
			disp_message ":$(l 'box_backup_no_valid_source_mode_1')" ":$(l 'box_backup_no_valid_source_mode_2')" ":$(l 'box_backup_no_valid_source_mode_3')" "s=b:3"
			TARGET_MODE='none'
			SOURCE_MODE='none'
		fi
	fi

	# Set the ACT LED to blink at 500ms to indicate that the source device has been mounted
	sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"


	TRIES_MAX=5
	FILES_TO_SYNC=0
	SYNC_LOG=""
	declare -A MAIL_SOURCE_PATHS
	declare -A TRIES_DONE
	declare -A TRANSFER_INFO

	TRANSFER_INFO_DISP=""
	SOURCE_FOLDER_NUMBER=""
	MESSAGE_DISPLAY=""
	SYNC_ERROR_FINAL_RUN=false
	SYNC_ERROR_NOT_SOLVED=false

	#sourcepaths-loop
	for SOURCE_PATH in "${SOURCE_PATHS[@]}"; do

		#SOURCE_FOLDER_NUMBER
		if [ -z "${SOURCE_FOLDER_NUMBER}" ]; then
			SOURCE_FOLDER_NUMBER=1
		else
			SOURCE_FOLDER_NUMBER=$(($SOURCE_FOLDER_NUMBER + 1))
		fi
		SOURCE_FOLDER_INFO="${SOURCE_FOLDER_NUMBER}/${#SOURCE_PATHS[@]}"

		MAIL_SOURCE_PATHS[${SOURCE_FOLDER_NUMBER}]="'${SOURCE_PATH}'"

		#retry-loop
		TRIES_DONE[$SOURCE_FOLDER_NUMBER]=0
		SYNC_ERROR_TMP="-" # not empty!

		while [[ "${TRIES_MAX}" -gt "${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}" ]] && [[ "${SYNC_ERROR_TMP}" != "" ]]; do

			# RETRIES
			TRIES_DONE[$SOURCE_FOLDER_NUMBER]=$((TRIES_DONE[$SOURCE_FOLDER_NUMBER]+1))

			if [ ! -z "${SYNC_LOG}" ]; then
				SYNC_LOG="${SYNC_LOG}\n"
			fi

			SYNC_LOG="${SYNC_LOG}---- $(l 'box_backup_try') ${TRIES_DONE[$SOURCE_FOLDER_NUMBER]} ${SOURCE_PATH} ----"

			if [ "${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}" -gt "1" ]; then
				disp_message "a:$(l 'box_backup_try_backup') ${TRIES_DONE[$SOURCE_FOLDER_NUMBER]} $(l 'box_backup_of') ${TRIES_MAX}"
				sleep 5 # time to stabilize the system after device-lost
			fi

			# Remount devices if "Err.Lost device"
			if [[ "${SYNC_ERROR_TMP}" =~ "Err.Lost device" ]]; then
				log_exec "Lost device: pre remount" "sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE" 3

				if [ "${UUID_USB_1}" != "" ]; then
					RESULT_DEVICE_MOUNTED=$(device_mounted "${UUID_USB_1}")
					if [ -z "${RESULT_DEVICE_MOUNTED}" ]; then
						log_message "remount usb" 3
						mount_device "usb_1" true "${UUID_USB_1}" "${UUID_USB_2}"
					fi
				fi

				if [ "${UUID_USB_2}" != "" ]; then
					RESULT_DEVICE_MOUNTED=$(device_mounted "${UUID_USB_2}")
					if [ -z "${RESULT_DEVICE_MOUNTED}" ]; then
						log_message "remount usb" 3
						mount_device "usb_2" true "${UUID_USB_1}" "${UUID_USB_2}"
					fi
				fi
			fi

			SYNC_START_TIME=$(get_uptime_seconds)

		##############
		# RUN BACKUP #
		##############

			# START

			SYNC_ERROR_TMP="" # empty, no further loop if no error
			SYNC_RETURN_CODE="0"

			# Count of files in usb before backup starts
			if [ "${TARGET_MODE}" != "rsyncserver" ]; then
				FILES_COUNT_STORAGE_PRE=$(find $BACKUP_PATH -type f | wc -l)
			fi

			disp_message ":$(l "box_backup_working")..."

			FILES_TO_SYNC="$(calculate_files_to_sync "${SOURCE_PATH}")"
			log_message "Files to sync before backup: ${FILES_TO_SYNC}" 3

			if [[ " usb internal ios " =~ " ${SOURCE_MODE} " ]]; then
				# If source is usb, internal or ios

				if [ "${TARGET_MODE}" = "rsyncserver" ]; then
					# to rsyncserver
					if [ $conf_LOG_SYNC = true ]; then
						sudo sshpass -p "${conf_RSYNC_PASSWORD}" rsync -avh --info=FLIST0,PROGRESS2 --mkpath --no-perms --stats --min-size=1 --exclude "*.id" --exclude "*tims/" --exclude "${const_IMAGE_DATABASE_FILENAME}" --log-file="${const_LOGFILE_SYNC}" "${SOURCE_PATH}/" "${RSYNC_CONNECTION}/${BACKUP_PATH}/" | syncprogress "rsync" "${SOURCE_FOLDER_INFO}"
						SYNC_RETURN_CODE="${PIPESTATUS[0]}"
						SYNC_LOG="${SYNC_LOG}\n$(<"${const_LOGFILE_SYNC}")"
						log_pick_file "${const_LOGFILE_SYNC}"
					else
						sudo sshpass -p "${conf_RSYNC_PASSWORD}" rsync -avh --info=FLIST0,PROGRESS2 --mkpath --no-perms --stats --min-size=1 --exclude "*.id" --exclude "*tims/" "${SOURCE_PATH}/" --exclude "${const_IMAGE_DATABASE_FILENAME}" "${RSYNC_CONNECTION}/${BACKUP_PATH}/" | syncprogress "rsync" "${SOURCE_FOLDER_INFO}"
						SYNC_RETURN_CODE="${PIPESTATUS[0]}"
					fi

				else
					# not to rsyncserver
					sudo mkdir -p "${BACKUP_PATH}"
					if [ $conf_LOG_SYNC = true ]; then
						sudo rsync -avh --info=FLIST0,PROGRESS2 --stats --min-size=1 --exclude "*.id" --exclude "*tims/" --exclude "${const_IMAGE_DATABASE_FILENAME}" --log-file="${const_LOGFILE_SYNC}" "${SOURCE_PATH}"/ "${BACKUP_PATH}" | syncprogress "rsync" "${SOURCE_FOLDER_INFO}"
						SYNC_RETURN_CODE="${PIPESTATUS[0]}"
						SYNC_LOG="${SYNC_LOG}\n$(<"${const_LOGFILE_SYNC}")"
						log_pick_file "${const_LOGFILE_SYNC}"
					else
						sudo rsync -avh --info=FLIST0,PROGRESS2 --stats --min-size=1 --exclude "*.id" --exclude "*tims/" --exclude "${const_IMAGE_DATABASE_FILENAME}" "${SOURCE_PATH}"/ "${BACKUP_PATH}" | syncprogress "rsync" "${SOURCE_FOLDER_INFO}"
						SYNC_RETURN_CODE="${PIPESTATUS[0]}"
					fi
				fi

			elif [ "${SOURCE_MODE}" = "camera" ]; then
				# If source is camera
				# Switch to const_USB_TARGET_MOUNT_POINT and transfer files from the camera

				sudo mkdir -p "${BACKUP_PATH}"
				cd "${BACKUP_PATH}"

				# gphoto2: Filename-format at backup; %F is undocumented? = path of the file at the camera; $f = filename without suffix; %C=suffix

				log_message "Backup from camera: ${SOURCE_PATH}" 3

				sudo gphoto2 --filename "%F/%f.%C" --get-all-files --folder "${SOURCE_PATH}" --skip-existing | syncprogress "gphoto2" "${SOURCE_FOLDER_INFO}"

				SYNC_RETURN_CODE="${PIPESTATUS[0]}"
				if [ $conf_LOG_SYNC = true ]; then
					SYNC_LOG="${SYNC_LOG}\n$(<"${const_LOGFILE_SYNC}")"
				fi
				log_message "gphoto2 --filename \"%F/%f.%C\" --get-all-files --folder \"${SOURCE_PATH}\"  --skip-existing:\nexitcode=${SYNC_RETURN_CODE}$(sync_return_code_decoder "${SOURCE_MODE}" "${SYNC_RETURN_CODE}")\n" 3

				cd
			elif [ "${SOURCE_MODE}" = "thumbnails" ]; then
				# no backup action
				echo "" # dummy action
			elif [ "${SOURCE_MODE}" = "database" ]; then
				# no backup action
				echo "" # dummy action
			elif [ "${SOURCE_MODE}" = "exif" ]; then
				# no backup action
				echo "" # dummy action
			else
				# no defined mode selected
				if [ "${TARGET_MODE}:${SOURCE_MODE}" != "none:none" ]; then
					disp_message ":$(l 'box_backup_no_valid_source_mode_1')" ":$(l 'box_backup_no_valid_source_mode_2')" ":$(l 'box_backup_no_valid_source_mode_3')" "s=b:4"
					TARGET_MODE='none'
					SOURCE_MODE='none'
				fi
			fi

			SYNC_STOP_TIME=$(get_uptime_seconds)

			# END BACKUP

			# Remove empty files (maybe can result from disconnection of a source-device)
			if [ "${TARGET_MODE}" != "rsyncserver" ]; then
				sudo find "${BACKUP_PATH}" -size 0 -delete
			fi

			# Re-calculate FILES_TO_SYNC
			if [ "${TARGET_MODE}" != "rsyncserver" ]; then
				FILES_COUNT_STORAGE_POST=$(find $BACKUP_PATH -type f | wc -l)
				FILES_TO_SYNC_NEW=$(($FILES_TO_SYNC - $FILES_COUNT_STORAGE_POST + $FILES_COUNT_STORAGE_PRE))
			else
				FILES_TO_SYNC_NEW="$(calculate_files_to_sync "${SOURCE_PATH}")"
			fi

			if [ "${FILES_TO_SYNC}" != "" ] && [ "${FILES_TO_SYNC_NEW}" != "" ]; then
				FILES_TRANSFERRED=$((${FILES_TO_SYNC} - ${FILES_TO_SYNC_NEW}))
				if [ "${FILES_TRANSFERRED}" -lt "0" ]; then FILES_TRANSFERRED=$(l "box_backup_unknown"); fi
				TRANSFER_INFO[${SOURCE_FOLDER_NUMBER},${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}]="$FILES_TRANSFERRED $(l "box_backup_of") ${FILES_TO_SYNC} $(l "box_backup_files_copied").\n"
				TRANSFER_INFO_DISP="${TRANSFER_INFO_DISP}:${SOURCE_FOLDER_NUMBER}. $FILES_TRANSFERRED $(l "box_backup_of") ${FILES_TO_SYNC} $(l "box_backup_files_copied")\n"
			else
				TRANSFER_INFO[${SOURCE_FOLDER_NUMBER},${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}]="$(l "box_backup_result_suspect").\n"
				TRANSFER_INFO_DISP="${TRANSFER_INFO_DISP}:${SOURCE_FOLDER_NUMBER}. $(l "box_backup_result_suspect")\n"
				FILES_TO_SYNC=0
			fi
			FILES_TO_SYNC="${FILES_TO_SYNC_NEW}"

			if [ "${FILES_TO_SYNC}" -gt "0" ] && [ "${SOURCE_MODE}" != "camera" ]; then
				SYNC_ERROR_TMP="${SYNC_ERROR_TMP} Files missing!"
				log_message "Files missing: ${FILES_TO_SYNC} files not synced."
				log_exec "Files missing" "sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE" 3
				log_message "$(get_abnormal_system_conditions)" 1
			fi

			if [ "${SYNC_RETURN_CODE}" != "0" ]; then
				SYNC_ERROR_TMP="${SYNC_ERROR_TMP} Exception"
				log_message "Exception: ${SYNC_RETURN_CODE}$(sync_return_code_decoder "${SOURCE_MODE}" "${SYNC_RETURN_CODE}")"
				log_message "$(get_abnormal_system_conditions)" 1
			fi

			# Check for lost devices
			for MOUNTED_DEVICE in "${MOUNTED_DEVICES[@]}"; do
				RESULT_DEVICE_MOUNTED=$(device_mounted "${MOUNTED_DEVICE}")
				log_message "Lost device? '${MOUNTED_DEVICE}': '${RESULT_DEVICE_MOUNTED}'" 3

				if [ -z "${RESULT_DEVICE_MOUNTED}" ]; then
					SYNC_ERROR_TMP="${SYNC_ERROR_TMP} Err.Lost device!"
					log_message "Lost device '${MOUNTED_DEVICE}': DEVICE LOST"

					log_exec "Lost device" "sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE" 3
					log_message "$(get_abnormal_system_conditions)" 1
				fi
			done

			# Controller- overheating-error?
			SYNC_TIME=$(($SYNC_STOP_TIME - $SYNC_START_TIME))
			log_message "SYNC_RETURN_CODE: ${SYNC_RETURN_CODE}$(sync_return_code_decoder "${SOURCE_MODE}" "${SYNC_RETURN_CODE}"); SYNC_TIME: ${SYNC_TIME}" 3

			if [[ "${SYNC_ERROR_TMP}" =~ "Err.Lost device!" ]] && [ "${SYNC_RETURN_CODE}" -gt "0" ] && [ "${SYNC_TIME}" -ge "${const_SYNC_TIME_OVERHEATING_THRESHOLD_SEC}" ] && [ "${TRIES_MAX}" -gt "${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}" ]; then
					disp_message "a:$(l 'box_backup_error_cooling_1')" ":$(l 'box_backup_error_cooling_2') ${const_SYNC_TIME_OVERHEATING_WAIT_SEC} $(l 'seconds_short') ..." ":$(l 'box_backup_error_cooling_3')" ":$(l 'box_backup_error_cooling_4')"
					sleep ${const_SYNC_TIME_OVERHEATING_WAIT_SEC}
			fi

			# prepare message for mail and power off
			SOURCE_FOLDER_MESSAGE_INFO="${SOURCE_FOLDER_INFO}: "

			if [ -z "${SYNC_ERROR_TMP}" ]; then
				SYNC_ERROR_FINAL_RUN=false

				MESSAGE_MAIL[${SOURCE_FOLDER_NUMBER},${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}]="$(l 'box_backup_mail_backup_complete')."
				MESSAGE_DISPLAY="${MESSAGE_DISPLAY}:${SOURCE_FOLDER_MESSAGE_INFO}$(l 'box_backup_complete').\n"
				SOURCE_FOLDER_MESSAGE_INFO=''
			else
				SYNC_ERROR_FINAL_RUN=true

				if [[ "${SYNC_ERROR_TMP}" =~ "Err.Lost device!" ]]; then
					MESSAGE_MAIL[${SOURCE_FOLDER_NUMBER},${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}]="$(l 'box_backup_mail_lost_device')"
					MESSAGE_DISPLAY="${MESSAGE_DISPLAY}s=a:${SOURCE_FOLDER_MESSAGE_INFO}$(l 'box_backup_lost_device')\n"
					SOURCE_FOLDER_MESSAGE_INFO=''
				fi

				if [[ "${SYNC_ERROR_TMP}" =~ "Files missing!" ]]; then
					MESSAGE_MAIL[${SOURCE_FOLDER_NUMBER},${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}]="$(l 'box_backup_mail_files_missing')"
					MESSAGE_DISPLAY="${MESSAGE_DISPLAY}s=a:${SOURCE_FOLDER_MESSAGE_INFO}$(l 'box_backup_files_missing')\n"
					SOURCE_FOLDER_MESSAGE_INFO=''
				fi

				if [[ "${SYNC_ERROR_TMP}" =~ "Exception" ]]; then
					MESSAGE_MAIL[${SOURCE_FOLDER_NUMBER},${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}]="$(l 'box_backup_mail_exception'):$(sync_return_code_decoder "${SOURCE_MODE}" "${SYNC_RETURN_CODE}")"
					MESSAGE_DISPLAY="${MESSAGE_DISPLAY}s=a:${SOURCE_FOLDER_MESSAGE_INFO}$(l 'box_backup_exception'):$(sync_return_code_decoder "${SOURCE_MODE}" "${SYNC_RETURN_CODE}")\n"
					SOURCE_FOLDER_MESSAGE_INFO=''
				fi
			fi

			MESSAGE_DISPLAY="${MESSAGE_DISPLAY}:$(l 'box_backup_try') ${TRIES_DONE[$SOURCE_FOLDER_NUMBER]}\n"

		done # retry

		if [ $SYNC_ERROR_FINAL_RUN = true ]; then SYNC_ERROR_NOT_SOLVED=true; fi
	done # sources

########################
# VPN STOP             #
########################

	if [ $(vpn_status "OpenVPN") = "up" ] || [ $(vpn_status "WireGuard" "${const_VPN_DIR_WireGuard}/$const_VPN_FILENAME_WireGuard") = "up" ]; then
		disp_message ":$(l 'box_backup_vpn_disconnecting')"
		vpn_stop "OpenVPN"
		vpn_stop "WireGuard" "${const_VPN_DIR_WireGuard}/$const_VPN_FILENAME_WireGuard"

		sudo "${WORKING_DIR}/cron-ip.sh" 'force'
	fi

########################
# Mail result          #
########################

	# prepare message for mail

	# create mail summary

	MailSummary=""
	for (( i=1; i<=$SOURCE_FOLDER_NUMBER; i++ )); do
		if [ ! -z "${MailSummary}" ]; then
			MailSummary="${MailSummary}\n"
		fi

		MailSummary="${MailSummary}$i.: ${MAIL_SOURCE_PATHS[${SOURCE_FOLDER_NUMBER}]}\n"
		for (( j=1; j<=${TRIES_DONE[$i]}; j++ )); do
			MailSummary="${MailSummary}  ${j}. $(l 'box_backup_try')\n"
			MailSummary="${MailSummary}    ${MESSAGE_MAIL[$i,$j]}\n"
			MailSummary="${MailSummary}    ${TRANSFER_INFO[$i,$j]}\n"
		done
	done

	# Check internet connection and send
	# a notification by mail if the conf_MAIL_NOTIFICATIONS option is enabled
	INTERNET_STATUS=$(get_internet_status)
	if [[ ! " database thumbnails exif " =~ " ${SOURCE_MODE} " ]]; then

		if [ ${SYNC_ERROR_NOT_SOLVED} = true  ] || [ "${TARGET_MODE}:${SOURCE_MODE}" = "none:none" ]; then
			SUBJ_MSG="$(l 'box_backup_mail_error')"
		else
			SUBJ_MSG="$(l 'box_backup_mail_backup_complete')"
		fi

		if [ "${TARGET_MODE}:${SOURCE_MODE}" = "none:none" ]; then
			BODY_MSG="$(l 'box_backup_mail_backup_failed')"
		else
			BODY_MSG="$(l 'box_backup_mail_backup_type'): $(l "box_backup_mode_${SOURCE_MODE}") $(l 'box_backup_mail_to') $(l "box_backup_mode_${TARGET_MODE}") ${CLOUDSERVICE}
${SOURCE_IDENTIFIER}

${MailSummary}

$(l 'box_backup_mail_log'):
${SYNC_LOG}

${TRIES_DONE[$SOURCE_FOLDER_NUMBER]} $(l 'box_backup_mail_tries_needed')."
		fi

		send_email "Little Backup Box: ${SUBJ_MSG}" "${BODY_MSG}"

	fi


########################
# SYNCHRONISE DATABASE #
########################

	DB="${TARGET_PATH}/${const_IMAGE_DATABASE_FILENAME}"
	if ([ ! -f "${DB}" ] || [ "${SOURCE_MODE}" = "database" ]) && ([ "${GENERATE_THUMBNAILS}" = "true" ] || [[ " thumbnails database exif " =~ " ${SOURCE_MODE} " ]]) && [[ " usb internal " =~ " ${TARGET_MODE} " ]]; then

		# prepare database
		db_setup

		# clean database
		LCD1="$(l "box_backup_cleaning_database")" # header1
		LCD2="$(l "box_backup_mode_${TARGET_MODE}")" # header2

		# remove duplicates
		sudo sqlite3 "${DB}" "delete from EXIF_DATA where ID not in (select min(ID) from EXIF_DATA group by File_Name, Directory);"

		# select directory and filename as DirFile and replace spaces by placeholder
		DB_STR=$(sudo sqlite3 "${DB}" "select ID, Directory || '/' || File_Name as DirFile from EXIF_DATA" | sed 's/\ /##\*\*##/g')
		IFS=$'\n' read -rd '' -a DB_ARRAY <<<"${DB_STR}"
		unset IFS

		IMAGE_COUNT=${#DB_ARRAY[@]}

		START_TIME=$(get_uptime_seconds)

		progressmonitor "${START_TIME}" "${IMAGE_COUNT}" "0" "${LCD1}" "${LCD2}" ""

		for ((i=0; i < ${#DB_ARRAY[@]}; i++)); do
			IMAGE_FILENAME="${TARGET_PATH}/$(echo ${DB_ARRAY[$i]} | cut -d'|' -f2 | sed 's/##\*\*##/\ /g')"

			if [ ! -f "${IMAGE_FILENAME}" ]; then
				ID="$(echo ${DB_ARRAY[$i]} | cut -d'|' -f1)"
				sudo sqlite3 "${DB}" "DELETE from EXIF_DATA WHERE ID=${ID};"
				log_message "DELETE from EXIF_DATA WHERE ID=${ID};" 3
			fi
			progressmonitor "${START_TIME}" "${IMAGE_COUNT}" "${i}" "${LCD1}" "${LCD2}"
		done

		# vacuum database
		sudo sqlite3 "${DB}" "VACUUM;"

		# import preexisting tims into database
		disp_message "set:clear" ":$(l "box_backup_generating_database_finding_images1")" ":$(l "box_backup_mode_${TARGET_MODE}")" ":$(l "box_backup_counting_images")" ":$(l "box_backup_generating_database_finding_images3")"

		# find all tims and convert their filename to the estimated original filename:
		## 1. replace space by substitute of space ##**##
		## 2. replace only last '/tims/' by '/'
		## 3. remove last part of file extension
		TIMS_STR=$(sudo find "$TARGET_PATH" -type f \( -iname '*.jpg' -o -iname '*.jpeg' \) -path '*/tims/*'  | sed 's/\ /##\*\*##/g' | sed -E 's#(.*)/tims/#\1/#' | sed 's/\.[^.]*$//')
		IFS=$'\n' read -rd '' -a TIMS_ARRAY <<<"${TIMS_STR}"
		unset IFS

		# prepare loop to create thumbnails
		IMAGE_COUNT=${#TIMS_ARRAY[@]}

		START_TIME=$(get_uptime_seconds)
		LAST_MESSAGE_TIME=$START_TIME

		LCD1="$(l "box_backup_generating_database")" # header1
		LCD2="$(l "box_backup_mode_${TARGET_MODE}")" # header2

		progressmonitor "${START_TIME}" "${IMAGE_COUNT}" "0" "${LCD1}" "${LCD2}" ""

		for ((i=0; i < ${#TIMS_ARRAY[@]}; i++)); do
			# replace substitute of space by space

			SOURCE_IMAGE_FILENAME=$(echo ${TIMS_ARRAY[$i]} | sed 's/##\*\*##/\ /g')
			File_Name=$(basename "${SOURCE_IMAGE_FILENAME}")
			Directory=$(dirname "${SOURCE_IMAGE_FILENAME}")

			# clean Directory from /media/storage|source|internal/
			TARGET_PATH_MARKED="0-0-0-0${TARGET_PATH}"
			DIRECTORY_MARKED="0-0-0-0${Directory}"
			Directory=${DIRECTORY_MARKED//${TARGET_PATH_MARKED}}

			if [[ ! $(sqlite3 "${DB}" "select ID from EXIF_DATA where File_Name=\"${File_Name}\" and Directory=\"${Directory}\"") ]]; then
				db_insert "${SOURCE_IMAGE_FILENAME}" "${TARGET_PATH}"
			fi

			progressmonitor "${START_TIME}" "${IMAGE_COUNT}" "${i}" "${LCD1}" "${LCD2}" ""

		done
	fi

#######################
# GENERATE THUMBNAILS #
#######################

	if ([ "${GENERATE_THUMBNAILS}" = "true" ] || [ "${SOURCE_MODE}" = "thumbnails" ]) && [[ " usb internal " =~ " ${TARGET_MODE} " ]]; then
		# generate thumbnails on local drive (usb or internal)

		# prepare database
		db_setup

		disp_message "set:clear" ":$(l "box_backup_generating_thumbnails_finding_images1")" ":$(l "box_backup_mode_${TARGET_MODE}")" ":$(l "box_backup_counting_images")" ":$(l "box_backup_generating_thumbnails_finding_images3")"

		#find all images; replace "space" by substitute of space "##**##"
		INAMES=""

		IFS=$' ' read -rd '' -a FILE_EXTENSIONS_ARRAY <<<"${const_FILE_EXTENSIONS_LIST_JPG} ${const_FILE_EXTENSIONS_LIST_HEIC} ${const_FILE_EXTENSIONS_LIST_RAW} ${const_FILE_EXTENSIONS_LIST_VIDEO} ${const_FILE_EXTENSIONS_LIST_AUDIO}"
		unset IFS

		# create list of valid extensions
		for extension in "${FILE_EXTENSIONS_ARRAY[@]}";do
			if [ ! -z "${INAMES}" ]; then
				INAMES="${INAMES} -o "
			fi
			INAMES="${INAMES} -iname '*.${extension}'"
		done

		IMAGES_STR=$(eval "sudo find \"$TARGET_PATH\" -type f \( ${INAMES} \) -not -path '*/tims/*' | sed 's/\ /##\*\*##/g'") # temporarily replace spaces
		IFS=$'\n' read -rd '' -a IMAGES_ARRAY <<<"${IMAGES_STR}"
		unset IFS

		# find all tims and convert their filename to the estimated original filename:
		## 1. replace space by substitute of space ##**##
		## 2. replace only last '/tims/' by '/'
		## 3. remove last part of file extension
		TIMS_STR=$(sudo find "$TARGET_PATH" -type f \( -iname '*.jpg' \) -path '*/tims/*'  | sed 's/\ /##\*\*##/g' | sed -E 's#(.*)/tims/#\1/#' | sed 's/\.[^.]*$//')
		IFS=$'\n' read -rd '' -a TIMS_ARRAY <<<"${TIMS_STR}"
		unset IFS

		#remove from IMAGES_ARRAY all lines known in TIMS_ARRAY (quick!)
		IMAGES_ARRAY=($(echo -e "$(printf "%s\n" "${IMAGES_ARRAY[@]}")\n$(printf "%s\n" "${TIMS_ARRAY[@]}")" | sort | uniq -u))

		#remove from IMAGES_ARRAY all files from list having tims already created (reverse check to prevent jobs comming from TIMS_ARRAY)
		##1 find them, get indexes
		REMOVE_IMAGES_ID_ARRAY=()
		for i in "${!IMAGES_ARRAY[@]}"; do
			if [[ " ${TIMS_ARRAY[@]} " =~ " ${IMAGES_ARRAY[$i]} " ]]; then
				REMOVE_IMAGES_ID_ARRAY+=($i)
			fi
		done

		##2 sort indexes inverted
		IFS=$'\n' REMOVE_IMAGES_ID_ARRAY=($(sort -n -r <<<"${REMOVE_IMAGES_ID_ARRAY[*]}")); unset IFS

		##3 remove images from ##1 from IMAGES_ARRAY
		for REMOVE_IMAGES_ID in ${REMOVE_IMAGES_ID_ARRAY[@]}; do
			unset IMAGES_ARRAY[$REMOVE_IMAGES_ID]
		done

		##4 reindex IMAGES_ARRAY
		IMAGES_ARRAY=( "${IMAGES_ARRAY[@]}" )

		#prepare loop to create thumbnails
		IMAGE_COUNT=${#IMAGES_ARRAY[@]}

		START_TIME=$(get_uptime_seconds)
		LAST_MESSAGE_TIME=$START_TIME

		LCD1="$(l "box_backup_generating_thumbnails")" # header1
		LCD2="$(l "box_backup_mode_${TARGET_MODE}")" # header2

		progressmonitor "${START_TIME}" "${IMAGE_COUNT}" "0" "${LCD1}" "${LCD2}" ""

		for ((i=0; i < ${#IMAGES_ARRAY[@]}; i++)); do
			#replace substitute of space by space
			SOURCE_IMAGE_FILENAME=$(echo ${IMAGES_ARRAY[$i]} | sed 's/##\*\*##/\ /g')

			#extract extension from filename
			SOURCE_IMAGE_FILENAME_EXTENSION="${SOURCE_IMAGE_FILENAME##*.}"

			#change extension to lowercase
			SOURCE_IMAGE_FILENAME_EXTENSION="${SOURCE_IMAGE_FILENAME_EXTENSION,,}"

			TIMS_FOLDER="$(dirname "${SOURCE_IMAGE_FILENAME}")/tims"
			TIMS_FILENAME="${TIMS_FOLDER}/$(basename "${SOURCE_IMAGE_FILENAME}").JPG"
			mkdir -p "${TIMS_FOLDER}"

			if [[ " ${const_FILE_EXTENSIONS_LIST_JPG} " =~ " ${SOURCE_IMAGE_FILENAME_EXTENSION} " ]]; then
				# file-type: image
				convert "${SOURCE_IMAGE_FILENAME}" -resize 800\> "${TIMS_FILENAME}"
			elif [[ " ${const_FILE_EXTENSIONS_LIST_HEIC} " =~ " ${SOURCE_IMAGE_FILENAME_EXTENSION} " ]]; then
				# file-type: heic/heif
				heif-convert "${SOURCE_IMAGE_FILENAME}" "${SOURCE_IMAGE_FILENAME}.JPG"
				exiftool  -overwrite_original -TagsFromFile "${SOURCE_IMAGE_FILENAME}" "${SOURCE_IMAGE_FILENAME}.JPG"
				convert "${SOURCE_IMAGE_FILENAME}.JPG" -resize 800\> "${TIMS_FILENAME}"

				if [ ${conf_VIEW_CONVERT_HEIC} = true ]; then
					IMAGES_ARRAY+=("${SOURCE_IMAGE_FILENAME}.JPG")
					IMAGE_COUNT=${#IMAGES_ARRAY[@]}
				else
					rm "${SOURCE_IMAGE_FILENAME}.JPG"
				fi

			elif [[ " ${const_FILE_EXTENSIONS_LIST_RAW} " =~ " ${SOURCE_IMAGE_FILENAME_EXTENSION} " ]]; then
				# file-type: raw-image
 				/usr/lib/libraw/dcraw_emu -w -Z - "${SOURCE_IMAGE_FILENAME}" | convert - -resize 800 "${TIMS_FILENAME}"
			elif [[ " ${const_FILE_EXTENSIONS_LIST_VIDEO} " =~ " ${SOURCE_IMAGE_FILENAME_EXTENSION} " ]]; then
				# file-type: video
				ffmpeg -i "${SOURCE_IMAGE_FILENAME}" -ss 00:00:01 -vframes 1 "${TIMS_FILENAME}"
				if [ ! -f "${TIMS_FILENAME}" ]; then
					# tims file not generated. Video too short? Try at second 0
					ffmpeg -i "${SOURCE_IMAGE_FILENAME}" -ss 00:00:00 -vframes 1 "${TIMS_FILENAME}"
				fi

				mogrify -resize 800\> "${TIMS_FILENAME}"
				composite -gravity center '/var/www/little-backup-box/img/play.png' "${TIMS_FILENAME}" "${TIMS_FILENAME}"
			elif [[ " ${const_FILE_EXTENSIONS_LIST_AUDIO} " =~ " ${SOURCE_IMAGE_FILENAME_EXTENSION} " ]]; then
				cp '/var/www/little-backup-box/img/audio.JPG' "${TIMS_FILENAME}"
				convert "${TIMS_FILENAME}" -gravity center -pointsize 50 -annotate 0 "$(basename "${SOURCE_IMAGE_FILENAME}")" "${TIMS_FILENAME}"
			fi

			if [ ! -f "${TIMS_FILENAME}" ]; then
				log_message "ERROR: TIMS of '"${SOURCE_IMAGE_FILENAME}"' ('${TIMS_FILENAME}') not regular created."
				cp '/var/www/little-backup-box/img/unknown.JPG' "${TIMS_FILENAME}"
				convert "${TIMS_FILENAME}" -gravity center -pointsize 50 -annotate 0 "$(basename "${SOURCE_IMAGE_FILENAME}")" "${TIMS_FILENAME}"
			fi

			db_insert "${SOURCE_IMAGE_FILENAME}" "${TARGET_PATH}"

			progressmonitor "${START_TIME}" "${IMAGE_COUNT}" "${i}" "${LCD1}" "${LCD2}" ""
		done

	fi

###############
# EXIF UPDATE #
###############

	if ([ "${UPDATE_EXIF}" = "true" ] || [ "${SOURCE_MODE}" = "exif" ]) && [[ " usb internal " =~ " ${TARGET_MODE} " ]]; then
		# update exif-information in original files on local drive (usb or internal)

		# prepare database
		db_setup

		disp_message "set:clear" ":$(l "box_backup_updating_exif")" ":$(l "box_backup_mode_${TARGET_MODE}")" ":$(l "box_backup_counting_images")"


		# select directory and filename as DirFile and replace spaces by placeholder
		DB_STR=$(sudo sqlite3 "${DB}" "select ID, Directory || '/' || File_Name as DirFile, LbbRating from EXIF_DATA where LbbRating != Rating or Rating is null" | sed 's/\ /##\*\*##/g')
		IFS=$'\n' read -rd '' -a DB_ARRAY <<<"${DB_STR}"
		unset IFS

		#prepare loop to create thumbnails
		DB_COUNT=${#DB_ARRAY[@]}

		START_TIME=$(get_uptime_seconds)
		LAST_MESSAGE_TIME=$START_TIME

		LCD1="$(l "box_backup_updating_exif")" # header1
		LCD2="$(l "box_backup_mode_${TARGET_MODE}")" # header2

		progressmonitor "${START_TIME}" "${DB_COUNT}" "0" "${LCD1}" "${LCD2}" ""

		for ((i=0; i < ${#DB_ARRAY[@]}; i++)); do
			#replace substitute of space by space
			MEDIA_ID="$(echo ${DB_ARRAY[$i]} | cut -d'|' -f1)"
			MEDIA_FILENAME="${TARGET_PATH}/$(echo ${DB_ARRAY[$i]} | cut -d'|' -f2 | sed 's/##\*\*##/\ /g')"
			MEDIA_LBB_RATING="$(echo ${DB_ARRAY[$i]} | cut -d'|' -f3)"

			sudo exiftool -overwrite_original -Rating="${MEDIA_LBB_RATING}" "${MEDIA_FILENAME}"
			sudo sqlite3 "${DB}" "update EXIF_DATA set Rating=${MEDIA_LBB_RATING} where ID=${MEDIA_ID};"

			progressmonitor "${START_TIME}" "${DB_COUNT}" "${i}" "${LCD1}" "${LCD2}" ""

		done

	fi

# umount (try, state unknown)
	umount_device "usb_1"
	umount_device "usb_2"
	umount_device "${const_IOS_MOUNT_POINT}"
	sudo fusermount -uz "${const_CLOUD_MOUNT_POINT}" 2>/dev/null

# Power off
	if [ "${SECONDARY_BACKUP_FOLLOWS}" = "false" ]; then
		#remove leading spaces
		MESSAGE_DISPLAY="$(echo -e "${MESSAGE_DISPLAY}" | sed -e 's/^[[:space:]]*//')"

		source "${WORKING_DIR}/poweroff.sh" "poweroff" "${POWER_OFF_FORCE}" "${MESSAGE_DISPLAY}" "${TRANSFER_INFO_DISP::-2}"
	fi
