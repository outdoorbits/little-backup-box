#!/usr/bin/env bash

# Author: Dmitri Popov, dmpop@linux.com
# License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt

if [ -z "$1" ] && [ -z "$2" ]; then
    echo "ERROR: Specify image file and device name"
    echo "E.g.: $0 /path/to/foo.img /dev/sdb"
    exit 1
fi

echo "Writing the image file..."
echo
sudo dd if="$1" of="$2" bs=1M status=progress

sudo mount "$2"1 /mnt
sudo touch /mnt/ssh
sudo umount /mnt

sudo mount "$2"2 /mnt
sudo mv /mnt/etc/wpa_supplicant/wpa_supplicant.conf /mnt/etc/wpa_supplicant/wpa_supplicant.conf.bak

echo "Enter the name of the wireless network: "
read SSID
echo "Enter the wireless network password: "
read -s PASS
cat >$HOME/wpa_supplicant.conf <<EOF
ctrl_interface=DIR=/var/run/wpa_supplicant GROUP=netdev
update_config=1

network={
	ssid="$SSID"
	psk="$PASS"
	proto=RSN
	key_mgmt=WPA-PSK
	pairwise=CCMP
	auth_alg=OPEN
}
EOF
sudo mv $HOME/wpa_supplicant.conf /mnt/etc/wpa_supplicant/wpa_supplicant.conf
sudo umount /mnt
echo "All done!"
