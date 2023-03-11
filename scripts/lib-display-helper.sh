#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com; Stefan Saam github@saams.de

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

# takes up to 5 arguments (lines of the display)
# leading "-" is interpreted as "force to print inverted"
# leading "+" is interpreted as "force to print normal"

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

#load time library
. "${WORKING_DIR}/lib-time.sh"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load DISPLAY library
. "${WORKING_DIR}/lib-display.sh"

#Arguments:
LineCount=$#
Lines=( "$@" )

disp_message "${Lines[0]}" "${Lines[1]}" "${Lines[2]}" "${Lines[3]}" "${Lines[4]}"

exit 0
