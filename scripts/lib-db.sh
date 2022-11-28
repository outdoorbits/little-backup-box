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
# - EXIF_COLUMNS_ARRAY
# - source constants.sh
# - source lib-log.sh

DB="${TARGET_PATH}/${const_IMAGE_DATABASE_FILENAME}"

function db_setup() {

	# define database, append lines for updates, do not change existing lines!
	DB_SETUP_ARRAY=()

	DB_SETUP_ARRAY+=("create table CONFIG (VERSION integer);")
	DB_SETUP_ARRAY+=("insert into CONFIG (VERSION) values (0);")
	DB_SETUP_ARRAY+=("create table EXIF_DATA (ID integer primary key autoincrement);")
	DB_SETUP_ARRAY+=("alter table EXIF_DATA add column Directory text;")
	DB_SETUP_ARRAY+=("alter table EXIF_DATA add column File_Name text;")
	DB_SETUP_ARRAY+=("alter table EXIF_DATA add column Create_Date text;")
	DB_SETUP_ARRAY+=("DEPRECATED") # keep array-keys for correct update-status, keyword "DEPRECATED" will be ignored to execute
	DB_SETUP_ARRAY+=("DEPRECATED") # keep array-keys for correct update-status, keyword "DEPRECATED" will be ignored to execute
	DB_SETUP_ARRAY+=("alter table EXIF_DATA add column LbbRating integer default 2;")

	DB_VERSION=0

	# try to get version of existing db

	if [ -f "${DB}" ]; then
		DB_VERSION=$(sqlite3 "${DB}" "select VERSION from CONFIG;")
	#  	echo "VERSION: ${DB_VERSION}"
	fi

	if [ "${DB_VERSION}" == "" ]; then
		DB_VERSION=0
	fi

	# update if necessary
	if [ -z "${DB_VERSION}" ] || [ "${DB_VERSION}" -lt "${#DB_SETUP_ARRAY[@]}" ]; then
		for ((i = 0; i < ${#DB_SETUP_ARRAY[@]}; i++)); do
			if [ "${i}" -ge "${DB_VERSION}" ]; then
				if [ "${DB_SETUP_ARRAY[$i]}" != "DEPRECATED" ]; then
	# 	  			echo "UPDATE: ${DB_SETUP_ARRAY[$i]}"
					sqlite3 "${DB}" "${DB_SETUP_ARRAY[$i]}"
				fi
			fi
		done
		sqlite3 "${DB}" "update CONFIG set VERSION = $(($i + 1));"
	fi

	#export EXIF_COLUMNS_ARRAY
	EXIF_COLUMNS_STR="$(sqlite3 ${DB} 'PRAGMA table_info(EXIF_DATA);' | cut -d'|' -f2)"
	IFS=$'\n' read -rd '' -a EXIF_COLUMNS_ARRAY <<<"${EXIF_COLUMNS_STR}"
	unset IFS
}

function db_insert() {
	local SOURCE_IMAGES_FILENAME="${1}"
	local TARGET_PATH="${2}"

	#read exif-data from file
	EXIF_STR=$(sudo exiftool "${SOURCE_IMAGES_FILENAME}")
	IFS=$'\n' read -rd '' -a EXIF_ARRAY <<< "${EXIF_STR}"
	unset IFS

	#prepare data
	local FIELDS=""
	local VALUES=""
	local DATE=""
	local HAS_CREATE_DATE=false

	for ((j = 0; j < ${#EXIF_ARRAY[@]}; j++)); do

		IFS=':' read -r FIELD VALUE <<< "${EXIF_ARRAY[$j]}"

		FIELD=$(echo ${FIELD} | sed -e 's/[[:space:]]*$//')
		FIELD=${FIELD//[^a-zA-Z0-9_\.]/_}
		VALUE=$(echo ${VALUE} | sed -e 's/^[[:space:]]*//')
		VALUE=${VALUE//\"/\'\'}

		if [ ! -z "${FIELD}" ] && [ ! -z "${VALUE}" ]; then

			# prepare and care database-structure
			## do not allow to use ID as EXIF-field
			if [ "${FIELD}" == "ID" ]; then
				FIELD="ID_CAMERA"
			fi

			## add column to the table if doesn't exist
			if [[ ! " ${EXIF_COLUMNS_ARRAY[@]} " =~ " ${FIELD} " ]]; then
				sqlite3 "${DB}" "alter table EXIF_DATA add column ${FIELD} text;"
				EXIF_COLUMNS_ARRAY+=(${FIELD})
			fi

			## prepare values
			# manage date
			if [ "${FIELD}" = "Create_Date" ]; then
				HAS_CREATE_DATE=true
			fi

			if [ $HAS_CREATE_DATE = false ] && [ -z "${DATE}" ]; then
				if [[ " File_Modification_Date_Time File_Access_Date_Time Date_Time_Original " =~ "${FIELD}" ]]; then
					DATE="${VALUE}"
				fi
			fi

			# catch value of Rating for LbbRating
			if [ "${FIELD}" = "Rating" ] && [ "${VALUE}" -gt "0" ]; then
				EXIF_ARRAY+=("LbbRating:${VALUE}")
			fi


			#replace media-path from Directory
			if [ "${FIELD}" == "Directory" ]; then
				local TARGET_PATH_MARKED="0-0-0-0${TARGET_PATH}"
				local VALUE_MARKED="0-0-0-0${VALUE}"
				VALUE=${VALUE_MARKED//${TARGET_PATH_MARKED}}
			fi

			if [ ! -z "${FIELDS}" ]; then
				FIELDS="${FIELDS}, "
				VALUES="${VALUES}, "
			fi

			FIELDS="${FIELDS}${FIELD}"
			VALUES="${VALUES}\"${VALUE}\""
		fi
	done

	if [ $HAS_CREATE_DATE = false ] && [ ! -z "${DATE}" ]; then

		if [ ! -z "${FIELDS}" ]; then
			FIELDS="${FIELDS}, "
			VALUES="${VALUES}, "
		fi

		FIELDS="${FIELDS}Create_Date"
		VALUES="${VALUES}\"${DATE}\""
	fi

	#insert data
	if [ ! -z "${FIELDS}" ]; then
# 		echo "insert into EXIF_DATA (${FIELDS}) values (${VALUES});"
		sqlite3 "${DB}" "insert into EXIF_DATA (${FIELDS}) values (${VALUES});"
		log_message "db_insert ${SOURCE_IMAGES_FILENAME}" 3
	fi
}
