#!/usr/bin/env python3
# -*- coding: utf-8 -*-

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

from __future__ import annotations
import argparse
import shutil
import subprocess
from pathlib import Path
from typing import Optional

import lib_setup

class ExiftoolError(RuntimeError):
	print(RuntimeError)

class MetadataTool:
	# Handles writing XMP sidecars for RAWs and embedded metadata for non-RAWs.

	def __init__(self, dry_run: bool = False):
		self.dry = dry_run
		self._ensure_exiftool()

		self.__setup	= lib_setup.setup()

		self.const_FILE_EXTENSIONS_LIST_WEB_IMAGES		= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_WEB_IMAGES')
		self.const_FILE_EXTENSIONS_LIST_HEIC			= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_HEIC')
		self.const_FILE_EXTENSIONS_LIST_RAW				= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_RAW')
		self.const_FILE_EXTENSIONS_LIST_TIF				= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_TIF')
		self.const_FILE_EXTENSIONS_LIST_VIDEO			= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_VIDEO')
		self.const_FILE_EXTENSIONS_LIST_AUDIO			= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_AUDIO')

	def process_one(self, path: Path, rating: Optional[int], description: Optional[str]) -> None:

		if not path.exists() or not path.is_file():
			raise FileNotFoundError(f"Not a file: {path}")
		Extension = path.suffix.lower().removeprefix('.')

		EMBED_EXTS	= ';'.join(
				[
					self.const_FILE_EXTENSIONS_LIST_WEB_IMAGES,
					self.const_FILE_EXTENSIONS_LIST_HEIC,
					self.const_FILE_EXTENSIONS_LIST_TIF,
					self.const_FILE_EXTENSIONS_LIST_VIDEO,
					self.const_FILE_EXTENSIONS_LIST_AUDIO
				]
			).split(';')
		print(f"if {Extension} in {self.const_FILE_EXTENSIONS_LIST_RAW.split(';')}:")
		if Extension in self.const_FILE_EXTENSIONS_LIST_RAW.split(';'):
			print('raw')
			self._sidecar_for_raw(path, rating=rating, description=description)
		elif Extension in EMBED_EXTS:
			print('embedded')
			self._embed_into_image(path, rating=rating, description=description)

	# ---------- RAW → XMP sidecar ----------

	def _sidecar_for_raw(self, raw_path: Path, rating: Optional[int], description: Optional[str]) -> None:
		# Create/update <basename>.xmp next to RAW.

		xmp_path = raw_path.with_suffix(".xmp")

		# Step 1: Copy time-related tags from RAW into the sidecar (create or overwrite the XMP file).
		# We copy widely-supported XMP time fields; if the RAW lacks any of them, exiftool just skips them.
		if xmp_path.exists():
			# Update existing sidecar IN PLACE (kein -o)
			self._run_exiftool([
				"-overwrite_original",
				"-tagsFromFile", str(raw_path),
				"-XMP-xmp:CreateDate",
				"-XMP-xmp:ModifyDate",
				"-XMP-xmp:MetadataDate",
				"-XMP-photoshop:DateCreated",
				str(xmp_path),
			], context=f"update sidecar {xmp_path.name}")
		else:
			# Create new sidecar FROM RAW (mit -o)
			self._run_exiftool([
				"-o", str(xmp_path),
				"-tagsFromFile", str(raw_path),
				"-XMP-xmp:CreateDate",
				"-XMP-xmp:ModifyDate",
				"-XMP-xmp:MetadataDate",
				"-XMP-photoshop:DateCreated",
				str(raw_path),  # Quelle angeben
			], context=f"create sidecar {xmp_path.name}")

		# Step 2: Apply optional fields.
		set_cmd = ["-overwrite_original"]  # operate on the sidecar we just created
		if rating is not None:
			self._normalize_rating(rating)
			set_cmd.append(f"-XMP-xmp:Rating={rating}")
		if description is not None:
			# XMP supports UTF-8 and multi-line content natively.
			set_cmd.append(f"-XMP-dc:Description={description}")

		if len(set_cmd) > 1:
			set_cmd.append(str(xmp_path))
			self._run_exiftool(set_cmd, context=f"set fields in {xmp_path.name}")
		else:
			self._log(f"[OK] Sidecar up-to-date: {xmp_path.name}")

	# ---------- Non-RAW → embed ----------

	def _embed_into_image(self, image_path: Path, rating: Optional[int], description: Optional[str]) -> None:
		# Embed metadata into a non-RAW image.

		cmd = ["-overwrite_original"]

		# Map times into XMP if present; safe if source tags are missing.
		cmd += [
			"-XMP-xmp:CreateDate<DateTimeOriginal",
			"-XMP-xmp:ModifyDate<FileModifyDate",
			"-XMP-xmp:MetadataDate<FileModifyDate",
		]
		if rating is not None:
			rating	= self._normalize_rating(rating)
			cmd.append(f"-XMP-xmp:Rating={rating}")
		if description is not None:
			cmd.extend([
				f"-XMP-dc:Description={description}",
				# Compatibility for older consumers:
				f"-EXIF:ImageDescription={description}",
				"-charset", "iptc=utf8",
				f"-IPTC:Caption-Abstract={description}",
			])

		cmd.append(str(image_path))
		self._run_exiftool(cmd, context=f"embed metadata into {image_path.name}")

	# ---------- Helpers ----------

	def _run_exiftool(self, args: list[str], context: str = "") -> None:
		if self.dry:
			print(f"[DRY] exiftool {' '.join(args)}")
			return
		try:
			res = subprocess.run(["exiftool"] + args, text=True, capture_output=True)
		except FileNotFoundError:
			raise ExiftoolError("exiftool not found on PATH")
		if res.returncode != 0:
			raise ExiftoolError(f"{context}: {res.stderr.strip() or res.stdout.strip()}")

	def _ensure_exiftool(self) -> None:
		if shutil.which("exiftool") is None:
			raise ExiftoolError("exiftool not found. Please install exiftool and ensure it is on PATH.")

	@staticmethod
	def _normalize_rating(rating: int) -> None:
		if (1 <= rating <= 5):
			return(rating)
		else:
			return(2)


# ---------- CLI ----------

def parse_args() -> argparse.Namespace:
	p = argparse.ArgumentParser(
		description="Write XMP sidecar for RAW or embed metadata into a non-RAW image (single file).",
		epilog=(
			"Examples:\n"
			"  tool.py foto.NEF --rating 5 --comment 'Line 1\\nLine 2'\n"
		),
		formatter_class=argparse.RawTextHelpFormatter,
	)
	p.add_argument("input", help="Path to a single image file")
	p.add_argument("--rating", type=int, default=None, help="Optional rating (1..5)")
	p.add_argument("--comment", type=str, default=None, help="Optional text comment (use \\n for newlines)")
	p.add_argument("--dry-run", action="store_true", help="Print exiftool calls without writing")
	return p.parse_args()

def main() -> None:
	args = parse_args()

	description: Optional[str] = None
	if args.comment is not None:
		# Turn literal "\n" into actual newline characters for multi-line comments
		description = args.comment.encode("utf-8").decode("unicode_escape")

	tool = MetadataTool(dry_run=args.dry_run)
	tool.process_one(Path(args.input).expanduser().resolve(), rating=args.rating, description=description)

if __name__ == "__main__":
	main()

