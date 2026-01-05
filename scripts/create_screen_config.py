#!/usr/bin/env python3

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
import os
import subprocess

import lib_setup
import lib_system

class display_config(object):
	def __init__(self, activate=None):
		self.CONFIG_FILE	= '/boot/firmware/lbb-display.txt'
		self.UDEV_FILE		= '/etc/udev/hwdb.d/61-ads7846-touch.hwdb'

		self.__setup	= lib_setup.setup()

		self.conf_SCREEN_DRIVER		= self.__setup.get_val('conf_SCREEN_DRIVER')
		self.conf_SCREEN_SPEED		= self.__setup.get_val('conf_SCREEN_SPEED')
		self.conf_SCREEN_ROTATE		= self.__setup.get_val('conf_SCREEN_ROTATE')
		self.conf_TOUCH_MATRIX_X	= self.__setup.get_val('conf_TOUCH_MATRIX_X')
		self.conf_TOUCH_MATRIX_Y	= self.__setup.get_val('conf_TOUCH_MATRIX_Y')

		self.conf_DISP				= activate if activate in ['display', 'screen', '0'] else self.__setup.get_val('conf_DISP')

	def setup_display(self):
		if not os.path.isfile('/usr/sbin/lightdm'):
			return(False)

		self.__write_config_txt()
		self.__write_touch_udev()

	def __write_config_txt(self):
		rpi	= lib_system.get_pi_model(number_only=True)

		if self.conf_DISP == 'screen':
			# define driver
			match self.conf_SCREEN_DRIVER:
				case 'piscreen':
					DRIVER	= f'dtoverlay=piscreen,speed={self.conf_SCREEN_SPEED},rotate={self.conf_SCREEN_ROTATE}'
				case 'waveshare35a':
					DRIVER	= f'dtoverlay=waveshare35a,speed={self.conf_SCREEN_SPEED},rotate={self.conf_SCREEN_ROTATE}'
				case 'mipi-dbi':
					DRIVER	= f'''dtoverlay=mipi-dbi,spi0-0,ili9486
dtparam=speed={self.conf_SCREEN_SPEED}
dtparam=rotate={self.conf_SCREEN_ROTATE}
dtparam=reset-gpio=25
dtparam=dc-gpio=24'''

			# Raspberry Pi 4 specific settings
			Pi4	= '' if rpi >= 5 else 'hdmi_force_hotplug=1'

			# assemble include for config.txt
			CONFIG	= f"""# Display settings

	# Enable SPI bus
	dtparam=spi=on

	# Primary graphics driver for Raspberry Pi
	dtoverlay=vc4-kms-v3d

	{Pi4}
	{DRIVER}

	dtparam=drm=on
"""
		else:
			CONFIG	= ''

		with open(self.CONFIG_FILE, 'w') as config_file:
			config_file.write(CONFIG)

	def __write_touch_udev(self):
		# create and activate self.UDEV_FILE
		if self.conf_DISP == 'screen':
			CONFIG	= f"""evdev:name:ADS7846 Touchscreen*:*
 LIBINPUT_MODEL_PRESSURE_PAD=1
 LIBINPUT_ATTR_PRESSURE_RANGE=10:255
 LIBINPUT_ATTR_TOUCH_SIZE_RANGE=1:1
 LIBINPUT_CALIBRATION_MATRIX={self.conf_TOUCH_MATRIX_X} {self.conf_TOUCH_MATRIX_Y} 0 0 1
"""
		else:
			CONFIG	= ''

		with open(self.UDEV_FILE, 'w') as config_file:
				config_file.write(CONFIG)

		subprocess.run(['sudo', 'systemd-hwdb', 'update'])
		subprocess.run(['sudo', 'udevadm', 'trigger', '-s', 'input'])

def parse_args() -> argparse.Namespace:
	parser = argparse.ArgumentParser(
		description="Write config files for SPI touchscreens"
	)

	activates	= ['display', 'screen', '0']
	parser.add_argument(
		"--activate",
		choices=activates,
		default="",
		help=f'One of {activates}',
	)

	return parser.parse_args()

if __name__ == "__main__":
	args	= parse_args()
	display_config(activate=args.activate).setup_display()


