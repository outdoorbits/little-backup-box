**Setup a RSYNC-Server**\
*Install rsync*\
`sudo apt-get install rsync -y`\
\
*Config-Files*\
/etc/rsyncd.conf:

	#global (global settings)
	log file = /var/log/rsync.log
	timeout = 300
	max connections = 0
	
	#profiles
	[MODULENAME]
	path = /ABSOLUTE/PATH/AT/YOUR/SERVER
	comment = MODULENAME
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
\
/etc/rsyncd.secrets:

	USERNAME:conf_PASSWORD

/etc/rsyncd.secrets needs permissions 600.

	chmod 600 /etc/rsyncd.secrets
