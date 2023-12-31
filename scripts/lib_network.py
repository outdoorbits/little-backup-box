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
import qrcode
import subprocess
from urllib import request
import sys

def get_IP():
	IP	= subprocess.check_output(['hostname','-I']).decode().replace(' ','\n').strip()

	return(IP)

def create_ip_link_qr_image(IP, const_IP_QR_FILE, SquareSize): # for SquareSize use min(conf_DISP_RESOLUTION_X, conf_DISP_RESOLUTION_Y)
	IP_QR_FILE	= const_IP_QR_FILE.replace('_', IP.replace('.', '-'), 1)
	IP_QR_FILE	= IP_QR_FILE.replace('_', f"_{SquareSize}")

	if not os.path.isfile(IP_QR_FILE) and (SquareSize > 10):
		qr	= qrcode.QRCode(
			version=1,
			error_correction=qrcode.constants.ERROR_CORRECT_L,
			box_size=SquareSize,
			border=1,
		)
		qr.add_data(f"https://{IP}")
		qr.make(fit=True)

		img = qr.make_image(fill_color="black", back_color="white")
		img.save(IP_QR_FILE)

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
