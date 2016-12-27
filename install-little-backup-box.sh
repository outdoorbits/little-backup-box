#!/usr/bin/env bash

sudo apt update && sudo apt dist-upgrade -y && sudo apt install rsync exfat-fuse exfat-utils ntfs-3g minidlna gphoto2 -y

sudo mkdir /media/card
sudo mkdir /media/storage
sudo chown -R pi:pi /media/storage
sudo chmod -R 775 /media/storage
sudo setfacl -Rdm g:pi:rw /media/storage

crontab -l | { cat; echo "@reboot sudo /home/pi/little-backup-box/backup.sh"; } | crontab

sudo sed -i 's|'media_dir=/var/lib/minidlna'|'media_dir=/media/storage'|' /etc/minidlna.conf
sudo service minidlna start

echo "------------------------"
echo "All done! Please reboot."
echo "------------------------"
