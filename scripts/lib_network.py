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

import os
from PIL import Image, ImageDraw, ImageFont
import qrcode
import subprocess
from urllib import request
import sys

def get_IP():
	IP	= subprocess.check_output(['hostname','-I']).decode().replace(' ','\n').strip()

	return(IP)

def create_ip_link_qr_image(IP, const_IP_QR_FILE, width, height, font=None, fontsize=8):

	qr_box_size	= int(height/10)
	qr_border	= 1

	LinkText	= f"https://{IP}"

	# set file name
	IP_QR_FILE	= const_IP_QR_FILE.replace('_', IP.replace('.', '-'), 1)
	IP_QR_FILE	= IP_QR_FILE.replace('_', f"_{height}")

	if not os.path.isfile(IP_QR_FILE) and (height >= 30):

		if not font is None:
			qr_height	= height - fontsize - 1
		else:
			qr_height	= height

		qr	= qrcode.QRCode(
			version=1,
			error_correction	= qrcode.constants.ERROR_CORRECT_L,
				box_size		= qr_box_size,
				border			= qr_border,
		)
		qr.add_data(LinkText)
		qr.make(fit=True)

		qr_image	= qr.make_image(fill_color="black", back_color="white")
		qr_image	= qr_image.resize((qr_height,qr_height))

		final_image	= Image.new('RGB', (width, height))

		final_image.paste(qr_image,(0, 0))

		draw = ImageDraw.Draw(final_image)
		font			= ImageFont.truetype(font, fontsize)
		draw.text((0, qr_height), IP, font=font)

		final_image.save(IP_QR_FILE)

	if not os.path.isfile(IP_QR_FILE):
		IP_QR_FILE	= None

	return(IP_QR_FILE)

def get_internet_status():
	try:
		request.urlopen('https://google.com', timeout=5)
		return(True)
	except:
		pass

	return(False)

if __name__ == "__main__":
	Mode	= None
	try:
		Mode	= sys.argv[1]
	except:
		pass

	if Mode == 'ip':
		print (get_IP())

	if Mode == 'internet_status':
		print (get_internet_status())
