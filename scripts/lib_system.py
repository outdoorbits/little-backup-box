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
import datetime
import os
import subprocess

import lib_language
import lib_setup

def get_uptime_sec():
	with open('/proc/uptime', 'r') as f:
		uptime_sec = float(f.readline().split()[0])
	return(uptime_sec)


def rpi_leds(led='PWR', trigger='', delay_on='', delay_off='', brightness=''):
	# led: ['PWR','ACT']
	# trigger: ['none','timer','heartbeat']
	# delay_on, delay_off: in ms
	# brightness: ['0','1']

	delay_on	= str(delay_on)
	delay_off	= str(delay_off)
	brightness	= str(brightness)

	if led in ['PWR', 'ACT']:
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

def get_pi_model(number_only=False):

	model	= ''

	try:
		with open('/proc/device-tree/model') as f:
			model	= f.read()
	except:
		model	= 'unknown'

	if number_only:
		if model.startswith('Raspberry Pi Model B'):
			model	= 1

		elif model.startswith('Raspberry Pi Zero'):
			model	= 0

		elif model.startswith('Raspberry Pi 2'):
			model	= 2

		elif model.startswith('Raspberry Pi 3'):
			model	= 3

		elif model.startswith('Raspberry Pi 4'):
			model	= 4

		elif model.startswith('Raspberry Pi 5'):
			model	= 5

		else:
			model	= -1

	return(model)

def parse_args() -> argparse.Namespace:
	parser = argparse.ArgumentParser(
		description="system tools",
		formatter_class=argparse.RawTextHelpFormatter,
	)

	parser.add_argument(
		'--get_uptime_sec',
		'-u',
		action	= 'store_true',
		help	= 'Get system uptime'
	)

	parser.add_argument(
		'--get_abnormal_system_conditions',
		'-sc',
		action	= 'store_true',
		help	= 'Get a list of abnormal system conditions'
	)

	parser.add_argument(
		'--get_pi_model',
		'-pi',
		action	= 'store_true',
		help	= 'Get Raspberry Pi model'
	)

	parser.add_argument(
		'--set_led',
		'-led',
		action	= 'store_true',
		help	= 'Set Raspberry Pi LEDs'
	)

	LEDs	= ['PWR', 'ACT']
	parser.add_argument(
		'-LED',
		'-L',
		choices		= LEDs,
		required	= False,
		default		= 'PWR',
		help=f'Select LED {LEDs}.'
	)

	LED_TRIGGERs	= ['none', 'timer', 'heartbeat']
	parser.add_argument(
		'-LED_TRIGGER',
		'-LT',
		choices		= LED_TRIGGERs,
		required	= False,
		default		= 'none',
		help=f'Select LED trigger {LED_TRIGGERs}.'
	)

	parser.add_argument(
		'-LED_DELAY_ON',
		'-LON',
		required	= False,
		default		= 'none',
		help=f'Set Delay for ON in ms.'
	)

	parser.add_argument(
		'-LED_DELAY_OFF',
		'-LOFF',
		required	= False,
		default		= 'none',
		help=f'Set Delay for OFF in ms.'
	)

	LED_BRIGHTNESSs	= ['0', '1']
	parser.add_argument(
		'-LED_BRIGHTNESS',
		'-LB',
		choices		= LED_BRIGHTNESSs,
		required	= False,
		default		= '0',
		help=f'Select brightness of LED {LED_BRIGHTNESSs}.'
	)

	args = parser.parse_args()

	return args

if __name__ == "__main__":
	args = parse_args()

	if args.get_uptime_sec:
		print(get_uptime_sec())

	if args.get_abnormal_system_conditions:
		lan	= lib_language.language()
		print(get_abnormal_system_conditions(lan))

	if args.get_pi_model:
		print(get_pi_model())

	if args.set_led:
		rpi_leds(led=args.LED, trigger=args.LED_TRIGGER, delay_on=args.LED_DELAY_ON, delay_off=args.LED_DELAY_OFF, brightness=args.LED_BRIGHTNESS)
