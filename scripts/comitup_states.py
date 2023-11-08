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

import subprocess
import sys

import lib_display

class comitup_states(object):
	def __init__(self):
		self.__display	= lib_display.display()

	def new_status(self,status):
		# display new status
		if status:
			self.__display.message([':Comitup:', f':{status}'])

		# setup apache ports
		ApachePortsConf	= '/etc/apache2/ports.conf'

		BasicPorts	= [
			8000,
			443,
			81,
			8443
		]

		with open(ApachePortsConf,'w') as f:
			for Port in BasicPorts:
				f.write(f'Listen {Port}\n')

			if len(sys.argv) > 1:
				if status == 'CONNECTED':
					f.write(f'Listen 80\n')

		subprocess.run('service apache2 restart || service apache2 start', shell=True)

if __name__ == "__main__":
	try:
		status	= sys.argv[1]
	except:
		status	= ''

	comitup_states().new_status(status)
