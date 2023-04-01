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

function log_message() {

    # Takes up to two arguments:
    # - MESSAGE
    # - LEVEL (optional)

    # Arguments
    local MESSAGE="${1}"
    local LEVEL="${2}"
    local i=0
    local PWD=""
    local ESCAPED_PASSWORD=""
    local ESCAPE_STRING=""

    if [ ! -z "$(log_level_reached ${LEVEL})" ]; then

		if [ ! -f "${const_LOGFILE}" ]; then
			mkdir -p "${WORKING_DIR}/tmp"
			touch "${const_LOGFILE}"
		fi

		#remove passwords from log
		for PWD in "${conf_PASSWORD}" "${conf_MAIL_PASSWORD}" "${conf_RSYNC_PASSWORD}"; do
			ESCAPED_PASSWORD=""

			if [ ! -z "${PWD}" ]; then
				for (( i=0; i<${#PWD}; i++ )); do
					ESCAPE_STRING=""

					if [[ "\$*./[^" =~ "${PWD:$i:1}" ]]; then
						ESCAPE_STRING="\\"
					fi

					ESCAPED_PASSWORD="${ESCAPED_PASSWORD}${ESCAPE_STRING}${PWD:$i:1}"
				done

				MESSAGE=$(echo "${MESSAGE}" | sed -e "s/${ESCAPED_PASSWORD}/PASSWORD/g")
			fi

		done

		echo -e "$(date '+%H:%M:%S')\n${MESSAGE}\n" | sudo tee -a "${const_LOGFILE}" > /dev/null 2>&1

	fi
}

function log_exec() {
	# Takes up to three arguments:
    # - COMMAND
    # - MESSAGE
    # - LEVEL (optional)

	local MESSAGE="${1}"
	local COMMAND="${2}"
    local LEVEL="${3}"
    local LOG_COMMAND_LINE="${4}" # 'true'/'false', default if 'true'

    if [ "${LOG_COMMAND_LINE}" != 'false' ]; then
		COMMAND_LOG="$COMMAND:\n"
    fi

	if [ ! -z "${MESSAGE}" ]; then
		MESSAGE="${MESSAGE}\n"
	fi

	log_message "${MESSAGE}> ${COMMAND_LOG}$(eval ${COMMAND} 2>&1)" "${LEVEL}"
}

function log_level_reached() {
	# Takes one argument:
    # - LEVEL (optional)

    # returns "" for no and "1" for yes

    local LEVEL="${1}"

    if [ -z "${LEVEL}" ] || [ "${conf_LOGLEVEL}" -ge "${LEVEL}" ]; then
		echo "1"
    fi
}

function log_pick_file() {
	local FILENAME="${1}"
	local LEVEL="${2}"

	if [ -f "${FILENAME}" ]; then
		log_message "${FILENAME}:\n>>>>>\n$(cat ${FILENAME})\n<<<<<" "${LEVEL}"
	else
		log_message "${FILENAME}:\n>>>>>\nFile not found.\n<<<<<" "${LEVEL}"
	fi

	rm "${FILENAME}"
}
