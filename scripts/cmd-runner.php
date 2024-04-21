<!doctype html>

<?php
/*
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
#######################################################################*/

	ini_set("session.use_only_cookies", 0);
	ini_set("session.use_trans_sid", 1);

	session_start();
	session_write_close();

	$CMD			= $_SESSION['CMD'];
	$PARAM1			= $_SESSION['PARAM1'];
	$PARAM2			= $_SESSION['PARAM2'];
	$MAIL_RESULT	= $_SESSION['MAIL_RESULT'];

	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include "${WORKING_DIR}/sub-security.php";

	function doFlush() {
		if (!headers_sent()) {
			// Disable gzip in PHP.
			ini_set('zlib.output_compression', 0);

			// Force disable compression in a header.
			// Required for flush in some cases (Apache + mod_proxy, nginx, php-fpm).
			header('Content-Encoding: none');
		}

		// Fill-up buffer (should be enough in most cases).
		echo str_pad('', ini_get('output_buffering')),"\n";

		// Flush all buffers.
		do {
			$flushed = @ob_end_flush();
		} while ($flushed);

		@ob_flush();
		flush();
	}
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
	<head>
	</head>
	<body>
		<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

		<?php
// 			allowed parameters

			if ($CMD !== '') {
				echo '<div id="command_output"></div>';
			} else {
				echo "NOT AUTHORISED";
			}
		?>
	</body>
</html>
<?php
	if ($CMD !== '') {

		switch($CMD) {
			case 'update':
				$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_update_start1 . "' ':" . L::box_cmd_update_start2 . "'";
				$COMMAND_LINE	.= ";cd ~pi; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | sudo -u pi bash";
				break;

			case 'update_development':
				$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_update_start1 . "' ':" . L::box_cmd_update_start2 . "'";
				$COMMAND_LINE	.= ";cd ~pi; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/development/install-little-backup-box.sh | sudo -u pi bash -s -- development";
				break;

			case 'fsck':
				$DEVICE_FSTYPE	= exec("sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep /dev/".clean_argument($PARAM1));
				$DEVICE_FSTYPE	= explode('FSTYPE=',$DEVICE_FSTYPE)[1];
				$DEVICE_FSTYPE	= explode('"',$DEVICE_FSTYPE)[1];

				if ($PARAM2 == 'repair') {
					if ($DEVICE_FSTYPE	== 'exfat') {
						$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE -p -y '/dev/".clean_argument($PARAM1)."'";
					}
					else {
						$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE -p -f -y '/dev/".clean_argument($PARAM1)."'";
					}
				}
				else {
					$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE -p '/dev/".clean_argument($PARAM1)."'";
				}

				$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_fsck_start1 . "' ':" . L::box_cmd_fsck_start2 . "' ':" . clean_argument($PARAM2,array(' ')) . "'";
				$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
				$COMMAND_LINE	.= ";echo ''";
				$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
				$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
				$COMMAND_LINE	.= ";echo ''";
				$COMMAND_LINE	.= ";echo 'FINISHED.'";
				$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_fsck_stop1 . "' ':" . L::box_cmd_fsck_stop2 . "' ':" . clean_argument($PARAM2,array(' ')) . "'";
				break;

			case 'format':
				if (($PARAM1 !== "-") and ($PARAM1 !== " ") and ($PARAM2 !== "-") and ($PARAM2 !== " ")) {
					if ($PARAM2 == "FAT32") {
						$MAIN_COMMAND	= "mkfs.vfat -v -I -F32 '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "exFAT") {
						$MAIN_COMMAND	= "mkfs.exfat '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "NTFS (compression enabled)") {
						$MAIN_COMMAND	= "mkfs.ntfs --enable-compression --force --verbose '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "NTFS (no compression)") {
						$MAIN_COMMAND	= "mkfs.ntfs --force --verbose '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "Ext4") {
						$MAIN_COMMAND	= "mkfs.ext4 -v -F '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "Ext3") {
						$MAIN_COMMAND	= "mkfs.ext3 -v -F '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "HFS Plus") {
						$MAIN_COMMAND	= "mkfs.hfsplus '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck.hfsplus '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "HFS") {
						$MAIN_COMMAND	= "mkfs.hfs '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck.hfs '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					else {
						$COMMAND_LINE	= '';
					}

				} else {
					$COMMAND_LINE	= '';
				}
				break;

			case 'f3':
				switch($PARAM2) {
					case 'f3probe_non_destructive':
						$MAIN_COMMAND	= "f3probe --time-ops '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_start1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_non_destructive . "' ':" . L::box_cmd_f3_probe_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_non_destructive . "' ':" . L::box_cmd_f3_probe_stop2 . "'";
						break;

					case 'f3probe_destructive':
						$MAIN_COMMAND	= "f3probe --destructive --time-ops '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_start1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_destructive . "' ':" . L::box_cmd_f3_probe_start2 . "'";
						$COMMAND_LINE	.= ";sudo umount '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_destructive . "' ':" . L::box_cmd_f3_probe_stop2 . "'";
						break;

					default:
						$COMMAND_LINE	= "";
				}
				break;

			case 'comitup_reset':
				$MAIN_COMMAND	= "$WORKING_DIR/comitup-reset.sh";

				$COMMAND_LINE	= "cat $MAIN_COMMAND | grep '^[^# ]'";
				$COMMAND_LINE	.= ";echo ''";
				$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
				$COMMAND_LINE	.= ";echo ''";
				$COMMAND_LINE	.= ";echo 'FINISHED.'";
				$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::config_comitup_section . "' ':" . L::cmd_reset . "'";
				break;

			default:
				$COMMAND_LINE	= "";
		}

		ob_implicit_flush(true);
		ob_start();

		# write lockfile
		$lockfile = fopen($constants["const_CMD_RUNNER_LOCKFILE"],"w");
			fwrite($lockfile, $COMMAND_LINE);
			fclose($lockfile);

		$descriptorspec = array(
		0 => array("pipe", "r"),	// stdin
		1 => array("pipe", "w"),	// stdout
		2 => array("pipe", "w")		// stderr
		);

		# start command
		$process = proc_open($COMMAND_LINE, $descriptorspec, $pipes);
		if (is_resource($process)) {
			fclose($pipes[0]);
			stream_set_blocking($pipes[1], true);
			stream_set_blocking($pipes[2], false);

			echo 'WORKING...<br>';

			$RESULT = '';
			while ($s = fgets($pipes[1], ini_get('output_buffering'))) {
				echo $s."<br>";
				$RESULT .= $s;
				doFlush();
			}

			$error	= stream_get_contents($pipes[2], ini_get('output_buffering'));
			if ($error) {
				$error	= "\nErrors:\n$error";
				echo(str_replace("\n", "<br>\n", $error));
				$RESULT .= $error;
			}
		}

		fastcgi_finish_request();

		fclose($pipes[1]);
		fclose($pipes[2]);
		proc_close($process);

		unlink($constants["const_CMD_RUNNER_LOCKFILE"]);

		if ($MAIL_RESULT) {
			$RESULT=str_replace('`',"'",$RESULT);
			$RESULT=str_replace('"',"'",$RESULT);

			shell_exec('sudo python3 ' . $WORKING_DIR . '/lib_mail.py "' . $CMD . ' ' . clean_argument($PARAM1,array(' ')) . ' ' . clean_argument($PARAM2,array(' ')) . '" "' . $RESULT . '"');
		}
	}


?>
