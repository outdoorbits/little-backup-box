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
## INSTALLER_DIR

# Don't start as root
if [[ $EUID -eq 0 ]]; then
    echo "Run the script as a regular user"
    exit 1
fi

# Don't start setup if no graphical system installed
if [ ! -f "/usr/bin/startx" ]; then
	return
fi

# check if INSTALLER_DIR is set
if [[ ! -v INSTALLER_DIR ]]; then
	echo "INSTALLER_DIR is not defined."
	exit 1
fi

# settings
USER="lbb-desktop"

# create user
sudo useradd --create-home -s /bin/bash ${USER}

# auto logon ${USER}
## enable desktop and auto login
sudo raspi-config nonint do_boot_behaviour B4

## edit /etc/lightdm/lightdm.conf to set auto login user
CONFIG_FILE="/etc/lightdm/lightdm.conf"

VAR="autologin-user"
NEW_VALUE="${USER}"
sudo sed $CONFIG_FILE -i -e "s/^\(#\|\)${VAR}=.*/${VAR}=${NEW_VALUE}/"

# disable auto mount for user
sudo mkdir -p /etc/polkit-1/rules.d
sudo tee /etc/polkit-1/rules.d/10-no-automount-${USER}.rules >/dev/null <<EOF
polkit.addRule(function(action, subject) {
  if (subject.user == "${USER}" &&
      action.id.indexOf("org.freedesktop.udisks2.filesystem-mount") === 0) {
    return polkit.Result.NO;
  }
});
EOF

sudo service polkit restart

## legacy fallback to prevent auto mount
sudo -u ${USER} mkdir -p /home/${USER}/.config/gtk-3.0
cat <<EOF | sudo -u ${USER} tee /home/${USER}/.config/gtk-3.0/settings.ini
[Settings]
gtk-enable-auto-mount=false
gtk-enable-auto-mount-open=false
EOF

# install wallpaper
BG_FILE="black.jpg"
BG_DIR="/usr/share/rpd-wallpaper"

sudo cp "$INSTALLER_DIR/scripts/img/$BG_FILE" "${BG_DIR}/"
sudo chown -R "root:root" "${BG_DIR}/${BG_FILE}"
sudo chmod -R 644 "${BG_DIR}/${BG_FILE}"

# activate wallpaper
sudo find /etc/xdg/pcmanfm/default -type f -name 'desktop-items-?.conf' -exec sed -i "s|^wallpaper=.*|wallpaper=${BG_DIR}/${BG_FILE}|" {} +

# set background and start browser in kiosk mode
sudo -u "${USER}" mkdir -p /home/${USER}/.config/labwc
cat <<EOF | sudo -u "${USER}" tee /home/${USER}/.config/labwc/autostart >/dev/null
#!/bin/sh
# Labwc autostart script
sleep 1

# set background color
/usr/bin/swaybg -c '#000000' &

# start Firefox in kiosk mode
/usr/bin/firefox -setDefaultBrowser -private --kiosk http://localhost:8080 &
EOF

sudo chmod +x /home/${USER}/.config/labwc/autostart
sudo chown -R "${USER}:${USER}" /home/${USER}/.config/labwc
