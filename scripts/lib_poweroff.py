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

import lib_clean
import lib_display
import lib_language
import lib_setup
import lib_storage
import lib_system

class poweroff(object):

	def __init__(self, Action='poweroff', DisplayMessage=[]):
		# Action:	['poweroff','reboot'] or anything else for no action

		# Arguments
		self.Action					= Action
		self.DisplayMessage		= DisplayMessage

		# objects
		self.__setup		= lib_setup.setup()
		self.__lan			= lib_language.language()
		self.__display		= lib_display.display()

		# setup
		self.conf_POWER_OFF 				= self.__setup.get_val('conf_POWER_OFF')
		self.conf_DISP_FRAME_TIME			= self.__setup.get_val('conf_DISP_FRAME_TIME')
		self.const_LOGFILE					= self.__setup.get_val('const_LOGFILE')
		self.const_DISPLAY_CONTENT_PATH	= self.__setup.get_val('const_DISPLAY_CONTENT_PATH')

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
					self.DisplayMessage +
					[
						f"s=a:{self.__lan.l('box_poweroff_poweroff')}",
						f"s=a:{self.__lan.l('box_poweroff_do_not_unplug')}",
						f"s=a:{self.__lan.l('box_poweroff_while_act_led_on_1')}",
						f"s=a:{self.__lan.l('box_poweroff_while_act_led_on_2')}"
					]
				)
			elif self.Action == 'reboot':
				self.__display.message(
					[
						'set:clear'
					] +
					self.DisplayMessage +
					[
						f"s=a:{self.__lan.l('box_poweroff_rebooting')}...",
						f"s=a:{self.__lan.l('box_poweroff_do_not_unplug')}!"
					]
				)

			# remaining display pages
			self.__display.wait_for_empty_stack()

			time.sleep(3 * self.conf_DISP_FRAME_TIME)

			# cleanup
			open(self.const_LOGFILE,'w').close()
			lib_clean.clean().cleanup(jobs=['full'])

			# Power off
			if self.Action == 'poweroff':
				self.__display.message(['set:kill'])

				# Set the PWR LED BLINKING to indicate that the system is shutting down
				lib_system.rpi_leds(trigger='timer',delay_on=500,delay_off=500)

				subprocess.run(['sudo', 'halt'])

			elif self.Action == 'reboot':
				self.__display.message(['set:kill'])

				# Set the PWR LED BLINKING to indicate that the system is rebooting
				lib_system.rpi_leds(trigger='timer',delay_on=500,delay_off=500)

				subprocess.run(['sudo', 'reboot'])

		else:
			# cleanup
			open(self.const_LOGFILE,'w').close()
			lib_clean.clean().cleanup(jobs=['full'], skips=['ipmail', 'display_content', 'log'])

			self.__display.message(
				self.DisplayMessage +
				[
					'set:clear',
					f"s=a:{self.__lan.l('box_poweroff_do_not_unplug')}!",
					f"s=a:{self.__lan.l('box_poweroff_power_down_via_gui_1')}",
					f"s=a:{self.__lan.l('box_poweroff_power_down_via_gui_2')}"
				]
			)

if __name__ == "__main__":
	if len(sys.argv) >= 2:
		Action					= sys.argv[1]

		try:
			DisplayMessage	= sys.argv[2].split('\n')
		except:
			DisplayMessage	= []

		poweroff(Action, DisplayMessage).poweroff()
	else:
		pass



