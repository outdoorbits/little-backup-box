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

import base64
import os
from PIL import Image, ImageDraw, ImageFont
import qrcode
import secrets
import shutil
import string
import subprocess
import sys

import lib_display
import lib_language
import lib_setup

# import lib_debug
# xx	= lib_debug.debug()

class comitup(object):
	def __init__(self):

		#config
		self.__configfile	= '/etc/comitup.conf'

		#objects
		self.__setup	= lib_setup.setup()
		self.__lan		= lib_language.language()
		self.__display	= lib_display.display()

		self.__conf_DISP_FRAME_TIME		= self.__setup.get_val('conf_DISP_FRAME_TIME')

		self.__conf_WIFI_PASSWORD			= base64.b64decode(self.__setup.get_val('conf_WIFI_PASSWORD')).decode("utf-8")
		self.__conf_DISP_RESOLUTION_X		= self.__setup.get_val('conf_DISP_RESOLUTION_X')
		self.__conf_DISP_RESOLUTION_Y		= self.__setup.get_val('conf_DISP_RESOLUTION_Y')
		self.__const_WIFI_QR_FILE_PATH		= self.__setup.get_val('const_WIFI_QR_FILE_PATH')

	def installed(self):
		return(True if shutil.which("comitup-cli") else False)

	def config(self, Password=''): # use general password if None is given

		try:
			with open(self.__configfile,'w') as f:
				f.write('ap_name: little-backup-box-<nnnn>\n')
				f.write('web_service: apache2.service\n')
				f.write('external_callback: /var/www/little-backup-box/comitup-states.sh\n')
				if Password:
					if (
						(len(Password) >= 8) and
						(len(Password) <= 63)
					):
						f.write(f'ap_password: {Password}\n')
		except:
			print("Error writing comitup config file.")

	def dynamic_password(self):
		if	self.__setup.get_val('conf_WIFI_PASSWORD_TYPE') != 'dynamic' or \
			not self.__setup.get_val('conf_DISP') or \
			self.__setup.get_val('conf_DISP_RESOLUTION_X') < 64 or \
			self.__setup.get_val('conf_DISP_RESOLUTION_Y') < 64:
			return

		alphabet = string.ascii_letters + string.digits  # A-Z, a-z, 0-9
		Password = ''.join(secrets.choice(alphabet) for _ in range(13))
		self.config(Password=Password)
		self.__setup.set_val('conf_WIFI_PASSWORD', Password)
		self.__setup.rewrite_configfile()

	def get_status(self):
		status	= {
			'SSID':		False,
			'mode':		False,
			'state':	False
		}

		if not self.installed():
			return(status)

		try:
			output	= subprocess.check_output(['comitup-cli', 'i'], timeout=2).decode()
		except:
			return(status)

		output	= output.split('\n')

		for line in output:
			if line.startswith('Host'):
				try:
					lineparts	= line.split(' ')
				except:
					pass
				if len(lineparts) > 1:
					status['SSID']	= lineparts[1].rsplit('.')[0]
			elif line.endswith(' mode'):
				lineparts	= line.split(' ')
				status['mode']		= lineparts[0].strip("'")
			elif line.endswith(' state'):
				lineparts	= line.split(' ')
				status['state']		= lineparts[0]

		return(status)

	def create_wifi_link_qr_image(self):
		status	= self.get_status()

		width			= self.__conf_DISP_RESOLUTION_X
		height			= self.__conf_DISP_RESOLUTION_Y

		qr_box_size	= int(2*height/64)
		qr_border	= 1

		final_image	= Image.new('RGB', (width, height))

		size		= height if height <= width else width

		shift_x		= size if height <= width else 0
		shift_y		= size if height > width else 0

		if 	(status['mode'] == 'router' or status['state'] == 'HOTSPOT') and \
			status['SSID'] and \
			width >= 64 and \
			height >= 64 and \
			not any(c in self.__conf_WIFI_PASSWORD for c in [':', ';']):
			# create QR code

			LinkText	= f"WIFI:T:WPA;S:{status['SSID']};P:{self.__conf_WIFI_PASSWORD};H:;;"

			qr	= qrcode.QRCode(
				version				= 3,
				error_correction	= qrcode.constants.ERROR_CORRECT_L,
					box_size		= qr_box_size,
					border			= qr_border,
			)
			qr.add_data(LinkText)
			qr.make(fit=True)

			qr_image	= qr.make_image(fill_color="black", back_color="white")
			qr_image	= qr_image.resize((size, size))

			final_image.paste(qr_image, box=(0 , 0))
		else:
			# create "NO HOTSPOT"
			draw		= ImageDraw.Draw(final_image)

			margin		= size // 8
			circle_bbox	= (margin, margin, size - margin, size - margin)

			draw.ellipse(circle_bbox, outline='white', width=max(1, size // 32))

			draw.line(
				(margin, size - margin, size - margin, margin),
				fill='white',
				width=max(2, size // 24)
			)

			font	= ImageFont.load_default()
			text	= "NO\nHOTSPOT"
			bbox = draw.multiline_textbbox((0, 0), text, font=font)
			tw = bbox[2] - bbox[0] # width
			th = bbox[3] - bbox[1] # height
			tx		= (size - tw) // 2
			ty		= (size - th) // 2
			draw.multiline_text((tx, ty), text, font=font, fill='white', align='center')

		# create WIFI symbol at the right
		draw		= ImageDraw.Draw(final_image)

		size		= size if width - size >= size else width - size

		# center
		cx = size // 2 + shift_x
		cy = size // 2 + shift_y

		if shift_x == 0 and width > size:
			cx	=	width // 2
		elif shift_y == 0 and height > size:
			cy	=	height // 2

		thickness = max(2, size // 16)

		# arc 1
		r1	= size * 7 // 12
		draw.arc(
			[cx - r1, cy - r1, cx + r1, cy + r1],
			start	= 225,
			end		= 315,
			fill	= 'white',
			width	= thickness
		)

		# arc 2
		r2	= size * 5 // 12
		draw.arc(
			[cx - r2, cy - r2, cx + r2, cy + r2],
			start	= 220,
			end		= 320,
			fill	= 'white',
			width	= thickness
		)

		# arc 3
		r3	= size * 3 // 12
		draw.arc(
			[cx - r3, cy - r3, cx + r3, cy + r3],
			start	= 215,
			end		= 325,
			fill	= 'white',
			width	= thickness
		)

		point_r = size * 1 // 12
		draw.ellipse(
			[cx - point_r, cy - point_r, cx + point_r, cy + point_r],
			fill	= 'white'
		)

		font	= ImageFont.load_default()
		HOT		= 'HOT'
		bbox	= draw.textbbox((0, 0), HOT, font=font)
		HOT_w	= bbox[2] - bbox[0]

		HOT_x = cx - HOT_w // 2
		HOT_y = cy + point_r + (size // 12)

		draw.text((HOT_x, HOT_y), HOT, font=font, fill="white")

		if os.path.exists(self.__const_WIFI_QR_FILE_PATH):
			os.remove(self.__const_WIFI_QR_FILE_PATH)

		final_image.save(self.__const_WIFI_QR_FILE_PATH)

		return()

	def new_status(self, status):
		# display new status
		status_translated	= None
		if status in ['HOTSPOT', 'CONNECTING', 'CONNECTED']:
			status_translated	= self.__lan.l(f'box_comitup_status_{status}')
			status_translated	= status_translated if status_translated!=f'box_comitup_status_{status}' else status
		elif status == 'RESET':
			status_translated	= self.__lan.l('box_comitup_reset_done')

		if status_translated is not None:
			self.__display.message([f'set:temp,time={self.__conf_DISP_FRAME_TIME * 4}', ':Comitup:', f':{status_translated}'], logging=False)

		# setup apache ports
		ApachePortsConf	= '/etc/apache2/ports.conf'

		BasicPorts	= [
			81,
			443,
			8080,
			8443,
			8843
		]

		with open(ApachePortsConf,'w') as f:
			for Port in BasicPorts:
				f.write(f'Listen {Port}\n')

			if not (status in ['HOTSPOT', 'RESET'] or self.hotspot_active()):
				f.write(f'Listen 80\n')

		subprocess.run('service apache2 restart || service apache2 start', shell=True)

		# create WIFI QR
		self.create_wifi_link_qr_image()

	def hotspot_active(self):
		status	= self.get_status()
		return(status['mode'] == 'router' or status['state'] == 'HOTSPOT')

	def reset(self):
		try:
			subprocess.run(['sudo', 'comitup-cli', 'd'])
			subprocess.run(['sudo', 'systemctl', 'restart', 'comitup'])
		except:
			pass
		else:
			# adapt apache ports
			self.new_status('RESET')


if __name__ == "__main__":
	try:
		Mode	= sys.argv[1]
	except:
		Mode	= ''

	if Mode == '--config':
		try:
			Password	= sys.argv[2]
		except:
			Password	= None

		comitup().config(Password)

	elif Mode == '--get_status':
		print(comitup().get_status())

	elif Mode == '--set_status':
		try:
			Status	= sys.argv[2]
		except:
			Status	= ''

		if Status:
			comitup().new_status(Status)

	elif Mode == '--hotspot_active':
		print('active' if comitup().hotspot_active() else 'inactive')
	elif Mode == 'create_wifi_link_qr_image':
		comitup().create_wifi_link_qr_image()

	elif Mode == '--reset':
		comitup().reset()
