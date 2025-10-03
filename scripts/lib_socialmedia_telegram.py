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

from pathlib import Path
import re

from lib_socialmedia_parent import services

# import lib_debug
# xx	= lib_debug.debug()

class telegram(services):

	def __init__(self, TG_TOKEN, TG_CHAT_ID, check_only=False):
		super().__init__()

		self.TOKEN		= (TG_TOKEN or "").strip()
		self.CHAT_ID	= TG_CHAT_ID

		self.bot_configured	= not check_only and self.configured()

		if self.bot_configured:
			import asyncio
			self.asyncio		= asyncio
			from telegram import Bot
			self.Bot			= Bot
			from telegram.request import HTTPXRequest
			self.HTTPXRequest	= HTTPXRequest
			from telegram import InputFile
			self.InputFile		= InputFile
			from telegram.constants import ParseMode
			self.ParseMode		= ParseMode

	def configured(self):
		return(bool(self.TOKEN and self.CHAT_ID != 0))

	async def __publish_async(self, msgtype, Comment='', FilePath=None):

		if not FilePath is None:
			FilePath	= Path(FilePath)

		request	= self.HTTPXRequest(
				connect_timeout			= 30.0,			# Max seconds to establish the TCP/TLS connection
				read_timeout			= 120.0,		# Max seconds waiting for Telegram's response (headers/body)
				write_timeout			= 120.0,		# Max seconds to send non-media request data
				pool_timeout			= 30.0,			# Max seconds to wait for a free connection from the pool
				media_write_timeout		= 300.0			# Max seconds to upload media chunks (photos/videos/documents)
			)
		async with self.Bot(token=self.TOKEN, request=request) as BOT:
			try:
				if msgtype.main == 'text':
					if msgtype.sub == 'html':
						Comment = re.sub(r'<br\s*/?>', '\n', Comment, flags=re.IGNORECASE)
						Comment = Comment.lstrip("\ufeff")
						Comment = re.sub(r'^\s+', '', Comment)

						TXTParseMode	= self.ParseMode.HTML
					elif msgtype.sub == 'md':
						TXTParseMode	= self.ParseMode.MARKDOWN
					else:
						TXTParseMode	= None

					CommentParts	= self.split_text(Comment, 4096)
					for CommentPart in CommentParts:
						msg	= await BOT.send_message(
							chat_id		= self.CHAT_ID,
							text		= CommentPart,
							parse_mode	= TXTParseMode
						)

				elif msgtype.main == 'video' and FilePath:
					with open(FilePath, 'rb') as f:
						msg	= await BOT.send_video(
							chat_id=self.CHAT_ID,
							video=self.InputFile(f, filename=FilePath.name),
							caption=Comment,
							supports_streaming=True
						)

				elif msgtype.main == 'audio':
					with open(FilePath, 'rb') as f:
						msg	= await BOT.send_voice(
							chat_id=self.CHAT_ID,
							voice=self.InputFile(f, filename=FilePath.name),
							caption=Comment
						)

				elif msgtype.main == 'photo':
					with open(FilePath, 'rb') as f:
						msg	= await BOT.send_photo(
							chat_id=self.CHAT_ID,
							photo=self.InputFile(f, filename=FilePath.name),
							caption=Comment
						)

				elif msgtype.main == 'document':
					with open(FilePath, 'rb') as f:
						msg	= await BOT.send_document(
							chat_id=self.CHAT_ID,
							document=self.InputFile(f, filename=FilePath.name),
							caption=Comment
						)
				else:
					self.ok = False
					self.returnmessage = f'unsupported msgtype.main {msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}'

				if FilePath:
					FileName	= FilePath.name
				else:
					FileName	= ''

				sep	= '' if not FileName else ': '

			except self.asyncio.CancelledError:
				raise
			except Exception as e:
				self.ok				= False
				name	= f" {getattr(FilePath, 'name', '')}" if FilePath else ''
				self.returnmessage	= f'{msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}{name}: {type(e).__name__}, {e}'

		if self.ok is None:
			self.ok				= True
			name				= f" {getattr(FilePath, 'name', '')}" if FilePath else ''
			self.returnmessage	= f'{msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}{name}: o.k.'

	def publish(self, msgtype, Comment='', FilePath=None):
		self.reset_return()

		if self.bot_configured:
			try:
				self.asyncio.get_running_loop()
			except RuntimeError:
				self.asyncio.run(self.__publish_async(msgtype, Comment=Comment, FilePath=FilePath))
			else:
				raise RuntimeError("publish() called from async-context – please use 'await publish_async(...)'.")
		else:
			self.ok = False
			self.returnmessage = "not configured"
