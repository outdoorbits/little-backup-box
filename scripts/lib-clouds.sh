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

function mount_cloud() {

	# Takes2 arguments
	# CLOUDSERVICE
	# MOUNTPOINT

	local CLOUDSERVICE="${1}"
	local MOUNTPOINT="${2}"
	local RESULT=true

	if [ -d "${MOUNTPOINT}" ]; then
		while [ $(cloud_mounted ${CLOUDSERVICE}) = true ]; do
			log_message "cloud umount ${MOUNTPOINT}" 3
			sudo umount "${MOUNTPOINT}"
			sleep 3
		done
		if [ $(cloud_mounted ${CLOUDSERVICE}) = false ]; then
			sudo rm -R ${MOUNTPOINT}/*
		fi

		while [ $(cloud_mounted ${CLOUDSERVICE}) = false ]; do
			log_message "rclone mount ${CLOUDSERVICE}: ${MOUNTPOINT}" 3
			sudo rclone mount ${CLOUDSERVICE}: "${MOUNTPOINT}" --umask 0 --config "${const_RCLONE_CONFIG_FILE}" &
			sleep 3
		done

		RESULT=$(cloud_mounted ${CLOUDSERVICE})

	else
		RESULT=false
		log_message "ERROR: CLOUD_MOUNT_POINT '${MOUNTPOINT}' does not exist." 1
	fi

	log_message "mount_cloud finished ${CLOUDSERVICE}: ${MOUNTPOINT} RESULT=${RESULT}" 3

	echo ${RESULT}
}

function cloud_mounted() {
	# returns true, if cloud is mounted
	# Takes 1 argument:
	# CLOUDSERVICE
	# returns true or false

	# Arguments
	local CLOUDSERVICE=${1}
	local MOUNTSTATUS=""
	local RESULT=true

	MOUNTSTATUS=$(sudo mount | grep "fuse.rclone" | grep "${CLOUDSERVICE}")

	log_message "sudo cloud_mounted mount | grep fuse.rclone | grep ${CLOUDSERVICE}: ${MOUNTSTATUS}" 3

	if [ -z "${MOUNTSTATUS}" ]; then
		RESULT=false
	fi

	log_message "cloud_mounted?: '${CLOUDSERVICE}' = '${RESULT}'" 2

	echo ${RESULT}
}

