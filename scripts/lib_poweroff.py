#!/usr/bin/env python

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

import os
import re
import sqlite3
import subprocess
import sys
import time

import lib_display
import lib_language
import lib_setup
import lib_storage
import lib_system

class poweroff(object):

	def __init__(self, Action='poweroff', TransferInfoDisplay=[], SummaryDisplay=[]):
		# Action:	['poweroff','reboot'] or anything else for no action

		# Arguments
		self.Action					= Action
		self.TransferInfoDisplay	= TransferInfoDisplay
		self.SummaryDisplay			= SummaryDisplay

		# objects
		self.__setup		= lib_setup.setup()
		self.__lan			= lib_language.language()
		self.__display		= lib_display.display()

		# setup
		self.conf_POWER_OFF 				= self.__setup.get_val('conf_POWER_OFF')
		self.conf_DISP_FRAME_TIME			= self.__setup.get_val('conf_DISP_FRAME_TIME')
		self.const_LOGFILE					= self.__setup.get_val('const_LOGFILE')
		self.conf_DISP_BLACK_ON_POWER_OFF	= self.__setup.get_val('conf_DISP_BLACK_ON_POWER_OFF')
		self.const_DISPLAY_CONTENT_FOLDER	= self.__setup.get_val('const_DISPLAY_CONTENT_FOLDER')

	def poweroff(self):
		# Set the PWR LED ON to indicate that the no backup is active
		lib_system.rpi_leds(trigger='none',brightness='1')

		# umount
		lib_storage.umount(self.__setup,'all')

		if self.Action in ['poweroff','reboot']:

			if self.Action == 'poweroff':
				self.__display.message(
					[
						'set:clear'
					] +
					self.SummaryDisplay +
					[
						f"s=hc:{self.__lan.l('box_poweroff_poweroff')}",
						f"s=hc:{self.__lan.l('box_poweroff_do_not_unplug')}",
						f"s=hc:{self.__lan.l('box_poweroff_while_act_led_on_1')}",
						f"s=hc:{self.__lan.l('box_poweroff_while_act_led_on_2')}"
					] +
					self.TransferInfoDisplay
				)
			elif self.Action == 'reboot':
				self.__display.message(
					[
						'set:clear',
						f"s=hc:{self.__lan.l('box_poweroff_rebooting')}...",
						f"s=hc:{self.__lan.l('box_poweroff_do_not_unplug')}!"
					] +
					self.TransferInfoDisplay
				)

		# remaining display pages
			remainingPages = 0
			# Iterate directory
			for Line in os.listdir(self.const_DISPLAY_CONTENT_FOLDER):
				if os.path.isfile(os.path.join(self.const_DISPLAY_CONTENT_FOLDER, Line)):
					remainingPages += 1

			time.sleep((remainingPages + 3) * self.conf_DISP_FRAME_TIME)

			# cleanup
			open(self.const_LOGFILE,'w').close()

		# Power off
			if self.Action == 'poweroff':
				if self.conf_DISP_BLACK_ON_POWER_OFF:
					self.__display.message(['set:clear'])

				# Set the PWR LED BLINKING to indicate that the system is shutting down
				lib_system.rpi_leds(trigger='timer',delay_on=500,delay_off=500)

				subprocess.run(['sudo', 'halt'])

			elif self.Action == 'reboot':
				# Set the PWR LED BLINKING to indicate that the system is rebooting
				lib_system.rpi_leds(trigger='timer',delay_on=500,delay_off=500)

				subprocess.run(['sudo', 'reboot'])

		else:
			if self.SummaryDisplay:
				self.__display.message(
					self.SummaryDisplay +
					self.TransferInfoDisplay +
					[
						'set:clear',
						f"s=hc:{self.__lan.l('box_poweroff_do_not_unplug')}!",
						f"s=hc:{self.__lan.l('box_poweroff_power_down_via_gui_1')}",
						f"s=hc:{self.__lan.l('box_poweroff_power_down_via_gui_2')}"
					]
				)
			else:
				self.__display.message(
					[
						'set:clear',
						f":{self.__lan.l('box_backup_complete')}.",
					] +
					self.TransferInfoDisplay +
					[
						f"s=hc:{self.__lan.l('box_poweroff_do_not_unplug')}!",
						f"s=hc:{self.__lan.l('box_poweroff_power_down_via_gui_1')}",
						f"s=hc:{self.__lan.l('box_poweroff_power_down_via_gui_2')}"
					]
				)

if __name__ == "__main__":
	if len(sys.argv) >= 2:
		Action					= sys.argv[1]

		try:
			TransferInfoDisplay	= sys.argv[2].split('\n')
		except:
			TransferInfoDisplay	= []

		try:
			SummaryDisplay		= sys.argv[3].split('\n')
		except:
			SummaryDisplay		= []

		poweroff(Action, TransferInfoDisplay, SummaryDisplay).poweroff()
	else:
		pass



