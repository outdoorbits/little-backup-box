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

from bs4 import BeautifulSoup
import html
import markdown
from markdownify import markdownify
import random
import re
import time

import lib_display
import lib_language
import lib_system

# import lib_debug
# xx	= lib_debug.debug()

class services(object):

	def __init__(
			self,
			service,
			check_only=False,
			upload_times=[]
		):

		self.__display		= lib_display.display()
		self.__lan			= lib_language.language()

		self.service						= service

		self.upload_times					= upload_times

		self.rate_limit_count				= None
		self.rate_limit_seconds				= None
		self.rate_limits_variable_seconds	= 0

		self.post_maxlength					= 300
		self.newPostsOnTop					= False



		self.reset_messages()

	def reset_messages(self):
		self.ok			= None
		self.messages	= ''

	def configured(self):
		return(False)

	def publish(self):
		self.reset_messages()
		return(False)

	def keep_posting_rate(self):
		self.upload_times.append(lib_system.get_uptime_sec())

		delaytime	= self.get_delaytime()
		if delaytime > 0:
			ServiceName	= 'unknown service' if not self.service else self.__lan.l(f'box_backup_mode_social_{self.service}')
			while delaytime > 0:
				self.__display.message([
					'set:clear',
					f":{ServiceName}",
					f":{self.__lan.l('box_backup_delay_rate_limit')}",
					f":{delaytime:.0f} s"
				])
				time.sleep(1)
				delaytime	-= 1

	def get_delaytime(self):
		variable_seconds	= random.randint(0, self.rate_limits_variable_seconds)

		if not self.rate_limit_count or not self.rate_limit_seconds:
			return(variable_seconds)

		if len(self.upload_times) >= self.rate_limit_count:
			uptime	= lib_system.get_uptime_sec()
			if uptime - self.upload_times[0] < self.rate_limit_seconds:
				return(self.rate_limit_seconds - uptime + self.upload_times[0] + variable_seconds)

		return(variable_seconds)

	def html_to_plain(self, html: str) -> str:
		if not html:
			return ''

		soup	= BeautifulSoup(html, "html.parser")

		for a in soup.find_all("a"):
			text = a.get_text(strip=True)
			href = a.get("href", "")
			if text and href:
				a.replace_with(f"{text} ({href})")
			elif href:
				a.replace_with(href)
			else:
				a.decompose()

		return(soup.get_text().strip())

	def md_to_plain(self, md: str) -> str:
		return(self.html_to_plain(self.md_to_html(md)))

	def md_to_html(self, md: str) -> str:
		return(markdown.markdown(md))

	def html_to_md(self, html: str) -> str:
		return(markdownify(html))

	def cleanComment(self, Comment):
		return(Comment.lstrip("\ufeff"))

	def split_text(self, text: str, maxlength_primary: int, maxlength_follow: int = None):
		# Split 'text' into chunks of size <= maxlength.
		# Priority: paragraph boundaries (\n\n) -> sentence boundaries -> word boundaries -> hard split.

		if maxlength_follow is None:
			maxlength_follow	= maxlength_primary

		if maxlength_primary <= 0 or maxlength_follow <= 0:
			return [text]
		if not text:
			return []

		# --- 0) Split into paragraphs while keeping separators normalized to exactly "\n\n"
		# keep paragraphs as units and never merge across them unless it still fits.
		raw_parts = re.split(r'(\n{2,})', text)
		# Normalize any multi-blank-lines to exactly "\n\n"
		parts = [p if i % 2 == 0 else "\n\n" for i, p in enumerate(raw_parts)]

		chunks, current = [], ''

		def flush():
			# Push current buffer to chunks and reset it.
			nonlocal current
			if current:
				chunks.append(current.strip())
				current = ""

		def add_piece(piece: str, sep: str = " "):
			# Append 'piece' to 'current' using 'sep' if needed; start new chunk if it doesn't fit.
			nonlocal current
			if piece == "":
				return
			if not current:
				if len(piece) <= maxlength:
					current = piece
				else:
					# Piece too long: split by words first, then hard split if needed
					for sub in split_by_words(piece, maxlength):
						if len(sub) <= maxlength:
							chunks.append(sub)
						else:
							chunks.extend(hard_split(sub, maxlength))
			else:
				if len(current) + len(sep) + len(piece) <= maxlength:
					current = current + sep + piece
				else:
					flush()
					add_piece(piece, sep="")

		def split_by_words(s: str, limit: int):
			# Split 's' on word boundaries into parts <= limit (preserves no internal extra spaces).
			parts, buf = [], ""
			for word in re.findall(r'\S+', s):
				if not buf:
					if len(word) <= limit:
						buf = word
					else:
						parts.append(word)  # will be hard-split later
				else:
					if len(buf) + 1 + len(word) <= limit:
						buf += " " + word
					else:
						parts.append(buf)
						buf = word if len(word) <= limit else word
						if len(word) > limit:
							parts.append(buf)
							buf = ""
			if buf:
				parts.append(buf)
			return parts

		def hard_split(s: str, limit: int):
			# Hard split string every 'limit' characters.
			return [s[i:i+limit] for i in range(0, len(s), limit)]

		# --- 1) For each paragraph: split into sentences and pack
		sentence_end = re.compile(r'(?<=\.|!|\?|…)(?=\s|$)')

		i = 0
		while i < len(parts):
			maxlength	= maxlength_primary if i == 0 else maxlength_follow

			block = parts[i]
			if block == "\n\n":
				# Paragraph separator: try to append it if it still fits; otherwise start a new chunk.
				if current and (len(current) + 2) <= maxlength:
					current += "\n\n"
				else:
					flush()
				i += 1
				continue

			# Split the paragraph into sentences (do NOT strip to avoid killing internal newlines;
			# the paragraph itself has no "\n\n" anymore, but may have single "\n")
			sentences = []
			start = 0
			for m in sentence_end.finditer(block):
				end = m.end()
				sentences.append(block[start:end])  # no strip()
				start = end
			if start < len(block):
				sentences.append(block[start:])

			# Pack sentences into chunks
			for sent in sentences:
				if len(sent) <= maxlength:
					# When appending inside a paragraph, prefer a single space unless current ends with newline
					sep = "" if not current else (" " if not current.endswith("\n") else "")
					add_piece(sent.strip(), sep=sep)  # strip only around the sentence, not across paragraphs
				else:
					# Sentence too long -> split by words/hard split
					for part in split_by_words(sent, maxlength):
						if len(part) <= maxlength:
							add_piece(part, sep=(" " if current and not current.endswith("\n") else ""))
						else:
							for cut in hard_split(part, maxlength):
								add_piece(cut, sep="")
			i += 1

		flush()
		return chunks

	def add_message(self, message):
		self.messages	= f'{self.messages}{"\n" if self.messages else ""}{message}'
