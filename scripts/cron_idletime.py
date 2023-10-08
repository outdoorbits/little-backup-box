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

			CmdRunnerActive	= os.path.isfile(self.const_CMD_RUNNER_LOCKFILE)

			CompareTime	= time.time()

			LbbLogfileAgeSec	= IdleSecToPowerOff
			if os.path.isfile(self.const_LOGFILE):
				LbbLogfileAgeSec	= (time.time() - os.stat(self.const_LOGFILE).st_mtime)

			ApacheLogfileAgeSec	= IdleSecToPowerOff
			if os.path.isfile(self.ApacheAccessLogfile):
				ApacheLogfileAgeSec	= (time.time() - os.stat(self.ApacheAccessLogfile).st_mtime)

			#first layer: Is idletime reached?
			if (
				(not CmdRunnerActive) and
				(lib_system.get_uptime_sec() >= IdleSecToPowerOff) and
				(LbbLogfileAgeSec >= IdleSecToPowerOff) and
				(ApacheLogfileAgeSec >= IdleSecToPowerOff)
			):
				#second layer: Are rsync or gphoto2 active?
				rsync_active	= True
				if (
					(subprocess.run('pgrep rsync',shell=True).returncode > 0) and 	# no match
					(subprocess.run('pgrep gphoto2',shell=True).returncode > 0)		# no match
				):
					lib_poweroff.poweroff(Action='poweroff', SummaryDisplay=[self.__lan.l('box_poweroff_idle_time_reached')]).poweroff()

if __name__ == "__main__":
	idletime().check()
