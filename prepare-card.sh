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

MOUNT_POINT="/mnt/tmp"

if [ -z "$1" ] && [ -z "$2" ]; then
	echo "ERROR: Specify image file and device name"
	echo "E.g.: $0 /path/to/IMAGE.img /dev/sdb"
	exit 1
fi

echo "Writing the image file..."
echo
sudo dd if="$1" of="$2" bs=1M status=progress

sudo mkdir -p $MOUNT_POINT
sudo mount "$2"1 $MOUNT_POINT
sudo touch $MOUNT_POINT/ssh

echo "Enter your country code (e.g., DE): "
read COUNTRY
echo "Enter the name of the wireless network: "
read SSID
echo "Enter the wireless network password: "
read -s PASS
cat >$HOME/wpa_supplicant.conf <<EOF
country=$COUNTRY
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1

network={
	ssid="$SSID"
	psk="$PASS"
	proto=RSN
	key_mgmt=WPA-PSK
}
EOF
sudo mv $HOME/wpa_supplicant.conf $MOUNT_POINT/wpa_supplicant.conf
sudo umount $MOUNT_POINT
sudo rm -rf $MOUNT_POINT
echo "All done!"
