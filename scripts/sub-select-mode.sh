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

# sub expects from calling script:
# - $WORKING_DIR

# Definitions
BACKTITLE="Little Backup Box"
DISP=false

# Enable OLED screen support
dialog --clear \
    --title "Enable OLED support" \
    --backtitle "$BACKTITLE" \
    --yesno "Enable support for a 128x64 OLED display?" 7 60

CHOICE=$?

clear

case $CHOICE in
0)
    DISP=true
    sudo apt install -y wiringpi i2c-tools
    git clone https://github.com/dmpop/ssd1306_rpi.git
    cd ssd1306_rpi
    cc -o oled oled.c fontx.c -lwiringPi -lpthread -DI2C
    sudo cp oled /usr/local/bin/
    sudo chown root:root /usr/local/bin/oled
    sudo chmod 755 /usr/local/bin/oled
    sudo raspi-config nonint do_i2c 0
    echo -e 'DISP=true # Enable OLED display' >> "${WORKING_DIR}/config.cfg"
    ;;
1)
    echo -e 'DISP=false # Enable OLED display' >> "${WORKING_DIR}/config.cfg"
    ;;
255)
    echo -e 'DISP=false" # Enable OLED display' >> "${WORKING_DIR}/config.cfg"
    ;;
esac

# Prompt to choose the default backup mode
OPTIONS=(1 "Source -> external storage"
    2 "Source -> internal storage"
    3 "Camera -> external storage"
    4 "Camera -> internal storage"
    5 "iOS -> external storage"
    6 "iOS -> internal storage")

CHOICE=$(dialog --clear \
    --backtitle "$BACKTITLE" \
    --title "Backup Mode" \
    --menu "Select the default backup mode:" \
    15 50 6 \
    "${OPTIONS[@]}" \
    2>&1 >/dev/tty)

clear

# remove all from crontab
crontab -r

# write new crontab
case $CHOICE in
1)
    crontab -l | {
        cat
        echo "@reboot sudo ${WORKING_DIR}/backup.sh storage external >/dev/null 2>&1"
    } | crontab
    ;;
2)
    crontab -l | {
        cat
        echo "@reboot sudo ${WORKING_DIR}/backup.sh storage internal >/dev/null 2>&11"
    } | crontab
    ;;
3)
    crontab -l | {
        cat
        echo "@reboot sudo ${WORKING_DIR}/backup.sh camera external >/dev/null 2>&1"
    } | crontab
    ;;
4)
    crontab -l | {
        cat
        echo "@reboot sudo ${WORKING_DIR}/backup.sh camera internal >/dev/null 2>&1"
    } | crontab
    ;;
5)
    crontab -l | {
        cat
        echo "@reboot sudo ${WORKING_DIR}/backup.sh ios external >/dev/null 2>&1"
    } | crontab
    ;;
6)
    crontab -l | {
        cat
        echo "@reboot sudo ${WORKING_DIR}/backup.sh ios internal >/dev/null 2>&1"
    } | crontab
    ;;
esac

# # clear

# write basic crontab
crontab -l | {
    cat
    echo "@reboot sudo ${WORKING_DIR}/start.sh"
} | crontab

crontab -l | {
    cat
    echo "@reboot sudo ${WORKING_DIR}/restart-servers.sh"
} | crontab

crontab -l | {
    cat
    echo "*/1 * * * * sudo ${WORKING_DIR}/ip.sh"
} | crontab

if [ -f "${WORKING_DIR}/../../mejiro/index.php" ]; then
    crontab -l | {
        cat
        echo "@reboot cd && cd mejiro && sudo php -S 0.0.0.0:8081"
    } | crontab
fi
