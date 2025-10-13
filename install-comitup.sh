#!/usr/bin/env bash

# Author: Stefan Saam, stefan@saams.de

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

const_WEB_ROOT_LBB="/var/www/little-backup-box"

echo "Installing comitup..."

#clean download dir
if [ -d davesteele.github.io ]; then
	sudo rm -R davesteele.github.io
fi

#download installation file following meta http-equiv="refresh"
wget -r https://davesteele.github.io/comitup/latest/davesteele-comitup-apt-source_latest.html

sudo dpkg -i -R --force-all davesteele.github.io/comitup/deb

# update and upgrade
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		full-upgrade -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages

# Installing comitup*: install comitup
sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
			python3-cachetools \
			python3-networkmanager \
			python3-six \
			comitup

# Enable and start NetworkManager
sudo systemctl enable NetworkManager.service

# Installing comitup*: Allow NetworkManager to manage the wifi interfaces by removing references to them from /etc/network/interfaces
sudo rm /etc/network/interfaces

# Installing comitup*: Rename or delete /etc/wpa_supplicant/wpa_supplicant.conf
if [ -f "/etc/wpa_supplicant/wpa_supplicant.conf.old" ]; then
	sudo rm /etc/wpa_supplicant/wpa_supplicant.conf.old
fi
if [ -f "/etc/wpa_supplicant/wpa_supplicant.conf" ]; then
	sudo mv /etc/wpa_supplicant/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf.old
fi

# Installing comitup*: The systemd.resolved service should be disabled and masked to avoid contention for providing DNS service. EDIT - there is a reported alternate method to keep the service from interfering.
sudo systemctl mask dnsmasq.service
sudo systemctl mask systemd-resolved.service
sudo systemctl mask dhcpd.service
sudo systemctl mask dhcpcd.service
sudo systemctl mask wpa-supplicant.service

# create config
sudo python3 "${const_WEB_ROOT_LBB}/lib_comitup.py" --config

echo "All done. Connect to the little-backup-box-<nn> network and open http://10.41.0.1/"
echo "comitup will be available after reboot."


# *Installing comitup: https://github.com/davesteele/comitup/wiki/Installing-comitup
