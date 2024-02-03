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

import lib_display
import lib_language
import lib_log
import lib_network
import lib_setup
import lib_system

import os
import subprocess
import sys
import time


class vpn(object):

# exit codes:
# 101:

	def __init__(self,VPNMode):
		#VPNMode:['OpenVPN','WireGuard']

		self.VPNMode	= VPNMode

		self.__VPN_Connection = None

		self.__WORKING_DIR = os.path.dirname(__file__)

		self.__setup	= lib_setup.setup()
		self.__conf_VPN_TYPE_RSYNC	= self.__setup.get_val('conf_VPN_TYPE_RSYNC')
		self.__conf_VPN_TYPE_CLOUD	= self.__setup.get_val('conf_VPN_TYPE_CLOUD')
		self.__conf_VPN_TIMEOUT		= self.__setup.get_val('conf_VPN_TIMEOUT')
		self.__VPN_Dir				= self.__setup.get_val(f"const_VPN_DIR_{VPNMode}")
		self.__VPN_FileName			= self.__setup.get_val(f"const_VPN_FILENAME_{VPNMode}")

		self.__display				= lib_display.display()
		self.__log					= lib_log.log()
		self.__lan					= lib_language.language()

		self.IP_pre_VPN				= lib_network.get_IPs()

		self.connected				= False
		self.__status_check_time	= 0


	def __del__(self):
		print(f"DESTRUCTED: VPN {self.VPNMode}")

	def __status(self):
		Status	= False
		if self.VPNMode in ['OpenVPN','WireGuard']:

			if self.VPNMode == 'OpenVPN':
				Command	= ['sudo','ip','tuntap','show']
				try:
					Status	= (subprocess.check_output(Command) != '') and (self.IP_pre_VPN != lib_network.get_IPs())
				except:
					Status	= False

			elif self.VPNMode == "WireGuard":
				Command	= ['sudo','wg','show',self.__VPN_FileName.split('.')[0]]
				try:
					Status = (self.__VPN_FileName.split('.')[0] in subprocess.check_output(Command).decode()) and (self.IP_pre_VPN != lib_network.get_IPs())
				except:
					Status	= False

			self.__status_check_time	= lib_system.get_uptime_sec()

		return(Status)

	def check_status(self,min_interval_sec=10):
		if lib_system.get_uptime_sec() >= self.__status_check_time + min_interval_sec:
			Status	= self.VPNMode if self.__status() else '-'
		else:
			Status	= self.VPNMode if self.connected else '-'

		return(Status)

	def start(self):
		self.connected	= False
		if (self.VPNMode in ['OpenVPN','WireGuard']) and (os.path.isfile(f"{self.__VPN_Dir}/{self.__VPN_FileName}")):

			self.__display.message([f":{self.__lan.l('box_backup_vpn_connecting')}"])

			if self.VPNMode == 'OpenVPN':
				Command	= ['sudo','bash','-c','openvpn','--config',f"{self.__VPN_Dir}/{self.__VPN_FileName}"]
				self.__log.message(' '.join(Command),3)
				subprocess.Popen(Command,stdout=subprocess.DEVNULL, stdin=subprocess.DEVNULL)
			elif self.VPNMode == 'WireGuard':
				Command	= ['sudo','wg-quick','up',f"{self.__VPN_Dir}/{self.__VPN_FileName}"]
				self.__log.message(' '.join(Command),3)
				subprocess.run(Command)

			VPN_TimeoutTime	= lib_system.get_uptime_sec() + self.__conf_VPN_TIMEOUT

			while (not self.connected) and (lib_system.get_uptime_sec() < VPN_TimeoutTime):
				self.connected	= self.__status()
				time.sleep(1)

			if self.connected:
				self.__display.message([f":{self.__lan.l('box_backup_vpn_connecting_success')}"])
			else:
				self.stop()
				self.__display.message([f":{self.__lan.l('box_backup_vpn_connecting_failed')}"])

		return(self.connected)

	def stop(self):
		self.__display.message([f":{self.__lan.l('box_backup_vpn_disconnecting')}",self.VPNMode])
		if self.VPNMode == 'OpenVPN':
			if self.__VPN_Connection:
					self.__VPN_Connection.kill()
		elif self.VPNMode == 'WireGuard':
			subprocess.run(['sudo','wg-quick','down',self.__VPN_FileName.split('.')[0]])



if __name__ == "__main__":
	try:
		Action	= sys.argv[1]
	except:
		Action	= ''

	if Action in ['OpenVPN','WireGuard']:
		vpn(Action).start()
	elif Action == 'stop':
		vpn('OpenVPN').stop()
		vpn('WireGuard').stop()


