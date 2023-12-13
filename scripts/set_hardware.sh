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

# Definitions
raspi_config="/boot/config.txt"

# fan control
dtoverlay=gpio-fan,gpiopin=18,temp=60000
if [ ! "${conf_FAN_PWM_GPIO}" = "-" ] && [ ! "${conf_FAN_PWM_TEMP_C}" = "0" ]; then
	# activate fan control
	raspi-config nonint do_fan 0 ${conf_FAN_PWM_GPIO} ${conf_FAN_PWM_TEMP_C}
else
	# deactivate fan control
	raspi-config nonint do_fan 1
fi
