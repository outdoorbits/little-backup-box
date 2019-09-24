
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


# Catch incorrect commandline and print usage information
[[ $1 ]] || {
  echo "Usage:  rsync -Pah --info=progress2 --exclude=exclude-file ... | $0 exclude-file" >&2
  exit 1
}

# Variables
exclude_file=$1
filename=


while IFS=$'\n' read -r -d $'\r' -a pieces; do

  for piece in "${pieces[@]}"; do
    case $piece in
      "sending incremental file list") continue ;;
      [[:space:]]*)
        read -r size pct rate time <<<"$piece"
        oled r
        oled +a "Backup progress:"
        oled +b "$pct - $rate"
        sudo oled s
        ;;
      *) filename=$piece;  ;;
    esac
  done
done
