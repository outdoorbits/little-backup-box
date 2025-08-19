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

		self.__conf_DISP_FRAME_TIME	= self.__setup.get_val('conf_DISP_FRAME_TIME')

		# config
		self.configfile	= '/etc/proftpd/lbb_DefaultRoot.conf'

		# values
		self.FTP_DefaultRoot	= ''

	def setDefaultRoot(self, FTP_DefaultRoot=None):

		self.FTP_DefaultRoot	= FTP_DefaultRoot

		# block hacking
		if self.FTP_DefaultRoot is not None:
			if ';' in self.FTP_DefaultRoot:
				return(False)
			if '\\' in self.FTP_DefaultRoot:
				return(False)

			# format self.FTP_DefaultRoot
			self.FTP_DefaultRoot	= self.FTP_DefaultRoot.strip()

		self.FTP_DefaultRoot	= self.__setup.get_val('const_MEDIA_DIR') if self.FTP_DefaultRoot is None else self.FTP_DefaultRoot

		config_new	= f'DefaultRoot {self.FTP_DefaultRoot} lbb\n'

		# read old config file
		try:
			with open(self.configfile, 'r') as f:
				config_old	= f.read().strip()
		except:
			config_old	= ''

		config_changed	= config_new.strip() != config_old

		# write config file
		if config_changed:
			try:
				with open(self.configfile,'w') as f:
					f.write(config_new)
			except:
				print("Error writing proftpd config file.")
				return()

			# restart server
			self.ftpservice('reload', config_changed)

		return(True)

	def ftpservice(self, job, config_changed=True):
		if job in ['start', 'stop', 'reload', 'restart']:

			if job == 'reload':
 				# bug in proftpd? reload is ignored
				job	= 'restart' # workaround but slow

			if job == 'restart':
				# proftpd running?
				command	= ['sudo', 'service', 'proftpd', 'status']
				try:
					proftpd_active	= 'Active: active (running)' in subprocess.check_output(command).decode()
				except:
					proftpd_active	= False

				if proftpd_active and not config_changed:
					return()

				job	= job if proftpd_active else 'start'

			self.__display.message([f'set:temp,time={self.__conf_DISP_FRAME_TIME * 4}', f":{self.__lan.l('box_backup_ftp_starting')}", self.FTP_DefaultRoot])

			command	= ['sudo', 'service', 'proftpd', job]
			print(f'proftpd {job}')
			subprocess.run(command)

			self.__display.message([f'set:temp,time={self.__conf_DISP_FRAME_TIME * 4}', f":{self.__lan.l('box_backup_ftp_started')}", self.FTP_DefaultRoot])

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
		help=f"Job for service proftpd: One of {jobs}"
	)

	args	= vars(parser.parse_args())

	if args['Action'] == 'setDefaultRoot':
		proftpd().setDefaultRoot(args['DefaultRoot'])
	elif args['Action'] == 'server':
		if 'ServerCommand' in args.keys():
			proftpd().ftpservice(args['ServerCommand'])


