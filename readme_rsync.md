## Setup a RSYNC-Server
<br>
## Install rsync
sudo apt install rsync -y<br>
<br>
## Config-Files
# /etc/rsyncd.conf
nano /etc/rsyncd.conf<br>
<br>
/etc/rsyncd.conf:
#global (global settings)<br>
log file = /var/log/rsync.log<br>
timeout = 300<br>
<br>
#profiles
[USERNAME]<br>
path = /ABSOLUTE/PATH/AT/YOUR/SERVER<br>
comment = USERNAME<br>
max connections = 2<br>
hosts allow = *<br>
#hosts deny = *<br>
use chroot = yes<br>
list = true<br>
uid = USER-ID-NUMBER<br>
gid = GROUP-ID-NUMBER<br>
read only = false<br>
auth users = USERNAME<br>
secrets file = /etc/rsyncd.secrets<br>
<br>
# /etc/rsyncd.secrets
nano /etc/rsyncd.secrets<br>
<br>
/etc/rsyncd.secrets:<br>
USERNAME:PASSWORD<br>
