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
import markdown
import mimetypes

from lib_socialmedia_parent import services
import lib_system

# import lib_debug
# xx	= lib_debug.debug()

class matrix(services):
	def __init__(
			self,
			service,
			HOMESERVER,
			ACCESS_TOKEN,
			ROOM_ID,
			check_only=False,
			upload_times=[]
		):
		# HOMESERVER:   e.g. 'https://matrix.example.org'
		# ACCESS_TOKEN: permanent access token for the bot user
		# ROOM_ID:      '!something:example.org' (or alias, if your server supports that)

		super().__init__(service=service, check_only=check_only, upload_times=upload_times)

		self.homeserver								= (HOMESERVER or "").strip()
		self.access_token							= (ACCESS_TOKEN or "").strip()
		self.room_id								= ROOM_ID

		self.caption_maxlength						= 1000     # for the first message caption when sending media
		self.post_maxlength							= 4000     # general limit for text (Matrix has its own internal limits)

		self.rate_limit_leading_unlimitted_count	= 10
		self.rate_limit_count						= 1
		self.rate_limit_seconds						= 5

		# The bot is considered "configured" if homeserver, room_id, and access_token exist
		self.bot_configured = (
			not check_only and self.configured()
		)

		if self.bot_configured:
			import asyncio
			self.asyncio = asyncio
			from nio import AsyncClient, RoomSendResponse, UploadResponse
			self.AsyncClient = AsyncClient
			self.RoomSendResponse = RoomSendResponse
			self.UploadResponse = UploadResponse

	def configured(self):
		# Only proceed if both the homeserver and access token are present
		return bool(self.homeserver and self.room_id and self.access_token)

	async def __publish_async(self, msgtype, Comment='', FilePath=None):
		# msgtype.main: 'text', 'photo', 'video', 'audio', 'document'
		# msgtype.sub:  'html', 'md', or None – same semantics as your Telegram version

		if FilePath is not None:
			FilePath = Path(FilePath)

		# Initialize Matrix client
		client				= self.AsyncClient(self.homeserver)
		client.access_token	= self.access_token

		try:
			async def send_text(text, formatted=None):
				# formatted: dict containing 'format' and 'formatted_body' for HTML,
				# otherwise None for plain text.

				content	= {
					"msgtype": "m.text",
					"body": text,
				}
				if formatted is not None:
					content.update(formatted)

				self.keep_posting_rate()
				resp	= await client.room_send(
					room_id=self.room_id,
					message_type="m.room.message",
					content=content
				)
				if isinstance(resp, self.RoomSendResponse):
					return True
				else:
					self.ok				= False
					self.add_message(f"room_send error: {resp}")
					return(False)

			# Handle message types
			if msgtype.main == 'text':
				if msgtype.sub == 'md':
					Comment	= markdown.markdown(Comment)
					msgtype.sub	= 'html'

				if msgtype.sub == 'html':
					# Replace <br> with \n, strip BOM and leading whitespace
					Comment_local	= Comment.lstrip("\ufeff")
					Comment_local	= re.sub(r'^\s+', '', Comment_local)

					def make_formatted_part(part):
						return({
							"format": "org.matrix.custom.html",
							"formatted_body": part
						})

					CommentParts	= self.split_text(Comment_local, self.post_maxlength)
					for CommentPart in CommentParts:
						formatted	= make_formatted_part(CommentPart)
						ok = await send_text(CommentPart, formatted=formatted)
						if not ok:
							break

				else:
					# Plain text
					# No native Markdown support in Matrix - send as plain text as well
					CommentParts = self.split_text(Comment, self.post_maxlength)
					for CommentPart in CommentParts:
						ok	= await send_text(CommentPart)
						if not ok:
							break

			elif msgtype.main in ['video', 'audio', 'photo', 'document']:
				CommentParts	= self.split_text(Comment, self.caption_maxlength, self.post_maxlength)

				# Upload function
				async def upload_file(path: Path):
					mime_type, _	= mimetypes.guess_type(path.name)
					mime_type		= mime_type or "application/octet-stream"

					file_stat = path.stat()

					with open(path, "r+b") as f:
						upload_resp, decryption_dict = await client.upload(
							data_provider	= f,
							content_type	= mime_type,
							filename		= path.name,
							filesize		= file_stat.st_size,
						)

					if isinstance(upload_resp, self.UploadResponse):
						return upload_resp.content_uri, mime_type
					else:
						self.ok	= False
						self.add_message( f"upload error: {upload_resp}")
						return None, None

				media_uri	= None
				media_mime	= None

				if FilePath:
					media_uri, media_mime	= await upload_file(FilePath)
					if not media_uri:
						await client.close()
						return

				# Map msgtype.main to Matrix equivalents
				if msgtype.main == 'photo':
					matrix_msgtype	= "m.image"
				elif msgtype.main == 'video':
					matrix_msgtype	= "m.video"
				elif msgtype.main == 'audio':
					matrix_msgtype	= "m.audio"
				else:
					matrix_msgtype	= "m.file"

				for index, CommentPart in enumerate(CommentParts):
					if index == 0 and media_uri:
						# First part: send media with caption
						content = {
							"msgtype":	matrix_msgtype,
							"body":		FilePath.name if FilePath else "file",
							"url":		media_uri,
						}

						if CommentPart:
							content["caption"]				= CommentPart

						if media_mime:
							content.setdefault("info", {})
							content["info"]["mimetype"]	= media_mime

						self.keep_posting_rate()
						resp	= await client.room_send(
							room_id			= self.room_id,
							message_type	= "m.room.message",
							content			= content
						)
						if not isinstance(resp, self.RoomSendResponse):
							self.ok	= False
							self.add_message( f"room_send media error: {resp}")
							break
					else:
						if CommentPart:
							ok	= await send_text(CommentPart)
							if not ok:
								break
			else:
				self.ok	= False
				self.add_message(f'unsupported msgtype {msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}')

			FileName = FilePath.name if FilePath else ''
			sep = '' if not FileName else ': '

		except self.asyncio.CancelledError:
			raise
		except Exception as e:
			self.ok	= False
			name = f" {getattr(FilePath, 'name', '')}" if FilePath else ''
			self.add_message(f'{msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"} {name}: {type(e).__name__}, {e}')
		finally:
			await client.close()

		if self.ok is None:
			self.ok	= True
			name = f" {getattr(FilePath, 'name', '')}" if FilePath else ''
			self.add_message(f'{msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"} {name}: o.k.')

	def publish(self, msgtype, Comment='', FilePath=None):
		super().publish()

		if self.bot_configured:
			try:
				self.asyncio.get_running_loop()
			except RuntimeError:
				self.asyncio.run(
					self.__publish_async(
						msgtype,
						Comment=Comment,
						FilePath=FilePath
					)
				)
			else:
				raise RuntimeError(
					"publish() called from async-context – "
					"please use 'await publish_async(...)'."
				)
		else:
			self.ok				= False
			self.add_message("not configured")

	def get_delaytime(self):
		if not self.rate_limit_count or not self.rate_limit_seconds:
			return(0)

		# allow first n uploads without time limits
		if self.rate_limit_leading_unlimitted_count > 0:
			self.rate_limit_leading_unlimitted_count	-= 1
			return(0)

		# respect time limit
		if len(self.upload_times) >= self.rate_limit_count:
			uptime	= lib_system.get_uptime_sec()
			if uptime - self.upload_times[0] < self.rate_limit_seconds:
				return(self.rate_limit_seconds - uptime + self.upload_times[0])

		return(0)

