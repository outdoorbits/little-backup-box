<!-- Author: Stefan Saam, github@saams.de
License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
	<head>
		<script language="javascript">
			var int = self.setInterval("window.scrollBy(0,1000);", 200);
		</script>
	</head>
	<body>
		<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

		<?php
			extract($_GET);

			$MAIL_RESULT = isset($MAIL_RESULT);

			if ((strpos($_SERVER["HTTP_REFERER"],"/cmd.php") !== False) and isset($CMD)) {

				switch($CMD) {
					case 'update':
						$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_update_start1 . "' ':" . L::box_cmd_update_start2 . "'";
						$COMMAND_LINE	.= ";cd ~pi; curl -sSL -H 'Cache-Control: no-cache'" . $config["conf_INSTALLER_URL"] . " | sudo -u pi bash -s " . $config["conf_INSTALLER_URL"];
						break;

					case 'format':
						if (($PARAM1 !== "-") and ($PARAM2 !== "-")) {
							if ($PARAM2 == "FAT32") {
								$MAIN_COMMAND	= "mkfs.vfat -v -I -F32 /dev/$PARAM1";

								$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_start1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_stop1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "exFAT") {
								$MAIN_COMMAND	= "mkfs.exfat /dev/$PARAM1";

								$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_start1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_stop1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "NTFS (compression enabled)") {
								$MAIN_COMMAND	= "mkfs.ntfs --enable-compression --force --verbose /dev/$PARAM1";

								$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_start1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_stop1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "NTFS (no compression)") {
								$MAIN_COMMAND	= "mkfs.ntfs --force --verbose /dev/$PARAM1";

								$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_start1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_stop1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "Ext4") {
								$MAIN_COMMAND	= "mkfs.ext4 -v -F /dev/$PARAM1";

								$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_start1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_stop1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "Ext3") {
								$MAIN_COMMAND	= "mkfs.ext3 -v -F /dev/$PARAM1";

								$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_start1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_stop1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "HFS Plus") {
								$MAIN_COMMAND	= "mkfs.hfsplus /dev/$PARAM1";

								$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_start1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck.hfsplus /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_stop1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_stop2 . "'";
							}

							elseif ($PARAM2 == "HFS") {
								$MAIN_COMMAND	= "mkfs.hfs /dev/$PARAM1";

								$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_start1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_start2 . "'";
								$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fdisk -l /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";lsblk -f /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";sudo fsck.hfs /dev/$PARAM1";
								$COMMAND_LINE	.= ";echo ''";
								$COMMAND_LINE	.= ";echo 'FINISHED.'";
								$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_format_stop1 . "' ':$PARAM1: $PARAM2' ':" . L::box_cmd_format_stop2 . "'";
							}

						} else {
							$COMMAND_LINE	= "";
						}
						break;

					case 'f3':
						switch($PARAM2) {
							case 'f3probe_non_destructive':
									$MAIN_COMMAND	= "f3probe --time-ops /dev/$PARAM1";

									$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_f3_probe_start1 . "' ':$PARAM1: " . L::box_cmd_f3_probe_non_destructive . "' ':" . L::box_cmd_f3_probe_start2 . "'";
									$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
									$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
									$COMMAND_LINE	.= ";echo ''";
									$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
									$COMMAND_LINE	.= ";echo ''";
									$COMMAND_LINE	.= ";echo 'FINISHED.'";
									$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_f3_probe_stop1 . "' ':$PARAM1: " . L::box_cmd_f3_probe_non_destructive . "' ':" . L::box_cmd_f3_probe_stop2 . "'";
								break;

							case 'f3probe_destructive':
									$MAIN_COMMAND	= "f3probe --destructive --time-ops /dev/$PARAM1";

									$COMMAND_LINE	= "sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_f3_probe_start1 . "' ':$PARAM1: " . L::box_cmd_f3_probe_destructive . "' ':" . L::box_cmd_f3_probe_start2 . "'";
									$COMMAND_LINE	.= ";sudo umount /dev/$PARAM1";
									$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
									$COMMAND_LINE	.= ";echo ''";
									$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
									$COMMAND_LINE	.= ";echo ''";
									$COMMAND_LINE	.= ";echo 'FINISHED.'";
									$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::box_cmd_f3_probe_stop1 . "' ':$PARAM1: " . L::box_cmd_f3_probe_destructive . "' ':" . L::box_cmd_f3_probe_stop2 . "'";
								break;

							default:
								$COMMAND_LINE	= "";
						}
						break;

							case 'comitup':
									$MAIN_COMMAND	= "comitup-cli d";

									$COMMAND_LINE	= "echo 'sudo $MAIN_COMMAND'";
									$COMMAND_LINE	.= ";echo ''";
									$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
									$COMMAND_LINE	.= ";echo ''";
									$COMMAND_LINE	.= ";echo 'FINISHED.'";
									$COMMAND_LINE	.= ";sudo $WORKING_DIR/lib-display-helper.sh ':" . L::config_comitup_section . "' ':" . L::cmd_reset . "'";
								break;
					default:
						$COMMAND_LINE	= "";
				}

				if ($COMMAND_LINE != "") {
					ob_implicit_flush(true);
					ob_end_flush();

					$descriptorspec = array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w")
					);

					# write lockfile
					$lockfile = fopen($constants["const_CMD_RUNNER_LOCKFILE"],"w");
					fwrite($lockfile, $COMMAND_LINE);
					fclose($lockfile);

					# start command
					echo ('WORKING...');
// 					echo ($COMMAND_LINE);
					$process = proc_open($COMMAND_LINE, $descriptorspec, $pipes, realpath('./'), array());

					echo '<pre>';
					if (is_resource($process)) {
						$RESULT = '';
						while ($s = fgets($pipes[1])) {
							print $s;
							$RESULT .= $s;
						}
					}
					echo '</pre>';

					unlink($constants["const_CMD_RUNNER_LOCKFILE"]);

					if ($MAIL_RESULT) {
						$RESULT=str_replace('`',"'",$RESULT);
						$RESULT=str_replace('"',"'",$RESULT);

						shell_exec('sudo ' . $WORKING_DIR . '/lib-mail-helper.sh "' . $CMD . ' ' . $PARAM1 . ' ' . $PARAM2 . '" "' . $RESULT . '"');
					}
				}
			} else {
				echo "NOT AUTHORISED";
			}
		?>

	</body>
</html>

