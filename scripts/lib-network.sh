#!/usr/bin/env bash

# Author: Stefan Saam github@saams.de

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

function get_ip() {
	hostname -I | awk '{$1=$1};1' # trims the result
}


function get_internet_status() {
	ping -c1 "google.com" &>/dev/null
	local STATUS=$?
	if [ "${STATUS}" == "0" ]; then
		echo "connected"
	else
		echo "disconnected"
	fi
}
