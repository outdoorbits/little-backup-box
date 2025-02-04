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

if [ -d /tmp/apt ]; then
	sudo rm -R /tmp/apt
fi

mkdir -p /tmp/apt

cd /tmp/apt

sudo wget http://ftp.debian.org/debian/pool/main/libr/libraw/libraw-bin_0.21.3-1+b1_arm64.deb
sudo wget http://ftp.debian.org/debian/pool/main/libr/libraw/libraw23t64_0.21.3-1+b1_arm64.deb

sudo DEBIAN_FRONTEND=noninteractive apt-get -o "Dpkg::Options::=--force-confold" -o "Dpkg::Options::=--force-confdef" install ./libraw23t64_0.21.3-1+b1_arm64.deb ./libraw-bin_0.21.3-1+b1_arm64.deb

cd ../
sudo rm -R ./apt
