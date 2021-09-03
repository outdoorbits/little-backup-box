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

CONFIG_DIR=$(dirname "$0")
CONFIG="${CONFIG_DIR}/config.cfg"
dos2unix "$CONFIG"
source "$CONFIG"

# Get backup path from the parameter passed from
# the backup script that sources progress.sh
BACKUP_PATH=$1

while [ true ]; do
    # Count files on the source device and in the backup destination
    # Calculate the number of files to be transferred
    source_count=$(find $SOURCE_MOUNT_POINT -type f | wc -l)
    storage_count=$(find $BACKUP_PATH -type f | wc -l)
    result=$(expr $source_count - $storage_count)
    oled r
    oled +a "Remaining files:"
    oled +c "$result"
    oled s
    sleep 5
done