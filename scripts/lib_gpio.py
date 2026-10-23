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


# import lib_debug
# xx	= lib_debug.debug()

import atexit
import os
from datetime import timedelta
import threading

import gpiod
from gpiod.line import Bias, Direction, Edge

class Button:
	_buttons = {}
	_buttons_lock = threading.RLock()

	def __init__(
		self,
		pin,
		callback,
		pull_up		= True,
		edge_rising	= True,
		bounce_time	= None,
		chip		= "/dev/gpiochip0"
	):
		self.pin		= pin
		self.chip		= chip
		self.callback	= callback

		self.__request	= None
		self.__thread	= None
		self.__stop		= threading.Event()

		self.edge_rising = edge_rising

		key = (self.chip, self.pin)

		# Release an existing button instance using the same GPIO.
		with Button._buttons_lock:
			old_button	= Button._buttons.get(key)

			if old_button is not None:
				if not old_button.close():
					raise RuntimeError(f"GPIO {self.pin} could not be released")

			bias	= Bias.PULL_UP if pull_up else Bias.PULL_DOWN
			edge	= Edge.RISING if edge_rising else Edge.FALLING

			self.__request	= gpiod.request_lines(
				self.chip,
				consumer	= f"little-backup-box-button-{os.getpid()}",
				config		= {
					self.pin: gpiod.LineSettings(
						direction		= Direction.INPUT,
						edge_detection	= edge,
						bias			= bias,
						debounce_period	= timedelta(
							seconds		= bounce_time or 0
						),
					)
				},
			)

			Button._buttons[key]	= self

		self.__thread	= threading.Thread(
			target	= self.__event_loop,
			daemon	= True
		)
		self.__thread.start()


	def __event_loop(self):
		try:
			while not self.__stop.is_set():

				if not self.__request.wait_edge_events(timeout = 0.25):
					continue

				events	= self.__request.read_edge_events()

				for event in events:
					if self.__stop.is_set():
						break

					try:
						self.callback()
					except Exception as error:
						pass

		finally:
			if self.__request is not None:
				self.__request.release()
				self.__request	= None

	def close(self):
		self.__stop.set()

		if (
			self.__thread is not None
			and self.__thread.is_alive()
			and self.__thread is not threading.current_thread()
		):
			self.__thread.join(timeout = 1)

		if self.__thread is not None and self.__thread.is_alive():
			return False

		key = (self.chip, self.pin)

		with Button._buttons_lock:
			if Button._buttons.get(key) is self:
				del Button._buttons[key]

		return True

	@classmethod
	def close_all(cls):
		with cls._buttons_lock:
			buttons	= list(cls._buttons.values())

		for button in buttons:
			button.close()

atexit.register(Button.close_all)

# if __name__ == "__main__":
# 	xx.d(f"TEST lib_gpio. PID={os.getpid()}")
#
# 	import time
#
# 	def callback():
# 		xx.d("event")
#
# 	button = Button(
# 		pin=5,
# 		pull_up=False,
# 		callback=callback
# 	)
#
# 	try:
# 		while True:
# 			time.sleep(1)
#
# 	except KeyboardInterrupt:
# 		xx.d("KeyboardInterrupt received")
#
# 	finally:
# 		xx.d("Closing GPIO")
# 		Button.close_all()
# 		xx.d("GPIO closed")
