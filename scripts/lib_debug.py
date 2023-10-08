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

from datetime import datetime
import sys


class debug(object):
	def __init__(self):
		self.debugbasetime=int(datetime.now().timestamp())

	def d(self,debugstring):
		Message	= f"{int(datetime.now().timestamp()) - self.debugbasetime}\t{__name__}\t{debugstring}"
		print(Message,file=sys.stderr)

		with open('/var/www/little-backup-box/lbb.log','a') as f:
			f.write(f"{Message}\n")
