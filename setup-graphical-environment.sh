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

# modify /boot/firmware/config.txt
grep -qxF "include lbb-display.txt" /boot/firmware/config.txt || echo "include lbb-display.txt" | sudo tee -a /boot/firmware/config.txt

# create /boot/firmware/lbb-display.txt
sudo python3 /var/www/little-backup-box/create_display_config.py --driver "${conf_SCREEN_DRIVER}" --speed "${conf_SCREEN_SPEED}" --rotate "${conf_SCREEN_ROTATE}"

# create and activate /etc/udev/hwdb.d/61-ads7846-touch.hwdb
cat <<'EOF' | sudo -u "${USER}" tee /etc/udev/hwdb.d/61-ads7846-touch.hwdb >/dev/null
evdev:name:ADS7846 Touchscreen*:*
 LIBINPUT_MODEL_PRESSURE_PAD=1
 LIBINPUT_ATTR_PRESSURE_RANGE=10:255
 LIBINPUT_ATTR_TOUCH_SIZE_RANGE=1:1
 LIBINPUT_CALIBRATION_MATRIX=1.114044 0 -0.050625  0 -1.169666 1.080834  0 0 1
EOF

sudo systemd-hwdb update
sudo udevadm trigger -s input

# install wallpaper
BG_FILE="black.jpg"
BG_DIR="/usr/share/rpd-wallpaper"

sudo cp "$INSTALLER_DIR/scripts/img/$BG_FILE" "${BG_DIR}/"
sudo chown -R "root:root" "${BG_DIR}/${BG_FILE}"
sudo chmod -R 644 "${BG_DIR}/${BG_FILE}"

# activate wallpaper
sudo find /etc/xdg/pcmanfm/default -type f -name 'desktop-items-?.conf' -exec sed -i "s|^wallpaper=.*|wallpaper=${BG_DIR}/${BG_FILE}|" {} +

# define Firefox-profile
FF_DIR="/home/${USER}/.mozilla/firefox/kiosk.default"
sudo -u "${USER}" mkdir -p "${FF_DIR}"
cat <<EOF | sudo -u "${USER}" tee "${FF_DIR}/prefs.js" >/dev/null
user_pref("browser.shell.checkDefaultBrowser", false);
user_pref("gfx.webrender.software", true);
user_pref("layers.acceleration.disabled", true);
EOF

# adapt firefox scrollbar
sudo cp ./setup-firefox.sh /home/lbb-desktop/setup-firefox.sh
sudo chown lbb-desktop:lbb-desktop /home/lbb-desktop/setup-firefox.sh
sudo -u lbb-desktop /home/lbb-desktop/setup-firefox.sh

# set background and start browser in kiosk mode
sudo -u "${USER}" mkdir -p /home/${USER}/.config/labwc
cat <<'EOF' | sudo -u "${USER}" tee /home/${USER}/.config/labwc/autostart >/dev/null
#!/bin/bash

# 1. Identify all connected displays
# We look for 'connected' status but filter OUT anything named HDMI
DETECTOR=$(grep -l "connected" /sys/class/drm/card*-*/status | grep -v "HDMI")

# 2. Extract the card name
# Example: /sys/class/drm/card2-SPI-1/status -> card2
SPI_CARD_PATH=$(echo "$DETECTOR" | head -n 1)
SPI_CARD=$(echo "$SPI_CARD_PATH" | cut -d'/' -f5 | cut -d'-' -f1)

# 3. Fallback and Debug
# If no SPI card is found, we fall back to card1 (typical for Pi 5)
if [ -z "$SPI_CARD" ]; then
    echo "No SPI display detected via status, falling back to card1"
    SPI_CARD="card1"
else
    echo "Found SPI display on $SPI_CARD"
fi

# 4. Apply to Environment
export WLR_DRM_DEVICES=/dev/dri/$SPI_CARD:/dev/dri/card0
export MOZ_ENABLE_WAYLAND=1
export XDG_RUNTIME_DIR=/run/user/$(id -u)

# 5. Start Firefox
sleep 2
firefox-esr --profile "$HOME/.mozilla/firefox/kiosk.default" --kiosk --private-window http://localhost:8080 &
EOF

sudo chmod +x /home/${USER}/.config/labwc/autostart
sudo chown -R "${USER}:${USER}" /home/${USER}/.config/labwc
