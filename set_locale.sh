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

# settings
TARGET_LOCALE="en_GB.UTF-8"
PI_HOME="/home/pi"

# Change system locale
sudo raspi-config nonint do_change_locale ${TARGET_LOCALE}

# Apply system default locale safely (force neutral LC to avoid Perl warnings)
sudo LC_ALL=C LANG=C update-locale LANG=${TARGET_LOCALE}

# Clean up system files that may still define old LC_* variables
sudo sed -i -E '/^LC_/d' /etc/default/locale || true
echo 'LANG=${TARGET_LOCALE}' | sudo tee /etc/default/locale >/dev/null

# /etc/environment: remove old LC_* entries and enforce LANG=${TARGET_LOCALE}
if grep -qE '^(LANG=|LC_)' /etc/environment 2>/dev/null; then
  sudo sed -i -E '/^LC_/d' /etc/environment
  if grep -q '^LANG=' /etc/environment; then
    sudo sed -i -E 's/^LANG=.*/LANG=${TARGET_LOCALE}/' /etc/environment
  else
    echo 'LANG=${TARGET_LOCALE}' | sudo tee -a /etc/environment >/dev/null
  fi
fi

# Clean user-level files that might override locale settings
## Helper function: returns the home directory of a given user
home_of() { getent passwd "$1" | awk -F: '{print $6}'; }

for USER_NAME in pi root; do
	USER_HOME="$(home_of "${USER_NAME}")"
	if [[ -n "${USER_HOME}" && -d "${USER_HOME}" ]]; then
		sudo sed -i -E '/^LC_|^LANG=/d' "${USER_HOME}/.profile" "${USER_HOME}/.bashrc" 2>/dev/null || true
		sudo sed -i -E '/^LC_|^LANG=/d' "${USER_HOME}/.pam_environment" 2>/dev/null || true
		sudo chown "${USER_NAME}:${USER_NAME}" "${USER_HOME}/.profile" "${USER_HOME}/.bashrc" 2>/dev/null || true
		sudo chown "${USER_NAME}:${USER_NAME}" "${USER_HOME}/.pam_environment" 2>/dev/null || true
	fi
done

# future users: clean /etc/skel, do not inherit  per-user overrides
if [[ -d /etc/skel ]]; then
  sudo sed -i -E '/LC_|^LANG=/d' /etc/skel/.profile 2>/dev/null || true
  sudo sed -i -E '/LC_|^LANG=/d' /etc/skel/.bashrc 2>/dev/null || true
  sudo sed -i -E '/LC_|^LANG=/d' /etc/skel/.pam_environment 2>/dev/null || true
fi

# Immediately load the new locale into the current shell (no reboot required)
unset LC_ALL LC_CTYPE LC_NUMERIC LC_COLLATE LC_TIME LC_MESSAGES LC_MONETARY LC_ADDRESS LC_IDENTIFICATION LC_MEASUREMENT LC_PAPER LC_TELEPHONE LC_NAME LANGUAGE

# export the new locale
export LANG=${TARGET_LOCALE}

# ensure future sudo commands also use the correct locale
alias sudo='sudo LANG=${TARGET_LOCALE} LC_ALL=${TARGET_LOCALE}'

# ssh: do not import locals
sudo sed -i -E 's/^[# ]*AcceptEnv[[:space:]].*/# AcceptEnv disabled/' /etc/ssh/sshd_config
sudo systemctl restart ssh

# show final locale status for verification
echo "== new locale settings =="
locale

