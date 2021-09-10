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
dos2unix "$CONFIG"
source "$CONFIG"

# Get source and backup paths from the parameters
# passed from the backup script
SOURCE_PATH=$1
BACKUP_PATH=$2

# Count files on the source device
source_count=$(find $SOURCE_PATH -type f | wc -l)

while [ true ]; do
    # Count files in the backup destination
    # Calculate the number of files to be transferred
    storage_count=$(find $BACKUP_PATH -type f | wc -l)
    result=$(expr $source_count - $storage_count)
    if [ "$result" -gt 0 ]; then
        bar_lengh=$(expr 16 \* $result / $source_count)
        bar_16="                "
        progress_bar=${bar_16:0:$bar_lengh}
    else
        progress_bar=""
    fi
    oled r
    oled +a "Total: $source_count"
    oled +b "Remains: $result"
    oled +R 4
    oled +d "$progress_bar"
    oled s
    echo -e "Transferred $storage_count of $source_count. Remains: $result\nProgress: <progress id='file' max='$source_count' value='$storage_count'></progress>" >/tmp/progress
    sleep 3
done
