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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Config
MOUNTED_DEVICES=()
UUID_USB_1=""
UUID_USB_2=""

CLOUDSERVICE=""

SYNC_TIME_OVERHEATING_ESTIMATED_SEC=120
SYNC_TIME_OVERHEATING_WAIT_SEC=60

#####################################
# SOURCE AND DESTINATION DEFINTIONS #
#####################################

# START

# To add a new definition, specify the desired arguments to the list
SOURCE_ARG="${1}"
DESTIN_ARG="${2}"

# Source definition
if [[ " storage camera ios internal " =~ " ${SOURCE_ARG} " ]]; then
	SOURCE_MODE="${SOURCE_ARG}"
else
	SOURCE_MODE="storage"
fi

# Destination definition
if [[ "${DESTIN_ARG}" =~ ^cloud_.* ]]; then
	DEST_MODE="cloud"
	CLOUDSERVICE=${DESTIN_ARG#"cloud_"}
	if [ -z "${CLOUDSERVICE}" ]; then
		DEST_MODE=""
	fi
else
	if [[ " internal external rsyncserver " =~ " ${DESTIN_ARG} " ]]; then
		DEST_MODE="${DESTIN_ARG}"
	else
		DEST_MODE="external"
	fi
fi

if [ "${SOURCE_MODE}" = "${DEST_MODE}" ]; then
		lcd_message "$(l 'box_backup_invalid_mode_combination_1')" "$(l 'box_backup_invalid_mode_combination_2')" "$(l 'box_backup_invalid_mode_combination_3')"
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

#load CLOUDS library
. "${WORKING_DIR}/lib-clouds.sh"

#load Systeminfo library
. "${WORKING_DIR}/lib-systeminfo.sh"

#load language library
. "${WORKING_DIR}/lib-language.sh"

# log
log_message "Source: ${SOURCE_MODE}"
log_message "Destination: ${DEST_MODE} ${CLOUDSERVICE}"

function calculate_files_to_sync() {
	#sets $FILES_TO_SYNC

	# To define a new method, add an elif block (example below)

	if [[ " storage ios internal " =~ " ${SOURCE_MODE} " ]]; then
		# Source storage ios internal
		if [ ${DEST_MODE} = "rsyncserver" ]; then
			FILES_TO_SYNC=$(sudo sshpass -p "${conf_RSYNC_conf_PASSWORD}" rsync -avh --stats --exclude "*.id" --exclude "*tims/" --dry-run "${SOURCE_PATH}"/ "${RSYNC_CONNECTION}/${BACKUP_PATH}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3)=="Number of created files:"){print $(i+4)}}' | sed s/,//g)
		else
			FILES_TO_SYNC=$(sudo rsync -avh --stats --exclude "*.id" --exclude "*tims/" --dry-run "${SOURCE_PATH}"/ "${BACKUP_PATH}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3)=="Number of created files:"){print $(i+4)}}' | sed s/,//g)
		fi

	#     elif [ "${SOURCE_MODE}" = "NEW_SOURCE_DEFINITION" ];
	#     then
	#         FILES_TO_SYNC=...

	elif [ "${SOURCE_MODE}" = "camera" ]; then
		# Source camera
		sudo mkdir -p "${BACKUP_PATH}"
		cd "${BACKUP_PATH}"
		FILES_TO_SYNC=0

		for Camera_Sync_Folder in "${Camera_Sync_Folders[@]}"
		do
			FILES_IN_FOLDER=$(sudo gphoto2 --list-files --folder "${Camera_Sync_Folder}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+3) " " $(i+4) " " $(i+5)=="There are files in folder" || $i " " $(i+1) " " $(i+3) " " $(i+4) " " $(i+5)=="There is file in folder"){SUM+=$(i+2);}} END {print SUM}')
			if [ -z "${FILES_IN_FOLDER}" ]; then
				FILES_IN_FOLDER=0
			fi

			FILES_TO_SYNC=$(( ${FILES_TO_SYNC} + ${FILES_IN_FOLDER} ))
		done

		cd

	else
		# no defined mode selected
		lcd_message "+$(l 'box_backup_no_valid_source_mode_1')" "+$(l 'box_backup_no_valid_source_mode_1')" "+$(l 'box_backup_no_valid_source_mode_1')" "+2"
		exit 1
	fi

	if [ -z "${FILES_TO_SYNC}" ]; then
		FILES_TO_SYNC="0"
	fi

}

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Unmount devices
umount_device "usb_1"
umount_device "usb_2"
sudo fusermount -uz "${const_IOS_MOUNT_POINT}"
sudo fusermount -uz "${const_CLOUD_MOUNT_POINT}"

#########################
# MANAGE STORAGE DEVICE #
#########################

# START

# To define a new method, add an elif block (example below)

if [ "${DEST_MODE}" = "external" ]; then
	# External mode
	# If display support is enabled, display the specified message

	lcd_message "$(l 'box_backup_insert_storage_1')" "$(l 'box_backup_insert_storage_2')"

	# Wait for a USB storage device (e.g., a USB flash drive)
	UUID_USB_1=$(mount_device "usb_1" true "${UUID_USB_1}" "${UUID_USB_2}")
	MOUNTED_DEVICES+=("${UUID_USB_1}")

	STORAGE_PATH="${const_STORAGE_MOUNT_POINT}"

	# notify that the storage device has been mounted
	ret="$(get_storage_spaces ${STORAGE_PATH})"

	IFS="|"
	set -- $ret

	STOR_SIZE="$(l 'box_backup_storage_size'): $1"
	STOR_FREE="$(l 'box_backup_storage_free'): $3"

	unset IFS

	lcd_message "$(l 'box_backup_ext_storage_ok')" "${STOR_SIZE}" "${STOR_FREE}"

	if [ $conf_DISP = true ]; then
		sleep 2
	fi

elif [ "${DEST_MODE}" = "internal" ]; then
	# Internal mode
	STORAGE_PATH="${const_INTERAL_BACKUP_DIR}"

	ret="$(get_storage_spaces ${STORAGE_PATH})"

	IFS="|"
	set -- $ret

	STOR_SIZE="$(l 'box_backup_storage_size'): $1"
	STOR_FREE="$(l 'box_backup_storage_free'): $3"

	unset IFS

	# If display support is enabled, notify that the storage device has been mounted
	lcd_message "$(l 'box_backup_int_storage_ok')" "${STOR_SIZE}" "${STOR_FREE}"

	if [ $conf_DISP = true ]; then
		sleep 2
	fi

elif [ "${DEST_MODE}" = "rsyncserver" ]; then
		RSYNC_CONNECTION="rsync://${conf_RSYNC_USER}@${conf_RSYNC_SERVER}:${conf_RSYNC_PORT}/${conf_RSYNC_SERVER_MODULE}"
		STORAGE_PATH="${conf_BACKUP_TARGET_BASEDIR_CLOUD}"

elif [ "${DEST_MODE}" = "cloud" ]; then
		lcd_message "+$(l 'box_backup_waiting_for_cloud_1')" "+$(l 'box_backup_waiting_for_cloud_2')" "+${CLOUDSERVICE}"

		STORAGE_PATH="${const_CLOUD_MOUNT_POINT}/${conf_BACKUP_TARGET_BASEDIR_CLOUD}"

		mount_cloud "${CLOUDSERVICE}" "${const_CLOUD_MOUNT_POINT}"

# elif [ "${DEST_MODE}" = "NEW_STORAGE_DEFINITION" ]; then
#         lcd_message "+$(l 'box_backup__1')" "+$(l 'box_backup__2')"
#         ...
#         # Set storage path
#         STORAGE_PATH

else
	# no defined mode selected
	lcd_message "$(l 'box_backup_no_valid_destination_mode_1')" "$(l 'box_backup_no_valid_destination_mode_2')" "$(l 'box_backup_no_valid_destination_mode_3')"
	exit 1
fi

# END

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

########################
# MANAGE SOURCE DEVICE #
########################

# START

# To define a new method, add an elif block (example below)

if [ "${SOURCE_MODE}" = "storage" ]; then

	# Source storage
	# If display support is enabled, display the specified message
	lcd_message "$(l 'box_backup_insert_source_1')" "$(l 'box_backup_insert_source_2')"

	# Source device
	if [ "${SOURCE_MODE}" = "storage" ]; then
		if [ "${DEST_MODE}" = "external" ]; then
			UUID_USB_2=$(mount_device "usb_2" true "${UUID_USB_1}" "${UUID_USB_2}")
			MOUNTED_DEVICES+=("${UUID_USB_2}")

			# Set SOURCE_PATH
			SOURCE_PATH="${const_SOURCE_MOUNT_POINT}"
		else
			UUID_USB_1=$(mount_device "usb_1" true "${UUID_USB_1}" "${UUID_USB_2}")
			MOUNTED_DEVICES+=("${UUID_USB_1}")

			# Set SOURCE_PATH
			SOURCE_PATH="${const_STORAGE_MOUNT_POINT}"
		fi
	fi

	# notify that the source device has been mounted
	ret="$(get_storage_spaces ${SOURCE_PATH})"
	IFS="|"
	set -- $ret
	STOR_SIZE="$(l 'box_backup_storage_size'): $1"
	STOR_USED="$(l 'box_backup_storage_used'): $2"

	unset IFS

	lcd_message "$(l 'box_backup_source_ok')" "$(l 'box_backup_working')..." "${STOR_SIZE}" "${STOR_USED}"
	if [ $conf_DISP = true ]; then
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
	lcd_message "$(l 'box_backup_connect_ios_1')" "$(l 'box_backup_connect_ios_2')" "$(l 'box_backup_connect_ios_3')"

	# Try to mount the iOS device
	ifuse ${const_IOS_MOUNT_POINT} -o allow_other

	# Waiting for the iOS device to be mounted
	until [ ! -z "$(ls -A ${const_IOS_MOUNT_POINT})" ]; do
		lcd_message "$(l 'box_backup_no_ios_waiting_1')" "$(l 'box_backup_no_ios_waiting_2')..."
		sleep 10
		sudo ifuse ${const_IOS_MOUNT_POINT} -o allow_other
	done

	# Mount iOS device
	SOURCE_PATH="${const_IOS_MOUNT_POINT}/DCIM"

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
	lcd_message "$(l 'box_backup_int_storage_ok')"

	# Set SOURCE_PATH
	SOURCE_PATH="${const_INTERAL_BACKUP_DIR}"

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
	lcd_message "$(l 'box_backup_connect_camera_1')" "$(l 'box_backup_connect_camera_2')"

	# Wait for camera
	DEVICE=$(sudo gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
	while [ -z "${DEVICE}" ]; do
		sleep 1
		DEVICE=$(sudo gphoto2 --auto-detect | grep usb | cut -b 36-42 | sed 's/,/\//')
	done

	# If display support is enabled, notify that the camera is detected
	lcd_message "$(l 'box_backup_camera_ok')" "$(l 'box_backup_working')..."

	# Obtain camera model
	# Create the target directory with the camera model as its name
	CAMERA=$(sudo gphoto2 --summary | grep "Model" | cut -d: -f2 | tr -d '[:space:]')

	lcd_message "${CAMERA}"
	log_message "Camera: ${CAMERA}" 1

	#Set SOURCE_PATH
	# not used

	# Set BACKUP_PATH
	BACKUP_PATH="${STORAGE_PATH}/${CAMERA}"

	# Set SOURCE_IDENTIFIER
	SOURCE_IDENTIFIER="Camera: ${CAMERA}"

	# Define source-folders
	Camera_Search_Folders=()
	Camera_Sync_Folders=()

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
					Camera_Sync_Folders+=("${MaskSetFolder:1}")
				elif [ ! -z "${MaskSetFolder}" ]; then
					Camera_Search_Folders+=("$MaskSetFolder")
				fi
			fi
		done
	fi

	# only if Camera_Search_Folders has no values yet
	if [ ${#Camera_Sync_Folders[@]} -eq 0 ]; then
		Camera_Folders=( $(sudo gphoto2 --list-folders | cut -d"'" -f2 | grep "^/") )

		for Camera_Folder in "${Camera_Folders[@]}"
		do
			log_message "Found folder: ${Camera_Folder}" 3
			for Camera_Search_Folder in "${Camera_Search_Folders[@]}"
			do
				if [[ "${Camera_Folder}" =~ "${Camera_Search_Folder}" ]]; then

					known=false
					for Camera_Sync_Folder in "${Camera_Sync_Folders[@]}"
					do
						if [[ ${Camera_Folder} = ${Camera_Sync_Folder}* ]]; then
							known=true
						fi
					done

					if [ $known = false ]; then
						Camera_Sync_Folders+=("${Camera_Folder}")
					fi
				fi
			done
		done

		if [ ${#Camera_Sync_Folders[@]} -eq 0 ]; then
			Camera_Sync_Folders=("/")
		fi

	fi

	#log Camera_Search_Folders
	log_message "Folders to sync from camera '${CAMERA}':" 1
	for Camera_Sync_Folder in "${Camera_Sync_Folders[@]}"
	do
		log_message " - ${Camera_Sync_Folder} - For use as pattern in Settings ('$(l 'config_backup_camera_folder_mask_header')'): '${CAMERA}:!${Camera_Sync_Folder}'" 1
	done
else
	# no defined mode selected
	lcd_message "+$(l 'box_backup_no_valid_source_mode_1')" "+$(l 'box_backup_no_valid_source_mode_2')" "+$(l 'box_backup_no_valid_source_mode_3')" "+1"
fi

# Set the ACT LED to blink at 500ms to indicate that the source device has been mounted
sudo sh -c "echo 500 > /sys/class/leds/led0/delay_on"


# In case of SYNC_ERROR retry
TRIES_MAX=5
TRIES_DONE=0
SYNC_ERROR="-" # not empty!
FILES_TO_SYNC=0
SYNC_OUTPUT=""

while [[ "${TRIES_MAX}" -gt "${TRIES_DONE}" ]] && [[ "${SYNC_ERROR}" != "" ]]; do

	# RETRIES

	TRIES_DONE=$((TRIES_DONE+1))

	if [ ! -z "${SYNC_OUTPUT}" ]; then
		SYNC_OUTPUT="${SYNC_OUTPUT}\n\n"
	fi

	SYNC_OUTPUT="${SYNC_OUTPUT}---- $(l 'box_backup_try') ${TRIES_DONE} ----\n"

	if [ "${TRIES_DONE}" -gt "1" ]; then
		lcd_message "$(l 'box_backup_try_backup') ${TRIES_DONE} $(l 'box_backup_of') ${TRIES_MAX}"
		sleep 5 # time to stabilize the system after device-lost
	fi

	# Remount devices if "Err.Lost device"
	if [[ "${SYNC_ERROR}" =~ "Err.Lost device" ]]; then
		log_exec "Lost device: pre remount" "sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE" 3

		if [ "${UUID_USB_1}" != "" ]; then
			RESULT_DEVICE_MOUNTED=$(device_mounted "${UUID_USB_1}")
			if [ -z "${RESULT_DEVICE_MOUNTED}" ]; then
				log_message "remount usb_2" 3
				mount_device "usb_1" true "${UUID_USB_1}" "${UUID_USB_2}"
			fi
		fi

		if [ "${UUID_USB_2}" != "" ]; then
			RESULT_DEVICE_MOUNTED=$(device_mounted "${UUID_USB_2}")
			if [ -z "${RESULT_DEVICE_MOUNTED}" ]; then
				log_message "remount usb_2" 3
				mount_device "usb_2" true "${UUID_USB_1}" "${UUID_USB_2}"
			fi
		fi
	fi

########################################
# CALCULATE NUMBER OF FILES TO BACK UP #
########################################

	calculate_files_to_sync

	log_message "Files to sync before backup: ${FILES_TO_SYNC}" 3

	SYNC_START_TIME=$(date +%s)

	# display
	source "${WORKING_DIR}/status-display.sh" &
	PID=$!

##############
# RUN BACKUP #
##############

	# START

	# To define a new method, add an elif block (example below)


	if [[ " storage ios internal " =~ " ${SOURCE_MODE} " ]]; then
		# If source is storage or ios

		if [ ${DEST_MODE} = "rsyncserver" ]; then
			# to rsyncserver
			if [ $conf_LOG_SYNC = true ]; then
				SYNC_OUTPUT="${SYNC_OUTPUT}$(sudo sshpass -p "${conf_RSYNC_conf_PASSWORD}" rsync -avh --mkpath --no-perms --stats --exclude "*.id" --exclude "*tims/" --log-file="${const_LOGFILE_SYNC}" "${SOURCE_PATH}/" "${RSYNC_CONNECTION}/${BACKUP_PATH}/")\n"
				SYNC_RETURN_CODE=$?
				log_pick_file "${const_LOGFILE_SYNC}"
			else
				SYNC_OUTPUT="${SYNC_OUTPUT}$(sudo sshpass -p "${conf_RSYNC_conf_PASSWORD}" rsync -avh --mkpath --no-perms --stats --exclude "*.id" --exclude "*tims/" "${SOURCE_PATH}/" "${RSYNC_CONNECTION}/${BACKUP_PATH}/")\n"
				SYNC_RETURN_CODE=$?
			fi

		else
			# not to rsyncserver
			sudo mkdir -p "${BACKUP_PATH}"

			if [ $conf_LOG_SYNC = true ]; then
				SYNC_OUTPUT="${SYNC_OUTPUT}$(sudo rsync -avh --stats --exclude "*.id" --exclude "*tims/" --log-file="${const_LOGFILE_SYNC}" "${SOURCE_PATH}"/ "${BACKUP_PATH}")\n"
				SYNC_RETURN_CODE=$?
				log_pick_file "${const_LOGFILE_SYNC}"
			else
				SYNC_OUTPUT="${SYNC_OUTPUT}$(sudo rsync -avh --stats --exclude "*.id" --exclude "*tims/" "${SOURCE_PATH}"/ "${BACKUP_PATH}")\n"
				SYNC_RETURN_CODE=$?
			fi

		fi

	#     elif [ "${SOURCE_MODE}" = "NEW_SOURCE_DEFINITION" ];
	#     then
	#     if [ $conf_LOG_SYNC = true ]; then
	#         SYNC_OUTPUT="${SYNC_OUTPUT}$(...)\n"
	#         SYNC_RETURN_CODE=$?
	#         log_pick_file "${const_LOGFILE_SYNC}"
	#     else
	#         SYNC_OUTPUT="${SYNC_OUTPUT}$(...)\n"
	#         SYNC_RETURN_CODE=$?

	elif [ "${SOURCE_MODE}" = "camera" ]; then
		# If source is camera
		# Switch to const_STORAGE_MOUNT_POINT and transfer files from the camera

		sudo mkdir -p "${BACKUP_PATH}"
		cd "${BACKUP_PATH}"

		# gphoto2: Filename-format at backup; %F is undocumented? = path of the file at the camera; $f = filename without suffix; %C=suffix
		for Camera_Sync_Folder in "${Camera_Sync_Folders[@]}"
		do
			if [ $conf_LOG_SYNC = true ]; then
				SYNC_OUTPUT="${SYNC_OUTPUT}$(sudo gphoto2 --filename "%F/%f.%C" --get-all-files --folder "${Camera_Sync_Folder}"  --skip-existing --list-files)\n" #  with debug enabled, backup fails: --debug --debug-loglevel "data" --debug-logfile "${const_LOGFILE_SYNC}"
				SYNC_RETURN_CODE=$?
# 				log_pick_file "${const_LOGFILE_SYNC}"
			else
				SYNC_OUTPUT="${SYNC_OUTPUT}$(sudo gphoto2 --filename "%F/%f.%C" --get-all-files --folder "${Camera_Sync_Folder}" --skip-existing --list-files)\n"
				SYNC_RETURN_CODE=$?
			fi
		done

		cd
	else
		# no defined mode selected
		lcd_message "+$(l 'box_backup_no_valid_source_mode_1')" "+$(l 'box_backup_no_valid_source_mode_1')" "+$(l 'box_backup_no_valid_source_mode_1')" "+3"
		exit 1
	fi

	SYNC_STOP_TIME=$(date +%s)

	# END BACKUP


	# RE-CALCULATE NUMBER OF FILES TO BACK UP
	if [[ " storage ios internal " =~ " ${SOURCE_MODE} " ]]; then
		calculate_files_to_sync
		log_message "Files left to sync after backup: ${FILES_TO_SYNC}" 3
	elif [ "${SOURCE_MODE}" = "camera" ]; then
		#FILES_TO_SYNC in camera-mode only counts the files at the camera, value is not suitable for completition-check
		FILES_TO_SYNC="0"
	fi

	# Check for lost devices
	SYNC_ERROR=""
	for MOUNTED_DEVICE in "${MOUNTED_DEVICES[@]}"; do
		RESULT_DEVICE_MOUNTED=$(device_mounted "${MOUNTED_DEVICE}")
		log_message "Lost device? '${MOUNTED_DEVICE}': '${RESULT_DEVICE_MOUNTED}'" 3

		if [ -z "${RESULT_DEVICE_MOUNTED}" ]; then
			SYNC_ERROR="Err.Lost device!"
			log_message "Lost device '${MOUNTED_DEVICE}': DEVICE LOST"
			log_exec "Lost device" "sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE" 3
			log_message "$(get_abnormal_system_conditions)" 1
		fi
	done

	if [ "${FILES_TO_SYNC}" -gt "0" ]; then
		SYNC_ERROR="${SYNC_ERROR} Files missing!"
		log_message "Files missing: ${FILES_TO_SYNC} files not synced."
		log_exec "Files missing" "sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE" 3
		log_message "$(get_abnormal_system_conditions)" 1
	fi

	# Keep progress on display after finish
	if [ $conf_DISP = true ] && [ -z "${SYNC_ERROR}" ]; then
		sleep 5
	fi

	# Kill the status-display.sh script
	kill $PID

	# Controller- overheating-error?
	SYNC_TIME=$(($SYNC_STOP_TIME - $SYNC_START_TIME))
	log_message "SYNC_RETURN_CODE: ${SYNC_RETURN_CODE}; SYNC_TIME: ${SYNC_TIME}" 3

	if [[ "${SYNC_ERROR}" =~ "Err.Lost device!" ]] && [ "${SYNC_RETURN_CODE}" -gt "0" ] && [ "${SYNC_TIME}" -ge "${SYNC_TIME_OVERHEATING_ESTIMATED_SEC}" ] && [ "${TRIES_MAX}" -gt "${TRIES_DONE}" ]; then
			lcd_message "$(l 'box_backup_error_cooling_1')" "$(l 'box_backup_error_cooling_2') ${SYNC_TIME_OVERHEATING_WAIT_SEC} $(l 'seconds_short') ..." "$(l 'box_backup_error_cooling_3')" "$(l 'box_backup_error_cooling_4')"
			sleep ${SYNC_TIME_OVERHEATING_WAIT_SEC}
	fi

done # retry

# umount (try, state unknown)
umount_device "usb_1"
umount_device "usb_2"
sudo fusermount -uz "${const_IOS_MOUNT_POINT}"
sudo fusermount -uz "${const_CLOUD_MOUNT_POINT}"

# prepare message for mail and power off
if [ -z "${SYNC_ERROR}" ]; then
	MESSAGE="$(l 'box_backup_complete')."
else
	MESSAGE=""
	if [[ "${SYNC_ERROR}" =~ "Err.Lost device!" ]]; then MESSAGE="$(l 'box_backup_lost_device') "; fi
	if [[ "${SYNC_ERROR}" =~ "Files missing!" ]]; then MESSAGE="${MESSAGE}$(l 'box_backup_files_missing')"; fi
fi

# Check internet connection and send
# a notification if the conf_NOTIFY option is enabled
check=$(wget -q --spider http://google.com/)
if [ $conf_NOTIFY = true ] || [ ! -z "$check" ]; then

	if [ ! -z "${MESSAGE}" ]; then
		SUBJ_MSG="${MESSAGE}"
		BODY_MSG="${MESSAGE}\n\n"
	else
		SUBJ_MSG="$(l 'box_backup_complete')"
		BODY_MSG=""
	fi

	send_email "Little Backup Box: $(l 'box_backup_mail_backup') ${SUBJ_MSG}" "${BODY_MSG}$(l 'box_backup_mail_backup_type'): ${SOURCE_MODE} $(l 'box_backup_mail_to') ${DEST_MODE} ${CLOUDSERVICE}\n${SOURCE_IDENTIFIER}\n\n$(l 'box_backup_mail_log'):\n\n${SYNC_OUTPUT}\n\n${TRIES_DONE} $(l 'box_backup_mail_tries_needed')."
fi

# Power off
source "${WORKING_DIR}/poweroff.sh" "poweroff" "" "${MESSAGE}"
