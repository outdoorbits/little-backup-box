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

function oled_message () {

	#Parameters:
	Line1=$1
	Line2=$2

	#Config
	FILE_OLED_OLD="/root/oled_old.txt"

	#fifo display
	if [ -z "$Line2" ] && [ -f "$FILE_OLED_OLD" ]; then
		readarray -t OLED_OLD < "$FILE_OLED_OLD"
		Line2=${OLED_OLD[0]}
	fi

	#save Line1 to file
	echo "${Line1}" > "${FILE_OLED_OLD}"

	#display
	oled r
    	oled +b "${Line1}"
    	oled +c "${Line2}"
    	oled s


}
