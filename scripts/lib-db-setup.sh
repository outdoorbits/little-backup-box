#!/bin/bash


#!/usr/bin/env bash

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

###############################################
# The commented examples in the script provide
# instructions on adding custom backup jobs
# To extend, just use the elif-section-examples
###############################################

# library expects from calling script:
# - TARGET_PATH
# - source constants.sh

DB="${TARGET_PATH}/${const_IMAGE_DATABASE_FILENAME}"

# define database, append lines for updates, do not change existing lines!
DB_SETUP_ARRAY=()

DB_SETUP_ARRAY+=("create table CONFIG (VERSION integer);")
DB_SETUP_ARRAY+=("insert into CONFIG (VERSION) values (0);")
DB_SETUP_ARRAY+=("create table EXIF_DATA (ID integer primary key autoincrement);")

DB_VERSION=0

# try to get version of existing db
if [ -f "${DB}" ]; then
	DB_VERSION=$(sqlite3 "${DB}" "select VERSION from CONFIG;")
# 	echo "VERSION: $DB_VERSION"
fi

# update if necessary
if [ -z "${DB_VERSION}" ] || [ "${DB_VERSION}" -lt "${#DB_SETUP_ARRAY[@]}" ]; then
	for ((i = 0; i < ${#DB_SETUP_ARRAY[@]}; i++)); do
		if [ "${i}" -ge "${DB_VERSION}" ]; then
# 			echo "UPDATE: ${DB_SETUP_ARRAY[$i]}"
			sqlite3 "${DB}" "${DB_SETUP_ARRAY[$i]}"
			sqlite3 "${DB}" "update CONFIG set VERSION = $(($i + 1));"
		fi
	done
fi

#export EXIF_COLUMNS_ARRAY
EXIF_COLUMNS_STR="$(sqlite3 ${DB} 'PRAGMA table_info(EXIF_DATA);' | cut -d'|' -f2)"
IFS=$'\n' read -rd '' -a EXIF_COLUMNS_ARRAY <<<"${EXIF_COLUMNS_STR}"
unset IFS





