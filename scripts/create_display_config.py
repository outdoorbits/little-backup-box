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
import subprocess

import lib_setup
import lib_system

class display_config(object):
	def __init__(self, args):
		self.CONFIG_FILE	= '/boot/firmware/lbb-display.txt'
		self.UDEV_FILE		= '/etc/udev/hwdb.d/61-ads7846-touch.hwdb'

		self.args			= args

		self.__setup	= lib_setup.setup()

		self.conf_TOUCH_MATRIX_X	= self.__setup.get_val('conf_TOUCH_MATRIX_X')
		self.conf_TOUCH_MATRIX_Y	= self.__setup.get_val('conf_TOUCH_MATRIX_Y')

	def setup_display(self):
		self.__write_config_txt()
		self.__write_touch_udev()

	def __write_config_txt(self):
		rpi	= lib_system.get_pi_model(number_only=True)

		# define driver
		match self.args['driver']:
			case 'piscreen':
				DRIVER	= f'dtoverlay=piscreen,speed={self.args['speed']},rotate={self.args['rotate']}'
			case 'waveshare35a':
				DRIVER	= f'dtoverlay=waveshare35a,speed={self.args['speed']},rotate={self.args['rotate']}'
			case 'mipi-dbi':
				DRIVER	= f'''dtoverlay=mipi-dbi,spi0-0,ili9486
	dtparam=speed={self.args['speed']}
	dtparam=rotate={self.args['rotate']}
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
		with open(self.CONFIG_FILE, 'w') as config_file:
			config_file.write(CONFIG)

	def __write_touch_udev(self):
		# create and activate /etc/udev/hwdb.d/61-ads7846-touch.hwdb
		CONFIG	= f"""evdev:name:ADS7846 Touchscreen*:*
 LIBINPUT_MODEL_PRESSURE_PAD=1
 LIBINPUT_ATTR_PRESSURE_RANGE=10:255
 LIBINPUT_ATTR_TOUCH_SIZE_RANGE=1:1
 LIBINPUT_CALIBRATION_MATRIX={self.conf_TOUCH_MATRIX_X} {self.conf_TOUCH_MATRIX_Y} 0 0 1
		"""
		with open(self.UDEV_FILE, 'w') as config_file:
				config_file.write(CONFIG)

		subprocess.run(['sudo', 'systemd-hwdb', 'update'])
		subprocess.run(['sudo', 'udevadm', 'trigger', '-s', 'input'])


def get_arguments():
	parser = argparse.ArgumentParser(
		description	= f'Creates display configuration for SPI touchscreens.',
		add_help	= True
	)

	Drivers	= ['piscreen', 'waveshare35a', 'mipi-dbi']
	parser.add_argument(
		'--driver',
		'-d',
		choices		= Drivers,
		required	= False,
		default		= Drivers[0],
		help=f'Driver name, one of {Drivers}'
	)

	Speeds	= ['16000000']
	parser.add_argument(
		'--speed',
		'-s',
		choices		= Speeds,
		required	= False,
		default		= Speeds[0],
		help=f'Speed, one of {Speeds}'
	)

	Rotations	= ['0', '90', '180', '270']
	parser.add_argument(
		'--rotate',
		'-r',
		choices		= Rotations,
		required	= False,
		default		= Rotations[0],
		help=f'Rotation, one of {Rotations}'
	)

	return(vars(parser.parse_args()))

if __name__ == "__main__":
	args	= get_arguments()

	display_config(args).setup_display()


