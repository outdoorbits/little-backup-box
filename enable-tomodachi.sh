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

sudo bash -c 'echo "enable_uart=1" >> "/boot/config.txt"'
sudo bash -c 'echo "dtparam=act_led_gpio=11" >> "/boot/config.txt"'
dialog --clear \
   --title "Reboot" \
   --msgbox "All done! Press OK to reboot the system." 5 45
clear
sudo reboot
