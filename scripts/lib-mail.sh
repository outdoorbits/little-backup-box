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
# - source lib-network

function mail_configured() {
	if [ ! -z "${conf_SMTP_SERVER}" ] && \
	   [ ! -z "${conf_SMTP_PORT}" ] && \
	   [ ! -z "${conf_MAIL_SECURITY}" ] && \
	   [ ! -z "${conf_MAIL_USER}" ] && \
	   [ ! -z "${conf_MAIL_PASSWORD}" ] && \
	   [ ! -z "${conf_MAIL_FROM}" ] && \
	   [ ! -z "${conf_MAIL_TO}" ]; then
		echo 'true'
	else
		echo 'false'
	fi
}

function send_email() {

# Takes up to 3 arguments:
# send_email "$Subject" "$Text_plain" "$Text_HTML (optional)"

	# Arguments
	SUBJECT="${1}"
	TEXT_PLAIN="${2}"
	TEXT_HTML="${3}"
	BOUNDARY="${RANDOM}${RANDOM}${RANDOM}"

	TEXT=""

	#Mail-body
	if [ ${conf_MAIL_HTML} == true ] && [ ! -z "${TEXT_HTML}" ]; then
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
	# a notification if the conf_MAIL_NOTIFICATIONS option is enabled
	INTERNET_STATUS=$(get_internet_status)
	if [ $conf_MAIL_NOTIFICATIONS = true ] || [ "${INTERNET_STATUS}" = "connected" ]; then

		MAIL_CONTENT_FILE="${WORKING_DIR}/tmp/email.txt"

		if [ "${conf_MAIL_SECURITY}" == "SSL" ]; then
			COMMAND="curl --url 'smtps://$conf_SMTP_SERVER:$conf_SMTP_PORT' --ssl"
		else
			COMMAND="curl --url 'smtps://$conf_SMTP_SERVER:$conf_SMTP_PORT' --ssl-reqd"
		fi

		COMMAND="${COMMAND} --mail-from '$conf_MAIL_FROM'"
		COMMAND="${COMMAND} --mail-rcpt '$conf_MAIL_TO'"
		COMMAND="${COMMAND} --user '$conf_MAIL_USER':'$conf_MAIL_PASSWORD'"
		COMMAND="${COMMAND} --upload-file ${MAIL_CONTENT_FILE}"

		echo "From: ${conf_MAIL_FROM}" | tee "${MAIL_CONTENT_FILE}"
		echo "To: ${conf_MAIL_TO}" | tee -a "${MAIL_CONTENT_FILE}"
		echo "Subject: ${SUBJECT}" | tee -a "${MAIL_CONTENT_FILE}"
		echo -e "${TEXT}" | tee -a "${MAIL_CONTENT_FILE}"

		log_exec "OUTGOING MAIL" "$COMMAND" 3
	fi

	log_message "Mail:\n${SUBJECT}\n${TEXT}"
}
