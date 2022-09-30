#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de

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

echo "Installing mejiro"

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

WEB_ROOT_MEJIRO="/var/www/mejiro"

sudo mkdir -p "${WEB_ROOT_MEJIRO}"

if [ "${SCRIPT_MODE}" = "update" ]; then
	sudo rm -R ${WEB_ROOT_MEJIRO}/*
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
		php-gd php-common

# Remove obsolete packages
sudo apt autoremove -y

# get files
sudo rm ./mejiro -R
git clone https://github.com/outdoorbits/mejiro.git

# move files in place
sudo cp -R ./mejiro/* ${WEB_ROOT_MEJIRO}

sudo sed -i 's/^$protect = .*/$protect = false; \/\/ Enable password protection/' "${WEB_ROOT_MEJIRO}/config.php"

# change owner and make scripts executable
sudo chown www-data:www-data "${WEB_ROOT_MEJIRO}" -R
sudo chmod +x ${WEB_ROOT_MEJIRO}/*.sh
sudo chmod +x ${WEB_ROOT_MEJIRO}/*.php

# Create Link to media
cd
sudo ln -s /media "${WEB_ROOT_MEJIRO}/photos"

#Finish
echo "mejiro will be available after reboot."
