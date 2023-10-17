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

# define DIRs
INSTALLER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ ! "${INSTALLER_DIR}" =~ "little-backup-box" ]]; then
    # in case it is called by regular install command (curl ...)
    INSTALLER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/little-backup-box"
fi

echo "Installing comitup..."

# Enable and start NetworkManager
sudo systemctl enable NetworkManager.service

# Installing comitup*: install comitup
sudo apt-get install comitup

# Installing comitup*: Allow NetworkManager to manage the wifi interfaces by removing references to them from /etc/network/interfaces
sudo rm /etc/network/interfaces
sudo systemctl mask dnsmasq.service

# Installing comitup*: Rename or delete /etc/wpa_supplicant/wpa_supplicant.conf
if [ -f "/etc/wpa_supplicant/wpa_supplicant.conf.old" ]; then
	sudo rm /etc/wpa_supplicant/wpa_supplicant.conf.old
fi
if [ -f "/etc/wpa_supplicant/wpa_supplicant.conf" ]; then
	sudo mv /etc/wpa_supplicant/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf.old
fi

# create config
sudo python3 "${INSTALLER_DIR}/scripts/lib_comitup.py"

echo "All done. Connect to the little-backup-box-<nn> network and open http://10.41.0.1/"
echo "comitup will be available after reboot."


# *Installing comitup: https://github.com/davesteele/comitup/wiki/Installing-comitup
