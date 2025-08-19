#!/usr/bin/env python3

import asyncio
from datetime import datetime
import os
from telegram import Bot

import lib_language

# import lib_debug
# xx	= lib_debug.debug()

class telegram(object):
	def __init__(self, TOKEN, CHAT_ID, EXTENSIONS_LIST_VIDEO, EXTENSIONS_LIST_AUDIO):
		self.__lan		= lib_language.language()

		self.date_old	= None

		self.TOKEN		= TOKEN
		self.CHAT_ID	= CHAT_ID

		self.EXTENSIONS_LIST_VIDEO	= EXTENSIONS_LIST_VIDEO.split(';')
		self.EXTENSIONS_LIST_AUDIO	= EXTENSIONS_LIST_AUDIO.split(';')


	def configured(self):
		return(self.TOKEN.strip() and self.CHAT_ID != 0)

	def __reformat_Comment(self, Comment, FileDate):
		FileDate	= FileDate if FileDate and FileDate != '0000-00-00 00:00:00' else ''
		if FileDate:
			if not isinstance(FileDate, datetime):
				try:
					FileDate = datetime.strptime(FileDate, "%Y-%m-%d %H:%M:%S")
				except:
					pass
			try:
				FileDate	=	FileDate.strftime(self.__lan.l('view_date_format_python').replace('X', '%'))
			except:
				pass

		return(f'{FileDate or ""}{(Comment or "").strip()}' if not FileDate or not Comment else f'{FileDate}: {Comment}')

	async def publish_async(self, Comment='', FilePath=None, FileDate=None):
		if self.configured():
			self.bot = Bot(token=self.TOKEN)
		else:
			return(False)

		Extension	= None
		try:
			Extension	= os.path.splitext(FilePath)[1].replace('.', '')
		except:
			pass

		if Comment and FilePath is None:
			Comment	= self.__reformat_Comment(Comment, FileDate)
			try:
				await self.bot.send_message(chat_id=self.CHAT_ID, text=Comment)
				return({'ok': True, 'msg': msg})
			except:
				return({'ok': False, 'msg': Comment})

		elif FilePath:
			Comment	= self.__reformat_Comment(Comment, FileDate)
			try:
				with open(FilePath, "rb") as media_file:
					if Extension in self.EXTENSIONS_LIST_VIDEO:
						msg	= await self.bot.send_video(
							chat_id=self.CHAT_ID,
							video=media_file,
							caption=Comment,
							supports_streaming=True
						)
					elif Extension in self.EXTENSIONS_LIST_AUDIO:
						msg	= await self.bot.send_voice(
							chat_id=self.CHAT_ID,
							voice=media_file,
							caption=Comment
						)
					else:
						msg	= await self.bot.send_photo(chat_id=self.CHAT_ID, photo=media_file, caption=Comment)
				return({'ok': True, 'msg': os.path.basename(FilePath)})
			except:
				return({'ok': False, 'msg': os.path.basename(FilePath)})

	def publish(self, Comment='', FilePath=None, FileDate=None):
		try:
			asyncio.get_running_loop()
		except RuntimeError:
			return asyncio.run(self.publish_async(Comment, FilePath, FileDate))
		else:
			raise RuntimeError("publish() called from async-context – please use 'await publish_async(...)'.")
