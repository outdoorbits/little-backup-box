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
import sys

import lib_setup

class start_rclone_gui(object):
	def __init__(self,update_gui=False):
		#arguments
		self.update_gui	= update_gui

		#objects
		self.__setup	= lib_setup.setup()

		#setup
		self.RCLONE_CONFIG_FILE	= f"{self.__setup.get_val('const_MEDIA_DIR')}/{self.__setup.get_val('const_RCLONE_CONFIG_FILE')}"

		self.conf_PASSWORD				= base64.b64decode(self.__setup.get_val('conf_PASSWORD')).decode("utf-8")

	def run(self):
		# kill rclone-gui
		try:
			subprocess.run("sudo pkill -f 'rclone rcd --rc-web-gui'",shell=True)
		except:
			print("Error: sudo pkill -f 'rclone rcd --rc-web-gui'")

		# prepare rclone config file
		if not os.path.isfile(self.RCLONE_CONFIG_FILE):
			try:
				open(self.RCLONE_CONFIG_FILE,'w').close()
			except:
				print(f"open({self.RCLONE_CONFIG_FILE},'w')")

		try:
			subprocess.run(f"sudo chmod 777 {self.RCLONE_CONFIG_FILE}")
		except:
			print(f"Error: sudo chmod 777 {self.RCLONE_CONFIG_FILE}")

		PassWord	= self.conf_PASSWORD if self.conf_PASSWORD else 'lbb'

		UpdateGUIArgument	= '--rc-web-gui-force-update' if self.update_gui else ''

		Command	= f"sh -c 'sudo rclone rcd --rc-web-gui {UpdateGUIArgument} --rc-web-gui-no-open-browser --rc-addr :5572 --config {self.RCLONE_CONFIG_FILE} --rc-user lbb --rc-pass \'{PassWord}\' &'"
		try:
			subprocess.run(Command,shell=True)
		except:
			print('Error starting rclone gui')

if __name__ == "__main__":
	update_gui	= False
	if len(sys.argv) > 1:
		update_gui	= (sys.argv[1] == 'True')

	start_rclone_gui(update_gui=update_gui).run()
