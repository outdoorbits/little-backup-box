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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

JOB="${1}" # log_message, log_exec or log_pick_file
MESSAGE="${2}"
COMMAND="${3}" # command or filename
LOGLEVEL="${4}"

if [ "${JOB}" = "log_message" ]; then
	log_message "${MESSAGE}" "${LOGLEVEL}"
elif [ "${JOB}" = "log_exec" ]; then
	log_exec "${COMMAND}" "${MESSAGE}" "{$LOGLEVEL}"
elif [ "${JOB}" = "log_exec" ]; then
	log_pick_file "${COMMAND}"
fi

exit 0
