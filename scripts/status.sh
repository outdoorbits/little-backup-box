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
source "$CONFIG"

# Create  a .id random identifier file if doesn't exist
cd "$CARD_MOUNT_POINT"
if [ ! -f *.id ]; then
echo "<p>Waiting...</p>"
    exit 1
fi
ID_FILE=$(ls *.id)
ID="${ID_FILE%.*}"
cd

# Set the backup path
BACKUP_PATH="$STORAGE_MOUNT_POINT"/"$ID"

# Count files on the card and in the backup destination
# Calculate the number of files to be transferred
count1=$(find $CARD_MOUNT_POINT -type f | wc -l)
count2=$(find $BACKUP_PATH -type f | wc -l)
result=$((count1-count2))
echo "Files to transfer: <strong>"$result"</strong>"
