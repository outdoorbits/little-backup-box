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
USER="$(whoami)"
if [ -z "$USER" ]; then
    USER="pi"
fi

# Create the required directories
SOURCE_MOUNT_POINT="/media/source"
STORAGE_MOUNT_POINT="/media/storage"
INTERAL_BACKUP_DIR="/media/internal"
IOS_MOUNT_POINT="/media/iOS"

sudo mkdir -p "${SOURCE_MOUNT_POINT}"
sudo mkdir -p "${STORAGE_MOUNT_POINT}"
sudo mkdir -p "${INTERAL_BACKUP_DIR}"
sudo mkdir -p "${IOS_MOUNT_POINT}"

sudo chown -R $USER:users "${SOURCE_MOUNT_POINT}"
sudo chown -R $USER:users "${STORAGE_MOUNT_POINT}"
sudo chown -R $USER:users "${INTERAL_BACKUP_DIR}"
sudo chown -R $USER:users "${IOS_MOUNT_POINT}"

sudo chmod -R 775 "${SOURCE_MOUNT_POINT}"
sudo chmod -R 775 "${STORAGE_MOUNT_POINT}"
sudo chmod -R 775 "${INTERAL_BACKUP_DIR}"
sudo chmod -R 775 "${IOS_MOUNT_POINT}"

sudo setfacl -Rdm g:$USER:rw "${SOURCE_MOUNT_POINT}"
sudo setfacl -Rdm g:$USER:rw "${STORAGE_MOUNT_POINT}"
sudo setfacl -Rdm g:$USER:rw "${INTERAL_BACKUP_DIR}"
sudo setfacl -Rdm g:$USER:rw "${IOS_MOUNT_POINT}"

# Configure miniDLNA
sudo cp /etc/minidlna.conf /etc/minidlna.conf.orig
sudo sed -i 's|'media_dir=/var/lib/minidlna'|'media_dir="${STORAGE_MOUNT_POINT}"'|' /etc/minidlna.conf
sudo sed -i 's/^#friendly_name=.*/friendly_name=little-backup-box/' /etc/minidlna.conf
sudo sh -c "echo 'media_dir=${INTERAL_BACKUP_DIR}' >> /etc/minidlna.conf"
sudo service minidlna start

# Clone and configure Little Backup Box
cd
git clone https://github.com/dmpop/little-backup-box.git
cd little-backup-box

WORKING_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/scripts"
CONFIG="${WORKING_DIR}/config.cfg"

if [ -f "${WORKING_DIR}/config.cfg" ]; then
    mv "${WORKING_DIR}/config.cfg" "${WORKING_DIR}/config.cfg.bak"
fi

# remove mount points from config.cfg
sed -i '/SOURCE_MOUNT_POINT/d' "${CONFIG}"
sed -i '/STORAGE_MOUNT_POINT/d' "${CONFIG}"
sed -i '/INTERAL_BACKUP_DIR/d' "${CONFIG}"
sed -i '/IOS_MOUNT_POINT/d' "${CONFIG}"

# add mount points to config.cfg
echo -e 'SOURCE_MOUNT_POINT="'${SOURCE_MOUNT_POINT}'" # Mount point of the source device' >>"${CONFIG}"
echo -e 'STORAGE_MOUNT_POINT="'${STORAGE_MOUNT_POINT}'" # Mount point of the storage device' >>"${CONFIG}"
echo -e 'INTERAL_BACKUP_DIR="'${INTERAL_BACKUP_DIR}'" # Internal backup directory path' >>"${CONFIG}"
echo -e 'IOS_MOUNT_POINT="'${IOS_MOUNT_POINT}'" # Mount point of the iOS device' >>"${CONFIG}"

chmod +x ${WORKING_DIR}/*.sh

# Choose display-setup, default backup mode, set crontab
source "${WORKING_DIR}/sub-select-mode.sh"

# Change php.ini defaults
sudo find /etc/php/ -name "php.ini" -exec sed -i "s/^\(max_file_uploads\s*=\s*\).*\$/\1100/" {} \;
sudo find /etc/php/ -name "php.ini" -exec sed -i "s/^\(post_max_size\s*=\s*\).*\$/\10/" {} \;
sudo find /etc/php/ -name "php.ini" -exec sed -i "s/^\(upload_max_filesize\s*=\s*\).*\$/\1256M/" {} \;

# Create web UI systemd unit
PORTS=("80" "8000")
for PORT in "${PORTS[@]}"; do
    sudo sh -c "echo '[Unit]' > /etc/systemd/system/webui${PORT}.service"
    sudo sh -c "echo 'Description=web UI Port ${PORT}' >> /etc/systemd/system/webui${PORT}.service"
    sudo sh -c "echo 'After=network.target' >> /etc/systemd/system/webui${PORT}.service"
    sudo sh -c "echo '[Service]' >> /etc/systemd/system/webui${PORT}.service"
    sudo sh -c "echo 'Type=simple' >> /etc/systemd/system/webui${PORT}.service"
    sudo sh -c "echo 'ExecStart=/usr/bin/php -S 0.0.0.0:${PORT} -t ${WORKING_DIR}' >> /etc/systemd/system/webui${PORT}.service"
    sudo sh -c "echo 'ExecStop=/usr/bin/kill -HUP \$MAINPID' >> /etc/systemd/system/webui${PORT}.service"
    sudo sh -c "echo '[Install]' >> /etc/systemd/system/webui${PORT}.service"
    sudo sh -c "echo 'WantedBy=multi-user.target' >> /etc/systemd/system/webui${PORT}.service"
    sudo systemctl enable webui${PORT}.service
    sudo systemctl start webui${PORT}.service
done

# Create File Browser systemd unit
curl -fsSL https://raw.githubusercontent.com/filebrowser/get/master/get.sh | bash
sudo sh -c "echo '[Unit]' > /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'Description=File Browser' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'After=network.target' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo '[Service]' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'Type=simple' >> /etc/systemd/system/filebrowser.service"
sudo sh -c "echo 'ExecStart=/usr/local/bin/filebrowser -a 0.0.0.0 -r /media' >> /etc/systemd/system/filebrowser.service"
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
sudo sh -c "echo '' >> /etc/samba/smb.conf"
sudo sh -c "echo '### Global Settings ###' > /etc/samba/smb.conf"
sudo sh -c "echo '[global]' >> /etc/samba/smb.conf"
sudo sh -c "echo 'workgroup = WORKGROUP' >> /etc/samba/smb.conf"
sudo sh -c "echo 'wide links = yes' >> /etc/samba/smb.conf"
sudo sh -c "echo 'unix extensions = no' >> /etc/samba/smb.conf"
sudo sh -c "echo 'dns proxy = no' >> /etc/samba/smb.conf"
sudo sh -c "echo '' >> /etc/samba/smb.conf"
sudo sh -c "echo '### Debugging/Accounting ###' >> /etc/samba/smb.conf"
sudo sh -c "echo 'log file = /var/log/samba/log.%m' >> /etc/samba/smb.conf"
sudo sh -c "echo 'max log size = 1000' >> /etc/samba/smb.conf"
sudo sh -c "echo 'syslog = 0' >> /etc/samba/smb.conf"
sudo sh -c "echo 'panic action = /usr/share/samba/panic-action %d' >> /etc/samba/smb.conf"
sudo sh -c "echo '' >> /etc/samba/smb.conf"
sudo sh -c "echo '### Authentication ###' >> /etc/samba/smb.conf"
sudo sh -c "echo 'security = user' >> /etc/samba/smb.conf"
sudo sh -c "echo 'map to guest = Bad User' >> /etc/samba/smb.conf"
sudo sh -c "echo 'guest account = root' >> /etc/samba/smb.conf"
sudo sh -c "echo '' >> /etc/samba/smb.conf"
sudo sh -c "echo '### Share Definitions ###' >> /etc/samba/smb.conf"

DIRECTORIES=("${SOURCE_MOUNT_POINT}" "${STORAGE_MOUNT_POINT}" "${INTERAL_BACKUP_DIR}" "${IOS_MOUNT_POINT}")
for DIRECTORY in "${DIRECTORIES[@]}"; do
    PATHNAME=$(basename ${DIRECTORY})

    sudo sh -c "echo '' >> /etc/samba/smb.conf"
    sudo sh -c "echo '[${PATHNAME}]' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'comment = Little Backup Box ${PATHNAME}' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'path = ${DIRECTORY}' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'browseable = yes' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'force user = root' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'force group = root' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'admin users = $USER' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'writeable = yes' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'read only = no' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'guest ok = yes' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'create mask = 0777' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'directory mask = 0777' >> /etc/samba/smb.conf"
done

sudo samba restart
cd

# Configure vsftpd
sudo sh -c "echo 'write_enable=YES' >> /etc/vsftpd.conf"
sudo service vsftpd restart

# Finished
clear
echo "Alle done! Rebooting..."
sleep 2

sudo "${WORKING_DIR}/poweroff.sh" reboot force
