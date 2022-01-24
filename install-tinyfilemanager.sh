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
sudo apt full-upgrade -y
sudo apt update

# Install the required packages
sudo apt install -y zip php-zip php-mbstring

# Remove obsolete packages
sudo apt autoremove -y

# get files
sudo rm ./tinyfilemanager -R
git clone https://github.com/prasathmani/tinyfilemanager.git

# move files in place
sudo cp -R ./tinyfilemanager/*.php ${WEB_ROOT_TINYFILEMANAGER}
sudo cp -R ./tinyfilemanager/*.json ${WEB_ROOT_TINYFILEMANAGER}

sudo mv "${WEB_ROOT_TINYFILEMANAGER}/tinyfilemanager.php" "${WEB_ROOT_TINYFILEMANAGER}/index.php"
sudo cp "${WEB_ROOT_TINYFILEMANAGER}/config-sample.php" "${WEB_ROOT_TINYFILEMANAGER}/config.php"

sudo ln -s "/media" "${WEB_ROOT_TINYFILEMANAGER}/media"

# setup
sudo sed -i 's/^$use_auth = .*/$use_auth = false;/' "${WEB_ROOT_TINYFILEMANAGER}/config.php"
sudo sed -i 's/^$root_path = .*/$root_path = "\/var\/www\/tinyfilemanager\/media";/' "${WEB_ROOT_TINYFILEMANAGER}/config.php"
sudo sed -i 's/^$root_url = .*/$root_url = "files\/media";/' "${WEB_ROOT_TINYFILEMANAGER}/config.php"
sudo sed -i 's/^$max_upload_size_bytes = .*/$max_upload_size_bytes = 1048576000;/' "${WEB_ROOT_TINYFILEMANAGER}/config.php"

# change owner and make scripts executable
sudo chown www-data:www-data "${WEB_ROOT_TINYFILEMANAGER}" -R
sudo chmod +x ${WEB_ROOT_TINYFILEMANAGER}/*.php

#Finish
echo "tinyfilemanager ist available after reboot."
