#!/usr/bin/env python3

from datetime import datetime
import os
from telegram import Bot

import lib_language
import lib_setup
import lib_time


# import lib_debug
# xx	= lib_debug.debug()

class socialmedia(object):

	def __init__(self, service=None, EXTENSIONS_LIST_VIDEO=None, EXTENSIONS_LIST_AUDIO=None, EXTENSIONS_LIST_PHOTO=None, telegram_token=None, telegram_chat_id=None):
		self.__lan		= lib_language.language()

		self.service	= service

		self.SERVICE_Obj	= None
		if service == 'telegram':
			self.SERVICE_Obj	= telegram(TOKEN=telegram_token, CHAT_ID=telegram_chat_id)

		self.EXTENSIONS_LIST_VIDEO	= EXTENSIONS_LIST_VIDEO.split(';')
		self.EXTENSIONS_LIST_AUDIO	= EXTENSIONS_LIST_AUDIO.split(';')
		self.EXTENSIONS_LIST_PHOTO	= EXTENSIONS_LIST_PHOTO.split(';')

	def configured(self):
		return(self.SERVICE_Obj.configured())

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

	def __reformat_Comment(self, Comment, FileDate):
		return(f'{FileDate or ""}{(Comment or "").strip()}' if not FileDate or not Comment else f'{FileDate}: {Comment}')

	def publish(self, Comment='', FilePath=None, Create_Date=''):
		if Create_Date:
			FileDate	= self.__format_DateTime(Create_Date)
		else:
			FileDate	= ''

		msgtype	= None
		if Comment and FilePath is None:
			msgtype	= 'text'
			Comment	= self.__reformat_Comment(Comment=Comment, FileDate=FileDate)
		elif FilePath:
			Comment	= self.__reformat_Comment(Comment=Comment, FileDate=FileDate)

			try:
				Extension	= os.path.splitext(FilePath)[1].replace('.', '')
			except:
				Extension	= None

			if Extension in self.EXTENSIONS_LIST_VIDEO:
				msgtype	= 'video'
			if Extension in self.EXTENSIONS_LIST_AUDIO:
				msgtype	= 'audio'
			if Extension in self.EXTENSIONS_LIST_PHOTO:
				msgtype	= 'photo'
			else:
				msgtype = 'document'

		if msgtype and self.SERVICE_Obj:
			self.SERVICE_Obj.publish(msgtype=msgtype, Comment=Comment, FilePath=FilePath)
		else:
			return(False)

###############################################################################################
# social media service objects must have
# 	- def __init__
# 	- def configured
# 	- def publish
###############################################################################################

class telegram(object):
	import asyncio

	def __init__(self, TOKEN, CHAT_ID):

		self.__setup	= lib_setup.setup()

		self.date_old	= None

		self.TOKEN		= TOKEN
		self.CHAT_ID	= CHAT_ID

	def configured(self):
		return(self.TOKEN.strip() and self.CHAT_ID != 0)

	async def __publish_async(self, msgtype, Comment='', FilePath=None):

		if msgtype == 'text':
			try:
				await self.bot.send_message(chat_id=self.CHAT_ID, text=Comment)
				return({'ok': True, 'msg': msg})
			except:
				return({'ok': False, 'msg': Comment})

		elif msgtype == 'video':
			try:
				with open(FilePath, "rb") as media_file:
					msg	= await self.bot.send_video(
						chat_id=self.CHAT_ID,
						video=media_file,
						caption=Comment,
						supports_streaming=True
					)
				return({'ok': True, 'msg': os.path.basename(FilePath)})
			except:
				return({'ok': False, 'msg': os.path.basename(FilePath)})

		elif msgtype == 'audio':
			try:
				with open(FilePath, "rb") as media_file:
					msg	= await self.bot.send_voice(
						chat_id=self.CHAT_ID,
						voice=media_file,
						caption=Comment
					)
				return({'ok': True, 'msg': os.path.basename(FilePath)})
			except:
				return({'ok': False, 'msg': os.path.basename(FilePath)})

		elif msgtype == 'photo':
			try:
				with open(FilePath, "rb") as media_file:
					msg	= await self.bot.send_photo(
						chat_id=self.CHAT_ID,
						photo=media_file,
						caption=Comment
					)
				return({'ok': True, 'msg': os.path.basename(FilePath)})
			except:
				return({'ok': False, 'msg': os.path.basename(FilePath)})

		elif msgtype == 'document':
			try:
				with open(FilePath, "rb") as media_file:
					msg	= await self.bot.send_document(
						chat_id=self.CHAT_ID,
						document=media_file,
						caption=Comment
					)
				return({'ok': True, 'msg': os.path.basename(FilePath)})
			except:
				return({'ok': False, 'msg': os.path.basename(FilePath)})

	def publish(self, msgtype, Comment='', FilePath=None):

		if self.configured():
			self.bot = Bot(token=self.TOKEN)
		else:
			return(False)

		try:
			asyncio.get_running_loop()
		except RuntimeError:
			return asyncio.run(self.__publish_async(msgtype, Comment=Comment, FilePath=FilePath))
		else:
			raise RuntimeError("publish() called from async-context – please use 'await publish_async(...)'.")
