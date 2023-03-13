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

function disp_message () {

	# Arguments:
	local RawLines=( "$@" )
	local RawLinesCount
	local RawLinesSepareted
	local RawLinesSeparetedCount
	local Lines
	local n
	local i

	# make sure, display.py is up
	if [ $conf_DISP = true ] && [ -z "$(pgrep -af "python3" | grep "${WORKING_DIR}/display.py")" ]; then
		sudo bash -c "nohup python3 ${WORKING_DIR}/display.py &"
	fi

	# explode lines (\n -> new element in Lines-array)
	RawLinesCount=${#RawLines[@]}

	n=0
	while [ "${n}" -lt $RawLinesCount ];  do
		IFS=$'\n' read -rd '' -a RawLinesSepareted <<<"${RawLines[$n]}"
		unset IFS

		RawLinesSeparetedCount=${#RawLinesSepareted[@]}

		for ((i=0; i < $RawLinesSeparetedCount; i++)); do

			if [ ! -z "${RawLinesSepareted}" ]; then
				Lines+=("${RawLinesSepareted[$i]}")
			fi

		done

		n=$(expr $n + 1)
	done

	# logging
	local LogLines=""
	local Progressbar_active=false

	LinesCount=${#Lines[@]}

	n=0
	while [ ${n} -lt $const_DISPLAY_LINES_LIMIT ] && [ ${n} -lt $LinesCount ];  do

		LogLine="${Lines[$n]}"

		if [ ! -z "${LogLine}" ]; then

			# remove format separated by ":"
			if [[ "${LogLine}" =~ ":" ]]; then
				LogLine="$( cut -d ':' -f 2- <<< "$LogLine" )"
			fi

			# modify LogLine for logging
			if [[ "${LogLine}" == "PGBAR="* ]]; then
				if [ "${Progressbar_active}" == false ]; then
					Progressbar_active=true

					PERCENT=${LogLine#"PGBAR="}

					if [ -z "${PERCENT}" ]; then
						PERCENT=0
					fi

					PROGRESSBAR_LENGTH=20
					PROGRESSBAR_DONE_LENGTH=$(echo "$PROGRESSBAR_LENGTH * $PERCENT / 100" | bc)
					PROGRESSBAR_TODO_LENGTH=$(echo "$PROGRESSBAR_LENGTH - $PROGRESSBAR_DONE_LENGTH" | bc)
					PROGRESSBAR="$(printf %${PROGRESSBAR_DONE_LENGTH}s | tr " " ">")$(printf %${PROGRESSBAR_TODO_LENGTH}s | tr " " "_")"
					LogLine="${PERCENT}% ${PROGRESSBAR}"
				else
					# don't print more than one progressbar
					LogLine=''
				fi
			fi

			# create log-text
			if [ ! -z "${LogLine}" ]; then
				if [ ! -z "${LogLines}" ]; then
					LogLines="${LogLines}\n"
				fi
				LogLines="${LogLines}| ${LogLine}"
			fi

		fi

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
}

