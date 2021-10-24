## Setup a RSYNC-Server

## Install rsync
sudo apt install rsync -y

## Config-Files
# /etc/rsyncd.conf
nano /etc/rsyncd.conf

/etc/rsyncd.conf:
#global (global settings)
log file = /var/log/rsync.log
timeout = 300

#profiles
[USERNAME]
path = /ABSOLUTE/PATH/AT/YOUR/SERVER
comment = USERNAME
max connections = 2
hosts allow = *
#hosts deny = *
use chroot = yes
list = true
uid = USER-ID-NUMBER
gid = GROUP-ID-NUMBER
read only = false
auth users = USERNAME
secrets file = /etc/rsyncd.secrets

# /etc/rsyncd.secrets
nano /etc/rsyncd.secrets

/etc/rsyncd.secrets:
USERNAME:PASSWORD
