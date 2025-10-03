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

import html
from pathlib import Path
import re

class mastodon(object):
	def __init__(
		self,
		MA_API_BASE_URL,
		MA_ACCESS_TOKEN,
		check_only=False
	):

		self.API_BASE_URL   = (MA_API_BASE_URL or '').strip()
		self.ACCESS_TOKEN   = (MA_ACCESS_TOKEN or '').strip()

		if not check_only and self.configured():
			from mastodon import Mastodon

			self.mastodon = Mastodon(
				access_token	= self.ACCESS_TOKEN,
				api_base_url	= self.API_BASE_URL
			)
		else:
			self.mastodon = None

		self.reset_return()

	def reset_return(self):
		self.ok				= None
		self.returnmessage	= ''

	def configured(self):
		return(bool(self.ACCESS_TOKEN and self.API_BASE_URL))

	def __publish(self, msgtype, Comment='', FilePath=None):

		if not FilePath is None:
			FilePath	= Path(FilePath)

		try:
			if msgtype.main == 'text':
				if msgtype.sub == 'html':
					Comment	= self.html_to_plain(Comment)

				self.mastodon.status_post(
					Comment
				)

			elif msgtype.main in ['photo','video','audio']:
				media = self.mastodon.media_post(
					FilePath
					# description='Alt text'  # optional alt-text
				)
				self.mastodon.status_post(
					Comment,
					media_ids=[media['id']]
				)

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

	def html_to_plain(self, Comment: str) -> str:
		if not Comment:
			return ''
		Comment = Comment.replace('\r\n', '\n').replace('\r', '\n')

		Comment = re.sub(r'\s*<br\s*/?>\s*', '\n', Comment, flags=re.IGNORECASE)
		Comment = re.sub(r'<[^>]+>', '', Comment)
		Comment = html.unescape(Comment)
		Comment = '\n'.join(line.rstrip() for line in Comment.splitlines())
		Comment = re.sub(r'\n\s*\n+', '\n\n', Comment)

		return Comment.strip()
