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

import argparse
import json
import subprocess

import lib_setup

class git(object):

	def __init__(self):
		self.setup	= lib_setup.setup()
		self.ActualVersionsDate	= None

	def UpdateAvailable(self):
		return(self.setup.get_val('conf_SOFTWARE_DATE_INSTALLED') != self.setup.get_val('conf_SOFTWARE_DATE_AVAILABLE'))

	def UpdateSetup(self, installation=False):
		self.__get_ActualVersionsDate()

		if not self.ActualVersionsDate is None:
			self.setup.set_val('conf_SOFTWARE_DATE_AVAILABLE', self.ActualVersionsDate)
			if installation:
				self.setup.set_val('conf_SOFTWARE_DATE_INSTALLED', self.ActualVersionsDate)
			self.setup.rewrite_configfile()

	def __get_ActualVersionsDate(self):

		if self.ActualVersionsDate is None:
			command	= ['curl', 'https://api.github.com/repos/outdoorbits/little-backup-box/commits/main']

			try:
				Respository	= json.loads(subprocess.check_output(command).decode())
			except:
				return(None)

			try:
				self.ActualVersionsDate	= Respository['commit']['author']['date']
				return(self.ActualVersionsDate)
			except:
				return(None)
		else:
			return(self.ActualVersionsDate)

if __name__ == "__main__":
	parser = argparse.ArgumentParser(
		description	= 'Tools to control version und update status.',
		add_help	= True,
		epilog		= 'This script can ideally be configured and started via the Little Backup Box web UI.'
	)

	parser.add_argument(
		'--write-installed',
		'-wi',
		action='store_true',
		required =	False,
		help=f'If set, try to get date of last commit and write it to conf_SOFTWARE_DATE_INSTALLED'
	)

	parser.add_argument(
		'--write-available',
		'-wa',
		action='store_true',
		required =	False,
		help=f'If set, try to get date of last commit and write it to conf_SOFTWARE_DATE_AVAILABLE'
	)

	parser.add_argument(
		'--update-available',
		'-u',
		action='store_true',
		required =	False,
		help=f'If set, script returns True or False'
	)

	args	= vars(parser.parse_args())

	gitObj	= git()

	if args['write_installed']:
		gitObj.UpdateSetup(installation=True)
	elif args['write_available']:
		gitObj.UpdateSetup(installation=False)

	if args['update_available']:
		print(gitObj.UpdateAvailable())
