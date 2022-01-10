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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Takes one arguments
MODE=${1} # "mount", "umount" or "check"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load Systeminfo library
. "${WORKING_DIR}/lib-systeminfo.sh"

if [ "${MODE}" = "abnormal_conditions" ]; then
	get_abnormal_system_conditions
#elif [ "${MODE}" = "check" ]; then
#	...
fi

exit 0
