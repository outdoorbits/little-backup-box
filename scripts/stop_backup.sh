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

ps -ef | grep "${WORKING_DIR}/backup" | grep -v grep | awk '{print $2}' | sudo xargs -r kill -9

sudo python3 ${WORKING_DIR}/lib_display.py "$(python3 ${WORKING_DIR}/lib_language.py box_backup_break1)" "$(python3 ${WORKING_DIR}/lib_language.py box_backup_break2)"
