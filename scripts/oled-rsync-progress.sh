
#!/bin/bash
################################################################################################
#
# file			:		rsync-progress.sh
# date			: 		08/09/2019
# author		:		Simon Thompson (st599)
# copyright		:		(c) Simon Thompson 2019
# licence		: 		GNU General Public License version 3
# description	:		Shell script to which an rsync command output is piped (see Usage:)
#				:			Extracts filename, total percentage complete, transfer rate per file
#				:			Echoes output to commandline stdout
# todo			:		1) Update Usage to reflect rsync command in little backup box
#				:		2) Format $PRINTSTR to match OLED/LCD frame size
#				:		3) Pipe/Redirect $PRINTSTR to OLED/LCD
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
file_count=0
logfile=rsync.log




# Echo Date to Log File
NOW=$(date +"%d%m%Y %H%M%S")
echo $NOW>>$logfile


while IFS=$'\n' read -r -d $'\r' -a pieces; do
  
  for piece in "${pieces[@]}"; do
    case $piece in
      "sending incremental file list") continue ;;
      [[:space:]]*)
        read -r size pct rate time <<<"$piece"
		PRINTSTR="$file_count = FILE: $filename - TOTAL: $pct - RATE: $rate"
		echo $PRINTSTR>>$logfile
		
		file_count=$((file_count + 1))
		
        ;;
      *) filename=$piece;  ;;
    esac
  done
done