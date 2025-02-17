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
import subprocess

import lib_display
import lib_language
import lib_setup

# import lib_debug
# xx=lib_debug.debug()

class proftpd(object):
	def __init__(self):
		self.__setup	= lib_setup.setup()
		self.__display	= lib_display.display()
		self.__lan		= lib_language.language()

		# config
		self.configfile	= '/etc/proftpd/proftpd_lbb_DefaultRoot.conf'

		# values
		self.FTP_DefaultRoot	= ''

	def setDefaultRoot(self, FTP_DefaultRoot=''):

		self.FTP_DefaultRoot	= FTP_DefaultRoot

		# block hacking
		if ' ' in self.FTP_DefaultRoot:
			return(False)
		if ';' in self.FTP_DefaultRoot:
			return(False)
		if '\\' in self.FTP_DefaultRoot:
			return(False)

		# format self.FTP_DefaultRoot
		self.FTP_DefaultRoot	= self.FTP_DefaultRoot.strip()
		self.FTP_DefaultRoot	= self.__setup.get_val('const_MEDIA_DIR') if self.FTP_DefaultRoot == '' else self.FTP_DefaultRoot

		# write config file
		try:
			with open(self.configfile,'w') as f:
				f.write(f'DefaultRoot {self.FTP_DefaultRoot} lbb\n')
				print(f"setDefaultRoot: {self.FTP_DefaultRoot}")
		except:
			print("Error writing proftpd config file.")
			return()

		# restart server
		self.ftpservice('restart')

		return(True)

	def ftpservice(self, job):
		if job in ['start', 'stop', 'reload', 'restart']:
			command	= ['sudo', 'service', 'proftpd', job]
			print(' '.join(command))
			subprocess.run(command)

			self.__display.message([f":{self.__lan.l('box_backup_ftp_started')}", self.FTP_DefaultRoot])

if __name__ == "__main__":

	parser = argparse.ArgumentParser(
		description	= 'Controls the proftpd server.',
		add_help	= True,
		epilog		= 'This script is used by lbb scripts.'
	)

	Actions	= ['setDefaultRoot','server']
	parser.add_argument(
		'--Action',
		'-a',
		choices		= Actions,
		required =	True,
		help=f'Action name, one of {Actions}'
	)

	parser.add_argument(
		'--DefaultRoot',
		'-dr',
		required	= False,
		type		= str,
		default		= '',
		help="Absolute path of ftp root dir."
	)

	jobs	= ['start', 'stop', 'reload', 'restart']
	parser.add_argument(
		'--ServerCommand',
		'-sc',
		required	= False,
		type		= str,
		default		= '',
		help=f"Job for service prftpd: One of {jobs}"
	)

	args	= vars(parser.parse_args())

	if args['Action'] == 'setDefaultRoot':
		proftpd().setDefaultRoot(args['DefaultRoot'])
	elif args['Action'] == 'server':
		if 'ServerCommand' in args.keys():
			proftpd().ftpservice(args['ServerCommand'])


