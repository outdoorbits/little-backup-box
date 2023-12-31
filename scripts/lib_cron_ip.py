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

import lib_display
import lib_language
import lib_mail
import lib_network
import lib_setup

# objects
__setup	= lib_setup.setup()
__lan	= lib_language.language()

# setup
__conf_DISP_IP_REPEAT				= __setup.get_val('conf_DISP_IP_REPEAT')


# Shared values
__IPs	= lib_network.get_IP().split('\n')

def display_ip():
	display	= lib_display.display()

	const_DISPLAY_CONTENT_OLD_FILE	= __setup.get_val('const_DISPLAY_CONTENT_OLD_FILE')
	const_IP_QR_FILE				= __setup.get_val('const_IP_QR_FILE')

	conf_DISP_RESOLUTION_X			= __setup.get_val('conf_DISP_RESOLUTION_X')
	conf_DISP_RESOLUTION_Y			= __setup.get_val('conf_DISP_RESOLUTION_Y')

	if (
		__conf_DISP_IP_REPEAT and
		__IPs
	):
		DisplayContentOld	= ''
		if os.path.isfile(const_DISPLAY_CONTENT_OLD_FILE):
			with open(const_DISPLAY_CONTENT_OLD_FILE,'r') as f:
				DisplayContentOld	= f.read()

		__IPsFormatted	= []

		OnlineMessage	= __lan.l('box_cronip_online') if lib_network.get_internet_status() else __lan.l('box_cronip_offline')

		for IP in __IPs:
			IP	= IP.strip()

			if IP and ((IP not in DisplayContentOld) or (OnlineMessage not in DisplayContentOld)):
				__IPsFormatted.append(f":{IP}")

		if __IPsFormatted:
			display.message([f":{OnlineMessage}, IP:"] + __IPsFormatted)

			IP_QR_FILE	= lib_network.create_ip_link_qr_image(IP=IP, const_IP_QR_FILE=const_IP_QR_FILE, SquareSize=min(conf_DISP_RESOLUTION_X, conf_DISP_RESOLUTION_Y))

			if not IP_QR_FILE is None:
				display.message([f"time=3:IMAGE={IP_QR_FILE}"] + __IPsFormatted)


def mail_ip():
	IP_sent_Markerfile		= __setup.get_val('const_IP_SENT_MARKERFILE')
	const_IP_QR_FILE		= __setup.get_val('const_IP_QR_FILE')

	conf_MAIL_NOTIFICATIONS	= __setup.get_val('conf_MAIL_NOTIFICATIONS')
	conf_DISP_RESOLUTION_X	= __setup.get_val('conf_DISP_RESOLUTION_X')
	conf_DISP_RESOLUTION_Y	= __setup.get_val('conf_DISP_RESOLUTION_Y')

	mailObj	= lib_mail.mail()

	if (
		__IPs and
		conf_MAIL_NOTIFICATIONS and
		lib_network.get_internet_status()
	):

		# read lockfile
		MarkerfileContent	= ''
		if os.path.isfile(IP_sent_Markerfile):
			with open(IP_sent_Markerfile,'r') as f:
				MarkerfileContent	= f.read()

		# check for new IP
		newIP	= False
		for IP in __IPs:
			if IP not in MarkerfileContent:
				newIP	= True

		# create qr link
		IP_QR_FILE	= lib_network.create_ip_link_qr_image(IP=IP, const_IP_QR_FILE=const_IP_QR_FILE, SquareSize=min(conf_DISP_RESOLUTION_X, conf_DISP_RESOLUTION_Y))

		# write lockfile
		with open(IP_sent_Markerfile,'w') as f:
			f.write(', '.join(__IPs))

		# create links
		indexLinksPlainSSL		= ''
		indexLinksPlain8000		= ''
		sambaLinksPlain			= ''

		indexLinksHTMLSSL		= ''
		indexLinksHTML8000		= ''
		sambaLinksHTML			= ''
		for IP in __IPs:
			indexLinksPlainSSL	+= f'  https://{IP}\n'
			indexLinksPlain8000	+= f'  http://{IP}:8000\n'
			sambaLinksPlain		+= f'  smb://{IP}\n'

			indexLinksHTMLSSL	+= f'  <a href="https://{IP}">https://{IP}</a><br>\n'
			indexLinksHTML8000	+= f'  <a href="http://{IP}:8000">http://{IP}:8000</a><br>\n'
			sambaLinksHTML		+= f'  <a href="smb://{IP}">smb://{IP}</a><br>\n'

		#send mail
		if newIP:
			mailObj.sendmail(
				f"{__lan.l('box_cronip_mail_info')}: {', '.join(__IPs)}",
				__getTextPlain(indexLinksPlainSSL,indexLinksPlain8000,sambaLinksPlain),
				__getTextHTML(indexLinksHTMLSSL,indexLinksHTML8000,sambaLinksHTML),
				IP_QR_FILE
			)

def __getTextPlain(indexLinksPlainSSL,indexLinksPlain8000,sambaLinksPlain):
	return(f"""
*** {__lan.l('box_cronip_mail_main')}: ***
{__lan.l('box_cronip_mail_description_https')}:
{indexLinksPlainSSL}

{__lan.l('box_cronip_mail_description_http')}:
{indexLinksPlain8000}

*** {__lan.l('box_cronip_mail_open_samba')}: ***
{sambaLinksPlain}""")

def __getTextHTML(indexLinksHTMLSSL,indexLinksHTML8000,sambaLinksHTML):
	return(f"""
<b>{__lan.l('box_cronip_mail_main')}:</b><br>
{__lan.l('box_cronip_mail_description_https')}:<br>
{indexLinksHTMLSSL}
<br>
{__lan.l('box_cronip_mail_description_http')}:<br>
{indexLinksHTML8000}
<br>
<b>{__lan.l('box_cronip_mail_open_samba')}:</b><br>
{sambaLinksHTML}"""
)




if __name__ == "__main__":
	display_ip()
	mail_ip()
