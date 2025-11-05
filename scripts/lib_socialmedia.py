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

from datetime import datetime
import os
from pathlib import Path
import time

import lib_display
import lib_language
import lib_time
import lib_setup
import lib_system

from lib_socialmedia_telegram import telegram
from lib_socialmedia_matrix import matrix
from lib_socialmedia_mastodon import mastodon
from lib_socialmedia_bluesky import bluesky

###############################################################################################
# social media service objects must have
# 	- def __init__
# 	- def configured
# 	- def publish
###############################################################################################

# import lib_debug
# xx	= lib_debug.debug()

class socialmedia(object):

	class messagetype(object):
		def __init__(self):
			self.main	= None
			self.sub	= None

	def __init__(self, service=None, TelegramChatID=0, upload_times=[]):

		self.__display	= lib_display.display()
		self.__lan		= lib_language.language()
		self.__setup	= lib_setup.setup()

		self.conf_SOCIAL_PUBLISH_DATE		= self.__setup.get_val('conf_SOCIAL_PUBLISH_DATE')
		self.conf_SOCIAL_PUBLISH_FILENAME	= self.__setup.get_val('conf_SOCIAL_PUBLISH_FILENAME')

		self.service		= service
		self.TelegramChatID	= TelegramChatID

		self.upload_times	= upload_times

		self.SERVICE_Obj	= self.get_service_object(service=service)

		self.EXTENSIONS_LIST_VIDEO	= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_VIDEO').split(';')
		self.EXTENSIONS_LIST_AUDIO	= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_AUDIO').split(';')
		self.EXTENSIONS_LIST_PHOTO	= ';'.join([self.__setup.get_val('const_FILE_EXTENSIONS_LIST_WEB_IMAGES'), self.__setup.get_val('const_FILE_EXTENSIONS_LIST_HEIC'), self.__setup.get_val('const_FILE_EXTENSIONS_LIST_TIF')]).split(';')
		self.EXTENSIONS_LIST_TEXT	= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_TEXT').split(';')

	def get_service_object(self, service=None, check_only=False):
		if service == 'telegram':
			self.TelegramChatID	= int(self.TelegramChatID)
			TelegramChatID	= self.TelegramChatID if self.TelegramChatID != 0 else self.__setup.get_val('conf_SOCIAL_TELEGRAM_CHAT_ID')
			return (
				telegram(
					TG_TOKEN	= self.__setup.get_val('conf_SOCIAL_TELEGRAM_TOKEN'),
					TG_CHAT_ID	= TelegramChatID,
					check_only	= check_only
				)
			)
		elif service == 'matrix':
			return(
				matrix(
					HOMESERVER		= self.__setup.get_val('conf_SOCIAL_MATRIX_HOMESERVER'),
					ACCESS_TOKEN	= self.__setup.get_val('conf_SOCIAL_MATRIX_TOKEN'),
					ROOM_ID			= self.__setup.get_val('conf_SOCIAL_MATRIX_ROOM_ID')
				)
			)
		elif service == 'mastodon':
			return(
				mastodon(
					MA_API_BASE_URL	= self.__setup.get_val('conf_SOCIAL_MASTODON_BASE_URL'),
					MA_ACCESS_TOKEN	= self.__setup.get_val('conf_SOCIAL_MASTODON_TOKEN'),
					check_only	= check_only
				)
			)
		elif service == 'bluesky':
			return(
				bluesky(
					BS_API_BASE_URL	= self.__setup.get_val('conf_SOCIAL_BLUESKY_API_BASE_URL'),
					BS_IDENTIFIER  	= self.__setup.get_val('conf_SOCIAL_BLUESKY_IDENTIFIER'),
					BS_APP_PASSWORD = self.__setup.get_val('conf_SOCIAL_BLUESKY_APP_PASSWORD'),
					check_only	= check_only
				)
			)
		else:
			return(None)

	def get_social_services(self):
		# returns services in the order to display
		social_services	= [
			'telegram',
			'matrix',
			'mastodon',
			'bluesky'

		]
		return(social_services)

	def get_social_service_bit(self, service):
		# return the bit number representing the service
		social_services	= [
			'telegram',
			'mastodon',
			'bluesky',
			'matrix'
		]

		if service not in social_services:
			return(None)

		return(social_services.index(service))

	def get_social_services_configured(self):
		services_configured	= []

		for service in self.get_social_services():
			if self.get_service_object(service=service, check_only=True).configured():
				services_configured.append(service)

		return(services_configured)

	def configured(self):
		return(False if self.SERVICE_Obj is None else self.SERVICE_Obj.configured())

	def __format_DateTime(self, Create_Date):
		if isinstance(Create_Date, datetime):
			FileDate	= Create_Date
		else:
			try:
				# convert string to datetime
				FileDate	= lib_time.timeinterpreter().parse_datetime_local(Create_Date)
			except:
				return(Create_Date)

		try:
			return(FileDate.strftime(self.__lan.l('view_date_format_python').replace('X', '%')))
		except:
			return(Create_Date)

	def __reformat_Comment(self, Comment, FileDate, msgtype=None, FilePath=None):

		FileDateShow	= FileDate if self.conf_SOCIAL_PUBLISH_DATE else ''
		FilePathShow	= FilePath if self.conf_SOCIAL_PUBLISH_FILENAME else ''

		# Content
		Content	= ''
		if msgtype is not None and msgtype.main == 'text' and FilePath is not None:
			try:
				with open(FilePath, 'r') as f:
					Content	= f.read()
			except:
				Content	= ''

		if msgtype is not None and msgtype.sub == 'html':
			sep_NewLine	= '<br>'
		else:
			sep_NewLine	= "\n"

		# bottom
		sep	= '' if not FilePathShow or not FileDateShow else ' - '
		Bottom	= f'{FileDateShow}{sep}{Path(FilePathShow).name}'

		sep	= '' if not Bottom or not Comment else sep_NewLine
		Bottom	= f'{Bottom}{sep}{Comment}'

		# concat Content and bottom
		sep	= '' if not Bottom or not Content else  f' {sep_NewLine}{sep_NewLine}'
		CommentNew	= f'{Content}{sep}{Bottom}'

		return(CommentNew)

	def publish(self, Comment='', FilePath=None, Create_Date=''):
		if Create_Date:
			FileDate	= self.__format_DateTime(Create_Date)
		else:
			FileDate	= ''

		msgtype	= self.messagetype()
		if FilePath is None:
			msgtype.main	= 'text'
		else:
			Extension	= Path(FilePath).suffix.lower().replace('.', '', 1)

			if Extension in self.EXTENSIONS_LIST_VIDEO:
				msgtype.main	= 'video'
			elif Extension in self.EXTENSIONS_LIST_AUDIO:
				msgtype.main	= 'audio'
			elif Extension in self.EXTENSIONS_LIST_TEXT:
				msgtype.main	= 'text'
				msgtype.sub	= Extension
			elif Extension in self.EXTENSIONS_LIST_PHOTO:
				msgtype.main	= 'photo'
			else:
				msgtype.main = 'document'

		Comment	= self.__reformat_Comment(Comment=Comment, FileDate=FileDate, msgtype=msgtype, FilePath=FilePath)

		if msgtype.main and self.SERVICE_Obj:
			delaytime	= self.SERVICE_Obj.delaytime(self.upload_times)
			if delaytime > 0:
				ServiceName	= self.__lan.l(f'box_backup_mode_social_{self.service}')
				while delaytime > 0:
					self.__display.message([
						'set:clear',
						f":{ServiceName}",
						f":{self.__lan.l('box_backup_delay_rate_limit')}",
						f":{delaytime:.0f} s"
					])
					time.sleep(1)
					delaytime	-= 1

			self.upload_times.append(lib_system.get_uptime_sec())
			while self.SERVICE_Obj.rate_limit_count and len(self.upload_times) > self.SERVICE_Obj.rate_limit_count:
				self.upload_times.pop(0)

			self.SERVICE_Obj.publish(msgtype=msgtype, Comment=Comment, FilePath=FilePath)
			return({'ok': self.SERVICE_Obj.ok, 'msg': self.SERVICE_Obj.returnmessage})
		else:
			return({'ok': False, 'msg': f'msgtype={msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}, self.SERVICE_Obj={self.SERVICE_Obj}'})

def parse_args() -> argparse.Namespace:
	parser = argparse.ArgumentParser(
		description="view database related tools",
		formatter_class=argparse.RawTextHelpFormatter,
	)

	actions	= ['get_social_services', 'get_social_service_bit', 'get_social_services_configured']
	parser.add_argument(
		'--action',
		'-a',
		choices		= actions,
		required =	True,
		help=f'One of {actions}'
	)

	services	= socialmedia().get_social_services()
	parser.add_argument(
		'--service',
		'-s',
		choices		= services,
		required =	False,
		help=f'One of {services}'
	)

	args = parser.parse_args()

	if args.action == "get_social_service_bit" and not args.service:
		parser.error("--service is required when --action=get_social_service_bit")

	return args

if __name__ == "__main__":
	args = parse_args()

	if args.action == 'get_social_services':
		social_services	= socialmedia().get_social_services()
		print(";".join(social_services))

	if args.action == 'get_social_service_bit':
		bit	= socialmedia().get_social_service_bit(args.service)
		print(bit)

	elif args.action == 'get_social_services_configured':
		social_services	= socialmedia().get_social_services_configured()
		print(";".join(social_services))
