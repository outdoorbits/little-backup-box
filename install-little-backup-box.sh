#!/usr/bin/env bash

# Author: Stefan Saam, github@saams.de
# Original author: Dmitri Popov, dmpop@linux.com

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


# IMPORTANT:
# All actions have to be sufficient for modes install and update!
# To differ between both modes, use variable SCRIPT_MODE:
# if [ "${SCRIPT_MODE}" = "install" ]; then ...
# if [ "${SCRIPT_MODE}" = "update" ]; then ...


# Don't start as root
if [[ $EUID -eq 0 ]]; then
    echo "Run the script as a regular user"
    exit 1
fi

# internet-connection required
ping -c1 google.com &>/dev/null
INTERNET_DISCONNECTED=$?
if [ "${INTERNET_DISCONNECTED}" != "0" ]; then
	echo "No internet-connection detected. Exit."
	exit 0
fi

# change into actual user-dir
cd

# Definitions
BACKTITLE="Little Backup Box"
const_WEB_ROOT_LBB="/var/www/little-backup-box"

# define DIRs
INSTALLER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
if [[ ! "${INSTALLER_DIR}" =~ "little-backup-box" ]]; then
    # in case it is called by regular install command (curl ...)
    INSTALLER_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/little-backup-box"
fi

# running as installer or as updater?
if [ -d "${const_WEB_ROOT_LBB}" ]; then
	SCRIPT_MODE="update"
	echo "Installer-script running as UPDATER"
else
	SCRIPT_MODE="install"
	echo "Installer-script running as INSTALLER"
	sudo apt install -y dialog
fi

# Do all user-interactions

## Prompt to choose the default backup mode
CHOICE_BACKUP_MODE=1
if [ "${SCRIPT_MODE}" = "install" ]; then
	OPTIONS=(
		1 "none"
		2 "Source -> external storage"
		3 "Source -> internal storage"
		4 "Camera -> external storage"
		5 "Camera -> internal storage"
		6 "iOS -> external storage"
		7 "iOS -> internal storage"
	)

	CHOICE_BACKUP_MODE=$(dialog --clear \
		--backtitle "$BACKTITLE" \
		--title "Backup Mode" \
		--menu "Select the default backup mode:" \
		20 50 7 \
		"${OPTIONS[@]}" \
		2>&1 >/dev/tty)

	clear
fi

## Promt to install mejiro
CHOICE_MEJIRO=1
if [ "${SCRIPT_MODE}" = "install" ]; then
	dialog --clear \
		--title "mejiro" \
		--backtitle "$BACKTITLE" \
		--yesno "Install mejiro?" \
		7 60

	CHOICE_MEJIRO=$?

	clear
elif [ -d "/var/www/mejiro" ]; then
		CHOICE_MEJIRO=0
fi

## Prompt to install comitup
CHOICE_COMITUP=1
if [ "${SCRIPT_MODE}" = "install" ]; then
	dialog --clear \
		--title "comitup" \
		--backtitle "$BACKTITLE" \
		--yesno "Install comitup?" \
		7 60

	CHOICE_COMITUP=$?

	clear
fi


# Update source and perform the full system upgrade
sudo apt update
sudo apt full-upgrade -y
sudo apt update

# Install the required packages
sudo apt install -y acl git-core screen rsync exfat-fuse exfat-utils ntfs-3g acl gphoto2 libimage-exiftool-perl dialog php php-cli minidlna samba samba-common-bin vsftpd imagemagick curl dos2unix libimobiledevice6 ifuse sshpass apache2 apache2-utils libapache2-mod-php bc

# Remove obsolete packages
sudo apt autoremove -y

# Clone Little Backup Box
echo "Clone Little Backup Box"
cd

sudo rm -R ${INSTALLER_DIR}
git clone https://github.com/outdoorbits/little-backup-box.git

# read new constants
source "${INSTALLER_DIR}/scripts/constants.sh"

# define run-mode
if [ "${SCRIPT_MODE}" = "update" ]; then
	echo "keep config..."
	yes | sudo cp -f "${const_WEB_ROOT_LBB}/config.cfg" "${INSTALLER_DIR}/scripts/"
	sudo rm -R ${const_WEB_ROOT_LBB}/*
fi

# install little-backup-box-files
sudo mkdir -p "${const_WEB_ROOT_LBB}"
yes | sudo cp -Rf ${INSTALLER_DIR}/scripts/* ${const_WEB_ROOT_LBB}/

if [ ! -f "${const_WEB_ROOT_LBB}/config.cfg" ]; then
	sudo cp "${const_WEB_ROOT_LBB}/config-standards.cfg" "${const_WEB_ROOT_LBB}/config.cfg"
fi

CONFIG="${const_WEB_ROOT_LBB}/config.cfg"

if [ "${SCRIPT_MODE}" = "update" ]; then
	source "${CONFIG}"
fi

#Install Rclone
curl https://rclone.org/install.sh | sudo bash

# define user
USER="www-data"

# Create the required media-directories
echo "Creating the required media-directories"

sudo mkdir -p "${const_MEDIA_DIR}"

sudo mkdir -p "${const_SOURCE_MOUNT_POINT}"
sudo mkdir -p "${const_STORAGE_MOUNT_POINT}"
sudo mkdir -p "${const_INTERAL_BACKUP_DIR}"
sudo mkdir -p "${const_IOS_MOUNT_POINT}"
sudo mkdir -p "${const_CLOUD_MOUNT_POINT}"

sudo chown -R ${USER}:${USER} "${const_MEDIA_DIR}"
sudo chmod -R 777 "${const_MEDIA_DIR}"
sudo setfacl -Rdm g:$USER:rw "${const_MEDIA_DIR}"

# Configure miniDLNA
echo "Configure miniDLNA"
if [ "${SCRIPT_MODE}" = "install" ]; then
	sudo cp "/etc/minidlna.conf" "/etc/minidlna.conf.orig"
else
	sudo rm "/etc/minidlna.conf"
	sudo cp "/etc/minidlna.conf.orig" "/etc/minidlna.conf"
fi
sudo sed -i 's|'media_dir=/var/lib/minidlna'|'media_dir="${const_STORAGE_MOUNT_POINT}"'|' /etc/minidlna.conf
sudo sed -i 's/^#friendly_name=.*/friendly_name=little-backup-box/' /etc/minidlna.conf
sudo sh -c "echo 'media_dir=${const_INTERAL_BACKUP_DIR}' >> /etc/minidlna.conf"
sudo service minidlna start

# add user www-data to sudoers
sudo usermod -aG sudo www-data
yes | sudo cp -f "${INSTALLER_DIR}/etc_sudoers_d_www-data" "/etc/sudoers.d/www-data"
sudo chmod 0440 "/etc/sudoers.d/www-data"

# change owner and make scripts executable
sudo chmod 777 ${INSTALLER_DIR}/*.sh

sudo chown www-data:www-data "${const_WEB_ROOT_LBB}" -R
sudo chmod 777 ${const_WEB_ROOT_LBB}/*

# Display
sudo apt-get install -y python3-pip python3-pil i2c-tools
sudo pip3 install adafruit-circuitpython-ssd1306
sudo raspi-config nonint do_i2c 0

# Enable OLED screen support if available
## append new line to config-file
echo -e '' | sudo tee -a "${CONFIG}"
## activate display if detected
if [ ! -z "$(sudo i2cdetect -y 1 | grep " 3c")" ]; then
	echo -e 'conf_DISP=true' | sudo tee -a "${CONFIG}"
else
	echo -e 'conf_DISP=false' | sudo tee -a "${CONFIG}"
fi

# set the default backup mode
if [ "${SCRIPT_MODE}" = "install" ]; then
	## append new line to config-file
	echo -e '' | sudo tee -a "${CONFIG}"

	# write new default-backup-method
	conf_BACKUP_DEFAULT_SOURCE="none"
	conf_BACKUP_DEFAULT_TARGET="none"

	case $CHOICE_BACKUP_MODE in
	1)
			conf_BACKUP_DEFAULT_SOURCE="none"
			conf_BACKUP_DEFAULT_TARGET="none"
		;;
	2)
			conf_BACKUP_DEFAULT_SOURCE="storage"
			conf_BACKUP_DEFAULT_TARGET="external"
		;;
	3)
			conf_BACKUP_DEFAULT_SOURCE="storage"
			conf_BACKUP_DEFAULT_TARGET="internal"
		;;
	4)
			conf_BACKUP_DEFAULT_SOURCE="camera"
			conf_BACKUP_DEFAULT_TARGET="external"
		;;
	5)
			conf_BACKUP_DEFAULT_SOURCE="camera"
			conf_BACKUP_DEFAULT_TARGET="internal"
		;;
	6)
			conf_BACKUP_DEFAULT_SOURCE="ios"
			conf_BACKUP_DEFAULT_TARGET="external"
		;;
	7)
			conf_BACKUP_DEFAULT_SOURCE="ios"
			conf_BACKUP_DEFAULT_TARGET="internal"
		;;
	esac
else
	if [ -z "${conf_BACKUP_DEFAULT_SOURCE}" ]; then
		conf_BACKUP_DEFAULT_SOURCE="none"
	fi
	if [ -z "${conf_BACKUP_DEFAULT_TARGET}" ]; then
		conf_BACKUP_DEFAULT_TARGET="none"
	fi
fi

echo "conf_BACKUP_DEFAULT_SOURCE=\"${conf_BACKUP_DEFAULT_SOURCE}\"" | sudo tee -a "${CONFIG}"
echo "conf_BACKUP_DEFAULT_TARGET=\"${conf_BACKUP_DEFAULT_TARGET}\"" | sudo tee -a "${CONFIG}"

# remove all from crontab
crontab -r

# write basic crontab
crontab -l | {
    cat
    echo "@reboot sudo ${const_WEB_ROOT_LBB}/start.sh"
} | crontab

crontab -l | {
    cat
    echo "@reboot sudo ${const_WEB_ROOT_LBB}/start-servers.sh"
} | crontab

crontab -l | {
    cat
    echo "*/1 * * * * sudo ${const_WEB_ROOT_LBB}/cron-ip.sh"
} | crontab

crontab -l | {
    cat
    echo "*/1 * * * * sudo ${const_WEB_ROOT_LBB}/cron-idletime.sh"
} | crontab

# tinyfilemanager
source "${INSTALLER_DIR}/install-tinyfilemanager.sh"

# Change php.ini defaults
sudo find /etc/php/ -name "php.ini" -exec sudo sed -i "s/^\(max_file_uploads\s*=\s*\).*\$/\1100/" {} \;
sudo find /etc/php/ -name "php.ini" -exec sudo sed -i "s/^\(post_max_size\s*=\s*\).*\$/\10/" {} \;
sudo find /etc/php/ -name "php.ini" -exec sudo sed -i "s/^\(upload_max_filesize\s*=\s*\).*\$/\1256M/" {} \;

# Create Apache-Configuration
sudo openssl req -x509 -nodes -days 3650 -subj '/C=OW/ST=MilkyWay/L=Earth/CN=10.42.0.1' -newkey rsa:2048 -keyout /etc/ssl/private/apache-selfsigned.key -out /etc/ssl/certs/apache-selfsigned.crt

 # Apache-config-files
if [ "${SCRIPT_MODE}" = "install" ]; then
	echo "ServerName little-backup-box" | sudo tee -a "/etc/apache2/apache2.conf"
fi
yes | sudo cp -f "${INSTALLER_DIR}/etc_apache2_ports.conf" "/etc/apache2/ports.conf"
yes | sudo cp -f "${INSTALLER_DIR}/etc_apache2_conf-available_ssl-params.conf" "/etc/apache2/conf-available/ssl-params.conf"
yes | sudo cp -f "${INSTALLER_DIR}/etc_apache2_sites-available_little-backup-box.conf" "/etc/apache2/sites-available/little-backup-box.conf"

sudo mkdir -p /etc/apache2/includes
sudo touch /etc/apache2/includes/password.conf

sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod proxy
sudo a2enmod proxy_http
# sudo a2enconf ssl-params # forces https
sudo a2dissite 000-default
sudo a2ensite little-backup-box

sudo systemctl reload apache2

# Configure Samba
sudo useradd --create-home -s /bin/bash "lbb" # create linux-user for samba

if [ "${SCRIPT_MODE}" = "update" ]; then
	yes | sudo cp -f /etc/samba/smb.conf.orig /etc/samba/smb.conf
else
	sudo cp /etc/samba/smb.conf /etc/samba/smb.conf.orig
fi
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
sudo sh -c "echo 'guest account = $USER' >> /etc/samba/smb.conf"
sudo sh -c "echo '' >> /etc/samba/smb.conf"
sudo sh -c "echo '### Share Definitions ###' >> /etc/samba/smb.conf"

DIRECTORIES=("${const_SOURCE_MOUNT_POINT}" "${const_STORAGE_MOUNT_POINT}" "${const_INTERAL_BACKUP_DIR}" "${const_IOS_MOUNT_POINT}")
for DIRECTORY in "${DIRECTORIES[@]}"; do
    PATHNAME=$(basename ${DIRECTORY})

    sudo sh -c "echo '' >> /etc/samba/smb.conf"
    sudo sh -c "echo '[${PATHNAME}]' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'comment = Little Backup Box ${PATHNAME}' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'include = /etc/samba/login.conf' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'path = ${DIRECTORY}' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'browseable = yes' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'force user = $USER' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'force group = $USER' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'admin users = $USER' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'writeable = yes' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'read only = no' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'create mask = 0777' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'directory mask = 0777' >> /etc/samba/smb.conf"
done

echo "guest ok = yes" | sudo tee "/etc/samba/login.conf"

sudo samba restart
cd

# Configure vsftpd
if [ "${SCRIPT_MODE}" = "install" ]; then
	sudo sh -c "echo 'write_enable=YES' >> /etc/vsftpd.conf"
fi
sudo service vsftpd restart

# install mejiro
case $CHOICE_MEJIRO in
0)
	echo "Installing mejiro ..."
	source "${INSTALLER_DIR}/install-mejiro.sh";
    ;;
1)
	echo "You can install mejiro later by script install-mejiro.sh"
	;;
255)
	echo "You can install mejiro later by script install-mejiro.sh"
	;;
esac

# install comitup
if [ "${SCRIPT_MODE}" = "install" ]; then
	case $CHOICE_COMITUP in
	0)
		echo "Installing comitup ..."
		source "${INSTALLER_DIR}/install-comitup.sh";
		;;
	1)
		echo "You can install comitup later by script install-comitup.sh"
		;;
	255)
		echo "You can install comitup later by script install-comitup.sh"
		;;
	esac
fi

# Restore password protection
if [ "${SCRIPT_MODE}" = "update" ] && [ ! -z "${conf_PASSWORD}" ]; then
	echo "Restore password-protection"
	sudo "${const_WEB_ROOT_LBB}/password.sh" set "${conf_PASSWORD}"
fi

echo "All done! Rebooting..."
sleep 3
sudo "${const_WEB_ROOT_LBB}/poweroff.sh" reboot force
