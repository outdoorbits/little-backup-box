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

from lib_socialmedia_parent import services

# import lib_debug
# xx	= lib_debug.debug()

class bluesky(services):
	def __init__(
		self,
		BS_API_BASE_URL,   # e.g. "https://bsky.social" or custom PDS base URL
		BS_IDENTIFIER,     # handle or DID, e.g. "alice.bsky.social"
		BS_APP_PASSWORD,    # app password from settings (NOT your normal password)
		check_only=False
	):
		super().__init__()

		self.API_BASE_URL	= (BS_API_BASE_URL or "").strip()
		self.IDENTIFIER		= (BS_IDENTIFIER or "").strip()
		self.APP_PASSWORD	= (BS_APP_PASSWORD or "").strip()

		if not check_only and self.configured():
			from atproto import Client, models
			self.models	= models

			try:
				# Create a client bound to the given service URL (PDS).
				self.bluesky = Client(base_url=self.API_BASE_URL)

				# Login using identifier (handle / DID) and an app password.
				self.bluesky.login(self.IDENTIFIER, self.APP_PASSWORD)
			except Exception as e:
				self.bluesky = None
				self.ok = False
				self.returnmessage = f'login: {type(e).__name__}, {e}'
		else:
			self.bluesky = None

	def configured(self):
		return (bool(self.API_BASE_URL and self.IDENTIFIER and self.APP_PASSWORD))

	def __publish(self, msgtype, Comment='', FilePath=None):

		if FilePath is not None:
			FilePath = Path(FilePath)

		try:
			if msgtype.main == 'text':
				if msgtype.sub == 'html':
					Comment	= self.html_to_plain(Comment)

				CommentParts	= self.split_text(Comment, 300)
				for CommentPart in CommentParts:
					self.bluesky.post(
						text = (CommentPart or '')
					)

			elif msgtype.main == 'photo':
				if FilePath is None:
					raise ValueError('FilePath is required for photo')

				# Upload the image as a blob.
				with FilePath.open('rb') as f:
					blob = self.bluesky.upload_blob(f)

				# Optional alt-text support (uncomment to use):
				# alt_text = 'Alt text'
				# image_obj = self.models.AppBskyEmbedImages.Image(alt=alt_text, image=blob.blob)

				image_obj = self.models.AppBskyEmbedImages.Image(alt='', image=blob.blob)

				embed = self.models.AppBskyEmbedImages.Main(images=[image_obj])

				# Create the post with the image embed.
				self.bluesky.post(text=Comment or '', embed=embed)

			elif msgtype.main in ['audio', 'video']:
				# As of now, the public Bluesky API client does not provide stable
				# posting for audio/video in regular feed posts. Mark as unsupported.
				self.ok = False
				self.returnmessage = f'unsupported msgtype {msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}'

			else:
				self.ok = False
				self.returnmessage = f'unsupported msgtype {msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}'

		except Exception as e:
			self.ok = False
			name = f" {getattr(FilePath, 'name', '')}" if FilePath else ''
			self.returnmessage = f'{msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}{name}: {type(e).__name__}, {e}'

		if self.ok is None:
			self.ok = True
			name = f" {getattr(FilePath, 'name', '')}" if FilePath else ''
			self.returnmessage = f'{msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}{name}: o.k.'

	def publish(self, msgtype, Comment='', FilePath=None):
		self.reset_return()

		if self.bluesky:
			self.__publish(msgtype, Comment=Comment, FilePath=FilePath)
		else:
			self.ok = False
			self.returnmessage = "not configured"
