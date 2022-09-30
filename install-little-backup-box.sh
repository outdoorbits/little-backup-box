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
	sudo DEBIAN_FRONTEND=noninteractive \
		apt \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
		dialog
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

	read -r -d '' MEJIRO_INSTALL_QUESTION << EOM
\Zb\ZuInstall mejiro?\Zn

Mejiro is a simple image-gallery, smoothly integrated into Little-Backup-Box.
EOM

	dialog --clear \
		--colors \
		--title "mejiro" \
		--backtitle "$BACKTITLE" \
		--yesno "${MEJIRO_INSTALL_QUESTION}" \
		14 80

	CHOICE_MEJIRO=$?

	clear
elif [ -d "/var/www/mejiro" ]; then
		CHOICE_MEJIRO=0
fi

## Prompt to install comitup
CHOICE_COMITUP=1
if [ "${SCRIPT_MODE}" = "install" ]; then

	read -r -d '' COMITUP_INSTALL_QUESTION << EOM
\Zb\ZuInstall comitup?\Zn

Do you want to allow Little-Backup-Box to work as a WiFi-accress-point and make yourself independent of other WiFi-networks?
Do you want zu connect your Little-Backup-Box flexible to any WiFi, managed conveniently by web-gui?

Comitup provides all these capabilities.
EOM

	dialog --clear \
		--colors \
		--title "comitup" \
		--backtitle "$BACKTITLE" \
		--yesno "${COMITUP_INSTALL_QUESTION}" \
		14 80

	CHOICE_COMITUP=$?

	clear
fi


# Update source and perform the full system upgrade
sudo apt update
sudo DEBIAN_FRONTEND=noninteractive \
		apt \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		full-upgrade -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages

# Install the required packages
sudo DEBIAN_FRONTEND=noninteractive \
		apt \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
		acl git-core screen rsync exfat-fuse exfat-utils ntfs-3g acl gphoto2 libimage-exiftool-perl dialog php php-cli minidlna samba samba-common-bin vsftpd imagemagick curl dos2unix libimobiledevice6 ifuse sshpass apache2 apache2-utils libapache2-mod-php bc

# Remove obsolete packages
sudo DEBIAN_FRONTEND=noninteractive \
	apt autoremove -y

# General raspi-config settings
sudo raspi-config nonint do_boot_wait 1 # wait for network on boot: disable

# Clone Little Backup Box
echo "Cloning Little Backup Box"
cd

sudo rm -R ${INSTALLER_DIR}
git clone https://github.com/outdoorbits/little-backup-box.git
GIT_CLONE=$?
if [ "${GIT_CLONE}" -gt 0 ]; then
	echo "Cloning little-backup-box from github.com failed. Please try again later."
	exit 0
fi

# read new constants
source "${INSTALLER_DIR}/scripts/constants.sh"

# define run-mode
if [ "${SCRIPT_MODE}" = "update" ]; then
	echo "keep config..."
	yes | sudo cp -f "${const_WEB_ROOT_LBB}/config.cfg" "${INSTALLER_DIR}/scripts/"
	yes | sudo cp -f "${const_UPDATE_LOCKFILE}" "${INSTALLER_DIR}/scripts/"

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

# define users
USER_WWW_DATA="www-data"
USER_SAMBA="lbb"

# create linux-user for samba
sudo useradd --create-home -s /bin/bash ${USER_SAMBA}
sudo usermod -aG ${USER_WWW_DATA} ${USER_SAMBA}

# Create the required media-directories
echo "Creating the required media-directories"

sudo mkdir -p "${const_MEDIA_DIR}"

sudo umount "${const_SOURCE_MOUNT_POINT}" > /dev/null 2>&1
sudo umount "${const_STORAGE_MOUNT_POINT}" > /dev/null 2>&1
sudo umount "${const_INTERAL_BACKUP_DIR}" > /dev/null 2>&1
sudo umount "${const_IOS_MOUNT_POINT}" > /dev/null 2>&1
sudo umount "${const_CLOUD_MOUNT_POINT}" > /dev/null 2>&1

sudo mkdir -p "${const_SOURCE_MOUNT_POINT}"
sudo mkdir -p "${const_STORAGE_MOUNT_POINT}"
sudo mkdir -p "${const_INTERAL_BACKUP_DIR}"
sudo mkdir -p "${const_IOS_MOUNT_POINT}"
sudo mkdir -p "${const_CLOUD_MOUNT_POINT}"

sudo chown -R ${USER_WWW_DATA}:${USER_WWW_DATA} "${const_MEDIA_DIR}"
sudo chmod -R 777 "${const_MEDIA_DIR}"
sudo setfacl -Rdm u:${USER_WWW_DATA}:rwX,g:${USER_WWW_DATA}:rwX "${const_MEDIA_DIR}"
sudo setfacl -Rdm u:${USER_SAMBA}:rwX,g:${USER_SAMBA}:rwX "${const_MEDIA_DIR}"

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
sudo DEBIAN_FRONTEND=noninteractive \
		apt \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
		python3-pip python3-pil i2c-tools
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

# set the default SECONDARY backup mode
if [ "${SCRIPT_MODE}" = "install" ]; then
	## append new line to config-file
	echo -e '' | sudo tee -a "${CONFIG}"

	# write new default-backup-method
	conf_BACKUP_DEFAULT_SOURCE2="none"
	conf_BACKUP_DEFAULT_TARGET2="none"

	case $CHOICE_BACKUP_MODE in
	1)
			conf_BACKUP_DEFAULT_SOURCE2="none"
			conf_BACKUP_DEFAULT_TARGET2="none"
		;;
	2)
			conf_BACKUP_DEFAULT_SOURCE2="storage"
			conf_BACKUP_DEFAULT_TARGET2="external"
		;;
	3)
			conf_BACKUP_DEFAULT_SOURCE2="storage"
			conf_BACKUP_DEFAULT_TARGET2="internal"
		;;
	4)
			conf_BACKUP_DEFAULT_SOURCE2="camera"
			conf_BACKUP_DEFAULT_TARGET2="external"
		;;
	5)
			conf_BACKUP_DEFAULT_SOURCE2="camera"
			conf_BACKUP_DEFAULT_TARGET2="internal"
		;;
	6)
			conf_BACKUP_DEFAULT_SOURCE2="ios"
			conf_BACKUP_DEFAULT_TARGET2="external"
		;;
	7)
			conf_BACKUP_DEFAULT_SOURCE2="ios"
			conf_BACKUP_DEFAULT_TARGET2="internal"
		;;
	esac
else
	if [ -z "${conf_BACKUP_DEFAULT_SOURCE2}" ]; then
		conf_BACKUP_DEFAULT_SOURCE2="none"
	fi
	if [ -z "${conf_BACKUP_DEFAULT_TARGET2}" ]; then
		conf_BACKUP_DEFAULT_TARGET2="none"
	fi
fi

echo "conf_BACKUP_DEFAULT_SOURCE2=\"${conf_BACKUP_DEFAULT_SOURCE2}\"" | sudo tee -a "${CONFIG}"
echo "conf_BACKUP_DEFAULT_TARGET2=\"${conf_BACKUP_DEFAULT_TARGET2}\"" | sudo tee -a "${CONFIG}"

# remove all from crontab
crontab -r

# write basic crontab
crontab -l | {
    cat
    echo "@reboot sudo ${const_WEB_ROOT_LBB}/backup-autorun.sh"
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
sudo sh -c "echo 'guest account = ${USER_WWW_DATA}' >> /etc/samba/smb.conf"
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
    sudo sh -c "echo 'force user = ${USER_WWW_DATA}' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'force group = ${USER_WWW_DATA}' >> /etc/samba/smb.conf"
    sudo sh -c "echo 'admin users = ${USER_WWW_DATA}' >> /etc/samba/smb.conf"
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

# Restore password protection
if [ "${SCRIPT_MODE}" = "update" ] && [ ! -z "${conf_PASSWORD}" ]; then
	echo "Restore password-protection"
	sudo "${const_WEB_ROOT_LBB}/password.sh" set "${conf_PASSWORD}"
fi

# install mejiro
## re-install (update) if installed
if [ -d "/var/www/mejiro" ]; then
	CHOICE_MEJIRO="0"
fi

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
## re-install (update) if installed
if [ "$(dpkg-query -W --showformat='${db:Status-Status}' "comitup" 2>&1)" = "installed" ]; then
	CHOICE_COMITUP="0"
fi

if [ "${SCRIPT_MODE}" = "install" ] || [ "${CHOICE_COMITUP}" = "0" ]; then
	case $CHOICE_COMITUP in
	0)
		if [ "${SCRIPT_MODE}" = "install" ]; then
			read -r -d '' COMITUP_INSTALL_MESSAGE << EOM
\Zb\ZuIMPORTANT INFORMATION\Zn

\ZbPlease read this carefully, if your raspberry pi is connected by wlan.\Zn

\Zb\Z1Next (and last) step of the installation is to install comitup. When comitup starts, it takes over control about the raspberry pi's wlan-connections.
That's why your pi's wlan-connection may break during this last step of the installation.
BUT DON'T WORRY!\Zn

The installation will finish seconds later, but you won't see that progress on screen anymore. Your raspberry pi will reboot when setup is finished. Have a look at the action-LED.

\ZuWhat's going on?\Zn
Your raspberry pi's wlan-configuration will be removed by comitup.
Because comitup at this time has no access-data for your wlan yet, it can't connect to it.
So it will start as a wlan-hotspot with ssid=\Zblittle-backup-box-nnnn\Zn (nnnn is a random number).

\ZuWhat to do?\Zn
After the reboot, please connect your mobile or notebook to the little-backup-box-nnnn wlan.

If you want to configure comitup now, navigate to \Zuhttp://10.41.0.1\Zn (http, not https!)

Alternatively can reach the web UI of Little Backup Box when you are connected to its hotspot as follows:

\Zuhttps://10.41.0.1\Zn (secure, certificate can't be verified automatically, please confirm it)
\Zuhttp://10.41.0.1:8000\Zn (insecure)

If you have further questions, please check the wiki first:
\Zuhttps://github.com/outdoorbits/little-backup-box/wiki\Zn

We are always happy to receive your feedback!

Press OK to proceed...
EOM
			dialog --colors --msgbox "${COMITUP_INSTALL_MESSAGE}" 40 80

			clear

		fi

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



# post-install-information
IP=$(hostname -I | cut -d' ' -f1)
echo ""
echo "********************************************************************************************"
echo "*** How to proceed:"
echo "*** Assuming your Little Backup Box gets the same IP address again after the reboot,"
echo "*** you can then reach the web UI as follows:"
echo "*** "
echo "*** https://${IP} (secure, certificate cannot be verified automatically, please confirm it)"
echo "*** http://${IP}:8000 (insecure)"
echo "*** "
echo "*** Please use the settings of the web UI to optimally adapt the Little Backup Box for you."
echo "***"
echo "*** If you have further questions, please check the wiki first:"
echo "*** https://github.com/outdoorbits/little-backup-box/wiki"
echo "***"
echo "*** We are always happy to receive your feedback!"
echo "********************************************************************************************"
echo ""

# finshed
echo "Little Backup Box: Setup finished." | tee "${INSTALLER_DIR}/SETUP_FINISHED.TXT"
echo "Mode=${SCRIPT_MODE}" | tee -a "${INSTALLER_DIR}/SETUP_FINISHED.TXT"
date | tee -a "${INSTALLER_DIR}/SETUP_FINISHED.TXT"

# reboot
echo "All done! Rebooting..."
sleep 3
sudo "${const_WEB_ROOT_LBB}/poweroff.sh" reboot force
