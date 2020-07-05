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

CONFIG_DIR=$(dirname "$0")
CONFIG="${CONFIG_DIR}/config.cfg"
source "$CONFIG"

BACKTITLE="Little Backup Box"

OPTIONS=(1 "Card backup"
    2 "Camera backup"
    3 "Internal backup")

CHOICE=$(dialog --clear \
    --backtitle "$BACKTITLE" \
    --title "Backup Mode" \
    --menu "Select the desired backup mode:" \
    15 40 3 \
    "${OPTIONS[@]}" \
    2>&1 >/dev/tty)

clear

crontab -r

case $CHOICE in
1)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/card-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"
    } | crontab
    ;;
2)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/camera-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"
    } | crontab
    ;;
3)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/internal-backup.sh >> /home/"$USER"/little-backup-box.log 2>&1"
    } | crontab
    ;;
esac

crontab -l | {
    cat
    echo "@reboot cd /home/"$USER"/little-backup-box/scripts && sudo php -S 0.0.0.0:80"
} | crontab
crontab -l | {
    cat
    echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/restart-servers.sh"
} | crontab
crontab -l | {
    cat
    echo "*/3 * * * * sudo /home/"$USER"/little-backup-box/scripts/ip.sh"
} | crontab

if [ $DISP = true ]; then
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/start.sh"
    } | crontab
fi

dialog --clear \
    --title "Change Backup Mode" \
    --backtitle "$BACKTITLE" \
    --msgbox "All done! Press OK to reboot Little Backup Box." 15 30
clear

sudo reboot
