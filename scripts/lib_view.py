#!/usr/bin/env python

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
from datetime import datetime
import os
import pathlib
import sqlite3
import subprocess
import sys

import lib_metadata

# import lib_debug
# xx	= lib_debug.debug()

class viewdb(object):

	def __init__(self, setup, log, MountPoint):
		self.__setup	= setup
		self.__log		= log

		self.MountPoint	= MountPoint

		self.dbFile									= os.path.join(self.MountPoint, self.__setup.get_val('const_IMAGE_DATABASE_FILENAME'))
		self.const_VIEW_RATING_STANDARD_VALUE		= self.__setup.get_val('const_VIEW_RATING_STANDARD_VALUE')
		self.const_METADATA_CREATE_SOURCES_HR		= self.__setup.get_val('const_METADATA_CREATE_SOURCES_HR').split(';')
		self.const_FILE_EXTENSIONS_LIST_RAW			= self.__setup.get_val('const_FILE_EXTENSIONS_LIST_RAW')

		self.__con	= sqlite3.connect(self.dbFile)
		self.__cur	= self.__con.cursor()

		self.__dbCreateUpgrade()

		# read __EXIF_KnownColumnsList
		self.__EXIF_KnownColumnsList	= []
		res = self.__cur.execute('PRAGMA table_info(EXIF_DATA);')
		Lines	= res.fetchall()
		for Line in Lines:
			self.__EXIF_KnownColumnsList.append(Line[1].lower())

	def __dbCreateUpgrade(self):
		# define database, append lines for updates, do not change existing lines!
		dbCreateArray	= []

		dbCreateArray.append("create table CONFIG (VERSION integer);")
		dbCreateArray.append("insert into CONFIG (VERSION) values (0);")
		dbCreateArray.append("create table EXIF_DATA (ID integer primary key autoincrement);")
		dbCreateArray.append("alter table EXIF_DATA add column Directory text;")
		dbCreateArray.append("alter table EXIF_DATA add column File_Name text;")
		dbCreateArray.append("alter table EXIF_DATA add column Create_Date text;")
		dbCreateArray.append("DEPRECATED") # keep array-keys for correct update-status, keyword "DEPRECATED" will be ignored to execute
		dbCreateArray.append("DEPRECATED") # keep array-keys for correct update-status, keyword "DEPRECATED" will be ignored to execute
		dbCreateArray.append(f"alter table EXIF_DATA add column LbbRating integer default {self.const_VIEW_RATING_STANDARD_VALUE};")
		dbCreateArray.append("alter table EXIF_DATA add column Rating text;")
		dbCreateArray.append("alter table EXIF_DATA add column Camera_Model_Name text;")

		dbCreateArray.append("alter table EXIF_DATA add column File_Type text;")
		dbCreateArray.append("alter table EXIF_DATA add column File_Type_Extension text;")
		dbCreateArray.append("alter table EXIF_DATA add column Comment text;")
		dbCreateArray.append("DEPRECATED") # keep array-keys for correct update-status, keyword "DEPRECATED" will be ignored to execute
		dbCreateArray.append("DEPRECATED") # keep array-keys for correct update-status, keyword "DEPRECATED" will be ignored to execute
		dbCreateArray.append("alter table EXIF_DATA add column social_publish integer default 0;") # for use as bitmask
		dbCreateArray.append("alter table EXIF_DATA add column social_published integer default 0;") # for use as bitmask

		# try to get version of existing db
		dbVersion	= -1
		if os.path.isfile(self.dbFile):
			try:
				res = self.__cur.execute("select VERSION from CONFIG ORDER BY VERSION DESC LIMIT 1;").fetchone()
				if res[0]:
					dbVersion	= res[0]
			except:
				dbVersion	= -1


		# update if necessary
		if dbVersion < len(dbCreateArray):
			i = 0
			for Command in dbCreateArray:
				i	+= 1
				if i > dbVersion:
					if Command != "DEPRECATED":
						try:
							self.__cur.execute(Command)
						except:
							print(f'Error executing "{Command}".',file=sys.stderr)

			self.dbExecute(f"update CONFIG set VERSION = {i};")

	def dbExecute(self, Command):
		try:
			self.__cur.execute(Command)
			self.__con.commit()
			return(True)
		except:
			return(False)

	def dbSelect(self, Command):
		try:
			return(self.__cur.execute(Command).fetchall())
		except:
			return(False)

	def dbInsertImage(self, ImageFileSubpathFilename):
		#read exif-data from file

		ImageFileSubpathFilename	= ImageFileSubpathFilename.strip('/')

		ImageFilePath		= os.path.dirname(ImageFileSubpathFilename).strip('/')
		ImageFileName		= os.path.basename(ImageFileSubpathFilename)

		try:
			ImageFileExtension	= os.path.splitext(ImageFileName)[1].strip('.')
		except:
			ImageFileExtension	= ''

		ImageFile	= os.path.join(self.MountPoint, ImageFileSubpathFilename)
		try:
			EXIF_List	= subprocess.check_output(f"sudo exiftool -use MWG '{ImageFile}' | grep ':'", shell=True).decode().strip().split('\n')
		except:
			EXIF_List	= []

		ImageRecord	= lib_metadata.normalize_exif_array(EXIF_List)

		# overwrite by xmp if available
		Extension = pathlib.Path(ImageFile).suffix.lower().removeprefix('.')
		if Extension in self.const_FILE_EXTENSIONS_LIST_RAW.split(';'):
			XMPFile	= pathlib.Path(ImageFile).with_suffix('.xmp')
			if os.path.isfile(XMPFile):
				try:
					EXIF_List	= subprocess.check_output(f"sudo exiftool -use MWG '{XMPFile}' | grep ':'", shell=True).decode().strip().split('\n')
				except:
					EXIF_List	= []

				XMPRecord	= lib_metadata.normalize_exif_array(EXIF_List)

				for var	in XMPRecord.keys():
					ImageRecord[var]	= XMPRecord[var]

		# define/overwrite elements of ImageRecord
		## file: name and directory
		ImageRecord['Directory']			= ImageFilePath
		ImageRecord['File_Name']			= ImageFileName
		ImageRecord['File_Type_Extension']	= ImageFileExtension

		if 'File_Type' not in ImageRecord:
			ImageRecord['File_Type']	= ImageFileExtension if ImageFileExtension else 'None'

		## date
		if 'Create_Date' not in ImageRecord:

			for CREATE_SOURCE_HR in self.const_METADATA_CREATE_SOURCES_HR:

				if CREATE_SOURCE_HR in ImageRecord:
					ImageRecord['Create_Date']	= ImageRecord[CREATE_SOURCE_HR]
					break

			if 'Create_Date' not in ImageRecord: # still not -> use actual date/time
				ImageRecord['Create_Date']	= datetime.now().strftime("%Y_%m_%d_%H_%M_%S")

		## rating
		if 'Rating' in ImageRecord:
			ImageRecord['LbbRating']	= ImageRecord['Rating'] if int(ImageRecord['Rating'])>0 else self.const_VIEW_RATING_STANDARD_VALUE
		else:
			ImageRecord['LbbRating']	= self.const_VIEW_RATING_STANDARD_VALUE

		# create database query
		dbFields					= ''
		dbValues					= ''

		for EXIF_Field in ImageRecord.keys():
			# add column to the table if doesn't exist
			if not EXIF_Field.lower() in self.__EXIF_KnownColumnsList:
				self.__cur.execute(f"alter table EXIF_DATA add column '{EXIF_Field}' text;")
				self.__EXIF_KnownColumnsList.append(EXIF_Field.lower())

			# create dbFields and dbValues for db query
			if dbFields:
				dbFields	+= ", "
				dbValues	+= ", "

			dbFields	+= EXIF_Field
			dbValues	+= f"'{ImageRecord[EXIF_Field]}'"

		#insert data
		if dbFields:
			# delete item if pre exists:
			Command	= f"delete from EXIF_DATA where File_Name='{ImageRecord['File_Name']}' and Directory='{ImageRecord['Directory']}'"
			self.dbExecute(Command)

			# insert new image
			Command	= f"insert into EXIF_DATA ({dbFields}) values ({dbValues});"
			self.dbExecute(Command)

def parse_args() -> argparse.Namespace:
	parser = argparse.ArgumentParser(
		description="view database related tools",
		formatter_class=argparse.RawTextHelpFormatter,
	)

	actions	= ['init']
	parser.add_argument(
		'--action',
		'-a',
		choices		= actions,
		required =	True,
		help=f'One of {actions}'
	)

	parser.add_argument(
		'--mountpoint',
		'-m',
		required =	False,
		help=f'One of {actions}'
	)

	args = parser.parse_args()

	if args.action == "init" and not args.mountpoint:
		parser.error("--mountpoint is required when --action=init")

	return args

if __name__ == "__main__":
	import lib_setup
	import lib_log

	args = parse_args()

	if args.action == 'init':
		setup	= lib_setup.setup()
		log		= lib_log.log()

		MountPoint	= args.mountpoint

		viewdb(setup, log, MountPoint)
