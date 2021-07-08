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

function send_email () {

# Takes up to 3 arguments:
# sendmail "$Subject" "$Text_plain" "$Text_HTML (optional)"

    # Arguments
    SUBJECT=$1
    TEXT_PLAIN=$2
    TEXT_HTML=$3

    # Config
    CONFIG_DIR=$(dirname "$0")
    CONFIG="${CONFIG_DIR}/config.cfg"
    dos2unix "$CONFIG"
    source "$CONFIG"

    BOUNDARY="${RANDOM}${RANDOM}${RANDOM}"
    TEXT=""
    
    #Mail-body
    if [ "${MAIL_HTML}" = "true" ];
    then
        TEXT="Content-Type: multipart/alternative;boundary=${BOUNDARY}\n\n$TEXT_PLAIN\n\n--${BOUNDARY}\nContent-type: text/html;charset=utf-8\n\n$TEXT_HTML\n\n--${BOUNDARY}--"
    else
        TEXT="\n\n$TEXT_PLAIN\n\n"
    fi
    
    # Check internet connection and send
    # a notification if the NOTIFY option is enabled
    check=$(wget -q --spider http://google.com/)
    if [ $NOTIFY = true ] || [ ! -z "$check" ]; then
        curl --url 'smtps://'$SMTP_SERVER':'$SMTP_PORT --ssl-reqd \
            --mail-from $MAIL_USER \
            --mail-rcpt $MAIL_TO \
            --user $MAIL_USER':'$MAIL_PASSWORD \
            -T <(echo -e "From: ${MAIL_USER}\nTo: ${MAIL_TO}\nSubject: ${SUBJECT}\n${TEXT}")
    fi
}
