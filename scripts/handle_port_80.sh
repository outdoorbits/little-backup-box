#!/bin/bash

# called by comitup (external_callback) to toggle port 80 on/off

echo "Listen 8000" | sudo tee "/etc/apache2/ports.conf"
echo "Listen 443"  | sudo tee -a "/etc/apache2/ports.conf"
echo "Listen 81"  | sudo tee -a "/etc/apache2/ports.conf"
echo "Listen 8443"  | sudo tee -a "/etc/apache2/ports.conf"

if [ "$1" = "CONNECTED" ]; then
	echo "Listen 80" | sudo tee -a "/etc/apache2/ports.conf"
fi

sudo service apache2 restart || service apache2 start

exit 0
