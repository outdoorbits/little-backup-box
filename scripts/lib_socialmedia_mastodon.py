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

class mastodon(services):
	def __init__(
		self,
		MA_API_BASE_URL,
		MA_ACCESS_TOKEN,
		check_only=False
	):
		super().__init__()

		self.API_BASE_URL   = (MA_API_BASE_URL or '').strip()
		self.ACCESS_TOKEN   = (MA_ACCESS_TOKEN or '').strip()

		if not check_only and self.configured():
			from mastodon import Mastodon

			self.mastodon = Mastodon(
				access_token	= self.ACCESS_TOKEN,
				api_base_url	= self.API_BASE_URL
			)
			try:
				self.post_maxlength	= self.mastodon.instance()['configuration']['statuses']['max_characters']
			except:
				self.post_maxlength	= 500
		else:
			self.mastodon = None
			self.post_maxlength	= 0

	def configured(self):
		return(bool(self.ACCESS_TOKEN and self.API_BASE_URL))

	def __publish(self, msgtype, Comment='', FilePath=None):

		if not FilePath is None:
			FilePath	= Path(FilePath)

		try:
			def send_text(text):
				self.mastodon.status_post(
					status	= text
				)

			if msgtype.main == 'text':
				if msgtype.sub == 'html':
					Comment	= self.html_to_plain(Comment)

				CommentParts	= self.split_text(Comment, self.post_maxlength)
				for CommentPart in reversed(CommentParts):
					send_text(CommentPart)

			elif msgtype.main in ['photo','video','audio']:

				CommentParts	= self.split_text(Comment, self.post_maxlength)
				for index, CommentPart in enumerate(reversed(CommentParts)):

					if index == (len(CommentParts) - 1): # media last

						media = self.mastodon.media_post(
							FilePath
							# description='Alt text'  # optional alt-text
						)

						self.mastodon.status_post(
							status		= CommentPart,
							media_ids	= [media['id']]
						)
					else:
						send_text(CommentPart)

			else:
				self.ok = False
				self.returnmessage = f'unsupported msgtype.main {msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}'

		except Exception as e:
			self.ok				= False
			name	= f' {getattr(FilePath, "name", "")}' if FilePath else ''
			self.returnmessage	= f'{msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}{name}: {type(e).__name__}, {e}'

		if self.ok is None:
			self.ok				= True
			name				= f' {getattr(FilePath, "name", "")}' if FilePath else ''
			self.returnmessage	= f'{msgtype.main}{"" if msgtype.sub is None else f" ({msgtype.sub})"}{name}: o.k.'

	def publish(self, msgtype, Comment='', FilePath=None):
		self.reset_return()

		if self.mastodon:
			self.__publish(msgtype, Comment=Comment, FilePath=FilePath)
		else:
			self.ok = False
			self.returnmessage = 'not configured'
