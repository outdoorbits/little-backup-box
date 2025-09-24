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

import asyncio
from pathlib import Path
from telegram import Bot
from telegram.request import HTTPXRequest
from telegram import InputFile

class telegram(object):

	def __init__(self, TG_TOKEN, TG_CHAT_ID):

		self.TOKEN		= (TG_TOKEN or "").strip()
		self.CHAT_ID	= TG_CHAT_ID

		self.ok				= None
		self.returnmessage	= ''

		self.bot_configured	= self.configured()

	def configured(self):
		return(self.TOKEN and self.CHAT_ID != 0)

	async def __publish_async(self, msgtype, Comment='', FilePath=None):

		if not FilePath is None:
			FilePath	= Path(FilePath)

		request	= HTTPXRequest(
				connect_timeout			= 30.0,			# Max seconds to establish the TCP/TLS connection
				read_timeout			= 120.0,		# Max seconds waiting for Telegram's response (headers/body)
				write_timeout			= 120.0,		# Max seconds to send non-media request data
				pool_timeout			= 30.0,			# Max seconds to wait for a free connection from the pool
				media_write_timeout		= 300.0			# Max seconds to upload media chunks (photos/videos/documents)
			)
		async with Bot(token=self.TOKEN, request=request) as BOT:
			try:
				if msgtype == 'text':
					await BOT.send_message(
						chat_id=self.CHAT_ID,
						text=Comment
					)

				elif msgtype == 'video' and FilePath:
					with open(FilePath, 'rb') as f:
						msg	= await BOT.send_video(
							chat_id=self.CHAT_ID,
							video=InputFile(f, filename=FilePath.name),
							caption=Comment,
							supports_streaming=True
						)

				elif msgtype == 'audio':
					with open(FilePath, 'rb') as f:
						msg	= await BOT.send_voice(
							chat_id=self.CHAT_ID,
							voice=InputFile(f, filename=FilePath.name),
							caption=Comment
						)

				elif msgtype == 'photo':
					with open(FilePath, 'rb') as f:
						msg	= await BOT.send_photo(
							chat_id=self.CHAT_ID,
							photo=InputFile(f, filename=FilePath.name),
							caption=Comment
						)

				elif msgtype == 'document':
					with open(FilePath, 'rb') as f:
						msg	= await BOT.send_document(
							chat_id=self.CHAT_ID,
							document=InputFile(f, filename=FilePath.name),
							caption=Comment
						)
				else:
					self.ok = False
					self.returnmessage = f"unsupported msgtype {msgtype}"

				if FilePath:
					FileName	= FilePath.name
				else:
					FileName	= ''

				sep	= '' if not FileName else ': '

			except asyncio.CancelledError:
				raise
			except Exception as e:
				self.ok				= False
				name	= f" {getattr(FilePath, 'name', '')}" if FilePath else ''
				self.returnmessage	= f'{msgtype}{name}: {type(e).__name__}, {e}'

		if self.ok is None:
			self.ok				= True
			name				= f" {getattr(FilePath, 'name', '')}" if FilePath else ''
			self.returnmessage	= f'{msgtype}{name}: o.k.'

	def publish(self, msgtype, Comment='', FilePath=None):
		if self.bot_configured:
			try:
				asyncio.get_running_loop()
			except RuntimeError:
				asyncio.run(self.__publish_async(msgtype, Comment=Comment, FilePath=FilePath))
			else:
				raise RuntimeError("publish() called from async-context – please use 'await publish_async(...)'.")
		else:
			self.ok = False
			self.returnmessage = "not configured"
