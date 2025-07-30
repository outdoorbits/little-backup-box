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
## const_WEB_ROOT_LBB
## USER_WWW_DATA
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

# settings
USER="lbb-desktop"

# create user
sudo useradd --create-home -s /bin/bash ${USER}

# remove wireplumber - causing high cpu load
sudo DEBIAN_FRONTEND=noninteractive \
		apt-get purge wireplumber -y

sudo DEBIAN_FRONTEND=noninteractive \
		apt-get autoremove -y

# auto logon $USER
## enable auto login
sudo raspi-config nonint do_boot_behaviour B4

## edit /etc/lightdm/lightdm.conf to set auto login user
CONFIG_FILE="/etc/lightdm/lightdm.conf"
VAR="autologin-user"
NEW_VALUE="${USER}"

sudo sed $CONFIG_FILE -i -e "s/^\(#\|\)${VAR}=.*/autologin-user=${NEW_VALUE}/"

# disable auto mount for user
CONFIG_DIR="/home/$USER/.config/pcmanfm/LXDE-pi"
CONFIG_FILE="${CONFIG_DIR}/pcmanfm.conf"

sudo -u $USER  mkdir -p $CONFIG_DIR
echo """[volume]
mount_on_startup=0
mount_removable=0
autorun=0
""" | sudo -u $USER tee $CONFIG_FILE

# set background
BG_FILE="lbb-desktop.png"
BG_DIR="/home/$USER/backgrounds"

sudo -u $USER mkdir -p "${BG_DIR}"
sudo cp "${INSTALLER_DIR}/img/${BG_FILE}" "${BG_DIR}"
sudo chown $USER:$USER "${BG_DIR}" -R

echo """[*]
wallpaper_mode=crop
wallpaper_common=1
wallpaper=${BG_DIR}/${BG_FILE}
desktop_bg=#ffffff
desktop_fg=#e8e8e8
desktop_shadow=#d6d3de
desktop_font=PibotoLt 12
show_wm_menu=0
sort=mtime;ascending;
show_documents=0
show_trash=0
show_mounts=0
""" | sudo -u $USER tee /home/$USER/.config/pcmanfm/LXDE-pi/desktop-items-0.conf


# auto start browser
AUTOSTART_USER_DIR="/home/$USER/.config/autostart"

sudo -u $USER mkdir -p $AUTOSTART_USER_DIR
echo """[Desktop Entry]
Type=Application
Name=little-backup-box
Exec=firefox -setDefaultBrowser -private --kiosk=http://localhost:8080
Terminal=false
""" | sudo -u $USER tee $AUTOSTART_USER_DIR/little-backup-box.desktop

# install display driver
## Prompt to install driver
if [ "${SCRIPT_MODE}" = "install" ]; then

	read -r -d '' DISPLAY_DRIVER_INSTALL_QUESTION << EOM
\Zb\ZuInstall display driver?\Zn

Do you want to install a display driver for a graphical user interface?

If so, you should think about which driver is the right one: https://github.com/goodtft/LCD-show
EOM

	dialog --clear \
		--colors \
		--title "Display driver" \
		--backtitle "$BACKTITLE" \
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

		if [[ "${CHOICE_DISPLAY_DRIVER}" = "None" || "${CHOICE_DISPLAY_DRIVER}" = "" ]]; then
			echo "No driver selected"
		else
			echo "Installing driver ${CHOICE_DISPLAY_DRIVER}"
			eval "sudo ./${CHOICE_DISPLAY_DRIVER}-show"
		fi
	fi

	clear
fi
