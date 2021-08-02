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


# library expects from calling script:
# - source config.cfg
# - source lib-log.sh
# - source lib-lcd.sh

# Arguments:
# 1. Number of Files to sync
# 2. (optional) backup-destination-path

# Get arguments
if [ -z $1 ];
then
    FILES_TO_SYNC=0
else
    FILES_TO_SYNC=$1
fi

if [ -z $2 ];
then
    BACKUP_PATH="${INTERAL_BACKUP_DIR}"
else
    BACKUP_PATH=$2
fi

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

# Count of files in storage before backup starts
FILES_COUNT_STORAGE_START=$(find $BACKUP_PATH -type f | wc -l)

while [ true ]; do
    # Count files in the backup destination
    # Calculate the number of files to be transferred

    FILES_COUNT_STORAGE=$(find $BACKUP_PATH -type f | wc -l)

    FILES_SYNCED=$(expr $FILES_COUNT_STORAGE - $FILES_COUNT_STORAGE_START)
    if [ "${FILES_TO_SYNC}" -gt "0" ];
    then
        FINISHED_PERCENT=$(expr 100 \* $FILES_SYNCED / $FILES_TO_SYNC)

        PROGRESSBAR_LENGTH=$(expr 16 \* $FILES_SYNCED / $FILES_TO_SYNC)
        PROGRESSBAR_16="                "
        PROGRESSBAR=${PROGRESSBAR_16:0:$PROGRESSBAR_LENGTH}
    else
        FINISHED_PERCENT="?"
        PROGRESSBAR=""
    fi

    lcd_message "+Backup status:" "+${FILES_SYNCED} of ${FILES_TO_SYNC}" "+${FINISHED_PERCENT}%" "-${PROGRESSBAR}"
    sleep 2
done
