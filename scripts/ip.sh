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

# Config
IP_MAIL_SENT_MARKERFILE="${WORKING_DIR}/tmp/ip-sent.txt"
FILE_OLED_OLD="${WORKING_DIR}/tmp/oled_old.txt"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"

# Load Mail library
. "${WORKING_DIR}/lib-mail.sh"

# Load LCD library
. "${WORKING_DIR}/lib-lcd.sh"

ping -c1 google.com &>/dev/null
while [ $? != 0 ]; do
    sleep 10
    ping -c1 google.com &>/dev/null
done

IP=$(hostname -I | cut -d' ' -f1)

if [ $DISP = true ]; then
    if ! grep -q "${IP}" "${FILE_OLED_OLD}"; then
        lcd_message "$IP"
    fi
fi

UPTIME=$(awk '{print int($1)}' /proc/uptime)

if [ $UPTIME -lt "80" ]  && [ -f "${IP_MAIL_SENT_MARKERFILE}" ]; then
    rm "${IP_MAIL_SENT_MARKERFILE}"
fi

if [ ! -z $SMTP_SERVER ] && [ ! -f "${IP_MAIL_SENT_MARKERFILE}" ]; then

TEXT_PLAIN="
Little Backup Box web UI: http://${IP}:8000
Little Backup Box Upload: http://${IP}:8000/upload.php
Little Backup Box Files: http://${IP}:8080
Little Backup Box MiniDLNA: http://${IP}:8200"

    TEXT_HTML="
Little Backup Box web UI: <a href='http://${IP}:8000'>http://${IP}:8000</a><br>
Little Backup Box Upload: <a href='http://${IP}:8000/upload.php'>http://${IP}:8000/upload.php</a><br>
Little Backup Box Files: <a href='http://${IP}:8080'>http://${IP}:8080</a><br>
Little Backup Box miniDLNA: <a href='http://${IP}:8200'>http://${IP}:8200</a><br>"

    if [ -f "${WORKING_DIR}/../../mejiro/index.php" ]; then
        TEXT_PLAIN="${TEXT_PLAIN}
Little Backup Box mejiro: http://${IP}:8081"
        TEXT_HTML="${TEXT_HTML}
Little Backup Box mejiro: <a href='http://${IP}:8081'>http://${IP}:8081</a><br>"
    fi

    send_email "Little Backup Box IP: ${IP}" "${TEXT_PLAIN}" "${TEXT_HTML}"
    touch "${IP_MAIL_SENT_MARKERFILE}"
fi
