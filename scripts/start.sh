#!/usr/bin/env bash

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

# IMPORTANT:
# Run the install-little-backup-box.sh script first
# to install the required packages and configure the system.

oled r
secs=11
while [ $secs -gt 1 ]; do
   sleep 1
   : $((secs--))
   oled +a "Ready in $secs sec."
   oled +b "******************"
   oled s
done

ip=$(hostname -I | cut -d' ' -f1)
if [ -z "$ip" ]; then
    a="Hello! I'm not"
    b="on the network"
else
    a="Hello! I'm here:"
    b=$ip
fi
echo "$ip"
oled r
oled +a "$a"
oled +b "$b"
sudo oled s

