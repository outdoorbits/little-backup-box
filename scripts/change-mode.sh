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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

BACKTITLE="Little Backup Box"

OPTIONS=(1 "Source -> external storage"
    2 "Source -> internal storage"
    3 "Camera -> external storage"
    4 "Camera -> internal storage")

CHOICE=$(dialog --clear \
    --backtitle "$BACKTITLE" \
    --title "Backup Mode" \
    --menu "Select the desired backup mode:" \
    15 45 4 \
    "${OPTIONS[@]}" \
    2>&1 >/dev/tty)

clear

crontab -r

case $CHOICE in
1)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/backup.sh storage external >> /home/"$USER"/little-backup-box.log 2>&1"
    } | crontab
    ;;
2)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/backup.sh storage internal >> /home/"$USER"/little-backup-box.log 2>&1"
    } | crontab
    ;;
3)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/backup.sh camera external >> /home/"$USER"/little-backup-box.log 2>&1"
    } | crontab
    ;;
4)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/backup.sh camera internal >> /home/"$USER"/little-backup-box.log 2>&1"
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
