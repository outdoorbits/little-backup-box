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

# installs a workaround for luma.core to enable the display

import luma.core as core
import os
import shutil


def install_workaround():
	WORKING_DIR	= os.path.dirname(__file__)
	lib_path	= os.path.dirname(core.__file__)

	files_to_copy	= [
			'lib.py',
			'rpi_gpio_bridge.py'
		]

	for file_to_copy in files_to_copy:
		source	= f'{WORKING_DIR}/{file_to_copy}'
		target	= f'{lib_path}/{file_to_copy}'

		shutil.copy(source,target)
		shutil.chown(target,user='root',group='root')
		os.chmod(target,0o644)

	print('Raspberry Pi 5 display workaround installed.')

if __name__ == "__main__":
	install_workaround()
