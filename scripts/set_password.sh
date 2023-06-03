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
new_PASSWORD="${1}"

if [ -z "${new_PASSWORD}" ]; then
# remove password
	log_message "General password removed."

	# Apache
	echo "" | sudo tee "/etc/apache2/includes/password.conf"

	# Samba
	echo "guest ok = yes" | sudo tee "/etc/samba/login.conf"

	sudo service smbd restart

	#comitup
	. "${WORKING_DIR}/comitup-conf.sh"

else
# set password
	log_message "General password changed."

	# Apache
	echo "${new_PASSWORD}" | sudo htpasswd -ci "/etc/apache2/includes/htpasswd" "${USER}"

	cat <<EOF | sudo tee "/etc/apache2/includes/password.conf"
Authtype Basic
Authname "Password Required"
AuthUserFile /etc/apache2/includes/htpasswd
Require valid-user
EOF

	# Linux
	echo "pi:${new_PASSWORD}" | sudo chpasswd
	echo "${USER}:${new_PASSWORD}" | sudo chpasswd

	# Samba
	echo -e "${new_PASSWORD}\n${new_PASSWORD}" | sudo smbpasswd -a -s "${USER}" # change/create smb-password

		cat <<EOF | sudo tee "/etc/samba/login.conf"
		valid users = ${USER}
		guest ok = no
EOF

	sudo service smbd restart

		#comitup
	. "${WORKING_DIR}/comitup-conf.sh" "${new_PASSWORD}"
fi
