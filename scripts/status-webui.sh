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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Check for .id file
cd "$SOURCE_MOUNT_POINT"
if [ ! -f *.id ]; then
echo "<p>Waiting...</p>"
    exit 1
fi
ID_FILE=$(ls -t *.id | head -n1)
ID="${ID_FILE%.*}"
cd

# Set the backup path
BACKUP_PATH="$STORAGE_MOUNT_POINT"/"$ID"

# Count files on the source device and in the backup destination
# Calculate the number of files to be transferred
count1=$(find $SOURCE_MOUNT_POINT -type f | wc -l)
count2=$(find $BACKUP_PATH -type f | wc -l)
result=$((count1-count2))
echo "Files to transfer: <strong>"$result"</strong>"
