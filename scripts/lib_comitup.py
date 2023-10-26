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

class comitup_conf(object):
	def __init__(self,Password=None):
		#arguments
		self.Password	= Password

		#config
		self.configfile	= '/etc/comitup.conf'

	def run(self):
		try:
			with open(self.configfile,'w') as f:
				f.write('ap_name: little-backup-box-<nnnn>\n')
				f.write('web_service: apache2.service\n')
				f.write('external_callback: /var/www/little-backup-box/apache_ports.sh\n')
				if self.Password:
					if (
						(len(self.Password) >= 8) and
						(len(self.Password) <= 63)
					):
						f.write(f'ap_password: {self.Password}\n')
		except:
			print("Error writing comitup config file.")



if __name__ == "__main__":
	Password	= None
	if len(sys.argv) > 1:
		Password	= sys.argv[1]

	comitup_conf(Password=Password).run()
