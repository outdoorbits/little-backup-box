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
## SETUP

# Don't start as root
if [[ $EUID -eq 0 ]]; then
    echo "Run the script as a regular user"
    exit 1
fi

# Don't start setup if no graphical system installed
if [ ! -f "/usr/sbin/lightdm" ]; then
	return
fi

# check if INSTALLER_DIR is set
if [[ -z INSTALLER_DIR ]]; then
	echo "INSTALLER_DIR is not defined."
	exit 1
fi

# install dependencies
sudo DEBIAN_FRONTEND=noninteractive \
	apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
			swaybg \
			firefox-esr

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

# activate rules
sudo service polkit restart

## legacy fallback to prevent auto mount
sudo -u ${USER} mkdir -p /home/${USER}/.config/gtk-3.0
cat <<EOF | sudo -u ${USER} tee /home/${USER}/.config/gtk-3.0/settings.ini
[Settings]
gtk-enable-auto-mount=false
gtk-enable-auto-mount-open=false
EOF

# setup display and touchscreen
sudo python3 /var/www/little-backup-box/create_screen_config.py

# modify /boot/firmware/config.txt (include lbb-display.txt)
grep -qxF "include lbb-display.txt" /boot/firmware/config.txt || echo "include lbb-display.txt" | sudo tee -a /boot/firmware/config.txt

# install wallpaper
BG_FILE="black.jpg"
BG_DIR="/usr/share/rpd-wallpaper"

echo "const_WEB_ROOT_LBB: $const_WEB_ROOT_LBB"
sudo cp "$const_WEB_ROOT_LBB/img/$BG_FILE" "${BG_DIR}/"
sudo chown -R "root:root" "${BG_DIR}/${BG_FILE}"
sudo chmod -R 644 "${BG_DIR}/${BG_FILE}"

# activate wallpaper
sudo find /etc/xdg/pcmanfm/default -type f -name 'desktop-items-?.conf' -exec sed -i "s|^wallpaper=.*|wallpaper=${BG_DIR}/${BG_FILE}|" {} +

# adapt firefox
$INSTALLER_DIR/setup-firefox.sh

# set background and start browser in kiosk mode
URL="http://localhost:8080"
sudo -u "${USER}" mkdir -p /home/${USER}/.config/labwc
cat <<EOF | sudo -u "${USER}" tee /home/${USER}/.config/labwc/autostart >/dev/null
#!/bin/bash

# 1. Identify all connected displays
# We look for 'connected' status but filter OUT anything named HDMI
DETECTOR=\$(grep -l "connected" /sys/class/drm/card*-*/status | grep -v "HDMI")

# 2. Extract the card name
# Example: /sys/class/drm/card2-SPI-1/status -> card2
SPI_CARD_PATH=\$(echo "$DETECTOR" | head -n 1)
SPI_CARD=\$(echo "\$SPI_CARD_PATH" | cut -d'/' -f5 | cut -d'-' -f1)

# 3. Fallback and Debug
# If no SPI card is found, we fall back to card1 (typical for Pi 5)
if [ -z "\$SPI_CARD" ]; then
    echo "No SPI display detected via status, falling back to card1"
    SPI_CARD="card1"
else
    echo "Found SPI display on \$SPI_CARD"
fi

# 4. Apply to Environment
export WLR_DRM_DEVICES=/dev/dri/\$SPI_CARD:/dev/dri/card0
export MOZ_ENABLE_WAYLAND=1
export XDG_RUNTIME_DIR=/run/user/\$(id -u)

# 5. Start Firefox
## wait for server
for i in \$(seq 1 60); do
	curl -fsS "$URL" >/dev/null && break
	sleep 1
done

sleep 1
firefox-esr --profile "/home/lbb-desktop/.mozilla/firefox-esr/kiosk.default" --kiosk --private-window $URL &
EOF

sudo chmod +x /home/${USER}/.config/labwc/autostart
sudo chown -R "${USER}:${USER}" /home/${USER}/.config/labwc
