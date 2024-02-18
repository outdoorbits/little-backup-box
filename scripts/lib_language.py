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

import lib_setup

import datetime
import os

import shutil
import subprocess
import sys


class language(object):

	def __init__(self):
		self.WORKING_DIR = os.path.dirname(__file__)

		self.setup	= lib_setup.setup()

		self.php = shutil.which('php')

	def l(self,key):
		return (subprocess.check_output([self.php,f"{self.WORKING_DIR}/lib-language-helper.php",key]).decode())


if __name__ == "__main__":
	try:
		key = sys.argv[1]
	except:
		key = None
		pass

	if key:
		lan=language()
		print(lan.l(key))

