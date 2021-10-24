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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Takes two arguments
MODE=${1} # "mount", "umount" or "check"
DEVICE=${2} # "usb_1" or "usb_2"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load Device library
. "${WORKING_DIR}/lib-devices.sh"

if [ "${MODE}" = "mount" ]; then
 echo "$(mount_device "${DEVICE}" false "$(device_mounted usb_1)" "$(device_mounted usb_2)")"
elif [ "${MODE}" = "check" ]; then
 echo "$(device_mounted ${DEVICE})"
elif [ "${MODE}" = "umount" ]; then
 echo "$(umount_device ${DEVICE})"
fi

exit 0
