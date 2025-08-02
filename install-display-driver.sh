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

# expected from calling script
## SCRIPT_MODE
## BACKTITLE

# Don't start as root
if [[ $EUID -eq 0 ]]; then
    echo "Run the script as a regular user"
    exit 1
fi

# Don't start setup if no graphical system installed
if [ ! -f "/usr/bin/startx" ]; then
	return
fi

# install display driver
## Prompt to install driver
if [ "${SCRIPT_MODE}" = "install" ]; then

	read -r -d '' DISPLAY_DRIVER_INSTALL_QUESTION << EOM
\Zb\ZuOptional Display Driver Setup\Zn

This installer can optionally download and run a third-party script collection
designed to help configure specific display models - specifically, mid-sized displays
that are capable of showing a full graphical desktop window.

Important: These scripts are not part of this project and are maintained by a third party.
They are downloaded directly from their original GitHub repository https://github.com/goodtft/LCD-show.
Because no license is attached to the script collection, it must be assumed that these scripts are proprietary.

After downloading you will be asked to select your display.
The selected script will be temporarily modified to prevent automatic reboot after execution.
No changes will be made to the original script, and no modified code will be distributed.

Please consult https://github.com/goodtft/LCD-show to determine which setup script matches your display.
EOM

	dialog --clear \
		--colors \
		--title "Display driver" \
		--backtitle "$BACKTITLE" \
		--defaultno \
		--yesno "${DISPLAY_DRIVER_INSTALL_QUESTION}" \
		14 80

	if [ $? -eq 0 ]; then
		clear

		# clone goodtft/LCD-show
		cd
		sudo rm -rf LCD-show
		git clone https://github.com/goodtft/LCD-show.git
		chmod -R 755 LCD-show
		cd LCD-show/

		read -r -d '' DISPLAY_DRIVER_SELECT_QUESTION << EOM
\Zb\ZuPlease select a driver to install:\Zn
EOM

		OPTIONS=()
		OPTIONS+=("None" "")
		while IFS= read -r file; do
			label="${file#./}"            # Remove leading ./
			label="${label%-show}"        # Remove trailing -show
			OPTIONS+=("$label" "")
		done < <(find ./ -type f -name "*-show")

		CHOICE_DISPLAY_DRIVER=$(dialog --clear \
			--colors \
			--title "Display driver" \
			--backtitle "$BACKTITLE" \
			--menu "Select one of these:" \
			25 50 15 \
			"${OPTIONS[@]}" \
			2>&1 >/dev/tty)

		clear

		if [[ "${CHOICE_DISPLAY_DRIVER}" = "None" || "${CHOICE_DISPLAY_DRIVER}" = "" ]]; then
			echo "No driver selected"
		else
			echo "Selected driver: ${CHOICE_DISPLAY_DRIVER}"

			tmp=$(mktemp)
			cp "./${CHOICE_DISPLAY_DRIVER}-show" "$tmp"

			sed -E -i '/^[[:space:]]*(sudo[[:space:]]+)?(\/sbin\/)?reboot([[:space:]]|$)/ s/^([[:space:]]*)/\1# BLOCKED: /' "$tmp"

 			sudo bash "$tmp"
			rm -f "$tmp"
		fi
	fi
	clear
fi
