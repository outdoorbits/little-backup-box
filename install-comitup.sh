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

if [ -f "/etc/wpa_supplicant/wpa_supplicant.conf.old" ]; then
	sudo rm /etc/wpa_supplicant/wpa_supplicant.conf.old
fi
if [ -f "/etc/wpa_supplicant/wpa_supplicant.conf" ]; then
	sudo mv /etc/wpa_supplicant/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf.old
fi

sudo apt install -y comitup

echo "ap_name: little-backup-box-<nnnn>" | sudo tee "/etc/comitup.conf"
echo "web_service: apache2.service" | sudo tee -a "/etc/comitup.conf"
echo "external_callback: /var/www/little-backup-box/handle_port_80.sh" | sudo tee -a "/etc/comitup.conf"

echo "All done. Connect to the little-backup-box-<nn> network and open http://10.41.0.1/"
echo "comitup ist available after reboot."
