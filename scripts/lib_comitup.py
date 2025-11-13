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

import base64
import os
import psutil
import subprocess
import sys

import lib_display
import lib_language
import lib_setup

# import lib_debug
# xx=lib_debug.debug()

class comitup(object):
	def __init__(self):

		#config
		self.configfile	= '/etc/comitup.conf'

		#objects
		self.__setup	= lib_setup.setup()
		self.__lan		= lib_language.language()
		self.__display	= lib_display.display()

		self.__conf_DISP_FRAME_TIME	= self.__setup.get_val('conf_DISP_FRAME_TIME')

	def config(self, Password=None): # use general password if None is given

		if Password is None:
			Password	= base64.b64decode(self.__setup.get_val('conf_WIFI_PASSWORD')).decode("utf-8")

		try:
			with open(self.configfile,'w') as f:
				f.write('ap_name: little-backup-box-<nnnn>\n')
				f.write('web_service: apache2.service\n')
				f.write('external_callback: /var/www/little-backup-box/comitup-states.sh\n')
				if Password:
					if (
						(len(Password) >= 8) and
						(len(Password) <= 63)
					):
						f.write(f'ap_password: {Password}\n')
		except:
			print("Error writing comitup config file.")

	def new_status(self, status):
		# display new status
		status_translated	= None
		if status in ['HOTSPOT', 'CONNECTING', 'CONNECTED']:
			status_translated	= self.__lan.l(f'box_comitup_status_{status}')
			status_translated	= status_translated if status_translated!=f'box_comitup_status_{status}' else status
		elif status == 'RESET':
			status_translated	= self.__lan.l('box_comitup_reset_done')

		if status_translated is not None:
			self.__display.message([f'set:temp,time={self.__conf_DISP_FRAME_TIME * 4}', ':Comitup:', f':{status_translated}'], logging=False)

		# setup apache ports
		ApachePortsConf	= '/etc/apache2/ports.conf'

		BasicPorts	= [
			81,
			443,
			8080,
			8443,
			8843
		]

		with open(ApachePortsConf,'w') as f:
			for Port in BasicPorts:
				f.write(f'Listen {Port}\n')

			if not (status in ['HOTSPOT', 'RESET'] or self.check_hotspot()):
				f.write(f'Listen 80\n')

		subprocess.run('service apache2 restart || service apache2 start', shell=True)

	def check_hotspot(self):
		return(len([p for p in psutil.process_iter() if 'comitup-web' in p.name()]) > 0)

	def reset(self):
		try:
			subprocess.run(['sudo', 'comitup-cli', 'd'])
			subprocess.run(['sudo', 'systemctl', 'restart', 'comitup'])
		except:
			pass
		else:
			# adapt apache ports
			self.new_status('RESET')


if __name__ == "__main__":
	try:
		Mode	= sys.argv[1]
	except:
		Mode	= ''

	if Mode == '--config':
		try:
			Password	= sys.argv[2]
		except:
			Password	= None

		comitup().config(Password)

	elif Mode == '--set_status':
		try:
			Status	= sys.argv[2]
		except:
			Status	= ''

		if Status:
			comitup().new_status(Status)

	elif Mode == '--check_hotspot':
		print('active' if comitup().check_hotspot() else 'inactive')

	elif Mode == '--reset':
		comitup().reset()
