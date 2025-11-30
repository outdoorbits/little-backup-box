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

import base64
import datetime
import os
import pathlib
import subprocess
import sys

class log(object):

	def __init__(self):
		self.WORKING_DIR = os.path.dirname(__file__)

		self.setup	= lib_setup.setup()

		self.LogFilePath			= self.setup.get_val('const_LOGFILE')
		self.conf_LOGLEVEL			= self.setup.get_val('conf_LOGLEVEL')

		self.hide_passwords_list	= self.setup.get_val('const_PASSWORDS_LIST').split(';')

	def message(self, Message, LogLevel=10):
		if LogLevel < self.conf_LOGLEVEL:
			return()

		#replace passwords out of logfile
		for PWD_var in self.hide_passwords_list:

			pwd_probe	= self.setup.get_val(PWD_var).strip()
			try:
				PWD	= base64.b64decode(pwd_probe).decode("utf-8")
			except:
				PWD	= pwd_probe

			if PWD:
				Message	= Message.replace(PWD, '***')

		# ensure path and logfile exists
		if not os.path.isfile(self.LogFilePath):
			pathlib.Path(os.path.join(self.WORKING_DIR,'tmp')).mkdir(parents=True, exist_ok=True)
			open(self.LogFilePath,'w').close()

		# write to logfile
		date_str	= datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
		with open(self.LogFilePath,'a') as f:
			f.write(f"{date_str}\n{Message}\n\n")

	def execute(self, Message, Command, LogLevel=10, LogCommand=True):
		log	= ''

		if Message != '':
			log= f"{Message}:\n"

		CommandResult	= 'Error'
		if LogCommand:
			log	= f"{log}>{Command}\n"

			CommandResult	= subprocess.check_output(f"eval {Command} 2>&1", shell=True).decode()

		log	= f"{log}{CommandResult}"

		self.message(log,LogLevel)


	def pick_file(self,Message,FileName,LogLevel=10):
		log	= ''

		if Message != '':
			log= f"{Message}:\n"

		log	= f"{log}\n{FileName}:\n"

		if os.path.isfile(FileName):
			FileContent	= ''
			with open(FileName,'r') as f:
				FileContent	= f.read()
			log	= f"{log}\n\n>>>>>\n{FileContent}\n<<<<<"
		else:
			log	= f"{log}\n>>>>>\nFile not found.\n<<<<<"

		self.message(log,LogLevel)

if __name__ == "__main__":
	if len(sys.argv) > 0:
		Job	= sys.argv[1]

		logobj	= log()

		if   Job == "message":
			try:
				Message = sys.argv[2]
			except:
				Message = ''

			try:
				LogLevel = int(sys.argv[3])
			except:
				LogLevel = 10

			logobj.message(Message,LogLevel)
		elif Job == "execute":
			try:
				Message = sys.argv[2]
			except:
				Message = ''

			# command or filename
			try:
				Command = sys.argv[3]
			except:
				Command = None

			try:
				LogLevel = int(sys.argv[4])
			except:
				LogLevel = 10

			# optional, default 'true', if 'false' the command itself will not be logged
			try:
				LogCommand = sys.argv[5]
			except:
				LogCommand = True

			logobj.execute(Message,Command,LogLevel,LogCommand)
		elif Job == "pick_file":
			try:
				Message = sys.argv[2]
			except:
				Message = ''

			# command or filename
			try:
				Command = sys.argv[3]
			except:
				Command = None

			try:
				LogLevel = int(sys.argv[4])
			except:
				LogLevel = 10
			logobj.pick_file(Message,Command,LogLevel)
