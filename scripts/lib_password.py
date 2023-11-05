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


import datetime
import os
import subprocess
import sys

import lib_comitup
import lib_language
import lib_log
import lib_setup

class password(object):

	def __init__(self):
		self.__setup	= lib_setup.setup()
		self.__lan		= lib_language.language()
		self.__log		= lib_log.log()

		self.user		= 'lbb'

	def set_password(self,Password=''):
		# prepare config files
		if not Password:
			#Apache
			open('/etc/apache2/includes/password.conf','w').close()

			#Samba
			with open('/etc/samba/login.conf','w') as f:
				f.write('guest ok = yes')

			#comitup
			lib_comitup.comitup_conf(Password=None).run()

			self.__log.message(self.__lan.l('config_password_removed'))

		else:
			#Apache
			with open('/etc/apache2/includes/password.conf','w') as f:
				f.write('Authtype Basic\n')
				f.write('Authname "Password Required"\n')
				f.write('AuthUserFile /etc/apache2/includes/htpasswd\n')
				f.write('Require valid-user\n')

			subprocess.run(f'echo "{Password}" | htpasswd -ci "/etc/apache2/includes/htpasswd" "{self.user}"',shell=True)

			# Linux
			subprocess.run(f'echo "pi:{Password}" | chpasswd',shell=True)
			subprocess.run(f'echo "{self.user}:{Password}" | chpasswd',shell=True)

			# samba
			subprocess.run(f'(echo {Password}; echo {Password}) | smbpasswd -a -s "{self.user}"',shell=True)

			with open('/etc/samba/login.conf','w') as f:
				f.write(f'valid users = {self.user}\n')
				f.write('guest ok = no\n')

			#comitup
			lib_comitup.comitup_conf(Password=Password).run()

			self.__log.message(self.__lan.l('config_password_changed'))

		# restart samba
		subprocess.run('service smbd restart',shell=True)

if __name__ == "__main__":
	Password	= None
	if len(sys.argv) > 1:
		Password	= sys.argv[1]

	password().set_password(Password)
