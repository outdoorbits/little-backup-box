# -*- coding: utf-8 -*-
# Copyright (c) 2017-2023 Richard Hull and contributors
# See LICENSE.rst for details.

import luma.core.error
from gpiozero import DigitalOutputDevice

class bridge_gpiozero(object):

	class pseudoGPIO(object):

		def __init__(self,RPi_GPIO):
			# constants
			self.BCM		= RPi_GPIO.BCM
			self.BOARD		= RPi_GPIO.BOARD

	def __init__(self):
		# RPi.GPIO _really_ doesn't like being run on anything other than
		# a Raspberry Pi... this is imported here so we can swap out the
		# implementation for a mock
		try:  # pragma: no cover
			import RPi.GPIO as RPi_GPIO

		except RuntimeError as e:
			if str(e) in ['This module can only be run on a Raspberry Pi!',
						'Module not imported correctly!']:
				raise luma.core.error.UnsupportedPlatform(
					'GPIO access not available')

		# constants
		## get pseudo object GPIO containing constants
		self.GPIO	= self.pseudoGPIO(RPi_GPIO)

		## more constants
		self.IN				= RPi_GPIO.IN
		self.OUT			= RPi_GPIO.OUT

		self.PUD_UP			= RPi_GPIO.PUD_UP
		self.PUD_DOWN		= RPi_GPIO.PUD_DOWN

		self.HIGH			= RPi_GPIO.HIGH
		self.LOW			= RPi_GPIO.LOW

		# dict of defined pins
		self.__PINS	= {}

		self.__MAP_BOARD_GPIO	= self.__GET_MAP_BOARD_GPIO()

		# mode
		self.mode	= self.GPIO.BCM


	def setmode(self,mode):
		if mode in [GPIO.BCM, GPIO.BOARD]:
			self.mode	= GPIO.BCM if mode == GPIO.BCM else GPIO.BOARD
		else:
			raise luma.core.error.UnsupportedPlatform(
					f'GPIO mode {mode} not available')

	def setwarnings(self,warnings=False):
		pass

	def setup(self,channel,direction=None,pull_up_down=None,initial=None):
		channel	= self.__normalize_gpio_number(channel)

		direction	= self.OUT if direction is None else direction

		if direction == self.IN:
			raise luma.core.error.UnsupportedPlatform('rpi_gpio_bridge inputs are not available yet.')

		pull_up_down	= self.PUD_UP if pull_up_down is None else pull_up_down

		self.__PINS[channel]	= DigitalOutputDevice(pin=channel,initial_value=initial)

	def output(self,channel,value=False):
		if not channel in self.__PINS.keys():
			raise luma.core.error.UnsupportedPlatform(f'rpi_gpio_bridge: Pin {channel} is not defined by setup().')

		if value:
			self.__PINS[channel].on()
		else:
			self.__PINS[channel].off()

	def cleanup(self, channels=None):
		if channels is None:
			channels	= self.__PINS.keys()
		else:
			channels = channels if type(channels) is list else [channels] if channels else []

		for channel in channels:
			self.__PINS[channel].off()
			del self.__PINS[channel]





	def __normalize_gpio_number(self, gpio_pin):
		if self.mode == self.GPIO.BCM:
			if gpio_pin in self.__MAP_BOARD_GPIO.values():
				return(gpio_pin)
			else:
				raise luma.core.error.UnsupportedPlatform(
					f'GPIO {gpio_pin} is no valid gpio. Did you set correct mode by setmode()?')
		else:
			if gpio_pin in self.__MAP_BOARD_GPIO.keys():
				return(self.__MAP_BOARD_GPIO[gpio_pin])
			else:
				raise luma.core.error.UnsupportedPlatform(
					f'GPIO pin {gpio_pin} is no valid pin. Did you set correct mode by setmode()?')

	def __GET_MAP_BOARD_GPIO(self):
		# PIN: GPIO
		return(
				{
					3:	2,
					5:	3,
					7:	4,
					8:	14,
					10:	15,
					11:	17,
					12:	18,
					13:	27,
					15:	22,
					16:	23,
					18:	24,
					19:	10,
					21:	9,
					22:	25,
					23:	11,
					24:	8,
					26:	7,
					27:	0,
					28:	1,
					29:	5,
					31:	6,
					32:	12,
					33:	13,
					35:	19,
					36:	16,
					37:	26,
					38:	20,
					40:	21
				}
			)


