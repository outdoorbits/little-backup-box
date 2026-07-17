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

# script is started by lib_comitup.py when state is set to CONNECTED
# As long state is CONNECTED, it tries to find out the link to a
# portal page. This is written to const_NETWORK_PORTAL_PAGE_LINK_FILE

import os
import pwd
import requests
import time

import lib_comitup
import lib_setup

class portal_page_detector(object):
	def __init__(self):
		self.__setup	= lib_setup.setup()

		self.__const_NETWORK_PORTAL_PAGE_LINK_FILE	= self.__setup.get_val('const_NETWORK_PORTAL_PAGE_LINK_FILE')

		self.test_URL								='http://connectivitycheck.gstatic.com/generate_204'

	def detect(self):
		try:
			response = requests.get(
				self.test_URL,
				timeout=8,
				allow_redirects=False
			)
		except:
			return

		# print(f'status-code: {response.status_code}')

		location	= None
		for key, value in response.headers.items():
			if key.lower()	== 'location':
				location	= value

		return location

	def write_linkfile(self, location):
		with open(self.__const_NETWORK_PORTAL_PAGE_LINK_FILE, 'w') as f:
			f. write(location if (not location is None) else '')
		try:
			with open(self.__const_NETWORK_PORTAL_PAGE_LINK_FILE, 'w') as f:
				f. write(location if (not location is None) else '')
		except:
			print(f'Error writing to "{self.__const_NETWORK_PORTAL_PAGE_LINK_FILE}"')

		try:
			user_info	= pwd.getpwnam('www-data')
			uid			= user_info.pw_uid
			gid			= user_info.pw_gid
			os.chown(self.__const_NETWORK_PORTAL_PAGE_LINK_FILE, uid, gid)
		except:
			print(f'Error in "chown {self.__const_NETWORK_PORTAL_PAGE_LINK_FILE}"')

	def run(self):
		comitup	= lib_comitup.comitup()

		location	= None
		while(comitup.get_status()['state'] == 'CONNECTED' and location is None):
			location	= self.detect()

			if location is None:
				time.sleep(5)

		self.write_linkfile(location)

		return location

if __name__ == "__main__":
	pd	= portal_page_detector()
	print(f'Portal page: {pd.run()}')
