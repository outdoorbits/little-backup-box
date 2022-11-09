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
# - SOURCE_IMAGES_FILENAME
# - source constants.sh
# - source lib-log.sh


DB="${TARGET_PATH}/${const_IMAGE_DATABASE_FILENAME}"

#read exif-data from file
EXIF_STR=$(sudo exiftool "${SOURCE_IMAGES_FILENAME}")
IFS=$'\n' read -rd '' -a EXIF_ARRAY <<< "${EXIF_STR}"
unset IFS

#prepare data
FIELDS=""
VALUES=""
NO_ENTRY="true"

for ((j = 0; j < ${#EXIF_ARRAY[@]}; j++)); do

	IFS=':' read -r FIELD VALUE <<< "${EXIF_ARRAY[$j]}"

	FIELD=$(echo ${FIELD} | sed -e 's/[[:space:]]*$//')
	FIELD=${FIELD//[^a-zA-Z0-9_\.]/_}
 	VALUE=$(echo ${VALUE} | sed -e 's/^[[:space:]]*//')
 	VALUE=${VALUE//\"/\'\'}

	if [ ! -z "${FIELD}" ] && [ ! -z "${VALUE}" ]; then

		# #add column to the table if doesn't exist
		if [[ ! " ${EXIF_COLUMNS_ARRAY[@]} " =~ " ${FIELD} " ]]; then
			sqlite3 "${DB}" "alter table EXIF_DATA add column ${FIELD} text;"
			EXIF_COLUMNS_ARRAY+=(${FIELD})
		fi

		#replace media-path from Directory
		if [ "${FIELD}" == "Directory" ]; then
 			VALUE=$(echo ${VALUE} | sed -E "s#^${TARGET_PATH}/##")
		fi

		if [ "${NO_ENTRY}" = "false" ]; then
			FIELDS="${FIELDS}, "
			VALUES="${VALUES}, "
		fi

		FIELDS="${FIELDS}${FIELD}"
		VALUES="${VALUES}\"${VALUE}\""

		NO_ENTRY=false
	fi
done

#insert data
if [ "${NO_ENTRY}" = "false" ]; then
	sqlite3 "${DB}" "insert into EXIF_DATA (${FIELDS}) values (${VALUES});"
fi
