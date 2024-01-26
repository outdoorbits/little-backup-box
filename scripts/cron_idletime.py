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
import subprocess
import time

import lib_language
import lib_poweroff
import lib_setup
import lib_system

class idletime(object):
	def __init__(self):
		#definitions
		self.WORKING_DIR	= os.path.dirname(__file__)
		self.ApacheAccessLogfile	= "/var/log/apache2/lbb-access.log"

		#objects
		self.__setup	= lib_setup.setup()
		self.__lan		= lib_language.language()

		#setup
		self.conf_POWER_OFF_IDLE_TIME	= self.__setup.get_val('conf_POWER_OFF_IDLE_TIME')

		self.const_LOGFILE				= self.__setup.get_val('const_LOGFILE')
		self.const_CMD_RUNNER_LOCKFILE	= self.__setup.get_val('const_CMD_RUNNER_LOCKFILE')

	def check(self):
		if self.conf_POWER_OFF_IDLE_TIME > 0:
			IdleSecToPowerOff	= self.conf_POWER_OFF_IDLE_TIME * 60

			CompareTime	= time.time()

			# uptime
			UpTime	= lib_system.get_uptime_sec()
			if UpTime < IdleSecToPowerOff:
				return(f'idletime: uptime < idletime ({UpTime}s < {IdleSecToPowerOff}s)')

			# logfile logmonitor
			LbbLogfileAgeSec	= IdleSecToPowerOff
			if os.path.isfile(self.const_LOGFILE):
				LbbLogfileAgeSec	= (CompareTime - os.stat(self.const_LOGFILE).st_mtime)

			if LbbLogfileAgeSec < IdleSecToPowerOff:
				return(f'idletime: logfile logmonitor idletime not reached ({LbbLogfileAgeSec}s < {IdleSecToPowerOff}s)')

			# logfile apache2
			ApacheLogfileAgeSec	= IdleSecToPowerOff
			if os.path.isfile(self.ApacheAccessLogfile):
				ApacheLogfileAgeSec	= (CompareTime - os.stat(self.ApacheAccessLogfile).st_mtime)

			if ApacheLogfileAgeSec < IdleSecToPowerOff:
				return(f'idletime: logfile apache2 idletime not reached ({ApacheLogfileAgeSec}s < {IdleSecToPowerOff}s)')

			# check processes
			for process in ['rsync','gphoto2']:
				if subprocess.run(['pgrep','-x',process]).returncode == 0:
					return(f'idletime: active process={process}')

			# CmdRunnerActive?
			if os.path.isfile(self.const_CMD_RUNNER_LOCKFILE):
				return('idletime: active process=cmd_runner')

			# shutdown
			lib_poweroff.poweroff(Action='poweroff', DisplayMessage=[self.__lan.l('box_poweroff_idle_time_reached')]).poweroff()

if __name__ == "__main__":
	print(idletime().check())
