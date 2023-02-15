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

# Arguments
conf_PASSWORD="${1}"

# create config
sudo echo "ap_name: little-backup-box-<nnnn>" | sudo tee "/etc/comitup.conf"
sudo echo "web_service: apache2.service" | sudo tee -a "/etc/comitup.conf"
sudo echo "external_callback: /var/www/little-backup-box/handle_port_80.sh" | sudo tee -a "/etc/comitup.conf"


if [ ${#conf_PASSWORD} -ge 8 ] && [ ${#conf_PASSWORD} -le 63 ]; then
	sudo echo "ap_password: ${conf_PASSWORD}" | sudo tee -a "/etc/comitup.conf"
fi
