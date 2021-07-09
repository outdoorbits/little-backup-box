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

CONFIG_DIR=$(dirname "$0")
CONFIG="${CONFIG_DIR}/config.cfg"
sudo dos2unix "$CONFIG"
source "$CONFIG"

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"

# Mount iOS device
ifuse $MOUNT_IOS_DIR
SOURCE_DIR="$MOUNT_IOS_DIR/DCIM/100APPLE"

# Set the ACT LED to blink at 1000ms to indicate that the storage device has been mounted
sudo sh -c "echo timer > /sys/class/leds/led0/trigger"
sudo sh -c "echo 1000 > /sys/class/leds/led0/delay_on"

# Perform backup using rsync
if [ $LOG = true ]; then
    sudo rm /root/little-backup-box.log
    RSYNC_OUTPUT=$(rsync -avh --stats --log-file=little-backup-box.log "$SOURCE_DIR"/ "$BAK_DIR")
else
    RSYNC_OUTPUT=$(rsync -avh --stats "$SOURCE_DIR"/ "$BAK_DIR")
fi

# Check internet connection and send
# a notification if the NOTIFY option is enabled
check=$(wget -q --spider http://google.com/)
if [ $NOTIFY = true ] || [ ! -z "$check" ]; then
	curl --url 'smtps://'$SMTP_SERVER':'$SMTP_PORT --ssl-reqd \
		--mail-from $MAIL_USER \
		--mail-rcpt $MAIL_TO \
		--user $MAIL_USER':'$MAIL_PASSWORD \
	-T <(echo -e "From: ${MAIL_USER}\nTo: ${MAIL_TO}\nSubject: Little Backup Box: Backup complete\n\nBackup log:\n\n${RSYNC_OUTPUT}")
fi
sudo umount $MOUNT_IOS_DIR

# Set the ACT LED to heartbeat
sudo sh -c "echo heartbeat > /sys/class/leds/led0/trigger"
