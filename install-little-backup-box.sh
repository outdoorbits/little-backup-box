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
sudo apt install acl git-core screen rsync exfat-fuse exfat-utils ntfs-3g gphoto2 libimage-exiftool-perl dialog python-pip -y
sudo pip install bottle

sudo mkdir /media/card
sudo mkdir /media/storage
sudo chown -R pi:pi /media/storage
sudo chmod -R 775 /media/storage
sudo setfacl -Rdm g:pi:rw /media/storage

cd
git clone https://github.com/dmpop/little-backup-box.git

HEIGHT=15
WIDTH=40
CHOICE_HEIGHT=4
BACKTITLE="Little Backup Box"
TITLE="Backup mode"
MENU="Select the desired backup mode:"

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
            crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/card-backup.sh"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/camera-backup.sh"; } | crontab
	    crontab -l | { cat; echo "@reboot sudo /home/pi/little-backup-box/rc/rc.py"; } | crontab
            ;;
        2)
            crontab -l | { cat; echo "@reboot sudo /home/pi/little-backup-box/scripts/card-backup.sh"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/camera-backup.sh"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/rc/rc.py"; } | crontab
            ;;
        3)
            crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/scripts/card-backup.sh"; } | crontab
	    crontab -l | { cat; echo "@reboot sudo /home/pi/little-backup-box/scripts/camera-backup.sh"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/pi/little-backup-box/rc/rc.py"; } | crontab
            ;;
esac

echo "------------------------"
echo "All done! Please reboot."
echo "------------------------"
