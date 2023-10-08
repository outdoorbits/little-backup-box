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

import lib_language
import lib_setup

import datetime
import os
import subprocess
import sys

def get_uptime_sec():
	with open('/proc/uptime', 'r') as f:
		uptime_sec = float(f.readline().split()[0])
	return(uptime_sec)


def rpi_leds(led='PWR',trigger='',delay_on='',delay_off='',brightness=''):
	# led: ['PWR','ACT']
	# trigger: ['none','timer','heartbeat']
	# delay_on, delay_off: in ms
	# brightness: ['0','1']

	delay_on	= str(delay_on)
	delay_off	= str(delay_off)
	brightness	= str(brightness)

	if led in ['PWR','ACT']:
		if trigger:
			try:
				with open(f"/sys/class/leds/{led}/trigger",'w') as f:
					f.write(trigger)
			except:
				pass

		if delay_on:
			try:
				with open(f"/sys/class/leds/{led}/delay_on",'w') as f:
					f.write(delay_on)
			except:
				pass

		if delay_off:
			try:
				with open(f"/sys/class/leds/{led}/delay_off",'w') as f:
					f.write(delay_off)
			except:
				pass

		if brightness:
			try:
				with open(f"/sys/class/leds/{led}/brightness",'w') as f:
					f.write(brightness)
			except:
				pass

def get_abnormal_system_conditions(lan):
	Conditions	= ''

	Status	= int(subprocess.check_output('vcgencmd get_throttled',shell=True).decode().split('=')[1],16)

	if Status != 0x0:

		if Status & 0x00001 != 0:
			Conditions	+= lan.l('sysconditions_under_voltage_cur')
		elif Status & 0x10000 != 0:
			Conditions	+= lan.l('sysconditions_under_voltage_prev')

		if Status & 0x00002 != 0:
			Conditions	+= lan.l('sysconditions_arm_frq_capped_cur')
		elif Status & 0x20000 != 0:
			Conditions	+= lan.l('sysconditions_arm_frq_capped_prev')

		if Status & 0x00004 != 0:
			Conditions	+= lan.l('sysconditions_cpu_throttled_cur')
		elif Status & 0x40000 != 0:
			Conditions	+= lan.l('sysconditions_cpu_throttled_prev')

		if Status & 0x00008 != 0:
			Conditions	+= lan.l('sysconditions_temperature_limit_cur')
		elif Status & 0x80000 != 0:
			Conditions	+= lan.l('sysconditions_temperature_limit_prev')

	else:
		Conditions	+= lan.l('sysconditions_normal')

	return(Conditions)

if __name__ == "__main__":
	if len(sys.argv)>1:
		if sys.argv[1] == 'get_uptime_sec':
			print(get_uptime_sec())
		if sys.argv[1] == 'get_abnormal_system_conditions':
			lan	= lib_language.language()
			print(get_abnormal_system_conditions(lan))

