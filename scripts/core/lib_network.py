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
import os
from PIL import Image, ImageDraw, ImageFont
import qrcode
import subprocess
from urllib import request
import sys
import time

import lib_comitup
import lib_setup

# import lib_debug
# xx	= lib_debug.debug()

def get_IPs(OneLine=False):
	IPs	= []
	try_count	= 0
	while try_count <= 3:
		try_count	+= 1
		try:
			IPs	= subprocess.check_output(['nmcli', '-g', 'IP4.ADDRESS', 'device', 'show']).decode().strip().replace('|', '\n').replace(' ', '\n').split('\n')
			break
		except:
			time.sleep(1)

	IPs	= [IP for IP in IPs if IP.strip()]
	IPs	= [IP.split('/')[0] for IP in IPs]
	IPs	= [IP for IP in IPs if not IP.startswith('127.0.0.')]

	if OneLine:
		if len(IPs) < 1:
			return('???.???.???.???')
		elif len(IPs) == 1:
			return(IPs[0])
		else:
			return(f'[{"|".join(IPs)}]')

	else:
		return('\n'.join(IPs))


def create_ip_link_qr_image(IP, OnlineStatus, IP_QR_FILE, width, height, font=None, fontsize=8):

	qr_box_size	= int(height/10)
	qr_border	= 1

	LinkText	= f"https://{IP}"

	# set file name
	IP_QR_FILE	= IP_QR_FILE.replace('_', IP.replace('.', '-'), 1)
	IP_QR_FILE	= IP_QR_FILE.replace('_', f"_{height}_{'online' if OnlineStatus else 'offline'}")

	if not os.path.isfile(IP_QR_FILE) and (height >= 64):

		if not font is None:
			qr_height	= height - fontsize - 1
		else:
			qr_height	= height

		qr	= qrcode.QRCode(
			version				= 1,
			error_correction	= qrcode.constants.ERROR_CORRECT_L,
				box_size		= qr_box_size,
				border			= qr_border
		)
		qr.add_data(LinkText)
		qr.make(fit=True)

		qr_image	= qr.make_image(fill_color="black", back_color="white")
		qr_image	= qr_image.resize((qr_height,qr_height))

		final_image	= Image.new('RGB', (width, height))

		final_image.paste(qr_image, box=(0 , 0))

		# text
		if not font is None:
			font			= ImageFont.truetype(font, fontsize)

			draw			= ImageDraw.Draw(final_image)

			(left, top, right, bottom)	= draw.textbbox((0, 0), IP, font=font)
			text_length					= right - left

			draw.text((int((width - text_length) / 2), qr_height), IP, font=font)

		# OnlineStatus
		status_size	= min(
			width - qr_height - 4,
			qr_height - 4
		)

		if status_size >= 0:
			status_icon_file	= 'img/online.png' if OnlineStatus else 'img/offline.png'
			WORKING_DIR			= os.path.dirname(__file__)
			status_icon_file	= f"{WORKING_DIR}/{status_icon_file}"

			with Image.open(status_icon_file) as status_image:
				status_image	= status_image.resize((status_size, status_size))
				final_image.paste(status_image, box=(qr_height + 2, 2))

		final_image.save(IP_QR_FILE)

	if not os.path.isfile(IP_QR_FILE):
		IP_QR_FILE	= None

	return(IP_QR_FILE)

def get_internet_status():
	try:
		request.urlopen('https://google.com', timeout=5)
		return(True)
	except:
		return(False)

def get_qr_links(protocol='https'):
	qr_links	= ''

	__setup	= lib_setup.setup()

	const_IP_QR_FILE_PATTERN	= __setup.get_val('const_IP_QR_FILE_PATTERN')
	const_FONT_PATH				= __setup.get_val('const_FONT_PATH')
	const_WEB_ROOT_LBB			= __setup.get_val('const_WEB_ROOT_LBB')

	conf_DISP_RESOLUTION_X		= __setup.get_val('conf_DISP_RESOLUTION_X')
	conf_DISP_RESOLUTION_Y		= __setup.get_val('conf_DISP_RESOLUTION_Y')
	conf_DISP_FONT_SIZE			= __setup.get_val('conf_DISP_FONT_SIZE')

	const_WIFI_QR_FILE_PATH		= __setup.get_val('const_WIFI_QR_FILE_PATH')

	if not os.path.isfile(const_WIFI_QR_FILE_PATH):
		lib_comitup.comitup().create_wifi_link_qr_image()

	if os.path.isfile(const_WIFI_QR_FILE_PATH):
		qr_links	= f'{qr_links}<img src="{const_WIFI_QR_FILE_PATH.replace(const_WEB_ROOT_LBB,'',1)}" style="padding: 5px;" title="HOTSPOT"> '

	IPs	= get_IPs().split('\n')

	for IP in IPs:
		IP_QR_FILE	= create_ip_link_qr_image(IP=IP, OnlineStatus=True, IP_QR_FILE=const_IP_QR_FILE_PATTERN, width=conf_DISP_RESOLUTION_X, height=conf_DISP_RESOLUTION_Y,font=const_FONT_PATH, fontsize=conf_DISP_FONT_SIZE)
		IP_QR_FILE	= IP_QR_FILE.replace(const_WEB_ROOT_LBB,'',1)
		qr_links	= f'{qr_links}<a href="{protocol}://{IP}"><img src="{IP_QR_FILE}" style="padding: 5px;"></a> '

	return(qr_links)

class traffic_monitor(object):
	def __init__(self):
		self.previous_time		= 0
		self.previous_transfer	= 0

		# init values
		self.get_traffic()

	def get_traffic(self):
		transfer	= 0

		try:
			with open('/proc/net/dev', 'r') as interfaces:
				for interface in interfaces:
					columns = interface.split()
					if len(columns) >= 9 and columns[0].endswith(':'):
						if columns[1].isdigit():
							transfer	+= int(columns[1])
						if columns[9].isdigit():
							transfer	+= int(columns[9])
		except:
			pass

		actualtime	= time.time()
		if actualtime - self.previous_time <= 0:
			return('')

		transferrate	= (transfer - self.previous_transfer) / (actualtime - self.previous_time)

		self.previous_time		= actualtime
		self.previous_transfer	= transfer

		return(f'{self.format_bytes(transferrate)}/s')

	def format_bytes(self, number_of_bytes):
		units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB']

		i = 0
		while number_of_bytes >= 1024 and i < len(units) - 1:
			number_of_bytes /= 1024.0
			i += 1

		return f"{number_of_bytes:.0f} {units[i]}"

if __name__ == "__main__":
	parser = argparse.ArgumentParser(
		description	= 'Provides network related functions',
		add_help	= True,
		epilog		= 'This script is designed to be used by other lbb scripts.'
	)

	ModeChoices	= ['ip', 'internet_status', 'qr_links']
	parser.add_argument(
		'--Mode',
		'-m',
		choices		= ModeChoices,
		required =	True,
		help=f'Mode, one of {ModeChoices}'
	)

	parser.add_argument(
		'--OneLine',
		'-o',
		action='store_true',
		required =	False,
		help=f'If set, the output will be string type. (for use in Mode=ip only)'
	)

	parser.add_argument(
		'--Protocol',
		'-p',
		required =	False,
		help=f'Protocol (http or https). (for use in Mode=qr_links only)'
	)

	args	= vars(parser.parse_args())

	if args['Mode'] == 'ip':
		print (get_IPs(OneLine=args['OneLine']))

	elif args['Mode'] == 'internet_status':
		print (get_internet_status())

	elif args['Mode'] == 'qr_links':

		if not 'Protocol' in args.keys():
			args['Protocol']	= 'https'

		print(get_qr_links(args['Protocol']))
