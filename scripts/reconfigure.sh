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

WORKING_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
CONFIG="${WORKING_DIR}/config.cfg"
dos2unix "$CONFIG"
source "$CONFIG"

BACKTITLE="Little Backup Box"

# Set the default backup mode
OPTIONS=(1 "Source backup"
    2 "Camera backup"
    3 "iOS backup")

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
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/source-backup.sh"
    } | crontab
    ;;
2)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/camera-backup.sh"
    } | crontab
    ;;
3)
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/ios-backup.sh"
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
    echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/ip.sh"
} | crontab

if [ $DISP = true ]; then
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/start.sh"
    } | crontab
fi

# Enable LCD screen support
dialog --clear \
    --title "Enable LCD support" \
    --backtitle "$BACKTITLE" \
    --yesno "Enable support for a 128x64 LCD display?" 7 60

response=$?
case $response in
0)
    clear
    sudo apt install -y wiringpi i2c-tools
    git clone https://github.com/dmpop/ssd1306_rpi.git
    cd ssd1306_rpi
    cc -o oled oled.c fontx.c -lwiringPi -lpthread -DI2C
    sudo cp oled /usr/local/bin/
    sudo chown root:root /usr/local/bin/oled
    sudo chmod 755 /usr/local/bin/oled
    sudo raspi-config nonint do_i2c 0
    cd
    echo -e 'DISP=true # Enable LCD display' >>little-backup-box/scripts/config.cfg
    ;;
1)
    echo -e 'DISP=false # Enable LCD display' >>little-backup-box/scripts/config.cfg
    ;;
255)
    :
    ;;
esac

# Enable LCD screen support
dialog --clear \
    --title "Enable Tomodachi" \
    --backtitle "$BACKTITLE" \
    --yesno "Enable the Tomodachi board?" 7 60

response=$?
case $response in
0)
    clear
    sudo bash -c 'echo "enable_uart=1" >> "/boot/config.txt"'
    sudo bash -c 'echo "dtparam=act_led_gpio=11" >> "/boot/config.txt"'
    ;;
1)
    :
    ;;
255)
    :
    ;;
esac

dialog --clear \
    --title "Reboot" \
    --msgbox "All done! Press OK to reboot the system." 5 45
clear
sudo reboot
