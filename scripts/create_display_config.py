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

import lib_system

CONFIG_FILE	= '/boot/firmware/lbb-display.txt'

def get_arguments():
	parser = argparse.ArgumentParser(
		description	= f'Creates {CONFIG_FILE}.',
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
	arguments	= get_arguments()

	print(arguments['driver'])
	print(arguments['speed'])
	print(arguments['rotate'])

	rpi	= lib_system.get_pi_model(number_only=True)
	print(rpi)

	match arguments['driver']:
		case 'piscreen':
			DRIVER	= f'dtoverlay=piscreen,speed={arguments['speed']},rotate={arguments['rotate']}'
		case 'waveshare35a':
			DRIVER	= f'dtoverlay=waveshare35a,speed={arguments['speed']},rotate={arguments['rotate']}'
		case 'mipi-dbi':
			DRIVER	= f'''dtoverlay=mipi-dbi,spi0-0,ili9486
dtparam=speed={arguments['speed']}
dtparam=rotate={arguments['rotate']}
dtparam=reset-gpio=25
dtparam=dc-gpio=24'''

	Pi4	= '' if rpi >= 5 else 'hdmi_force_hotplug=1'

	CONFIG	= f"""# Display settings

# Enable SPI bus
dtparam=spi=on

# Primary graphics driver for Raspberry Pi
dtoverlay=vc4-kms-v3d

{Pi4}
{DRIVER}

dtparam=drm=on
"""
	print(CONFIG)
	with open(CONFIG_FILE, 'w') as config_file:
		config_file.write(CONFIG)
