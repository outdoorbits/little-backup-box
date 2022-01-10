#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com; Stefan Saam github@saams.de

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
# - source config.cfg
# - source constants.sh
# - source lib-log.sh

function send_email () {

# Takes up to 3 arguments:
# sendmail "$Subject" "$Text_plain" "$Text_HTML (optional)"

    # Arguments
    SUBJECT="${1}"
    TEXT_PLAIN="${2}"
    TEXT_HTML="${3}"
    BOUNDARY="${RANDOM}${RANDOM}${RANDOM}"

    TEXT=""

    #Mail-body
    if [ ${conf_MAIL_HTML} = true ] && [ ! -z "${TEXT_HTML}" ];
    then
        TEXT="Content-Type: multipart/alternative; boundary=${BOUNDARY}

${TEXT_PLAIN}

--${BOUNDARY}
Content-Type: text/plain; charset='utf-8'
Content-Transfer-Encoding: quoted-printable
Content-Disposition: inline

${TEXT_PLAIN}

--${BOUNDARY}
Content-Type: text/html; charset='utf-8'
Content-Transfer-Encoding: quoted-printable
Content-Disposition: inline

${TEXT_HTML}

--${BOUNDARY}--"

    else
        TEXT="\n\n$TEXT_PLAIN\n\n"
    fi

    # Check internet connection and send
    # a notification if the conf_NOTIFY option is enabled
    check=$(wget -q --spider http://google.com/)
    if [ $conf_NOTIFY = true ] || [ ! -z "$check" ]; then
        curl --url 'smtps://'$conf_SMTP_SERVER':'$conf_SMTP_PORT --ssl-reqd \
            --mail-from $conf_MAIL_USER \
            --mail-rcpt $conf_MAIL_TO \
            --user $conf_MAIL_USER':'$conf_MAIL_conf_PASSWORD \
            -T <(echo -e "From: ${conf_MAIL_USER}\nTo: ${conf_MAIL_TO}\nSubject: ${SUBJECT}\n${TEXT}")
    fi

    log_message "Mail:\n${SUBJECT}\n${TEXT}"
}
