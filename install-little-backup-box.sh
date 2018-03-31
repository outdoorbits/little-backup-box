#!/usr/bin/env bash

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

sudo apt update
sudo apt dist-upgrade -y
sudo apt install acl git-core screen rsync exfat-fuse exfat-utils ntfs-3g gphoto2 libimage-exiftool-perl dialog python3-pip -y
sudo pip3 install bottle

sudo mkdir /media/card
sudo mkdir /media/storage
sudo chown -R pi:pi /media/storage
sudo chmod -R 775 /media/storage
sudo setfacl -Rdm g:pi:rw /media/storage

cd
git clone https://github.com/dmpop/little-backup-box.git
cd little-backup-box/fonts
sudo cp -R . /home/pi/.fonts
cd

HEIGHT=15
WIDTH=40
CHOICE_HEIGHT=4
BACKTITLE="Little Backup Box"
TITLE="Backup mode"
MENU="Select the default backup mode:"

OPTIONS=(1 "Remote control"
         2 "Card backup"
         3 "Camera backup")

CHOICE=$(dialog --clear \
                --backtitle "$BACKTITLE" \
                --title "$TITLE" \
                --menu "$MENU" \
                $HEIGHT $WIDTH $CHOICE_HEIGHT \
                "${OPTIONS[@]}" \
                2>&1 >/dev/tty)

clear
case $CHOICE in
        1)
            crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/card-backup.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/camera-backup.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "@reboot sudo /home/pi/little-backup-box/scripts/keep-rc-alive.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
            ;;
        2)
            crontab -l | { cat; echo "@reboot sudo /home/pi/little-backup-box/scripts/card-backup.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/camera-backup.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/keep-rc-alive.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
            ;;
        3)
            crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/card-backup.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "@reboot sudo /home/pi/little-backup-box/scripts/camera-backup.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/keep-rc-alive.sh >> /home/pi/little-backup-box.log 2>&1"; } | crontab
            ;;
esac

echo "---------------------------------------------"
echo "All done! The system will reboot in 1 minute."
echo "---------------------------------------------"

sudo shutdown -r 1
