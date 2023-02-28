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

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

#load DEVICES library
. "${WORKING_DIR}/lib-devices.sh"

#load language library
. "${WORKING_DIR}/lib-language.sh"

#Arguments
ACTION="${1}"
FORCE="${2}"
MESSAGE="${3}"
TRANSFER_INFO="${4}"

TRANSFER_INFO_MOD="${TRANSFER_INFO}"
if [ -z ${TRANSFER_INFO_MOD} ]; then
	TRANSFER_INFO_MOD="+"
fi

# predefined values?
if [ "${POWER_OFF}" = "" ]; then POWER_OFF="${conf_POWER_OFF}"; fi

# Power off
if [ "${POWER_OFF}" = "true" ] || [ "${FORCE}" = "force" ]; then
	# umount
	umount_device "usb_1"
	umount_device "usb_2"
	sudo fusermount -uz "${const_IOS_MOUNT_POINT}"
	sudo fusermount -uz "${const_CLOUD_MOUNT_POINT}"

	if [ ! -z "${MESSAGE}" ]; then
		lcd_message "${MESSAGE}" "+" "+" "+" "+"
		sleep ${const_DISPLAY_HOLD_SEC}
	fi

	if [ ! -z "${TRANSFER_INFO}" ]; then
		lcd_message "${TRANSFER_INFO}" "+" "+" "+" "+"
		sleep ${const_DISPLAY_HOLD_SEC}
	fi

	if [ "${ACTION}" = "poweroff" ]; then
		lcd_message "+$(l 'box_poweroff_poweroff')" "+$(l 'box_poweroff_do_not_unplug')" "+$(l 'box_poweroff_while_act_led_on_1')" "+$(l 'box_poweroff_while_act_led_on_2')" "${TRANSFER_INFO_MOD}" "+"
	elif [ "${ACTION}" = "reboot" ]; then
		lcd_message  "+$(l 'box_poweroff_rebooting')..." "+$(l 'box_poweroff_do_not_unplug')!" "${TRANSFER_INFO_MOD}" "+" "+" "+"
	fi

	# cleanup
	echo "" | sudo tee "${const_LOGFILE}"
	echo "" | sudo tee "${const_LOGFILE_SYNC}"

	if [ "${ACTION}" = "poweroff" ]; then
	if [ "${conf_DISP_BLACK_ON_POWER_OFF}" = "true" ]; then
			sleep 4
			lcd_message "+" "+" "+" "+" "+"
			sleep 1
		fi
		sudo halt
	elif [ "${ACTION}" = "reboot" ]; then
		sudo reboot
	fi

else
	# notify the backup status
	if [ ! -z "${MESSAGE}" ]; then
		lcd_message "${MESSAGE}" "+" "+" "+" "+"
		sleep ${const_DISPLAY_HOLD_SEC}
	fi

	if [ ! -z "${TRANSFER_INFO}" ]; then
		lcd_message "${TRANSFER_INFO}" "+" "+" "+" "+"
		sleep ${const_DISPLAY_HOLD_SEC}
	fi

	if [ -z "${MESSAGE}" ]; then
		lcd_message "+$(l 'box_backup_complete')." "+$(l 'box_poweroff_do_not_unplug')!" "+$(l 'box_poweroff_power_down_via_gui_1')" "+$(l 'box_poweroff_power_down_via_gui_2')" "${TRANSFER_INFO_MOD}" "+"
	else
		lcd_message "+$(l 'box_poweroff_do_not_unplug')!" "+$(l 'box_poweroff_power_down_via_gui_1')" "+$(l 'box_poweroff_power_down_via_gui_2')" "${TRANSFER_INFO_MOD}" "+" "+"
	fi
fi
