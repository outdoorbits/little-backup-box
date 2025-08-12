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

import argparse
import shlex
import shutil
import subprocess
from pathlib import Path
from typing import Optional

import lib_setup

class ExiftoolError(RuntimeError):
	pass

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

		# -------------------------
		# Central metadata source lists
		# -------------------------

		self.CREATE_SOURCES = [
			"XMP:CreateDate",
			"XMP:DateCreated",
			"XMP-photoshop:DateCreated",
			"XMP-exif:DateTimeOriginal",
			"Composite:SubSecCreateDate",
			"Composite:SubSecDateTimeOriginal",
			"EXIF:CreateDate",
			"EXIF:DateTimeOriginal",
			"EXIF:DateTimeDigitized",
			"Composite:DateTimeCreated",
			"QuickTime:CreateDate",
			"QuickTime:MediaCreateDate",
			"QuickTime:TrackCreateDate",
			"QuickTime:ContentCreateDate",
			"RIFF:DateTimeOriginal",
			"RIFF:DateTimeDigitized",
			"PNG:CreationTime",
			"File:FileCreateDate",
			"File:FileModifyDate",
		]

		self.MODIFY_SOURCES = [
			"XMP:ModifyDate",
			"Composite:SubSecModifyDate",
			"EXIF:ModifyDate",
			"QuickTime:ModifyDate",
			"QuickTime:TrackModifyDate",
			"File:FileModifyDate",
		]

		self.METADATA_SOURCES = [
			"XMP:MetadataDate",
			"XMP:ModifyDate",
			"Composite:SubSecModifyDate",
			"EXIF:ModifyDate",
			"QuickTime:ModifyDate",
			"File:FileModifyDate",
		]

		self.PS_DATECREATED_SOURCES = [
			"XMP-photoshop:DateCreated",
			"XMP:DateCreated",
			"XMP:CreateDate",
			"XMP-exif:DateTimeOriginal",
			"Composite:SubSecDateTimeOriginal",
			"Composite:SubSecCreateDate",
			"EXIF:DateTimeOriginal",
			"EXIF:CreateDate",
			"Composite:DateTimeCreated",
			"QuickTime:CreateDate",
			"QuickTime:MediaCreateDate",
			"QuickTime:ContentCreateDate",
			"RIFF:DateTimeOriginal",
			"PNG:CreationTime",
			"File:FileCreateDate",
			"File:FileModifyDate",
		]



	def process_one(self, path: Path, rating: Optional[int] = None, description: Optional[str] = None) -> None:

		if not path.exists() or not path.is_file():
			return()

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

		if Extension in self.const_FILE_EXTENSIONS_LIST_RAW.split(';'):
			self._sidecar_for_raw(path, rating=rating, description=description)
		elif Extension in EMBED_EXTS:
			self._embed_into_image(path, rating=rating, description=description)

	# ---------- RAW → XMP sidecar ----------

	def _sidecar_for_raw(self, raw_path: Path, rating: Optional[int], description: Optional[str]) -> None:
		# Create/update <basename>.xmp next to RAW.

		xmp_path = raw_path.with_suffix(".xmp")

		def fallback_grouped(src_file: str, dest: str, sources: list[str]) -> list[str]:
			"""
			One grouped fallback:
			[-tagsFromFile src_file, -Dest<Src1>, -Dest<Src2>, ...]
			With -wm cg, the first existing source sets Dest; later ones are ignored.
			"""
			args: list[str] = ["-tagsFromFile", src_file]
			for s in sources:
				args.append(f"-{dest}<{s}")
			return args

		# Common options
		common = ["-P", "-use", "MWG", "-api", "QuickTimeUTC=1", "-wm", "cg"]

		if xmp_path.exists():
			# Update existing sidecar in place
			args = [
				"-overwrite_original",
				*common,
				*fallback_grouped(str(raw_path), "XMP:CreateDate", self.CREATE_SOURCES),
				*fallback_grouped(str(raw_path), "XMP:ModifyDate",  self.MODIFY_SOURCES),
				*fallback_grouped(str(raw_path), "XMP:MetadataDate", self.METADATA_SOURCES),
				*fallback_grouped(str(raw_path), "XMP-photoshop:DateCreated", self.PS_DATECREATED_SOURCES),
				str(xmp_path),
			]
			self._run_exiftool(args, context=f"update sidecar {xmp_path.name}")
		else:
			# Create a new sidecar from RAW (-o …)
			args = [
				"-o", str(xmp_path),
				*common,
				*fallback_grouped(str(raw_path), "XMP:CreateDate", self.CREATE_SOURCES),
				*fallback_grouped(str(raw_path), "XMP:ModifyDate",  self.MODIFY_SOURCES),
				*fallback_grouped(str(raw_path), "XMP:MetadataDate", self.METADATA_SOURCES),
				*fallback_grouped(str(raw_path), "XMP-photoshop:DateCreated", self.PS_DATECREATED_SOURCES),
				str(raw_path),
			]
			self._run_exiftool(args, context=f"create sidecar {xmp_path.name}")


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

	# ---------- Non-RAW → embed ----------

	def _embed_into_image(self, image_path: Path, rating: Optional[int], description: Optional[str]) -> None:
		# Embed metadata into a non-RAW image.

		def fb_grouped(dest: str, sources: list[str]) -> list[str]:
			# One -tagsFromFile @ followed by multiple -Dest<Source> attempts
			args = ["-tagsFromFile", "@"]
			for s in sources:
				args.append(f"-{dest}<{s}")
			return args

		cmd = ["-overwrite_original","-P","-use","MWG","-api","QuickTimeUTC=1","-wm","cg",
			*fb_grouped("XMP:CreateDate", self.CREATE_SOURCES),
			*fb_grouped("XMP:ModifyDate", self.MODIFY_SOURCES),
			*fb_grouped("XMP:MetadataDate", self.METADATA_SOURCES),
			*fb_grouped("XMP-photoshop:DateCreated", self.PS_DATECREATED_SOURCES)]

		if rating is not None:
			rating = self._normalize_rating(rating)
			cmd.append(f"-XMP-xmp:Rating={rating}")
		if description is not None:
			cmd.extend([
				f"-XMP-dc:Description={description}",
				f"-EXIF:ImageDescription={description}",
				"-charset", "iptc=utf8",
				f"-IPTC:Caption-Abstract={description}",
			])

		cmd.append(str(image_path))

		self._run_exiftool(cmd, context=f"embed metadata into {image_path.name}")


	# ---------- Helpers ----------

	def _run_exiftool(self, args: list[str], context: str = "") -> None:
		if self.dry:
			# Print a copy-pasteable command line (properly quoted for Bash/Zsh)
			print(f"[DRY] {shlex.join(['exiftool'] + args)}")
			return
		try:
			res = subprocess.run(["exiftool"] + args, text=True, capture_output=True)
		except FileNotFoundError:
			return
		if res.returncode != 0:
			return

	def _ensure_exiftool(self) -> None:
		if shutil.which("exiftool") is None:
			raise ExiftoolError("exiftool not found. Please install exiftool and ensure it is on PATH.")

	@staticmethod
	def _normalize_rating(rating: int) -> int:
		return rating if 1 <= rating <= 5 else 2

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

