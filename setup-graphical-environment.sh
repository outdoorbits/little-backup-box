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

# settings
USER="lbb-desktop"

# create user
sudo useradd --create-home -s /bin/bash ${USER}


# Don't start as root
if [[ $EUID -eq 0 ]]; then
    echo "Run the script as a regular user"
    exit 1
fi

# Don't start setup if no graphical system installed
if [ ! -f "/usr/bin/startx" ]; then
	exit "No graphical system detected."
fi

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
Exec="firefox -setDefaultBrowser --kiosk http://localhost"
""" | sudo -u $USER tee $AUTOSTART_USER_DIR/little-backup-box.desktop

