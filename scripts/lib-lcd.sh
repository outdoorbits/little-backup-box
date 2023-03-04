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
# - WORKING_DIR
# - source config.cfg
# - source constants.sh
# - source lib-log.sh
# - source lib-time.sh

# Definitions

function lcd_message () {

	# Arguments:
	local LineCount=$#
	local Lines=( "$@" )

# 	#Wait for Lockfile
# 	while [ -f "${const_DISPLAY_LOCKFILE}" ];
# 	do
# 		DispayLockFileTime=$(sudo head -n 1 ${const_DISPLAY_LOCKFILE})
# 		ActualTime=$(date +%s.%N )
# 		TimeDiff=$(echo "${ActualTime} - ${DispayLockFileTime}" | bc)
#
# 		# if lockfile is older than defined seconds, it must be lost and can be removed
# 		if (( $(echo "${TimeDiff} > 2" | bc -l) )); then
# 			rm "${const_DISPLAY_LOCKFILE}"
# 		fi
#
# 		sleep 1
# 	done

	# make sure, oled.py is up
	if [ $conf_DISP = true ] && [ -z "$(pgrep -af "python3" | grep "${WORKING_DIR}/oled.py")" ]; then
		sudo bash -c "nohup python3 ${WORKING_DIR}/oled.py &"
	fi

	# write lockfile of this process
	date +%s.%N | tee "${const_DISPLAY_LOCKFILE}" > /dev/null 2>&1

	# define clean screen
	if [ "${LineCount}" -eq "0" ];
	then
		LineCount=5
		n=0
		while [ "$n" -lt 5 ]
		do
			Lines[$n]=''
			n=$(expr $n + 1)
		done
	fi

	# fifo display: if space left print old lines
	if [ -f "${const_DISPLAY_CONTENT_OLD_FILE}" ]; then
		readarray -t OLED_OLD < "${const_DISPLAY_CONTENT_OLD_FILE}"
	fi

	n=${LineCount}
	while [ "${n}" -lt 5 ]
	do
		Lines[${n}]=${OLED_OLD[$(expr ${n} - $LineCount)]}

		# remove format from old strings
		if [ "${Lines[${n}]:0:1}" = "+" ] || [ "${Lines[${n}]:0:1}" = "-" ]; then
			Lines[${n}]="${Lines[${n}]:1}"
		fi

		n=$(expr $n + 1)
	done

	# format
	local FORCE_FORMAT

	n=0
	while [ "${n}" -lt 5 ]
	do
		FORCE_FORMAT=""

		# read first letter for pos or neg text
		case "${Lines[${n}]:0:1}" in
			"+")
				FORCE_FORMAT="+"
				Lines[${n}]=${Lines[${n}]:1}
				;;
			"-")
				FORCE_FORMAT="-"
				Lines[${n}]=${Lines[${n}]:1}
				;;
		esac

		# print negativ: new lines and as negative defined lines
		if [ "${n}" -lt "${LineCount}" ] || [ "${FORCE_FORMAT}" = "-" ]; then
			if [ "${FORCE_FORMAT}" != "+" ]; then
				FORCE_FORMAT="-"
			fi
		else
			FORCE_FORMAT="+"
		fi

		Lines[${n}]="${FORCE_FORMAT}${Lines[${n}]}"

		n=$(expr $n + 1)
	done


	# logging
	local LogLines=""
	local Progressbar_active=false

	n=0
	while [ "${n}" -lt 5 ]
	do
		LOG_LINE="${Lines[$n]:1}" # remove format

		# modify LOG_LINE for logging
		if [[ "${LOG_LINE}" == "PGBAR:"* ]]; then
			if [ "${Progressbar_active}" == false ]; then
				Progressbar_active=true

				PERCENT=${LOG_LINE#"PGBAR:"}

				if [ -z "${PERCENT}" ]; then
					PERCENT=0
				fi

				PROGRESSBAR_LENGTH=20
				PROGRESSBAR_DONE_LENGTH=$(echo "$PROGRESSBAR_LENGTH * $PERCENT / 100" | bc)
				PROGRESSBAR_TODO_LENGTH=$(echo "$PROGRESSBAR_LENGTH - $PROGRESSBAR_DONE_LENGTH" | bc)
				PROGRESSBAR="$(printf %${PROGRESSBAR_DONE_LENGTH}s | tr " " ">")$(printf %${PROGRESSBAR_TODO_LENGTH}s | tr " " "_")"
				LOG_LINE="${PERCENT}% ${PROGRESSBAR}"
			else
				# don't print more than one progressbar
				LOG_LINE=''
			fi
		fi

		# create log-text
		if [ ! -z "${LogLines}" ]; then
			LogLines="${LogLines}\n"
		fi
		LogLines="${LogLines}| ${LOG_LINE}"

		n=$(expr $n + 1)
	done



	# output to display via file
	if [ $conf_DISP = true ]; then
		sudo mkdir -p "${const_DISPLAY_CONTENT_FOLDER}"
		sudo bash -c "echo -en '${Lines[0]}\n${Lines[1]}\n${Lines[2]}\n${Lines[3]}\n${Lines[4]}' > '${const_DISPLAY_CONTENT_FOLDER}/$(get_uptime_miliseconds).txt'"
	fi

	# log
	if [ ! -z "${LogLines}" ];
	then
		log_message "${LogLines}"
	fi

	#remove lockfile of this process
	rm "${const_DISPLAY_LOCKFILE}"
}

