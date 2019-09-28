
#!/bin/bash
################################################################################################
#
# file			   :		rsync-progress.sh
# date			   : 		08/09/2019
# author		   :		Simon Thompson (st599)
# copyright		 :		(c) Simon Thompson 2019
# licence		   : 		GNU General Public License version 3
# description	 :		Shell script to which an rsync command output is piped (see Usage:)
#				       :			Extracts filename, total percentage complete, transfer rate per file
#				       :			Echoes output to commandline stdout
#
################################################################################################


# Variables
OLEDBIN="/home/$USER/source/ssd1306_rpi/oled"
filename=

[[ $1 ]] || {
  echo "Usage: rsync -P --exclude=exclude-file ... | $0 exclude-file" >&2
  exit 1
}

while IFS=$'\n' read -r -d $'\r' -a pieces; do

  for piece in "${pieces[@]}"; do
    case $piece in
      "sending incremental file list") continue ;;
      [[:space:]]*)
        read -r size pct rate time <<<"$piece"
        $OLEDBIN r
        $OLEDBIN +a "Backup progress:"
        $OLEDBIN +b "$pct - $rate"
        sudo $OLEDBIN s
        echo "Backup progress: $pct - $rate"
        ;;
      *) filename=$piece;  ;;
    esac
  done
done
