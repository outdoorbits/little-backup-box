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

function log_to_file () {

# Takes one argument:
# log_to_file "Log-message"

    # Arguments
    MESSAGE=$1
    
    # Config
    CONFIG_DIR=$(dirname "$0")
#     CONFIG="${CONFIG_DIR}/config.cfg"
#     dos2unix "$CONFIG"
#     source "$CONFIG"

    LOGFILE="${CONFIG_DIR}/tmp/little-backup-box.log"
    sed -i "1s/^/$MESSAGE\n/" "${LOGFILE}"
}
