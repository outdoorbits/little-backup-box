#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com

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

# Don't start as root
if [[ $EUID -eq 0 ]]; then
    echo "Run the script as a regular user"
    exit 1
fi

CONFIG_DIR=$(dirname "$0")
CONFIG="${CONFIG_DIR}/config.cfg"
dos2unix "$CONFIG"
source "$CONFIG"

# Update source and perform the full system upgrade
sudo apt update
sudo apt full-upgrade -y
sudo apt update

# Install the required packages
sudo apt install -y php-cli php-gd php-common php-imagick

# Remove obsolete packages
sudo apt autoremove -y

# Read user
USER="$(whoami)"
if [ -z "$USER" ]; then
    USER="pi"
fi

cd
git clone https://github.com/dmpop/mejiro.git

# Create symlink
cd
sudo ln -s /media "mejiro/photos"

# Add a cronjob
crontab -l | {
    cat
    echo "@reboot cd && cd mejiro && sudo php -S 0.0.0.0:8081"
} | crontab

# Reboot
dialog --clear \
    --title "Reboot" \
    --infobox "All done! Press OK to reboot the system." 5 45
clear
sudo reboot
