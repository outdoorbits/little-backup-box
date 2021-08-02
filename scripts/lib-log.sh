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

function log_to_file() {

    # Takes one argument:
    # log_to_file "Log message"

    # Arguments
    MESSAGE=$1

    LOGFILE="${WORKING_DIR}/tmp/little-backup-box.log"

    if [ ! -f "${LOGFILE}" ]; then
        mkdir -p "${WORKING_DIR}/tmp"
        sudo echo "" >"${LOGFILE}"
    fi

    sudo echo -e "$(date '+%H:%M:%S')\n${MESSAGE}\n\n$(cat ${LOGFILE})" >"${LOGFILE}"
}
