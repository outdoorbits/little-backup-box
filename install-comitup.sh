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

# print installation hint
echo "********************************************************************************************"
echo "*** If you are connected to your raspberry pi by wlan, your wlan-connection may break now."
echo "***"
echo "*** DON'T WORRY! The installation will finish seconds later and your raspberry pi reboots."
echo "***"
echo "*** What's going on?"
echo "*** Your raspberry pis wlan-configuration is removed by comitup now."
echo "*** Because comitup has no access-data for your wlan yet, it can't connect to it. So it will"
echo "*** start as a wlan-hotspot, ssid=little-backup-box-nnnn (nnnn is a random number)."
echo "*** Please connect your mobile or your notebook to this wlan."
echo "***"
echo "*** If you want to configure comitup now, navigate to http://10.41.0.1 (http!)"
echo "***"
echo "*** You can reach the web UI of Little backup Box as follows:"
echo "***"
echo "*** https://10.41.0.1 (secure, certificate cannot be verified automatically, please confirm it)"
echo "*** http://10.41.0.1:8000 (insecure)"
echo "***"
echo "*** Please use the settings of the web UI to optimally adapt the Little Backup Box for you."
echo "***"
echo "*** If you have further questions, please check the wiki first:"
echo "*** https://github.com/outdoorbits/little-backup-box/wiki"
echo "***"
echo "*** We are always happy to receive your feedback"
echo "********************************************************************************************"


# create config
echo "ap_name: little-backup-box-<nnnn>" | sudo tee "/etc/comitup.conf"
echo "web_service: apache2.service" | sudo tee -a "/etc/comitup.conf"
echo "external_callback: /var/www/little-backup-box/handle_port_80.sh" | sudo tee -a "/etc/comitup.conf"

# install comitup
sudo DEBIAN_FRONTEND=noninteractive \
		apt \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
		comitup

echo "All done. Connect to the little-backup-box-<nn> network and open http://10.41.0.1/"
echo "comitup ist available after reboot."
