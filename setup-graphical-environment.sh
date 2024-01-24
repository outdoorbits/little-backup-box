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

# settings
USER="lbb"

# Don't start as root
if [[ $EUID -eq 0 ]]; then
    echo "Run the script as a regular user"
    exit 1
fi

# Don't start setup if no graphical system installed
if [ ! -f "/usr/bin/startx" ]; then
	exit "No graphical system detected."
fi

# auto logon user lbb
## enable auto login
sudo raspi-config nonint do_boot_behaviour B4

## edit /etc/lightdm/lightdm.conf
CONFIG_FILE="/etc/lightdm/lightdm.conf"
VAR="autologin-user"
NEW_VALUE="lbb"

sudo sed -i "s/^\($VAR\s*=\s*\).*\$/\1$NEW_VALUE/" $CONFIG_FILE
sed $CONFIG_FILE -i -e "s/^\(#\|\)autologin-user=.*/autologin-user=$NEW_VALUE"

# disable auto mount for user
CONFIG_DIR="/home/$USER/.config/pcmanfm/LXDE-pi"
CONFIG_FILE="${CONFIG_DIR}/pcmanfm.conf"

mkdir -p $CONFIG_DIR
echo """[volume]
mount_on_startup=0
mount_removable=0
autorun=0
""" | sudo -u $USER tee $CONFIG_FILE

# auto start browser
AUTOSTART_USER_DIR="/home/$USER/.config/lxsession/LXDE-pi"

sudo -u $USER mkdir -p $AUTOSTART_USER_DIR
sudo -u $USER cp /etc/xdg/lxsession/LXDE-pi/autostart ${AUTOSTART_USER_DIR}/
echo "@/usr/bin/chromium-browser --kiosk --no-first-run http://localhost" | sudo -u $USER tee -a $AUTOSTART_USER_DIR/autostart
sudo chmod 700 $AUTOSTART_USER_DIR/autostart
