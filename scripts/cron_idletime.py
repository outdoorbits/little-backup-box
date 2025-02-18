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
from pathlib import Path
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
		self.ApacheAccessLogfile				= '/var/log/apache2/lbb-access.log'
		self.ApacheRcloneAccessLogfile			= '/var/log/apache2/rclone-access.log'
		self.proftpdAccessLogfile				= '/var/log/proftpd/proftpd.log'

		#objects
		self.__setup	= lib_setup.setup()
		self.__lan		= lib_language.language()

		#setup
		self.conf_POWER_OFF_IDLE_TIME	= self.__setup.get_val('conf_POWER_OFF_IDLE_TIME')

		self.const_LOGFILE				= self.__setup.get_val('const_LOGFILE')
		self.const_CMD_RUNNER_LOCKFILE	= self.__setup.get_val('const_CMD_RUNNER_LOCKFILE')

	def check(self):
		if self.conf_POWER_OFF_IDLE_TIME == 0:
			return('idletime not configured')

		IdleSecToPowerOff	= self.conf_POWER_OFF_IDLE_TIME * 60

		CompareTime	= time.time()

		# uptime
		UpTime	= lib_system.get_uptime_sec()
		if UpTime < IdleSecToPowerOff:
			return(f'idletime: uptime < idletime ({UpTime}s < {IdleSecToPowerOff}s)')

		# logfile logmonitor
		LbbLogfileAgeSec			= CompareTime - os.stat(self.const_LOGFILE).st_mtime if os.path.isfile(self.const_LOGFILE) else IdleSecToPowerOff
		if LbbLogfileAgeSec < IdleSecToPowerOff:
			return(f'idletime: logfile logmonitor idletime not reached ({LbbLogfileAgeSec}s < {IdleSecToPowerOff}s)')

		# logfile apache2
		ApacheLogfileAgeSec			= CompareTime - os.stat(self.ApacheAccessLogfile).st_mtime if os.path.isfile(self.ApacheAccessLogfile) else IdleSecToPowerOff
		if ApacheLogfileAgeSec < IdleSecToPowerOff:
			return(f'idletime: logfile apache2 idletime not reached ({ApacheLogfileAgeSec}s < {IdleSecToPowerOff}s)')

		# logfile apache2 rclone gui
		ApacheRcloneLogfileAgeSec	= CompareTime - os.stat(self.ApacheRcloneAccessLogfile).st_mtime if os.path.isfile(self.ApacheRcloneAccessLogfile) else IdleSecToPowerOff
		if ApacheRcloneLogfileAgeSec < IdleSecToPowerOff:
			return(f'idletime: logfile rclone gui idletime not reached ({ApacheRcloneLogfileAgeSec}s < {IdleSecToPowerOff}s)')

		# logfile proftpd
		proftpdLogfileAgeSec			= CompareTime - os.stat(self.proftpdAccessLogfile).st_mtime if os.path.isfile(self.proftpdAccessLogfile) else IdleSecToPowerOff
		if proftpdLogfileAgeSec < IdleSecToPowerOff:
			return(f'idletime: logfile proftpd idletime not reached ({proftpdLogfileAgeSec}s < {IdleSecToPowerOff}s)')

		# check processes
		for process in [
			['--exact',	'rsync'],
			['--exact',	'gphoto2'],
			['--full',	'rclone check'],
			['--full',	'rclone copy'],
			['--full',	'rclone move']
		]:
			if subprocess.run(['pgrep'] + process).returncode == 0:
				# secure gap between processes by updating timestamp of logfile
				Path(self.const_LOGFILE).touch()

				return(f'idletime: active process={process}')

		# CmdRunnerActive?
		if os.path.isfile(self.const_CMD_RUNNER_LOCKFILE):
			return('idletime: active process=cmd_runner')

		# shutdown
		lib_poweroff.poweroff(Action='poweroff', DisplayMessage=[self.__lan.l('box_poweroff_idle_time_reached')]).poweroff()

if __name__ == "__main__":
	print(idletime().check())
