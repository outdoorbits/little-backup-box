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

echo "----------------------"
echo "Updating the system..."
echo "----------------------"

sudo apt update
sudo apt dist-upgrade -y
sudo apt update

echo "-----------------------------------"
echo "Installing the required packages..."
echo "-----------------------------------"

sudo apt install acl git-core screen rsync exfat-fuse exfat-utils ntfs-3g gphoto2 libimage-exiftool-perl dialog php minidlna -y

USER="$1"

if [ -z "$USER" ]; then
    USER="pi"
    fi

echo "------------------------------------"
echo "Creating the required directories..."
echo "------------------------------------"

sudo mkdir /media/card
sudo mkdir /media/storage
sudo chown -R $USER:users /media/storage
sudo chmod -R 775 /media/storage
sudo setfacl -Rdm g:$USER:rw /media/storage

sudo sed -i 's|'media_dir=/var/lib/minidlna'|'media_dir=/media/storage'|' /etc/minidlna.conf
sudo service minidlna start

echo "-----------------------------"
echo "Fetching Little Backup Box..."
echo "-----------------------------"

cd
git clone https://github.com/dmpop/little-backup-box.git

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
            crontab -l | { cat; echo "#@reboot sudo /home/"$USER"/little-backup-box/scripts/card-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/"$USER"/little-backup-box/scripts/camera-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "@reboot cd /home/"$USER"/little-backup-box/scripts && sudo php -S 0.0.0.0:8000"; } | crontab
            ;;
        2)
            crontab -l | { cat; echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/card-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "#@reboot sudo /home/"$USER"/little-backup-box/scripts/camera-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "#@reboot cd /home/"$USER"/little-backup-box/scripts && sudo php -S 0.0.0.0:8000"; } | crontab
            ;;
        3)
            crontab -l | { cat; echo "#@reboot sudo /home/"$USER"/little-backup-box/scripts/card-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/camera-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"; } | crontab
	    crontab -l | { cat; echo "#@reboot cd /home/"$USER"/little-backup-box/scripts && sudo php -S 0.0.0.0:8000"; } | crontab
            ;;
esac

echo "---------------------------------------------"
echo "All done! The system will reboot in 1 minute."
echo "---------------------------------------------"

sudo shutdown -r 1
