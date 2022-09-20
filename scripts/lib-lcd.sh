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

# Definitions

function lcd_message () {

# takes up to 5 arguments (lines of the display)
# leading "-" is interpreted as "force to print inverted"
# leading "+" is interpreted as "force to print normal"

	#Arguments:
	LineCount=$#
	Lines=( "$@" )

	#Wait for Lockfile
	if [ -f "${const_DISPLAY_LOCKFILE}" ]; then

		DispayLockFileTime=$(sudo head -n 1 ${const_DISPLAY_LOCKFILE})
		ActualTime=$(date +%s.%N )

		TimeDiff=$(echo "${ActualTime} - ${DispayLockFileTime}" | bc)

		while (( $(echo "${TimeDiff} < 1.0" | bc -l) ))
		do
			DispayLockFileTime=$(sudo head -n 1 ${const_DISPLAY_LOCKFILE})
			ActualTime=$(date +%s.%N )

			TimeDiff=$(echo "${ActualTime} - ${DispayLockFileTime}" | bc)

			sleep 1
		done
	fi

	date +%s.%N | tee "${const_DISPLAY_LOCKFILE}" > /dev/null 2>&1

	# clear screen
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

	#fifo display
	if [ -f "${const_DISPLAY_CONTENT_OLD_FILE}" ]; then
		readarray -t OLED_OLD < "${const_DISPLAY_CONTENT_OLD_FILE}"
	fi

	n=$LineCount
	while [ "${n}" -lt 5 ]
	do
		Lines[$n]=${OLED_OLD[$(expr $n - $LineCount)]}
		n=$(expr $n + 1)
	done

	#save Lines to file
	sudo bash -c "echo -en '${Lines[0]}\n${Lines[1]}\n${Lines[2]}\n${Lines[3]}\n${Lines[4]}' > '${const_DISPLAY_CONTENT_OLD_FILE}'"

	#display
	LogLines=""
	n=0

	while [ "${n}" -lt 5 ]
	do
		LINE="${Lines[$n]}"

		FORCE_FORMAT[$n]="standard"

		# read first letter for pos or neg text
		case "${LINE:0:1}" in
			"+")
				FORCE_FORMAT[$n]="pos"
				LINE=${LINE:1}
				;;
			"-")
				FORCE_FORMAT[$n]="neg"
				LINE=${LINE:1}
				;;
		esac

		# print negativ: new lines and as negative defined lines
		if [ "${n}" -lt "${LineCount}" ] || [ "${FORCE_FORMAT[$n]}" = "neg" ];
		then
			if [ "${FORCE_FORMAT[$n]}" != "pos" ]; then
				FORCE_FORMAT[$n]="neg"
			fi
		fi

		# definitive format: standard=pos
		if [ "${FORCE_FORMAT[$n]}" != "neg" ]; then
			FORCE_FORMAT[$n]="pos"
		fi

		if [[ "${LINE}" == "PGBAR:"* ]]; then
			PERCENT=${LINE#"PGBAR:"}

			if [ -z "${PERCENT}" ]; then
				PERCENT=0
			fi

			PROGRESSBAR_LENGTH=20
			PROGRESSBAR_DONE_LENGTH=$(echo "$PROGRESSBAR_LENGTH * $PERCENT / 100" | bc)
			PROGRESSBAR_TODO_LENGTH=$(echo "$PROGRESSBAR_LENGTH - $PROGRESSBAR_DONE_LENGTH" | bc)
			PROGRESSBAR="$(printf %${PROGRESSBAR_DONE_LENGTH}s | tr " " ">")$(printf %${PROGRESSBAR_TODO_LENGTH}s | tr " " "_")"
 			LINE="${PERCENT}% ${PROGRESSBAR}"
		fi

		# get LINE into Lines-Array
		Lines[$n]=${LINE}

		# modify LINE for logging
		if [[ "${LINE}" == "IMAGE:"* ]]; then
			LINE=$(echo "${LINE}" | cut -d":" -f3)
		fi

		# create log-text
		if [ ! -z "${LogLines}" ]; then
			LogLines="${LogLines}\n"
		fi
		LogLines="${LogLines}${LINE}"


		n=$(expr $n + 1)
	done



	# output to lcd
	if [ $conf_DISP = true ]; then
		sudo python3 ${WORKING_DIR}/oled.py "${FORCE_FORMAT[0]}" "${Lines[0]}" "${FORCE_FORMAT[1]}" "${Lines[1]}" "${FORCE_FORMAT[2]}" "${Lines[2]}" "${FORCE_FORMAT[3]}" "${Lines[3]}" "${FORCE_FORMAT[4]}" "${Lines[4]}"
	fi

	# log
	if [ ! -z "${LogLines}" ];
	then
		log_message "${LogLines}"
	fi
}

