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

#Arguments
RCLONE_MODE="${1}"

## kill rclone-gui
sudo pkill -f "rclone rcd --rc-web-gui"

##start rclone web-gui
if [ ! -f "${const_RCLONE_CONFIG_FILE}" ]; then
	sudo touch "${const_RCLONE_CONFIG_FILE}"
fi
sudo chmod 777 "${const_RCLONE_CONFIG_FILE}"

if [ ! -z "${conf_PASSWORD}" ]; then
	PASSWORD="${conf_PASSWORD}"
else
	PASSWORD="lbb"
fi

if [ "${RCLONE_MODE}" == "update_gui" ]; then
	UPDATE_GUI="--rc-web-gui-force-update "
fi

COMMAND="sudo rclone rcd --rc-web-gui ${UPDATE_GUI}--rc-web-gui-no-open-browser --rc-addr :5572 --config '${const_RCLONE_CONFIG_FILE}' --rc-user lbb --rc-pass '${PASSWORD}' &"
echo $COMMAND
eval "$COMMAND"

