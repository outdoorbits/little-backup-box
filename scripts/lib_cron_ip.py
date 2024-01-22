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

import lib_display
import lib_language
import lib_mail
import lib_network
import lib_setup


class ip_info(object):

	def __init__(self):
		# objects
		self.__setup	= lib_setup.setup()
		self.__display	= lib_display.display()
		self.__lan	= lib_language.language()

		# setup
		self.__conf_DISP_IP_REPEAT				= self.__setup.get_val('conf_DISP_IP_REPEAT')

		# Shared values
		self.__IPs	= []

	def get_ip(self):
		self.__IPs	= lib_network.get_IP().split('\n')

	def display_ip(self,FrameTime=None, force=False):

		if not (self.__conf_DISP_IP_REPEAT or force):
			return()

		const_DISPLAY_CONTENT_OLD_FILE	= self.__setup.get_val('const_DISPLAY_CONTENT_OLD_FILE')
		const_IP_QR_FILE_PATTERN		= self.__setup.get_val('const_IP_QR_FILE_PATTERN')

		conf_DISP_RESOLUTION_X			= self.__setup.get_val('conf_DISP_RESOLUTION_X')
		conf_DISP_RESOLUTION_Y			= self.__setup.get_val('conf_DISP_RESOLUTION_Y')
		const_FONT_PATH					= self.__setup.get_val('const_FONT_PATH')
		conf_DISP_FONT_SIZE				= self.__setup.get_val('conf_DISP_FONT_SIZE')

		self.get_ip()

		if self.__IPs:
			DisplayContentOld	= ''
			if os.path.isfile(const_DISPLAY_CONTENT_OLD_FILE):
				with open(const_DISPLAY_CONTENT_OLD_FILE,'r') as f:
					DisplayContentOld	= f.read()

			self.__IPsFormatted	= []

			OnlineStatus	= lib_network.get_internet_status()
			OnlineMessage	= self.__lan.l('box_cronip_online') if OnlineStatus else self.__lan.l('box_cronip_offline')

			for IP in self.__IPs:
				IP	= IP.strip()

				if IP and ((IP not in DisplayContentOld) or (OnlineMessage not in DisplayContentOld) or force):
					IP_QR_FILE	= lib_network.create_ip_link_qr_image(IP=IP, OnlineStatus=OnlineStatus, IP_QR_FILE=const_IP_QR_FILE_PATTERN, width=conf_DISP_RESOLUTION_X, height=conf_DISP_RESOLUTION_Y,font=const_FONT_PATH, fontsize=conf_DISP_FONT_SIZE)

					if not IP_QR_FILE is None:
						FrameTime	= 5 if FrameTime is None else FrameTime
						self.__display.message([f'set:time={FrameTime},temp,hidden={IP}_{OnlineMessage}', f":IMAGE={IP_QR_FILE}"])
					else:
						self.__IPsFormatted.append(f":{IP}")

			if self.__IPsFormatted:
				FrameTime	= 3 if FrameTime is None else FrameTime
				self.__display.message(['set:time={FrameTime}', f":{OnlineMessage}, IP:"] + self.__IPsFormatted)

		elif force and not self.__IPs:
			self.__display.message(['set:clear', f":{self.__lan.l('box_cronip_offline')}"])

	def mail_ip(self):
		IP_sent_Markerfile			= self.__setup.get_val('const_IP_SENT_MARKERFILE')
		const_IP_QR_FILE_PATTERN	= self.__setup.get_val('const_IP_QR_FILE_PATTERN')

		conf_MAIL_NOTIFICATIONS		= self.__setup.get_val('conf_MAIL_NOTIFICATIONS')
		conf_DISP_RESOLUTION_X		= self.__setup.get_val('conf_DISP_RESOLUTION_X')
		conf_DISP_RESOLUTION_Y		= self.__setup.get_val('conf_DISP_RESOLUTION_Y')
		const_FONT_PATH				= self.__setup.get_val('const_FONT_PATH')
		conf_DISP_FONT_SIZE			= self.__setup.get_val('conf_DISP_FONT_SIZE')

		mailObj	= lib_mail.mail()

		self.get_ip()

		if (
			self.__IPs and
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
			for IP in self.__IPs:
				if IP not in MarkerfileContent:
					newIP	= True

			# write lockfile
			with open(IP_sent_Markerfile,'w') as f:
				f.write(', '.join(self.__IPs))

			# create links
			indexLinksPlainSSL		= ''
			indexLinksPlain8000		= ''
			sambaLinksPlain			= ''

			indexLinksHTMLSSL		= ''
			indexLinksHTML8000		= ''
			sambaLinksHTML			= ''

			for IP in self.__IPs:
				# create qr link
				IP_QR_FILE	= lib_network.create_ip_link_qr_image(IP=IP, OnlineStatus=True, IP_QR_FILE=const_IP_QR_FILE_PATTERN, width=conf_DISP_RESOLUTION_X, height=conf_DISP_RESOLUTION_Y,font=const_FONT_PATH, fontsize=conf_DISP_FONT_SIZE)

				if IP_QR_FILE is None:
					qr_link	= ''
				else:
					try:
						with open(IP_QR_FILE, "rb") as qr_file:
							base64_image	= base64.b64encode(qr_file.read()).decode()

						qr_link	= f'<br><img src="data:image/png;base64, {base64_image}" style="border:5px solid black;">'
					except:
						base64_image	= ''
						qr_link	= ''

				indexLinksPlainSSL	+= f'  https://{IP}\n'
				indexLinksPlain8000	+= f'  http://{IP}:8000\n'
				sambaLinksPlain		+= f'  smb://{IP}\n'

				indexLinksHTMLSSL	+= f'  <a href="https://{IP}">https://{IP}{qr_link}</a><br>\n'
				indexLinksHTML8000	+= f'  <a href="http://{IP}:8000">http://{IP}:8000</a><br>\n'
				sambaLinksHTML		+= f'  <a href="smb://{IP}">smb://{IP}</a><br>\n'

			#send mail
			if newIP:
				mailObj.sendmail(
					Subject		= f"{self.__lan.l('box_cronip_mail_info')}: {', '.join(self.__IPs)}",
					TextPlain	= self.__getTextPlain(indexLinksPlainSSL,indexLinksPlain8000,sambaLinksPlain),
					TextHTML	= self.__getTextHTML(indexLinksHTMLSSL,indexLinksHTML8000,sambaLinksHTML)
				)

	def __getTextPlain(self,indexLinksPlainSSL,indexLinksPlain8000,sambaLinksPlain):
		return(f"""
	*** {self.__lan.l('box_cronip_mail_main')}: ***
	{self.__lan.l('box_cronip_mail_description_https')}:
	{indexLinksPlainSSL}

	{self.__lan.l('box_cronip_mail_description_http')}:
	{indexLinksPlain8000}

	*** {self.__lan.l('box_cronip_mail_open_samba')}: ***
	{sambaLinksPlain}""")

	def __getTextHTML(self,indexLinksHTMLSSL,indexLinksHTML8000,sambaLinksHTML):
		return(f"""
	<b>{self.__lan.l('box_cronip_mail_main')}:</b><br>
	{self.__lan.l('box_cronip_mail_description_https')}:<br>
	{indexLinksHTMLSSL}
	<br>
	{self.__lan.l('box_cronip_mail_description_http')}:<br>
	{indexLinksHTML8000}
	<br>
	<b>{self.__lan.l('box_cronip_mail_open_samba')}:</b><br>
	{sambaLinksHTML}"""
	)




if __name__ == "__main__":
	ip_info().display_ip()
	ip_info().mail_ip()
