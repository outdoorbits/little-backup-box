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

# library expects from calling script:
# nothing


function get_abnormal_system_conditions () {
	local STATUS=$(vcgencmd get_throttled | sed -n 's|^throttled=\(.*\)|\1|p')

	if [[ ${STATUS} -ne 0 ]]; then

		if [ $((${STATUS} & 0x00001)) -ne 0 ]; then
			echo "Power is currently Under Voltage"
		elif [ $((${STATUS} & 0x10000)) -ne 0 ]; then
			echo "Power has previously been Under Voltage"
		fi

		if [ $((${STATUS} & 0x00002)) -ne 0 ]; then
			echo "ARM Frequency is currently Capped"
		elif [ $((${STATUS} & 0x20000)) -ne 0 ]; then
			echo "ARM Frequency has previously been Capped"
		fi

		if [ $((${STATUS} & 0x00004)) -ne 0 ]; then
			echo "CPU is currently Throttled"
		elif [ $((${STATUS} & 0x40000)) -ne 0 ]; then
			echo "CPU has previously been Throttled"
		fi

		if [ $((${STATUS} & 0x00008)) -ne 0 ]; then
			echo "Currently at Soft Temperature Limit"
		elif [ $((${STATUS} & 0x80000)) -ne 0 ]; then
			echo "Previously at Soft Temperature Limit"
		fi

	else
		echo "No abnormal conditions have been detected"
	fi
}

