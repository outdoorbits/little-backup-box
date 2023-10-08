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
from urllib import request
import sys

def get_IP():
	IP	= subprocess.check_output(['hostname','-I']).decode().replace(' ','\n').strip()

	return(IP)

def get_internet_status():
	try:
		request.urlopen('https://google.com', timeout=5)
		return(True)
	except:
		pass

	return(False)

if __name__ == "__main__":
	Mode	= None
	try:
		Mode	= sys.argv[1]
	except:
		pass

	if Mode == 'ip':
		print (get_IP())

	if Mode == 'internet_status':
		print (get_internet_status())
