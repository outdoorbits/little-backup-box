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

# library expects from calling script:
# - source config.cfg
# - source lib-log.sh

function lcd_message () {

# takes up to 4 arguments (lines of the display)
# leading "-" is interpreted as "force to print inverted"
# leading "+" is interpreted as "force to print normal"

    #Arguments:
    LineCount=$#
    Lines=( "$@" )

    #Config
    FILE_OLED_OLD="${WORKING_DIR}/tmp/oled_old.txt"
    LockFile="${WORKING_DIR}/tmp/display.lock"
    DisplayLines=(a b c d)

    #Wait for Lockfile
    if [ -f "${LockFile}" ]; then
        LockFileTime=$(sudo head -n 1 ${LockFile})
        ActualTime=$(date +%s )
        while [ $(($ActualTime - $LockFileTime)) == 0 ]
        do
            ActualTime=$(date +%s )
            sleep 0.5
        done
    fi

    sudo date +%s > "${LockFile}"

    # clear screen
    if [ "${LineCount}" -eq 0 ];
    then
        LineCount=4
        n=0
        while [ "$n" -lt 4 ]
        do
            Lines[$n]=''
            n=$(expr $n + 1)
        done
    fi

    #fifo display
    if [ -f "${FILE_OLED_OLD}" ]; then
        readarray -t OLED_OLD < "${FILE_OLED_OLD}"
    fi

    n=$LineCount
    while [ "${n}" -le 3 ]
    do
        Lines[$n]=${OLED_OLD[$(expr $n - $LineCount)]}
        n=$(expr $n + 1)
    done

    #save Lines to file
    sudo echo -en "${Lines[0]}\n${Lines[1]}\n${Lines[2]}\n${Lines[3]}" > "${FILE_OLED_OLD}"

    #display

    LogLines=""
    n=0

    sudo oled r
    while [ "${n}" -le 3 ]
    do

        LINE="${Lines[$n]}"

        FORCE_FORMAT="standard"

        case "${LINE:0:1}" in
            "+")
                FORCE_FORMAT="pos"
                LINE=${LINE:1:16}
                ;;
            "-")
                FORCE_FORMAT="neg"
                LINE=${LINE:1:16}
                ;;
        esac

        if [ "${n}" -lt "${LineCount}" ] || [ "${FORCE_FORMAT}" = "neg" ];
        then
            if [ "${FORCE_FORMAT}" != "pos" ];
            then
                sudo oled +R $(expr $n + 1)
            fi
        fi

        sudo oled +${DisplayLines[$n]} "${LINE}"


        if [ ! -z "${LogLines}" ];
        then
            LogLines="${LogLines}\n"
        fi
        LogLines="${LogLines}${LINE}"

        n=$(expr $n + 1)
    done
    sudo oled s

    # log
    if [ ! -z "${LogLines}" ];
    then
        log_to_file "${LogLines}"
    fi
}

