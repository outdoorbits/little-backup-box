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

python3 ${WORKING_DIR}/lib_display.py ":$(python3 ${WORKING_DIR}/lib_language.py box_backup_break_start_1)" ":$(python3 ${WORKING_DIR}/lib_language.py box_backup_break_start_2)"

pkill -f '(^|/)backup\.py([[:space:]]|$)'
pkill -f '(^|/)backup-autorun\.py([[:space:]]|$)'

python3 ${WORKING_DIR}/lib_system.py --set_led -LED PWR -LED_TRIGGER none -LED_BRIGHTNESS 1
python3 ${WORKING_DIR}/lib_clean.py --clean display_content tasks
python3 ${WORKING_DIR}/lib_proftpd.py --Action setDefaultRoot --DefaultRoot "${const_MEDIA_DIR}"

python3 ${WORKING_DIR}/lib_display.py ":$(python3 ${WORKING_DIR}/lib_language.py box_backup_break_done_1)" ":$(python3 ${WORKING_DIR}/lib_language.py box_backup_break_done_2)"



