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

# accepts one optional argument: branch

# IMPORTANT for developers:
# All actions have to be sufficient for modes install and update!
# To differ between both modes, use variable SCRIPT_MODE:
# if [ "${SCRIPT_MODE}" = "install" ]; then ...
# if [ "${SCRIPT_MODE}" = "update" ]; then ...


# Don't start as root
if [[ $EUID -eq 0 ]]; then
    echo "Do not run the script as user root"
    exit 1
fi

# get OS release version
OS_RELEASE=$(lsb_release -a | grep 'Release:' | cut -d':' -f 2 | xargs)

if  [ "${OS_RELEASE}" == "13" ]; then
	echo -e "Info: Detected Raspberry Pi OS Trixie — the current supported and optimized platform for Little Backup Box.\nInstalling the latest version."

elif  [ "${OS_RELEASE}" == "12" ]; then
	# bookworm
	echo -e "Note: It looks like you are running an outdated Raspberry Pi OS Bookworm system.\nThe current development version of Little Backup Box is no longer compatible with this release.\nThe last version supporting Bookworm will be installed instead.\nUpgrading to Trixie is strongly recommended."
	branch='bookworm'; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/${branch}/install-little-backup-box.sh | bash -s -- ${branch} 2> install-error.log
	exit
else
	echo -e "Sorry: Installation and updates are only supported on Raspberry Pi OS Trixie (main branch) and Bookworm (non-asterisk branch).\nThe Bookworm branch is no longer maintained — development has moved to Trixie."
	exit
fi

# check arguments
if [ $# -gt 0 ]; then
	branch=${1}
else
	branch="main"
fi
echo "Selected branch: ${branch}"

# internet-connection required
ping -c1 google.com &>/dev/null
INTERNET_STATUS=$?
if [ "${INTERNET_STATUS}" != "0" ]; then
	echo "No internet-connection detected. Exit."
	exit 0
fi

# define users
USER_WWW_DATA="www-data"
USER_SAMBA="lbb"

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

# Clone Little Backup Box
sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
			git

echo "Cloning Little Backup Box"
cd

sudo rm -R ${INSTALLER_DIR} 2>/dev/null
git clone --branch "${branch}" https://github.com/outdoorbits/little-backup-box.git
GIT_CLONE=$?
if [ "${GIT_CLONE}" -gt 0 ]; then
	echo "Cloning little-backup-box from github.com failed. Please try again later."
	exit 1
fi

# set locale
source "${INSTALLER_DIR}/set_locale.sh"

# configure unconfigured/broken packages (=safety)
sudo dpkg --configure -a

# running as installer or as updater?
if [ -d "${const_WEB_ROOT_LBB}" ]; then
	SCRIPT_MODE="update"
	echo "Installer-script running as UPDATER"
else
	SCRIPT_MODE="install"
	echo "Installer-script running as INSTALLER"
	sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
		dialog
fi

# Do all user-interactions
## Prompt to install comitup
CHOICE_COMITUP=1
if [ "${SCRIPT_MODE}" = "install" ]; then

	read -r -d '' COMITUP_INSTALL_QUESTION << EOM
\Zb\ZuInstall comitup?\Zn

Do you want to allow Little-Backup-Box to work as a WiFi-access-point and make yourself independent of other WiFi-networks?
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

## re-install (update) comitup if installed
if [ "$(dpkg-query -W --showformat='${db:Status-Status}' "comitup" 2>&1)" = "installed" ]; then
	CHOICE_COMITUP="0"
fi

# Update source and perform the full system upgrade
echo "apt-get update..."
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		full-upgrade -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages

# Install the required packages

echo "apt-get install..."
sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
			acl \
			screen \
			rsync \
			exfat-fuse \
			exfatprogs \
			ntfs-3g \
			acl \
			bindfs \
			gphoto2 \
			libimage-exiftool-perl \
			php \
			php-cli \
			php-curl \
			samba \
			samba-common-bin \
			proftpd-core \
			proftpd-mod-crypto \
			imagemagick \
			curl \
			ifuse \
			sshpass \
			f3 \
			sqlite3 \
			php-sqlite3 \
			ffmpeg \
			libheif-examples \
			libraw-bin \
			openvpn \
			wireguard \
			openresolv \
			hfsprogs \
			fuse3 \
			python3 \
			python3-pip \
			python3-pil \
			python3-configobj \
			python3-gpiozero \
			python3-rpi-lgpio \
			python3-qrcode \
			python3-psutil \
			python3-dateutil \
			python3-urllib3 \
			python3-python-telegram-bot \
			python3-websockets \
			python3-matrix-nio \
			python3-six \
			python3-markdown \
			python3-bs4 \
			python3-bleach \
			python3-flask \
			python3-evdev \
			smartmontools \
			dos2unix

# Remove obsolete packages
sudo DEBIAN_FRONTEND=noninteractive \
	apt-get autoremove -y

# Install python3 packages not available for apt
## prepare for atproto
sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
			python3-pydantic \
			python3-annotated-types \
			python3-typing-extensions

sudo pip install --break-system-packages --no-deps --upgrade atproto libipld markdownify

sudo pip install --break-system-packages --upgrade Mastodon.py

# disable services
sudo systemctl disable openvpn.service

# change hostname
sudo raspi-config nonint do_hostname lbb

# raspberry pi 5: usb_max_current_enable
CONFIG_TXT="/boot/firmware/config.txt"
VAR="usb_max_current_enable"
VALUE=1
if ! grep -q "${VAR}" "${CONFIG_TXT}"; then
	echo "${VAR}=${VALUE}" | sudo tee -a "${CONFIG_TXT}"
else
	sudo sed -i "/^${VAR}/s/\(.[^=]*\)\([ \t]*=[ \t]*\)\(.[^=]*\)/\1\2${VALUE}/" "${CONFIG_TXT}"
fi

# configure Little Backup Box
## write branch into constants
echo "const_SOFTWARE_BRANCH='${branch}'" | sudo tee -a "${INSTALLER_DIR}/scripts/constants.sh"

## read new constants
source "${INSTALLER_DIR}/scripts/constants.sh"

## clean web root, keep still needed files
if [ "${SCRIPT_MODE}" = "update" ]; then
	# remove files
	sudo find ${const_WEB_ROOT_LBB} -type f -not -wholename "${const_WEB_ROOT_LBB}/config.cfg" -not -wholename "${const_CMD_RUNNER_LOCKFILE}" -not -wholename "${const_CMD_LOGFILE}" -delete

	# remove empty directories
	sudo find ${const_WEB_ROOT_LBB} -type d -empty -delete
fi

## install little-backup-box-files
sudo mkdir -p "${const_WEB_ROOT_LBB}"
yes | sudo cp -Rf "${INSTALLER_DIR}/scripts/"* "${const_WEB_ROOT_LBB}/"

## rewrite config files
sudo python3 "${const_WEB_ROOT_LBB}/lib_setup.py"

## set file permissions in const_WEB_ROOT_LBB
sudo chown ${USER_WWW_DATA}:${USER_WWW_DATA} "${const_WEB_ROOT_LBB}" -R
sudo chmod 777 ${const_WEB_ROOT_LBB}/*

## write conf_SOFTWARE_DATE_INSTALLED and conf_SOFTWARE_DATE_AVAILABLE into setup
sudo python3 "${const_WEB_ROOT_LBB}/lib_git.py" --write-installed

## read config file
CONFIG="${const_WEB_ROOT_LBB}/config.cfg"
echo "Loading restored settings from ${CONFIG}"
source "${CONFIG}"

# Install rclone
curl https://rclone.org/install.sh | sudo bash

## Install rclone gui (needs to start gui, random password for this session to prevent login)
sudo rclone rcd --rc-web-gui --rc-web-gui-force-update --rc-web-gui-no-open-browser --rc-addr :5572 --rc-user lbb --rc-pass "$(echo $RANDOM | md5sum | head -c 20)" &

# create linux-user for samba
sudo useradd --create-home -s /bin/bash ${USER_SAMBA}
sudo usermod -aG ${USER_WWW_DATA} ${USER_SAMBA}

# Create the required media-directories
echo "Creating the required media-directories"

sudo mkdir -p "${const_MEDIA_DIR}"

sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_USB_TARGET}" > /dev/null 2>&1
sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_USB_SOURCE}" > /dev/null 2>&1
sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_TECH_USB_TARGET}" > /dev/null 2>&1
sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_TECH_USB_SOURCE}" > /dev/null 2>&1

sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_NVME_TARGET}" > /dev/null 2>&1
sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_NVME_SOURCE}" > /dev/null 2>&1
sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_TECH_NVME_TARGET}" > /dev/null 2>&1
sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_TECH_NVME_SOURCE}" > /dev/null 2>&1

sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_CLOUD_TARGET}" > /dev/null 2>&1
sudo umount "${const_MEDIA_DIR}/${const_MOUNTPOINT_CLOUD_SOURCE}" > /dev/null 2>&1

sudo mkdir -p "${const_MEDIA_DIR}/${const_BACKGROUND_IMAGES_DIR}/lbb"

sudo chown -R ${USER_WWW_DATA}:${USER_WWW_DATA} "${const_MEDIA_DIR}"
sudo chmod -R 777 "${const_MEDIA_DIR}"
sudo setfacl -Rdm u:${USER_WWW_DATA}:rwX,g:${USER_WWW_DATA}:rwX "${const_MEDIA_DIR}"
sudo setfacl -Rdm u:${USER_SAMBA}:rwX,g:${USER_SAMBA}:rwX "${const_MEDIA_DIR}"

# move background images in place
mv "${INSTALLER_DIR}/scripts/img/backgrounds/"* "${const_MEDIA_DIR}/${const_BACKGROUND_IMAGES_DIR}/lbb/"

# add user www-data to sudoers
sudo usermod -aG sudo ${USER_WWW_DATA}
yes | sudo cp -f "${INSTALLER_DIR}/etc/sudoers_d_www-data" "/etc/sudoers.d/www-data"
sudo chmod 0440 "/etc/sudoers.d/www-data"

# change owner and make installer scripts executable
sudo chmod 777 ${INSTALLER_DIR}/*.sh

# prevent SSH freeze
if [ -z "$(cat /etc/ssh/sshd_config | grep 'IPQos cs0 cs0')" ]; then
	echo 'IPQos cs0 cs0' | sudo tee -a /etc/ssh/sshd_config
fi

# Display
## activate i2c and spi
sudo raspi-config nonint do_i2c 0
sudo raspi-config nonint do_spi 0

sudo DEBIAN_FRONTEND=noninteractive apt-get -o "Dpkg::Options::=--force-confold" -o "Dpkg::Options::=--force-confdef" \
	install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
		libjpeg-dev \
		zlib1g-dev \
		libfreetype-dev \
		liblcms2-dev \
		libopenjp2-7 \
		libtiff6 \
		i2c-tools \
		python3-luma.core \
		python3-luma.emulator \
		python3-luma.lcd \
		python3-luma.led-matrix \
		python3-luma.oled

# Enable OLED screen support if available
## append new line to config-file
echo -e '' | sudo tee -a "${CONFIG}"

## activate display if detected (may not work in install-mode, i2c is not always available on primary install.)
if [ "${SCRIPT_MODE}" = "install" ]; then

	I2C_DETECT=$(sudo i2cdetect -y 1)

	I2C_LIST=("3c" "3d")
	for I2C in "${I2C_LIST[@]}"; do
		if [[ "${I2C_DETECT}" =~ " ${I2C}" ]]; then
			sudo sed -i '/conf_DISP=/d' "${CONFIG}"
			echo -e 'conf_DISP=true' | sudo tee -a "${CONFIG}"

			sudo sed -i '/conf_DISP_I2C_ADDRESS=/d' "${CONFIG}"
			echo -e "conf_DISP_I2C_ADDRESS=\"${I2C}\"" | sudo tee -a "${CONFIG}"
			break
		fi
	done

fi

# remove all from crontab
crontab -r

# write basic crontab
## disable power management for all wlan
crontab -l | {
    cat
    echo "@reboot sudo bash -c \"iw dev | awk '\\\$1==\\\"Interface\\\"{print \\\$2}' | xargs -I {} iw dev {} set power_save off\""
} | crontab

crontab -l | {
    cat
    echo "@reboot sudo python3 ${const_WEB_ROOT_LBB}/backup-autorun.py"
} | crontab

crontab -l | {
    cat
    echo "@reboot sudo python3 ${const_WEB_ROOT_LBB}/start-rclone-gui.py"
} | crontab

crontab -l | {
    cat
    echo "* * * * * ${const_WEB_ROOT_LBB}/cron_ip.sh"
} | crontab

crontab -l | {
    cat
    echo "* * * * * sudo python3 ${const_WEB_ROOT_LBB}/cron_idletime.py"
} | crontab

# tinyfilemanager
source "${INSTALLER_DIR}/install-tinyfilemanager.sh"

# Change php.ini defaults
sudo find /etc/php/ -name "php.ini" \
  -exec sudo sed -i 's/^[;]*[[:space:]]*max_file_uploads[[:space:]]*=.*/max_file_uploads = 100/' {} \; \
  -exec sudo sed -i 's/^[;]*[[:space:]]*post_max_size[[:space:]]*=.*/post_max_size = 0/' {} \; \
  -exec sudo sed -i 's/^[;]*[[:space:]]*upload_max_filesize[[:space:]]*=.*/upload_max_filesize = 2G/' {} \; \
  -exec sudo sed -i 's/^[;]*[[:space:]]*max_input_vars[[:space:]]*=.*/max_input_vars = 10000/' {} \;


## install apache2
sudo DEBIAN_FRONTEND=noninteractive \
		apt-get \
		-o "Dpkg::Options::=--force-confold" \
		-o "Dpkg::Options::=--force-confdef" \
		install -y -q --allow-downgrades --allow-remove-essential --allow-change-held-packages \
			apache2 \
			apache2-utils \
			php-fpm \
			libapache2-mod-fcgid

#configure apache for php-fpm
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -f1-2 -d".")

#disable apache php
sudo a2dismod php*

#disable apache prefork module
sudo a2dismod mpm_prefork

#enable apache event mpm module
sudo a2enmod mpm_event

#enable apache modules
sudo a2enmod proxy_fcgi
sudo a2enmod setenvif
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod proxy_html
sudo a2enmod headers

#enable conf php<VERSION>-fpm
sudo a2enconf php${PHP_VERSION}-fpm

#configure php-fpm to disable private mount namespace
echo "[Unit]
Description=The PHP ${PHP_VERSION} FastCGI Process Manager
Documentation=man:php-fpm${PHP_VERSION}(8)
After=network.target

[Service]
Type=notify
ExecStart=/usr/sbin/php-fpm${PHP_VERSION} --nodaemonize --fpm-config /etc/php/${PHP_VERSION}/fpm/php-fpm.conf
ExecStartPost=-/usr/lib/php/php-fpm-socket-helper install /run/php/php-fpm.sock /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf 82
ExecStopPost=-/usr/lib/php/php-fpm-socket-helper remove /run/php/php-fpm.sock /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf 82
ExecReload=/bin/kill -USR2 $MAINPID

# Disable private mount namespace
PrivateTmp=false
RuntimeDirectory=php-fpm
RuntimeDirectoryMode=0755

[Install]
WantedBy=multi-user.target" | tee /etc/systemd/system/multi-user.target.wants/php${PHP_VERSION}-fpm.service

#start php-fpm service
sudo systemctl daemon-reload

#openssl
sudo openssl req -x509 -nodes -days 3650 -subj '/C=OW/ST=MilkyWay/L=Earth/O=little-backup-box/CN=10.42.0.1' -newkey rsa:2048 -keyout /etc/ssl/private/little-backup-box.key -out /etc/ssl/certs/little-backup-box.crt
sudo cat /etc/ssl/private/little-backup-box.key | sudo tee /etc/ssl/private/little-backup-box.pem
sudo cat /etc/ssl/certs/little-backup-box.crt | sudo tee -a /etc/ssl/private/little-backup-box.pem

sudo chown root:ssl-cert /etc/ssl/private/little-backup-box.key
sudo chmod 640 /etc/ssl/private/little-backup-box.key

sudo chown root:ssl-cert /etc/ssl/private/little-backup-box.pem
sudo chmod 640 /etc/ssl/private/little-backup-box.pem

sudo chmod 644 /etc/ssl/certs/little-backup-box.crt

# Apache-config-files
## remove comitup apache integration from previous installation (will be integrated in little-backup-box.conf now)
sudo a2dissite comitup
sudo rm /etc/apache2/sites-available/comitup.conf

## setup new files
if [ "${SCRIPT_MODE}" = "install" ]; then
	echo "ServerName little-backup-box" | sudo tee -a "/etc/apache2/apache2.conf"
fi

yes | sudo cp -f "${INSTALLER_DIR}/etc/apache2_ports.conf" "/etc/apache2/ports.conf"
yes | sudo cp -f "${INSTALLER_DIR}/etc/apache2_conf-available_ssl-params.conf" "/etc/apache2/conf-available/ssl-params.conf"
yes | sudo cp -f "${INSTALLER_DIR}/etc/apache2_sites-available_little-backup-box.conf" "/etc/apache2/sites-available/little-backup-box.conf"

sudo mkdir -p /etc/apache2/includes
sudo touch /etc/apache2/includes/password.conf

sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod proxy
sudo a2enmod proxy_http

sudo a2dissite 000-default
sudo a2ensite little-backup-box

# Configure Samba
yes | sudo cp -f "${INSTALLER_DIR}/etc/samba_smb.conf" "/etc/samba/smb.conf"
sudo chmod 0440 "/etc/samba/smb.conf"

# Configure proftpd
yes | sudo cp -f "${INSTALLER_DIR}/etc/proftpd_proftpd.conf" "/etc/proftpd/proftpd.conf"
yes | sudo cp -f "${INSTALLER_DIR}/etc/proftpd_lbb_DefaultRoot.conf" "/etc/proftpd/lbb_DefaultRoot.conf"
sudo chmod 644 "/etc/proftpd/proftpd.conf"
sudo chmod 644 "/etc/proftpd/lbb_DefaultRoot.conf"

sudo service proftpd restart

# setup graphical environment
source "${INSTALLER_DIR}/setup-graphical-environment.sh"

# install comitup
if [ "${SCRIPT_MODE}" = "install" ] || [ "${CHOICE_COMITUP}" = "0" ]; then

	case $CHOICE_COMITUP in
	0)
		if [ "${SCRIPT_MODE}" = "install" ]; then
			read -r -d '' COMITUP_INSTALL_MESSAGE << EOM
\Zb\ZuIMPORTANT INFORMATION\Zn

\ZbPlease read this carefully, if your raspberry pi is connected by wifi.\Zn

\Zb\Z1Next step of the installation is to install comitup. When comitup starts, it takes over control about the raspberry pi's wifi-connections.
That's why your pi's wifi-connection may break during this last step of the installation.
BUT DON'T WORRY!\Zn

The installation will finish seconds later, but maybe you won't see that progress on screen anymore. Your raspberry pi will reboot when setup is finished. Have a look at the action-LED.

\ZuWhat's going on?\Zn
Your raspberrys wifi-configuration has been removed by comitup now. Because comitup has no
access-data for your wifi yet, it can't connect to it and will start as a wifi-hotspot:
ssid=little-backup-box-nnnn (nnnn is a random number).

Please connect your mobile or notebook to this wifi.

\ZuThere are two options now:\Zn

1. If you want to configure comitup now, navigate to \Zb\Z1http\Zn://10.41.0.1 (http, not https!)
and connect your lbb to your wifi.
Connect your mobile or notbook to the same wifi. If you use a display on your lbb, it will
tell you its new IP. Connect to lbb:
\Zb\Z1https\Zn://IP.IN.YOUR.WIFI (secure, certificate can't be verified automatically, please confirm it)
\Zb\Z1http\Zn://IP.IN.YOUR.WIFI:8080 (insecure)

2. Alternatively can reach the web UI of Little Backup Box when you are connected to its
hotspot as follows:

\Zb\Z1https\Zn://10.41.0.1 (secure, certificate can't be verified automatically, please confirm it)
\Zb\Z1http\Zn://10.41.0.1:8080 (insecure)

Please use the settings of the web UI to optimally adapt the Little Backup Box for you.

If you have further questions, please check the wiki first:
https://github.com/outdoorbits/little-backup-box/wiki

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

# (re-)establish passwords
echo "(Restore) password-protection"
sudo python3 "${const_WEB_ROOT_LBB}/lib_password.py" "$(echo $conf_PASSWORD | base64 --decode)"
sudo samba restart

# setup hardware
	source "${const_WEB_ROOT_LBB}/set_hardware.sh"

# post-install-information
IP=$(python3 "${const_WEB_ROOT_LBB}/lib_network.py" --Mode 'ip' --OneLine)

echo ""
echo "********************************************************************************************"
echo "*** How to proceed:"
echo "*** Assuming your Little Backup Box gets the same IP address again after the reboot,"
echo "*** you can then reach the web UI as follows:"
echo "*** "
echo "*** https://${IP} (secure, certificate cannot be verified automatically, please confirm it)"
echo "*** http://${IP}:8080 (insecure)"
echo "*** "
echo "*** Please use the settings of the web UI to optimally adapt the Little Backup Box for you."
echo "***"
echo "*** If you have further questions, please check the wiki first:"
echo "*** https://github.com/outdoorbits/little-backup-box/wiki"
echo "***"
echo "*** IF YOU ARE USING COMITUP: READ ALSO %quot;How to proceed%quot; in README/Installation!"
echo "***"
echo "*** We are always happy to receive your feedback!"
echo "********************************************************************************************"
echo ""

# finished
echo "Little Backup Box: Setup finished." | tee "${INSTALLER_DIR}/SETUP_FINISHED.TXT"
echo "Mode=${SCRIPT_MODE}" | tee -a "${INSTALLER_DIR}/SETUP_FINISHED.TXT"
date | tee -a "${INSTALLER_DIR}/SETUP_FINISHED.TXT"

# show installation errors
if [ -f './install-error.log' ]; then
	echo 'Installation errors:'
	echo "To see all error messages, please execute 'cat ./install-error.log'."
	echo
fi

# remove raspberry pi imagers wifi settings (as late as possible)
sudo rm /etc/NetworkManager/system-connections/preconfigured.nmconnection

# reboot
echo "All done! Rebooting..."
sleep 3
sudo python3 "${const_WEB_ROOT_LBB}/lib_poweroff.py" reboot
