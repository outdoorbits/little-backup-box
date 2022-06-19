#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com

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


# sub expects from calling script:
# - source config.cfg
# - source constants.sh
# - source lib-log.sh
# - source lib-lcd.sh
# - source lib-language.sh

# - WORKING_DIR
# - RSYNC_CONNECTION

# Uses from main-script:
# $FILES_TO_SYNC
# $SYNC_START_TIME
# $BACKUP_PATH
# $DEST_MODE

# Definitions
LineLength=21
TIME_RUN=0

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

#load language library
. "${WORKING_DIR}/lib-language.sh"

# Count of files in storage before backup starts
if [ "${DEST_MODE}" = "rsyncserver" ]; then
	FILES_TO_TRANSFER_START=$(sudo sshpass -p "${conf_RSYNC_conf_PASSWORD}" rsync -avh --stats --exclude "*.id" --exclude "*tims/" --dry-run "${SOURCE_PATH}"/ "${RSYNC_CONNECTION}/${BACKUP_PATH}" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3)=="Number of created files:"){print $(i+4)}}' | sed s/,//g)
else
	FILES_COUNT_STORAGE_START=$(find $BACKUP_PATH -type f | wc -l)
fi

while [ true ]; do
	# Count files in the backup destination
	# Calculate the number of files to be transferred

	if [ "${DEST_MODE}" = "rsyncserver" ]; then
		FILES_TO_TRANSFER=$(sudo sshpass -p "${conf_RSYNC_conf_PASSWORD}" rsync -avh --stats --exclude "*.id" --exclude "*tims/" --dry-run "${SOURCE_PATH}"/ "${RSYNC_CONNECTION}/${BACKUP_PATH}/" | awk '{for(i=1;i<=NF;i++)if ($i " " $(i+1) " " $(i+2) " " $(i+3)=="Number of created files:"){print $(i+4)}}' | sed s/,//g)
		FILES_SYNCED=$(expr $FILES_TO_TRANSFER_START - $FILES_TO_TRANSFER)
	else
		FILES_COUNT_STORAGE=$(find $BACKUP_PATH -type f | wc -l)
		FILES_SYNCED=$(expr $FILES_COUNT_STORAGE - $FILES_COUNT_STORAGE_START)
	fi

	if [ "${FILES_TO_SYNC}" -gt "0" ];
	then
		if [ "${FILES_SYNCED}" -gt "0" ];
		then
			FINISHED_PERCENT=$(expr 100 \* $FILES_SYNCED / $FILES_TO_SYNC)

			PROGRESSBAR_LENGTH=$(expr $LineLength \* $FILES_SYNCED / $FILES_TO_SYNC)
			PROGRESSBAR=$(printf %${PROGRESSBAR_LENGTH}s | tr " " ">")
		else
			PROGRESSBAR="$(l 'box_backup_checking_old_files')..."
		fi
	else
		FINISHED_PERCENT="?"
		PROGRESSBAR=""
	fi

	if [ "${FILES_SYNCED}" -gt "0" ]; then
		TIME_RUN=$(echo "$(date +%s) - ${SYNC_START_TIME}" | bc)
		TIME_LEFT=$(echo "${TIME_RUN} * ( ${FILES_TO_SYNC} - ${FILES_SYNCED} ) / ${FILES_SYNCED}" | bc)
		TIME_LEFT_FORMATED=$(date -d@${TIME_LEFT} -u +%H:%M:%S)
		DAYS_LEFT=$((TIME_LEFT/86400))
		if [ "${DAYS_LEFT}" -gt "0" ]; then
			TIME_LEFT_FORMATED="${DAYS_LEFT}d ${TIME_LEFT_FORMATED}"
		fi
	else
		FILES_SYNCED="0"
		TIME_LEFT_FORMATED=""
	fi

	TIME_SPACER_LENGTH=$(echo "${LineLength} - ${#FINISHED_PERCENT} - 1 - ${#TIME_LEFT_FORMATED}" | bc)
	TIME_SPACER=$(printf %${TIME_SPACER_LENGTH}s)
	lcd_message "+$(l 'box_backup_status'):" "+${FILES_SYNCED} $(l 'box_backup_of') ${FILES_TO_SYNC}" "+${FINISHED_PERCENT}%${TIME_SPACER}${TIME_LEFT_FORMATED}" "-${PROGRESSBAR}"

	# display-frequency depends on destination, slower for cloud-storage
	INTERVAL_SEC=4
	if [[ " external internal " =~ " ${DEST_MODE} " ]]; then
		INTERVAL_SEC=2
	fi
	sleep "${INTERVAL_SEC}"

done
