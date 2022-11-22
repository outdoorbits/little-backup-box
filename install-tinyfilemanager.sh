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

# uses SCRIPT_MODE from parent script (if existing)

echo "Installing tinyfilemanager..."

# Don't start as root
if [[ $EUID -eq 0 ]]; then
   echo "Run the script as a regular user"
   exit 1
fi

# change into actual user-dir
cd

# define DIRs
INSTALLER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ ! "${INSTALLER_DIR}" =~ "little-backup-box" ]]; then
    # in case it is called by regular install command (curl ...)
    INSTALLER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/little-backup-box"
fi

WEB_ROOT_TINYFILEMANAGER="/var/www/tinyfilemanager"

sudo mkdir -p "${WEB_ROOT_TINYFILEMANAGER}"

if [ "${SCRIPT_MODE}" = "update" ]; then
	sudo rm -R ${WEB_ROOT_TINYFILEMANAGER}/*
fi

# Update source and perform the full system upgrade
sudo apt update
sudo DEBIAN_FRONTEND=noninteractive \
		apt \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		full-upgrade -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages

# Install the required packages
sudo DEBIAN_FRONTEND=noninteractive \
		apt \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
		zip php-zip php-mbstring

# Remove obsolete packages
sudo apt autoremove -y

# get files
sudo rm ./tinyfilemanager -R
git clone https://github.com/prasathmani/tinyfilemanager.git

# backup config-file
if [ -f "${WEB_ROOT_TINYFILEMANAGER}/config.php" ]; then
	sudo cp "${WEB_ROOT_TINYFILEMANAGER}/config.php" "./tinyfilemanager/"
fi

# move files in place
sudo cp -R ./tinyfilemanager/*.php "${WEB_ROOT_TINYFILEMANAGER}"
sudo cp -R ./tinyfilemanager/*.json "${WEB_ROOT_TINYFILEMANAGER}"

sudo mv "${WEB_ROOT_TINYFILEMANAGER}/tinyfilemanager.php" "${WEB_ROOT_TINYFILEMANAGER}/index.php"

# setup
if [ -f "./tinyfilemanager/config.php" ]; then
	sudo cp "./tinyfilemanager/config.php" "${WEB_ROOT_TINYFILEMANAGER}/"
else
	echo "<?php" | sudo tee "${WEB_ROOT_TINYFILEMANAGER}/config.php"
	echo "   \$use_auth = false;" | sudo tee -a "${WEB_ROOT_TINYFILEMANAGER}/config.php"
	echo "   \$root_path = \"\/var\/www\/tinyfilemanager\/media\";" | sudo tee -a "${WEB_ROOT_TINYFILEMANAGER}/config.php"
	echo "   \$root_url = \"files\/media\";" | sudo tee -a "${WEB_ROOT_TINYFILEMANAGER}/config.php"
	echo "   \$max_upload_size_bytes = 1048576000;" | sudo tee -a "${WEB_ROOT_TINYFILEMANAGER}/config.php"
	echo "?>" | sudo tee -a "${WEB_ROOT_TINYFILEMANAGER}/config.php"
fi

sudo ln -s "/media" "${WEB_ROOT_TINYFILEMANAGER}/media"

# change owner and make scripts executable
sudo chown www-data:www-data "${WEB_ROOT_TINYFILEMANAGER}" -R
sudo chmod +x ${WEB_ROOT_TINYFILEMANAGER}/*.php

#Finish
echo "tinyfilemanager will be available after reboot."
