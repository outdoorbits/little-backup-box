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
import re
import shlex
import shutil
import subprocess
from pathlib import Path
from typing import Optional

import lib_log
import lib_setup
import lib_view

# import lib_debug
# xx	= lib_debug.debug()

class ExiftoolError(RuntimeError):
	pass

class MetadataTool:
	# Handles writing XMP sidecars for RAWs and embedded metadata for non-RAWs.

	def __init__(self, dry_run: bool = False):
		self.dry = dry_run
		self._ensure_exiftool()

		self.__setup	= lib_setup.setup()
		self.__log		= lib_log.log()

		self.const_FILE_EXTENSIONS_LIST_WEB_IMAGES		= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_WEB_IMAGES').split(';')
		self.const_FILE_EXTENSIONS_LIST_HEIC			= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_HEIC').split(';')
		self.const_FILE_EXTENSIONS_LIST_TIF				= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_TIF').split(';')
		self.const_FILE_EXTENSIONS_LIST_VIDEO			= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_VIDEO').split(';')
		self.const_FILE_EXTENSIONS_LIST_AUDIO			= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_AUDIO').split(';')
		self.const_FILE_EXTENSIONS_LIST_RAW				= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_RAW').split(';')
		self.const_METADATA_CREATE_SOURCES				= self.__setup.get_val('const_METADATA_CREATE_SOURCES').split(';')
		self.const_METADATA_MODIFY_SOURCES				= self.__setup.get_val('const_METADATA_MODIFY_SOURCES').split(';')

	def write_metadata(self, path: Path, rating: Optional[int] = None, description: Optional[str] = None) -> None:

		if not path.exists() or not path.is_file():
			return()

		MountPoint					= str(Path(*path.parts[:3]))
		ImageFilePath				= str(Path(*path.parts[3:]).parent).strip('/')
		ImageFileName				= path.name
		Extension					= path.suffix.lower().removeprefix('.')

		db	= lib_view.viewdb(setup=self.__setup, log=self.__log, MountPoint=MountPoint)
		FileDB_Data	= db.dbSelect(f"select Rating, Comment from EXIF_DATA where Directory='{ImageFilePath}' and File_Name='{ImageFileName}';")
		if len(FileDB_Data) > 0:
			dbRating	= int(FileDB_Data[0][0])
			dbComment	= FileDB_Data[0][1]
		else:
			return(False)

		if (rating is None or dbRating == rating) and (description is None or dbComment == description):
			return(True)

		EMBED_EXTS	= 	self.const_FILE_EXTENSIONS_LIST_WEB_IMAGES + \
						self.const_FILE_EXTENSIONS_LIST_HEIC + \
						self.const_FILE_EXTENSIONS_LIST_TIF + \
						self.const_FILE_EXTENSIONS_LIST_VIDEO + \
						self.const_FILE_EXTENSIONS_LIST_AUDIO

		if Extension in self.const_FILE_EXTENSIONS_LIST_RAW:
			self._sidecar_for_raw(path, rating=rating, description=description)
		elif Extension in EMBED_EXTS:
			self._embed_into_image(path, rating=rating, description=description)

	# ---------- RAW → XMP sidecar ----------

	def _sidecar_for_raw(self, raw_path: Path, rating: Optional[int], description: Optional[str]) -> None:
		# Create/update <basename>.xmp next to RAW.

		xmp_path = raw_path.with_suffix('.xmp')

		def fallback_grouped(src_file: str, dest: str, sources: list[str]) -> list[str]:
			# One grouped fallback:
			# [-tagsFromFile src_file, -Dest<Src1>, -Dest<Src2>, ...]

			args: list[str] = ["-tagsFromFile", src_file]
			for source in sources:
				args.append(f"-{dest}<{source}")
			return args

		# step 1:
		common = [
			"-P",
			"-use", "MWG",
			"-api",
			"QuickTimeUTC=1",
			"-wm",
			"w"
		]

		if xmp_path.exists():
			# Update existing sidecar in place
			args = [
				"-overwrite_original",
				*common,
				*fallback_grouped(str(raw_path), "XMP:CreateDate", self.const_METADATA_CREATE_SOURCES),
				*fallback_grouped(str(raw_path), "XMP:ModifyDate",  self.const_METADATA_MODIFY_SOURCES),
				str(xmp_path),
			]
			self._run_exiftool(args, context=f"update sidecar {xmp_path.name}")
		else:
			# Create a new sidecar from RAW (-o …)
			args = [
				"-o", str(xmp_path),
				*common,
				*fallback_grouped(str(raw_path), "XMP:CreateDate", self.const_METADATA_CREATE_SOURCES),
				*fallback_grouped(str(raw_path), "XMP:ModifyDate",  self.const_METADATA_MODIFY_SOURCES),
				str(raw_path),
			]
			self._run_exiftool(args, context=f"create sidecar {xmp_path.name}")


		# step 2: Add fields
		update_command = ["-overwrite_original"]  # operate on the sidecar we just created

		if rating is not None:
			self._normalize_rating(rating)
			update_command.append(f"-XMP-xmp:Rating={rating}")

		if description is not None:
			# XMP supports UTF-8 and multi-line content natively.
			update_command.append(f"-XMP-dc:Description={description}")

		if len(update_command) > 1:
			update_command.append(str(xmp_path))
			self._run_exiftool(update_command, context=f"set fields in {xmp_path.name}")

	# ---------- Non-RAW -> embed ----------
	def _embed_into_image(self, image_path: Path, rating: Optional[int], description: Optional[str]) -> None:
		# Embed metadata into a non-RAW image.

		cmd_basic	= [
			"-overwrite_original",
			"-P",
			"-use","MWG",
			"-api",
			"QuickTimeUTC=1",
			"-wm",
			"w"
		]

		cmd_ext		= []
		if rating is not None:
			rating = self._normalize_rating(rating)
			cmd_ext.append(f"-XMP-xmp:Rating={rating}")

		if description is not None:
			cmd_ext.extend([
				f"-XMP-dc:Description={description}",
				f"-EXIF:ImageDescription={description}",
				"-charset", "iptc=utf8",
				f"-IPTC:Caption-Abstract={description}",
			])

		if len(cmd_ext) > 0:
			cmd	= cmd_basic + cmd_ext
			cmd.append(str(image_path))

			self._run_exiftool(cmd, context=f"embed metadata into {image_path.name}")

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

# functions
def formatValue(value):
	value	= value.replace('\r', '')
	value	= value.replace('\n', '<br>')
	value	= value.replace('"', '&#34;')
	value	= value.replace("'", '&#39;')
	pattern		= re.compile(r'[^a-zA-Z0-9_\-+\.,:; &#/()\[\]<>]')
	value	= '' if value is None else str(value)
	value	= pattern.sub('_', value)

	return(value)

def normalize_exif_array(EXIF_Array):
	# get image record out of exif data
	ImageRecord			= {}
	ImageRecord_lower	= [] # for case insensitive check for known fields

	for EXIF in EXIF_Array:

		try:
			EXIF_Field, EXIF_Value	= EXIF.split(':',1)
		except:
			EXIF_Field	= EXIF
			EXIF_Value	= ''

		EXIF_Field	= EXIF_Field.strip()
		EXIF_Value	= EXIF_Value.strip()

		EXIF_Field	= re.sub('[^a-zA-Z0-9]', '_', EXIF_Field)

		# prepare and care database-structure
		## do not allow to use ID as EXIF-field
		if EXIF_Field == "ID":
			EXIF_Field="ID_CAMERA"

		## do not accept field names shorter then 2 characters
		if len(EXIF_Field) < 2:
			continue

		## prevent doubles
		if EXIF_Field.lower() in ImageRecord_lower:
			continue

		if not EXIF_Field in ['File_Name', 'Directory']:
			EXIF_Value	= formatValue(EXIF_Value)

		ImageRecord[EXIF_Field]	= EXIF_Value
		ImageRecord_lower.append(EXIF_Field.lower())

	return(ImageRecord)

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

	# write rating and/or comment into file
	if (not args.rating is None) and (not args.comment is None):
		tool.write_metadata(Path(args.input), rating=args.rating, description=description)
	elif not args.rating is None:
		tool.write_metadata(Path(args.input), rating=args.rating)
	elif not args.comment is None:
		tool.write_metadata(Path(args.input), description=description)

if __name__ == "__main__":
	main()

