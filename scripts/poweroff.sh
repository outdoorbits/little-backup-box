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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
# dos2unix "$CONFIG"
source "$CONFIG"

#load time library
. "${WORKING_DIR}/lib-time.sh"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load DISPLAY library
. "${WORKING_DIR}/lib-display.sh"

#load DEVICES library
. "${WORKING_DIR}/lib-devices.sh"

#load language library
. "${WORKING_DIR}/lib-language.sh"

#Arguments
ACTION="${1}"
FORCE="${2}"
MESSAGE="${3}"
TRANSFER_INFO="${4}"

# Set the PWR LED ON to indicate that the no backup is active
sudo sh -c "echo 'none' >/sys/class/leds/PWR/trigger"
sudo sh -c "echo 1 >/sys/class/leds/PWR/brightness"

# predefined values?
if [ "${POWER_OFF}" = "" ]; then POWER_OFF="${conf_POWER_OFF}"; fi

# Power off
if [ "${POWER_OFF}" = "true" ] || [ "${FORCE}" = "force" ]; then
	# shutdown

	# umount
	umount_device "usb_1"
	umount_device "usb_2"
	sudo fusermount -uz "${const_IOS_MOUNT_POINT}"
	sudo fusermount -uz "${const_CLOUD_MOUNT_POINT}"

	if [ ! -z "${MESSAGE}" ]; then
		disp_message "set:clear" "${MESSAGE}"
		sleep ${conf_DISP_FRAME_TIME}
	fi

	if [ ! -z "${TRANSFER_INFO}" ]; then
		disp_message "set:clear" "${TRANSFER_INFO}"
		sleep ${conf_DISP_FRAME_TIME}
	fi

	if [ "${ACTION}" = "poweroff" ]; then
		disp_message "set:clear" ":$(l 'box_poweroff_poweroff')" ":$(l 'box_poweroff_do_not_unplug')" ":$(l 'box_poweroff_while_act_led_on_1')" ":$(l 'box_poweroff_while_act_led_on_2')" "${TRANSFER_INFO}"
	elif [ "${ACTION}" = "reboot" ]; then
		disp_message "set:clear" ":$(l 'box_poweroff_rebooting')..." ":$(l 'box_poweroff_do_not_unplug')!" "${TRANSFER_INFO}"
	fi

	# cleanup
	echo "" | sudo tee "${const_LOGFILE}"
	echo "" | sudo tee "${const_LOGFILE_SYNC}"

	if [ "${ACTION}" = "poweroff" ]; then
		if [ "${conf_DISP_BLACK_ON_POWER_OFF}" = "true" ]; then

			remaining_pages=$(ls -1 ${const_DISPLAY_CONTENT_FOLDER} | wc -l)
			sleep $(( (${remaining_pages} + 3) * ${conf_DISP_FRAME_TIME} ))

			disp_message "set:clear"
			sleep $(( ${conf_DISP_FRAME_TIME} + 1 ))
		fi
		sudo halt
	elif [ "${ACTION}" = "reboot" ]; then
		sudo reboot
	fi

else
	# no shutdown; notify the backup status
	if [ ! -z "${MESSAGE}" ]; then
		disp_message "set:clear" "${MESSAGE}"
		sleep ${conf_DISP_FRAME_TIME}
	fi

	if [ ! -z "${TRANSFER_INFO}" ]; then
		disp_message "set:clear" "${TRANSFER_INFO}"
		sleep ${conf_DISP_FRAME_TIME}
	fi

	if [ -z "${MESSAGE}" ]; then
		disp_message "set:clear" "s=b:$(l 'box_backup_complete')." "s=b:$(l 'box_poweroff_do_not_unplug')!" "s=b:$(l 'box_poweroff_power_down_via_gui_1')" "s=b:$(l 'box_poweroff_power_down_via_gui_2')" "s=b${TRANSFER_INFO}"
	else
		disp_message "set:clear" "s=b:$(l 'box_poweroff_do_not_unplug')!" "s=b:$(l 'box_poweroff_power_down_via_gui_1')" "s=b:$(l 'box_poweroff_power_down_via_gui_2')" "s=b${TRANSFER_INFO}"
	fi
fi
