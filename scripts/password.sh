#!/usr/bin/env bash

# Author: Stefan Saam, github@saams.de

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
source "${WORKING_DIR}/constants.sh"
CONFIG="${WORKING_DIR}/config.cfg"
source "$CONFIG"

# Load Log library
. "${WORKING_DIR}/lib-log.sh"


# Definitions
USER="lbb"

# Arguments
MODE=$1
conf_PASSWORD=$2

echo "$MODE $conf_PASSWORD" | sudo tee -a "${const_WEB_ROOT_LBB}/pwd.log"

if [ "${MODE}" = "remove" ]; then # Mode: remove #######################################################
	log_message "General password removed."

	# Apache
	echo "" | sudo tee "/etc/apache2/includes/password.conf"

	# Samba
	echo "guest ok = yes" | sudo tee "/etc/samba/login.conf"

	sudo service smbd restart

elif [ "${MODE}" = "set" ]; then # Mode: set ###########################################################
	log_message "General password changed."

	# Apache
	echo "${conf_PASSWORD}" | sudo htpasswd -ci "/etc/apache2/includes/htpasswd" "${USER}"

	cat <<EOF | sudo tee "/etc/apache2/includes/password.conf"
			Authtype Basic
			Authname "Password Required"
			AuthUserFile /etc/apache2/includes/htpasswd
			Require valid-user
EOF

	# Linux
	echo "pi:${conf_PASSWORD}" | sudo chpasswd
	echo "lbb:${conf_PASSWORD}" | sudo chpasswd

	# Samba
	echo -e "${conf_PASSWORD}\n${conf_PASSWORD}" | sudo smbpasswd -a -s "${USER}" # change/create smb-password

		cat <<EOF | sudo tee "/etc/samba/login.conf"
#		valid users = ${USER}
		guest ok = no
EOF

	sudo service smbd restart
fi
