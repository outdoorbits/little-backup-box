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

import lib_language
import lib_time

###############################################################################################
# social media service objects must have
# 	- def __init__
# 	- def configured
# 	- def publish
###############################################################################################

from lib_socialmedia_telegram import telegram
from lib_socialmedia_mastodon import mastodon
from lib_socialmedia_bluesky import bluesky

# import lib_debug
# xx	= lib_debug.debug()

def get_social_services():
	social_services	= [
		'telegram',
		'mastodon',
		'bluesky'
	]
	return(social_services)

class socialmedia(object):

	def __init__(
			self,
			service=None,
			EXTENSIONS_LIST_VIDEO=None,
			EXTENSIONS_LIST_AUDIO=None,
			EXTENSIONS_LIST_PHOTO=None,
			EXTENSIONS_LIST_TEXT=None,
			telegram_token=None,
			telegram_chat_id=None,
			mastodon_base_url=None,
			mastodon_token=None,
			bluesky_api_base_url=None,
			bluesky_identifier=None,
			bluesky_app_password=None
		):
		self.__lan		= lib_language.language()

		self.service	= service

		self.SERVICE_Obj	= None
		if service == 'telegram':
			self.SERVICE_Obj	= telegram(
				TG_TOKEN=telegram_token,
				TG_CHAT_ID=telegram_chat_id
			)
		elif service == 'mastodon':
			self.SERVICE_Obj	= mastodon(
				MA_API_BASE_URL=mastodon_base_url,
				MA_ACCESS_TOKEN=mastodon_token
			)
		elif service == 'bluesky':
			self.SERVICE_Obj	= bluesky(
				BS_API_BASE_URL	= bluesky_api_base_url,
				BS_IDENTIFIER  	= bluesky_identifier,
				BS_APP_PASSWORD = bluesky_app_password
			)

		self.EXTENSIONS_LIST_VIDEO	= EXTENSIONS_LIST_VIDEO.split(';')
		self.EXTENSIONS_LIST_AUDIO	= EXTENSIONS_LIST_AUDIO.split(';')
		self.EXTENSIONS_LIST_PHOTO	= EXTENSIONS_LIST_PHOTO.split(';')
		self.EXTENSIONS_LIST_TEXT	= EXTENSIONS_LIST_TEXT.split(';')

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

		# Content
		Content	= ''
		if msgtype == 'text' and not FilePath is None:
			try:
				with open(FilePath, 'r') as f:
					Content	= f.read()
			except:
				Content	= ''

		# bottom
		sep	= '' if not FilePath or not FileDate else ': '
		Bottom	= f'{Path(FilePath).name}{sep}{FileDate}'

		sep	= '' if not Bottom or not Comment else os.linesep
		Bottom	= f'{Bottom}{sep}{Comment}'

		# concat Content and bottom
		sep	= '' if not Bottom or not Content else  f'{os.linesep}{os.linesep}'
		CommentNew	= f'{Content}{sep}{Bottom}'

		return(CommentNew)

	def publish(self, Comment='', FilePath=None, Create_Date=''):
		if Create_Date:
			FileDate	= self.__format_DateTime(Create_Date)
		else:
			FileDate	= ''

		msgtype	= None
		if FilePath is None:
			msgtype	= 'text'
		else:
			Extension	= Path(FilePath).suffix.lower().replace('.', '', 1)

			if Extension in self.EXTENSIONS_LIST_VIDEO:
				msgtype	= 'video'
			elif Extension in self.EXTENSIONS_LIST_AUDIO:
				msgtype	= 'audio'
			elif Extension in self.EXTENSIONS_LIST_TEXT:
				msgtype	= 'text'
			elif Extension in self.EXTENSIONS_LIST_PHOTO:
				msgtype	= 'photo'
			else:
				msgtype = 'document'

		Comment	= self.__reformat_Comment(Comment=Comment, FileDate=FileDate, msgtype=msgtype, FilePath=FilePath)

		if msgtype and self.SERVICE_Obj:
			self.SERVICE_Obj.publish(msgtype=msgtype, Comment=Comment, FilePath=FilePath)
			return({'ok': self.SERVICE_Obj.ok, 'msg': self.SERVICE_Obj.returnmessage})
		else:
			return({'ok': False, 'msg': f'msgtype={msgtype}, self.SERVICE_Obj={self.SERVICE_Obj}'})

def parse_args() -> argparse.Namespace:
	parser = argparse.ArgumentParser(
		description="view database related tools",
		formatter_class=argparse.RawTextHelpFormatter,
	)

	actions	= ['get_social_services']
	parser.add_argument(
		'--action',
		'-a',
		choices		= actions,
		required =	True,
		help=f'One of {actions}'
	)

	args = parser.parse_args()

	return args

if __name__ == "__main__":
	args = parse_args()

	if args.action == 'get_social_services':
		social_services	= get_social_services()

		print("\n".join(social_services))
