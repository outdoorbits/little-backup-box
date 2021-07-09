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

# Update source and perform the full system upgrade
sudo apt update
sudo apt full-upgrade -y
sudo apt update

# Install the required packages
sudo apt install -y acl git-core screen rsync exfat-fuse exfat-utils ntfs-3g gphoto2 libimage-exiftool-perl dialog php-cli minidlna samba samba-common-bin vsftpd imagemagick curl dos2unix libimobiledevice6 ifuse

# Remove obsolete packages
sudo apt autoremove -y

#Install Rclone
curl https://rclone.org/install.sh | sudo bash

# Read user
USER="$1"
if [ -z "$USER" ]; then
    USER="pi"
fi

# Create the required directories
sudo mkdir -p /media/source
sudo mkdir -p /media/storage
sudo chown -R $USER:users /media/source
sudo chown -R $USER:users /media/storage
sudo chmod -R 775 /media/source
sudo chmod -R 775 /media/storage
sudo setfacl -Rdm g:$USER:rw /media/source
sudo setfacl -Rdm g:$USER:rw /media/storage

# Configure miniDLNA
sudo cp /etc/minidlna.conf /etc/minidlna.conf.orig
sudo sed -i 's|'media_dir=/var/lib/minidlna'|'media_dir=/media/storage'|' /etc/minidlna.conf
sudo sh -c "echo 'media_dir=/home/$USER/BACKUP' >> /etc/minidlna.conf"
sudo service minidlna start

# Clone and configure Little Backup Box
cd
git clone https://github.com/dmpop/little-backup-box.git
echo -e '\nBAK_DIR="/home/'$USER'/BACKUP" # Home directory path' >>little-backup-box/scripts/config.cfg
echo -e '\nMOUNT_IOS_DIR="/home/'$USER'/iOS" # Home directory path' >>little-backup-box/scripts/config.cfg
mkdir -p /home/$USER/BACKUP
chown $USER:users -R /home/$USER/BACKUP
chmod +x little-backup-box/scripts/*.sh

# Prompt to choose the default backup mode
BACKTITLE="Little Backup Box"
OPTIONS=(1 "Source backup"
    2 "Camera backup"
    3 "Internal backup")
CHOICE=$(dialog --clear \
    --backtitle "$BACKTITLE" \
    --title "Backup Mode" \
    --menu "Select the default backup mode:" \
    15 40 3 \
    "${OPTIONS[@]}" \
    2>&1 >/dev/tty)
clear
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
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/internal-backup.sh"
    } | crontab
    ;;
esac

crontab -l | {
    cat
    echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/restart-servers.sh"
} | crontab
crontab -l | {
    cat
    echo "@reboot /home/"$USER"/little-backup-box/scripts/ip.sh"
} | crontab

# Create web UI systemd unit
sudo sh -c "echo '[Unit]' > /etc/systemd/system/webui.service"
sudo sh -c "echo 'Description=web UI' >> /etc/systemd/system/webui.service"
sudo sh -c "echo '[Service]' >> /etc/systemd/system/webui.service"
sudo sh -c "echo 'Restart=always' >> /etc/systemd/system/webui.service"
sudo sh -c "echo 'ExecStart=/usr/bin/php -S 0.0.0.0:8000 -t /home/"$USER"/little-backup-box/scripts' >> /etc/systemd/system/webui.service"
sudo sh -c "echo 'ExecStop=/usr/bin/kill -HUP \$MAINPID' >> /etc/systemd/system/webui.service"
sudo sh -c "echo '[Install]' >> /etc/systemd/system/webui.service"
sudo sh -c "echo 'WantedBy=multi-user.target' >> /etc/systemd/system/webui.service"
sudo systemctl enable webui.service
sudo systemctl start webui.service

# Create File Browser systemd unit
curl -fsSL https://raw.githubusercontent.com/filebrowser/get/master/get.sh | bash
sudo sh -c "echo '[Unit]' > /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'Description=File Browser' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo '[Service]' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'Restart=always' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'ExecStart=/usr/local/bin/filebrowser -a 0.0.0.0 -r /media/storage' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'ExecStop=/usr/bin/kill -HUP \$MAINPID' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo '[Install]' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'WantedBy=multi-user.target' >> /etc/systemd/system/filebrowser.service"
sudo systemctl enable filebrowser.service
sudo systemctl start filebrowser.service

# Configure Samba
sudo cp /etc/samba/smb.conf /etc/samba/smb.conf.orig-$(date +%Y%m%d%H%M)
pw="raspberry"
(
    echo $pw
    echo $pw
) | sudo smbpasswd -s -a "$USER"
sudo sh -c "echo '### Global Settings ###' > /etc/samba/smb.conf"
sudo sh -c "echo '[global]' >> /etc/samba/smb.conf"
sudo sh -c "echo 'workgroup = WORKGROUP' >> /etc/samba/smb.conf"
sudo sh -c "echo 'wide links = yes' >> /etc/samba/smb.conf"
sudo sh -c "echo 'unix extensions = no' >> /etc/samba/smb.conf"
sudo sh -c "echo 'dns proxy = no' >> /etc/samba/smb.conf"
sudo sh -c "echo '### Debugging/Accounting ###' >> /etc/samba/smb.conf"
sudo sh -c "echo 'log file = /var/log/samba/log.%m' >> /etc/samba/smb.conf"
sudo sh -c "echo 'max log size = 1000' >> /etc/samba/smb.conf"
sudo sh -c "echo 'syslog = 0' >> /etc/samba/smb.conf"
sudo sh -c "echo 'panic action = /usr/share/samba/panic-action %d' >> /etc/samba/smb.conf"
sudo sh -c "echo '### Authentication ###' >> /etc/samba/smb.conf"
sudo sh -c "echo 'security = user' >> /etc/samba/smb.conf"
sudo sh -c "echo 'map to guest = Bad User' >> /etc/samba/smb.conf"
sudo sh -c "echo 'guest account = pi' >> /etc/samba/smb.conf"
sudo sh -c "echo '### Share Definitions ###' >> /etc/samba/smb.conf"
sudo sh -c "echo '[little-backup-box]' >> /etc/samba/smb.conf"
sudo sh -c "echo 'comment = Little Backup Box /media/storage' >> /etc/samba/smb.conf"
sudo sh -c "echo 'path = /media/storage' >> /etc/samba/smb.conf"
sudo sh -c "echo 'browseable = yes' >> /etc/samba/smb.conf"
sudo sh -c "echo 'force user = $USER' >> /etc/samba/smb.conf"
sudo sh -c "echo 'force group = $USER' >> /etc/samba/smb.conf"
sudo sh -c "echo 'admin users = $USER' >> /etc/samba/smb.conf"
sudo sh -c "echo 'writeable = yes' >> /etc/samba/smb.conf"
sudo sh -c "echo 'read only = no' >> /etc/samba/smb.conf"
sudo sh -c "echo 'guest ok = yes' >> /etc/samba/smb.conf"
sudo sh -c "echo 'create mask = 0777' >> /etc/samba/smb.conf"
sudo sh -c "echo 'directory mask = 0777' >> /etc/samba/smb.conf"

sudo sh -c "echo '[internal-backup]' >> /etc/samba/smb.conf"
sudo sh -c "echo 'comment = Little Backup Box internal backup' >> /etc/samba/smb.conf"
sudo sh -c "echo 'path = /home/$USER/BACKUP' >> /etc/samba/smb.conf"
sudo sh -c "echo 'browseable = yes' >> /etc/samba/smb.conf"
sudo sh -c "echo 'force user = $USER' >> /etc/samba/smb.conf"
sudo sh -c "echo 'force group = $USER' >> /etc/samba/smb.conf"
sudo sh -c "echo 'admin users = $USER' >> /etc/samba/smb.conf"
sudo sh -c "echo 'writeable = yes' >> /etc/samba/smb.conf"
sudo sh -c "echo 'read only = no' >> /etc/samba/smb.conf"
sudo sh -c "echo 'guest ok = yes' >> /etc/samba/smb.conf"
sudo sh -c "echo 'create mask = 0777' >> /etc/samba/smb.conf"
sudo sh -c "echo 'directory mask = 0777' >> /etc/samba/smb.conf"
sudo samba restart
cd

# Configure vsftpd
sudo sh -c "echo 'write_enable=YES' >> /etc/vsftpd.conf"
sudo service vsftpd restart

# Enable OLED screen support
dialog --clear \
    --title "Enable OLED support" \
    --backtitle "$BACKTITLE" \
    --yesno "Enable support for a 128x64 OLED display?" 7 60

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
    crontab -l | {
        cat
        echo "@reboot sudo /home/"$USER"/little-backup-box/scripts/start.sh"
    } | crontab
    echo -e 'DISP=true # Enable OLED display' >>little-backup-box/scripts/config.cfg
    dialog --clear \
        --title "Setup finished" \
        --backtitle "$BACKTITLE" \
        --infobox "\nAll done! The system will reboot now." 5 45
    sleep 3
    clear
    sudo reboot
    ;;
1)
    echo -e 'DISP=false # Enable OLED display' >>little-backup-box/scripts/config.cfg
    dialog --clear \
        --title "Setup finished" \
        --backtitle "$BACKTITLE" \
        --infobox "\nAll done! The system will reboot now." 5 45
    sleep 3
    clear
    sudo reboot
    ;;
255)
    echo -e 'DISP=false" # Enable OLED display' >>little-backup-box/scripts/config.cfg
    dialog --clear \
        --title "Setup finished" \
        --backtitle "$BACKTITLE" \
        --infobox "\nAll done! The system will reboot now." 5 45
    sleep 3
    clear
    sudo reboot
    ;;
esac
