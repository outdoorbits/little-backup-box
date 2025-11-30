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

import lib_setup

__cleaning_options	= ['full', 'ipmail', 'display_content', 'display_image', 'tasks', 'log', 'idlelock', 'cmdlock']

# import lib_debug
# xx	= lib_debug.debug()

class clean(object):
	def __init__(self):
		self.__setup	= lib_setup.setup()

		self.__workers	= {
			'full':				self.full,
			'ipmail':			self.ipmail,
			'display_content':	self.display_content,
			'display_image':	self.display_image,
			'tasks':			self.tasks,
			'log':				self.log,
			'idlelock':			self.idlelock,
			'cmdlock':			self.cmdlock
		}

		self.__const_DISPLAY_CONTENT_PATH		= self.__setup.get_val('const_DISPLAY_CONTENT_PATH')
		self.__const_DISPLAY_CONTENT_OLD_FILE	= self.__setup.get_val('const_DISPLAY_CONTENT_OLD_FILE')
		self.__const_DISPLAY_IMAGE_KEEP_PATH	= self.__setup.get_val('const_DISPLAY_IMAGE_KEEP_PATH')
		self.__conf_DIPLAY_IMAGES_KEEP			= self.__setup.get_val('conf_DIPLAY_IMAGES_KEEP')
		self.__const_TASKS_PATH					= self.__setup.get_val('const_TASKS_PATH')
		self.__const_IP_SENT_MARKERFILE			= self.__setup.get_val('const_IP_SENT_MARKERFILE')
		self.__const_LOGFILE					= self.__setup.get_val('const_LOGFILE')
		self.__const_IDLETIME_LOCKFILE			= self.__setup.get_val('const_IDLETIME_LOCKFILE')
		self.__const_CMD_LOGFILE				= self.__setup.get_val('const_CMD_LOGFILE')

		self.jobs	= []
		self.skips	= []

	def cleanup(self, jobs=['full'], skips=[]):
		self.jobs	= jobs
		self.skips	= skips

		for job in jobs:
			self.__workers[job]()
			if job == 'full':
				break

	def full(self):
		for worker in self.__workers:
			if worker not in (['full'] + self.skips):
				self.__workers[worker]()

	def ipmail(self):
		try:
			os.remove(self.__const_IP_SENT_MARKERFILE)
		except:
			pass

	def display_content(self):
		try:
			subprocess.run(f"bash -c 'shopt -s nullglob; rm -f {self.__const_DISPLAY_CONTENT_PATH}/*'", shell=True)
		except:
			pass

		try:
			os.remove(self.__const_DISPLAY_CONTENT_OLD_FILE)
		except:
			pass

	def display_image(self):
		try:
			subprocess.run(f"bash -c 'shopt -s nullglob; rm -f {self.__const_DISPLAY_IMAGE_EXPORT_FILE}'", shell=True)
		except:
			pass

		if not self.__conf_DIPLAY_IMAGES_KEEP:
			try:
				subprocess.run(f"bash -c 'shopt -s nullglob; rm -f -R {self.__const_DISPLAY_IMAGE_KEEP_PATH}'", shell=True)
			except:
				pass

	def tasks(self):
		try:
			subprocess.run(f"bash -c 'shopt -s nullglob; rm -f {self.__const_TASKS_PATH}/*'", shell=True)
		except:
			pass

	def log(self):
		try:
			with open(self.__const_LOGFILE,'w') as f:
				f.write('Little Backup Box\n\n')
			shutil.chown(self.__const_LOGFILE, user='www-data', group='www-data')
		except:
			pass

	def idlelock(self):
		try:
			os.remove(self.__const_IDLETIME_LOCKFILE)
		except:
			pass

	def cmdlock(self):
		try:
			with open(self.__const_CMD_LOGFILE,'w') as f:
				f.write('Little Backup Box\n\n')
			shutil.chown(self.__const_CMD_LOGFILE, user='www-data', group='www-data')
		except:
			pass

if __name__ == "__main__":
	parser = argparse.ArgumentParser(
		description	= 'Tool to clean up temporary files',
		add_help	= True,
		epilog		= 'Part of little-backup-box'
	)

	parser.add_argument(
		'--clean',
		'-c',
		choices		= __cleaning_options,
		required =	True,
		nargs = '+',
		help=f'What to clean? One of {", ".join(__cleaning_options)}. This argument can take multiple options separated by space.'
	)

	args	= vars(parser.parse_args())

	cleaner	= clean()
	cleaner.cleanup(jobs=args['clean'])
