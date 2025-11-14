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
import re

import lib_system

# import lib_debug
# xx	= lib_debug.debug()

class services(object):

	def __init__(self, check_only=False):

		self.rate_limit_count	= None
		self.rate_limit_seconds	= None

		self.reset_messages()

	def reset_messages(self):
		self.ok			= None
		self.messages	= ''

	def configured(self):
		return(False)

	def publish(self):
		self.reset_messages()
		return(False)

	def delaytime(self, upload_times):
		if not self.rate_limit_count or not self.rate_limit_seconds:
			return(0)

		if len(upload_times) >= self.rate_limit_count:
			uptime	= lib_system.get_uptime_sec()
			if uptime - upload_times[0] < self.rate_limit_seconds:
				return(self.rate_limit_seconds - uptime + upload_times[0])

		return(0)

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
