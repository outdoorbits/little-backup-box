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
oled +a "  LITTLE BACKUP "
oled +b "       BOX      "
oled +c " by Dmitri Popov"
oled +R 1
oled +R 2

secs=11
while [ $secs -gt 1 ]; do
   sleep 1
   : $((secs--))
   oled +d "Ready in $secs sec."
   oled s
done

ip=$(hostname -I | cut -d' ' -f1)
if [ -z "$ip" ]; then
    c="Hello! I'm not"
    d="on the network"
else
    c="Hello! I'm here:"
    d=$ip
fi
echo "$ip"
oled +c "$c"
oled +d "$d"
sudo oled s

