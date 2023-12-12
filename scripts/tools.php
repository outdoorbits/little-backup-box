<!doctype html>

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");

	$Roles	= array('target', 'source');
	include("get-cloudservices.php");
	$CloudServices_marked	= array();
	foreach($CloudServices as $CloudService) {
		$CloudServices_marked[]	= 'cloud:' . $CloudService;
	}
	$LocalServices	= array('usb');
	$MountableStorages	= array_merge($LocalServices,$CloudServices_marked);

	function get_device_selector($name, $list_partitions=true) {
		if ($list_partitions) {
			exec("ls /dev/sd* | xargs -n 1 basename", $devices);
		} else {
			exec("ls /dev/sd* | xargs -n 1 basename | grep -v '[0123456789]'", $devices);
		}

		$selector	= '<select name="' . $name . '">\n';
		$selector .= "<option value='-'>-</option>\n";
		foreach ($devices as $n => $device) {
			$selector .= "<option value='$device'>$device</option>\n";
		}
		$selector .= "</select>";
		return($selector);
	}
	?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Stefan Saam github@saams.de, Dmitri Popov, dmpop@linux.com
		License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
	<script src="js/logmonitor.js"></script>
</head>

<body onload="refreshLogMonitor()" <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo l::tools_tools; ?></h1>
	<div class="card">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::tools_mount_header; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
				<?php
					$MountsList	= shell_exec("sudo python3 ${WORKING_DIR}/lib_storage.py get_mounts_list");

					$l_Roles	= array(
							'target'	=> l::tools_mount_target,
							'source'	=> l::tools_mount_source
					);

					$ButtonClass		= '';
					$ButtonClass_old	= '';
					foreach($MountableStorages as $MountableStorage) {
						print('<div class="backupsection">');
						foreach($Roles as $Role) {

							$Storage		= $Role . "_" . $MountableStorage;
							$explodeMountableStorage	= explode(':',$MountableStorage,2);
							$LabelName		= end($explodeMountableStorage);

							$ButtonClass_old	= $ButtonClass;

							if (@substr_compare($MountableStorage, 'cloud:', 0, strlen('cloud:'))==0) {
								$ButtonClass	= 'cloud';
							}
							else {
								$ButtonClass	= 'usb';
								$LabelName		= l::tools_mount_usb;
							}

							if ($ButtonClass != 'usb' and $ButtonClass_old=='usb') {
								$Partitions	= shell_exec("sudo python3 ${WORKING_DIR}/lib_storage.py get_available_partitions True");
								$PartisionsArray	= explode(',', $Partitions);

								echo ('<label for="DeviceIdentifierPreset">'.L::tools_mount_select_partition_label.': </label>');
								echo ('<select class="usb" id="DeviceIdentifierPreset" name="DeviceIdentifierPreset">');
									echo ("<option value=''>".L::main_backup_preset_partition_auto."</option>");
									foreach ($PartisionsArray as $Partition) {
										$Partition	= trim($Partition, "[]' \n");
										list($Lum, $DeviceIdentifier)	= explode(':',$Partition,2);
										$Lum	= trim($Lum,'/dev');
										echo "<option value='".$DeviceIdentifier."'>".$Lum.($DeviceIdentifier!=''?" (".trim($DeviceIdentifier, '-').")":'')."</option>";
									}
								echo ('</select><br>');
							}

							$button = strpos($MountsList," $Storage ") !== false ? "<button class='$ButtonClass' name='umount' value='" . $Storage . "'>" . l::tools_umount_b . ": $LabelName " . $l_Roles[$Role] . "</button>" : "<button class='$ButtonClass' name='mount' value='" . $Storage . "'>" . l::tools_mount_b . ": $LabelName " . $l_Roles[$Role] . "</button>";
							echo ($button);
						}

						print('</div>');
					}
				?>
			</form>
	</div>

	<?php include "sub-logmonitor.php"; ?>

	<div class="card" style="margin-top: 3em;">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::tools_repair; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
					<label for="partition"><?php echo l::tools_select_partition ?></label>
						<?php
						print(get_device_selector("PARAM1"));
						echo ("<button name='fsck_check'>" . l::tools_fsck_check_b . "</button>");
						echo ("<button name='fsck_autorepair' class='danger'>" . l::tools_fsck_autorepair_b . "</button>");
						?>
			</form>
	</div>

	<div class="card" style="margin-top: 3em;">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::cmd_format_header; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
					<label for="PARAM1"><?php echo l::tools_select_partition; ?>:</label>
					<br>
					<?php
					print(get_device_selector("PARAM1"));
					?>
					<br>
					<label for="PARAM2"><?php echo l::tools_select_format_fstype; ?>:</label>
					<br>
					<select name="PARAM2">
						<option value="-">-</option>
						<option value="FAT32">FAT32 (Windows&#174;)</option>
						<option value="exFAT">exFAT (Windows&#174;)</option>
						<option value="NTFS (compression enabled)">NTFS (compression enabled) (Windows&#174;)</option>
						<option value="NTFS (no compression)">NTFS (compression disabled) (Windows&#174;)</option>
						<option value="Ext4">Ext4 (Linux)</option>
						<option value="Ext3">Ext3 (Linux)</option>
						<option value="HFS Plus">HFS Plus (Mac)</option>
						<option value="HFS">HFS (Mac)</option>
					</select>
					<br>
					<?php
					echo ("<button name='format' class='danger'>" . l::tools_format_b . "</button>");
					?>
			</form>
	</div>

	<div class="card" style="margin-top: 3em;">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::cmd_f3_header; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
					<label for="PARAM1"><?php echo l::tools_select_partition; ?>:</label>
					<br>
					<?php
					print(get_device_selector("PARAM1",false));
					?>
					<br>
					<label for="PARAM2"><?php echo l::tools_f3_select_action; ?>:</label>
					<br>
					<select name="PARAM2">
						<option value="-">-</option>
						<option value="f3probe_non_destructive"><?php echo l::tools_f3_probe_non_destructive; ?></option>
						<option value="f3probe_destructive"><?php echo l::tools_f3_probe_destructive; ?></option>
					</select>
					<br>
					<?php
					echo ("<button name='f3' class='danger'>" . l::tools_f3_b . "</button>");
					?>
			</form>
	</div>

	<div class="card" style="margin-top: 3em;">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo l::tools_help; ?></summary>
			<p><?php echo l::tools_help_text; ?></p>
		</details>
	</div>

	<?php include "sub-footer.php"; ?>

	<?php

	if (isset($_POST['mount'])) {
		[$Role,$Storage]	= explode('_',$_POST['mount'],2);
		$DeviceIdentifierPresetThis	= isset($_POST['DeviceIdentifierPreset'])?$_POST['DeviceIdentifierPreset']:'';

		$command = "sudo python3 $WORKING_DIR/lib_storage.py mount $Storage $Role True '$DeviceIdentifierPresetThis'";
// 		print$command;
		shell_exec ("python3 lib_log.py 'execute' '' '${command}' '1'");

		echo "<script>";
			echo "window.location = window.location.href;";
		echo "</script>";
	}

	elseif (isset($_POST['umount'])) {
		[$Role,$Storage]	= explode('_',$_POST['umount'],2);

		$command = "sudo python3 $WORKING_DIR/lib_storage.py umount $Storage $Role";
// 		print($command . '<br>');
		shell_exec ("python3 lib_log.py 'execute' '' '${command}' '1'");

		echo "<script>";
			echo "window.location = window.location.href;";
		echo "</script>";
	}

	elseif (isset($_POST['fsck_check']) or isset($_POST['fsck_autorepair'])) {

		$PARAM1 = $_POST['PARAM1'];
		$PARAM2 = isset($_POST['fsck_check']) ? 'check' : 'repair';

		if (($PARAM1 !== "-") and ($PARAM2 !== "-")) {
			?>
			<script>
					document.location.href="/cmd.php?CMD=fsck&PARAM1=<?php echo $PARAM1; ?>&PARAM2=<?php echo $PARAM2; ?>";
			</script>
			<?php
			shell_exec ("python3 lib_log.py 'message' \"fsck ${PARAM1} ${PARAM2}\" \"1\"");
		}
	}

	elseif (isset($_POST['format'])) {

		$PARAM1 = $_POST['PARAM1'];
		$PARAM2 = $_POST['PARAM2'];

		if (($PARAM1 !== "-") and ($PARAM2 !== "-")) {
			?>
			<script>
					document.location.href="/cmd.php?CMD=format&PARAM1=<?php echo $PARAM1; ?>&PARAM2=<?php echo $PARAM2; ?>";
			</script>
			<?php
			shell_exec ("python3 lib_log.py 'message' \"format ${PARAM1} ${PARAM2}\" \"1\"");
		}
	}

	elseif (isset($_POST['f3'])) {

			$PARAM1 = $_POST['PARAM1'];
			$PARAM2 = $_POST['PARAM2'];

			if (($PARAM1 !== "-") and ($PARAM2 !== "-")) {
				?>
				<script>
						document.location.href="/cmd.php?CMD=f3&PARAM1=<?php echo $PARAM1; ?>&PARAM2=<?php echo $PARAM2; ?>";
				</script>
				<?php
				shell_exec ("python3 lib_log.py 'message' \"format ${PARAM1} ${PARAM2}\" \"1\"");
			}
	}

	?>
</body>

</html>
