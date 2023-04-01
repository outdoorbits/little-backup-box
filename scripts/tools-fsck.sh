#!/usr/bin/env bash

# Author: Stefan Saam, github@saams.de

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

# arguments
DEVICE="${1}" # LUM-Alpha like sda or sda1
MODE="${2}" # 'check' or 'repair'

# get relevant information about the device
USB_DEVICE="$(sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep "^PATH=\"/dev/${DEVICE}\"")"

# extract the file system type
DEVICE_FSTYPE=$(echo ${USB_DEVICE} | awk '{for(i=1;i<=NF;i++) print $i}' | grep "^FSTYPE=" | cut -d'"' -f 2)

COMMANDS=()

if [ ! -z "${DEVICE_FSTYPE}" ]; then

	if [ "${MODE}" = "check" ]; then

		COMMANDS+=("sudo umount '/dev/${DEVICE}'")
		COMMANDS+=("sudo fsck.${DEVICE_FSTYPE} '/dev/${DEVICE}'")

	elif [ "${MODE}" = "repair" ]; then

		if [[ " exfat " =~ " ${DEVICE_FSTYPE} " ]]; then
			COMMANDS+=("sudo umount '/dev/${DEVICE}'")
			COMMANDS+=("sudo fsck.${DEVICE_FSTYPE} -p -y  '/dev/${DEVICE}'")
		else
			COMMANDS+=("sudo umount '/dev/${DEVICE}'")
			COMMANDS+=("sudo fsck.${DEVICE_FSTYPE} -p -f -y  '/dev/${DEVICE}'")
		fi

	fi

fi


if [ "${#COMMANDS[@]}" -gt "0" ]; then
	for command in "${COMMANDS[@]}"; do
    	echo -e "${NEWLINE}> ${command}"
		eval "${command}"
		NEWLINE="\n"
	done
else
	echo 'Error: Not configured. Please report this bug.'
fi
