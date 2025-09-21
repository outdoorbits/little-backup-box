#!/usr/bin/env python3

import argparse
import asyncio
from datetime import datetime
import os
from pathlib import Path

from telegram import Bot
from telegram.request import HTTPXRequest
from telegram import InputFile

from mastodon import Mastodon

import lib_language
import lib_setup
import lib_time


# import lib_debug
# xx	= lib_debug.debug()

def get_social_services():
	social_services	= [
		'telegram',
		'mastodon'
	]
	return(social_services)

class socialmedia(object):

	def __init__(
			self,
			service=None,
			EXTENSIONS_LIST_VIDEO=None,
			EXTENSIONS_LIST_AUDIO=None,
			EXTENSIONS_LIST_PHOTO=None,
			telegram_token=None,
			telegram_chat_id=None,
			mastodon_base_url=None,
			mastodon_token=None
		):
		self.__lan		= lib_language.language()

		self.service	= service

		self.SERVICE_Obj	= None
		if service == 'telegram':
			self.SERVICE_Obj	= telegram(TG_TOKEN=telegram_token, TG_CHAT_ID=telegram_chat_id)
		elif service == 'mastodon':
			self.SERVICE_Obj	= mastodon(MA_API_BASE_URL=mastodon_base_url, MA_ACCESS_TOKEN=mastodon_token)

		self.EXTENSIONS_LIST_VIDEO	= EXTENSIONS_LIST_VIDEO.split(';')
		self.EXTENSIONS_LIST_AUDIO	= EXTENSIONS_LIST_AUDIO.split(';')
		self.EXTENSIONS_LIST_PHOTO	= EXTENSIONS_LIST_PHOTO.split(';')

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

			Extension	= Path(FilePath).suffix.lower().replace('.', '', 1)

			if Extension in self.EXTENSIONS_LIST_VIDEO:
				msgtype	= 'video'
			elif Extension in self.EXTENSIONS_LIST_AUDIO:
				msgtype	= 'audio'
			elif Extension in self.EXTENSIONS_LIST_PHOTO:
				msgtype	= 'photo'
			else:
				msgtype = 'document'

		if msgtype and self.SERVICE_Obj:
			self.SERVICE_Obj.publish(msgtype=msgtype, Comment=Comment, FilePath=FilePath)
			return({'ok': self.SERVICE_Obj.ok, 'msg': self.SERVICE_Obj.returnmessage})
		else:
			return({'ok': False, 'msg': f'msgtype={msgtype}, self.SERVICE_Obj={self.SERVICE_Obj}'})

###############################################################################################
# social media service objects must have
# 	- def __init__
# 	- def configured
# 	- def publish
###############################################################################################

class telegram(object):

	def __init__(self, TG_TOKEN, TG_CHAT_ID):

		self.TOKEN		= TG_TOKEN
		self.CHAT_ID	= TG_CHAT_ID

		self.ok				= False
		self.returnmessage	= ''

		if self.configured():
			request	= HTTPXRequest(
				connect_timeout			= 30.0,			# Max seconds to establish the TCP/TLS connection
				read_timeout			= 120.0,		# Max seconds waiting for Telegram's response (headers/body)
				write_timeout			= 120.0,		# Max seconds to send non-media request data
				pool_timeout			= 30.0,			# Max seconds to wait for a free connection from the pool
				media_write_timeout		= 300.0			# Max seconds to upload media chunks (photos/videos/documents)
			)
			self.bot	= Bot(token=self.TOKEN, request=request)
		else:
			self.bot	= None

	def configured(self):
		return(self.TOKEN.strip() and self.CHAT_ID != 0)

	async def __publish_async(self, msgtype, Comment='', FilePath=None):

		if not FilePath is None:
			FilePath	= Path(FilePath)

		if msgtype == 'text':
			try:
				await self.bot.send_message(
					chat_id=self.CHAT_ID,
					text=Comment
				)

				self.ok				= True
				self.returnmessage	= msgtype
				return
			except asyncio.CancelledError:
				raise
			except Exception as e:
				self.ok				= False
				self.returnmessage	= f'{msgtype}: {type(e).__name__}, {e}'
				return

		elif msgtype == 'video' and FilePath:
			try:
				with open(FilePath, 'rb') as f:
					msg	= await self.bot.send_video(
						chat_id=self.CHAT_ID,
						video=InputFile(f, filename=FilePath.name),
						caption=Comment,
						supports_streaming=True
					)
				self.ok				= True
				self.returnmessage	= f'{msgtype} {FilePath.name}'
				return
			except asyncio.CancelledError:
				raise
			except Exception as e:
				self.ok				= False
				self.returnmessage	= f'{msgtype} {FilePath.name}: {type(e).__name__}, {e}'
				return

		elif msgtype == 'audio':
			try:
				with open(FilePath, 'rb') as f:
					msg	= await self.bot.send_voice(
						chat_id=self.CHAT_ID,
						voice=InputFile(f, filename=FilePath.name),
						caption=Comment
					)

				self.ok				= True
				self.returnmessage	= f'{msgtype} {FilePath.name}'
				return
			except asyncio.CancelledError:
				raise
			except Exception as e:
				self.ok				= False
				self.returnmessage	= f'{msgtype} {FilePath.name}: {type(e).__name__}, {e}'
				return

		elif msgtype == 'photo':
			try:
				with open(FilePath, 'rb') as f:
					msg	= await self.bot.send_photo(
						chat_id=self.CHAT_ID,
						photo=InputFile(f, filename=FilePath.name),
						caption=Comment
					)

				self.ok				= True
				self.returnmessage	= f'{msgtype} {FilePath.name}'
				return
			except asyncio.CancelledError:
				raise
			except Exception as e:
				self.ok				= False
				self.returnmessage	= f'{msgtype} {FilePath.name}: {type(e).__name__}, {e}'
				return

		elif msgtype == 'document':
			try:
				with open(FilePath, 'rb') as f:
					msg	= await self.bot.send_document(
						chat_id=self.CHAT_ID,
						document=InputFile(f, filename=FilePath.name),
						caption=Comment
					)

				self.ok				= True
				self.returnmessage	= f'{msgtype} {FilePath.name}'
				return
			except asyncio.CancelledError:
				raise
			except Exception as e:
				self.ok				= False
				self.returnmessage	= f'{msgtype} {FilePath.name}: {type(e).__name__}, {e}'
				return

	def publish(self, msgtype, Comment='', FilePath=None):
		if self.bot:
			try:
				asyncio.get_running_loop()
			except RuntimeError:
				asyncio.run(self.__publish_async(msgtype, Comment=Comment, FilePath=FilePath))
			else:
				raise RuntimeError("publish() called from async-context – please use 'await publish_async(...)'.")
		else:
			self.ok = False
			self.returnmessage = "not configured"

from mastodon import Mastodon

class mastodon(object):
	def __init__(
		self,
		MA_API_BASE_URL,
		MA_ACCESS_TOKEN
	):

		self.API_BASE_URL   = MA_API_BASE_URL
		self.ACCESS_TOKEN   = MA_ACCESS_TOKEN

		self.ok             = False
		self.returnmessage  = ''

		if self.configured():
			self.mastodon = Mastodon(
				access_token=self.ACCESS_TOKEN,
				api_base_url=self.API_BASE_URL
			)
		else:
			self.mastodon = None

	def configured(self):
		return(self.ACCESS_TOKEN.strip() and self.API_BASE_URL.strip())

	def __publish(self, msgtype, Comment='', FilePath=None):

		if not FilePath is None:
			FilePath	= Path(FilePath)

		if msgtype == 'text':
			try:
				self.mastodon.status_post(Comment)
				self.ok = True
				self.returnmessage = msgtype
			except Exception as e:
				self.ok = False
				self.returnmessage = f"{msgtype}: {type(e).__name__}, {e}"

		elif msgtype in ['photo','video','audio']:
			try:
				media = self.mastodon.media_post(
					FilePath
					# description='Alt text'  # optional alt-text
				)
				self.mastodon.status_post(
					Comment,
					media_ids=[media['id']]
				)
				self.ok = True
				self.returnmessage = f"{msgtype} {FilePath.name}"
			except Exception as e:
				self.ok = False
				self.returnmessage = f"{msgtype} {FilePath.name}: {type(e).__name__}, {e}"

		elif msgtype == 'document':
			self.ok = False
			self.returnmessage = "Mastodon does not support generic documents"

		else:
			self.ok = False
			self.returnmessage = f"unsupported msgtype {msgtype}"

	def publish(self, msgtype, Comment='', FilePath=None):
		if self.mastodon:
			self.__publish(msgtype, Comment=Comment, FilePath=FilePath)
		else:
			self.ok = False
			self.returnmessage = "not configured"


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
