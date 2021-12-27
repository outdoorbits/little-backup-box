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

### Checks the idle-time of the Little Backup Box and powers down the system
WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

APACHE_ACCESS_LOGFILE="/var/log/apache2/lbb-access.log"

if [ "$conf_POWER_OFF_IDLE_TIME" -gt "0" ]; then

	IDLE_SEC_to_POWER_OFF=$(($conf_POWER_OFF_IDLE_TIME * 60))

	LogfileAgeSek=`expr $(date +%s) - $(stat -c '%Y' "${const_LOGFILE}")`
	ApacheLogfileAgeSek=`expr $(date +%s) - $(stat -c '%Y' "${APACHE_ACCESS_LOGFILE}")`

	echo "IDLE_SEC_to_POWER_OFF=$IDLE_SEC_to_POWER_OFF"
	echo "LogfileAgeSek=$LogfileAgeSek"
	echo "ApacheLogfileAgeSek=$ApacheLogfileAgeSek"

	if [ "$LogfileAgeSek" -ge "$IDLE_SEC_to_POWER_OFF" ] && [ "$ApacheLogfileAgeSek" -ge "$IDLE_SEC_to_POWER_OFF" ]; then
		source "${WORKING_DIR}/poweroff.sh" "poweroff" "force" "Idle time reached"
	fi
fi
