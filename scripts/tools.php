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

	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");

	include("sub-common.php");
	$CloudServices	= get_cloudservices($constants);
	$NVMe_available	= get_nvme_available($WORKING_DIR, $constants, false);

	$Roles	= array('target', 'source');

	$CloudServices_marked	= array();
	foreach($CloudServices as $CloudService) {
		$CloudServices_marked[]	= 'cloud:' . $CloudService;
	}
	$LocalServices	= array('usb');
	if ($NVMe_available) {
		array_push($LocalServices, 'nvme');
	}

	$MountableStorages	= array_merge($LocalServices,$CloudServices_marked);

	function get_device_selector($name, $id='', $nullName='-', $nullValue='-', $storage_type='',$list_partitions=true, $skip_mounted=false, $ignore_fs=false, $OptionValue_is_DeviceIdentifier=false, $html_options='') {
		global $WORKING_DIR;

		$skip_mounted	= $skip_mounted ? 'True' : 'False';
		$ignore_fs		= $ignore_fs ? 'True' : 'False';

		$storage_type	= $storage_type == '' ? '' : "--StorageName ${storage_type}";

		if ($list_partitions) {
			exec("sudo python3 ${WORKING_DIR}/lib_storage.py --Action get_available_partitions ${storage_type} --skipMounted ${skip_mounted} --ignore-fs ${ignore_fs}", $Partitions);
		} else {
			exec("sudo python3 ${WORKING_DIR}/lib_storage.py --Action get_available_devices ${storage_type} --skipMounted ${skip_mounted} --ignore-fs ${ignore_fs}", $Partitions);
		}

		$id	= empty($id) ? $name : $id;

		$selector	= "<select $html_options name='$name' id='$id'>\n";
		$selector	.= "<option value='$nullValue'>$nullName</option>\n";

		foreach ($Partitions as $Partition) {
			if (str_contains($Partition, ':')) {
				list($Lum, $DeviceIdentifier)	= explode(': ',$Partition,2);
			} else {
				$Lum				= $Partition;
				$DeviceIdentifier	= '';
			}
			$Lum	= str_replace('/dev/', '', $Lum);

			$OptionValue	= ($OptionValue_is_DeviceIdentifier and ! empty($DeviceIdentifier)) ? $DeviceIdentifier : $Lum;

			$DeviceIdentifier	= (($DeviceIdentifier !== $Lum) and (! empty($DeviceIdentifier))) ? " ($DeviceIdentifier)" : '';
			$DeviceIdentifier	= str_replace('--','', $DeviceIdentifier);

			$selector .= "<option value='$OptionValue'>$Lum$DeviceIdentifier</option>\n";
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

<body onload="refreshLogMonitor('true')" <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo l::tools_tools; ?></h1>
	<div class="card">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::tools_mount_header; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
				<?php
					$MountsList	= shell_exec("sudo python3 ${WORKING_DIR}/lib_storage.py --Action get_mounts_list");

					$l_Roles	= array(
							'target'	=> l::tools_mount_target,
							'source'	=> l::tools_mount_source
					);

					$ButtonClass		= '';
					$MountableStorage_old	= '';
					foreach($MountableStorages as $MountableStorage) {
						print('<div class="backupsection">');
						foreach($Roles as $Role) {

							$Storage		= $Role . "_" . $MountableStorage;
							$explodeMountableStorage	= explode(':',$MountableStorage,2);
							$LabelName		= end($explodeMountableStorage);

							if (@substr_compare($MountableStorage, 'cloud:', 0, strlen('cloud:'))==0) {
								$ButtonClass	= 'cloud';
							}
							elseif ($MountableStorage == 'nvme') {
								$ButtonClass	= 'usb';
								$LabelName		= l::tools_mount_nvme;
							}
							else {
								$ButtonClass	= 'usb';
								$LabelName		= l::tools_mount_usb;
							}

							if (($MountableStorage != 'usb' and $MountableStorage_old=='usb') or ($MountableStorage != 'nvme' and $MountableStorage_old=='nvme')) {
								echo ("<label for='DeviceIdentifierPreset_${MountableStorage_old}'>".L::tools_mount_select_partition_label.': </label>');
								echo (get_device_selector(
									name: 'DeviceIdentifierPreset_'.$MountableStorage_old,
									id: 'DeviceIdentifierPreset',
									nullName: L::main_backup_preset_partition_auto,
									nullValue: '', storage_type: $MountableStorage_old,
									list_partitions: true,
									skip_mounted: true,
									ignore_fs: false,
									OptionValue_is_DeviceIdentifier: true,
									html_options: 'class="usb"')
								);
							}

							$button = strpos($MountsList," $Storage ") !== false ? "<button class='$ButtonClass' name='umount' value='" . $Storage . "'>" . l::tools_umount_b . ": $LabelName " . $l_Roles[$Role] . "</button>" : "<button class='$ButtonClass' name='mount' value='" . $Storage . "'>" . l::tools_mount_b . ": $LabelName " . $l_Roles[$Role] . "</button>";
							echo ($button);

							$MountableStorage_old	= $MountableStorage;
						}

						print('</div>');
					}
				?>
			</form>
	</div>

	<?php include "sub-logmonitor.php";
		logmonitor($sourcefile=$constants['const_LOGFILE'], $title=L::log_logmonitor, $allow_logfile_operations=true);
	?>

	<div class="card" style="margin-top: 3em;">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::tools_repair; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
					<label for="partition"><?php echo l::tools_select_partition ?></label>
						<?php
							echo(get_device_selector(name: "PARAM1"));
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
					<?php echo(get_device_selector(name: "PARAM1", ignore_fs: true)); ?>
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
					<?php echo(get_device_selector(name: "PARAM1",ignore_fs: false)); ?>
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
		$DeviceIdentifierPresetThis	= isset($_POST['DeviceIdentifierPreset_'.$Storage])?$_POST['DeviceIdentifierPreset_'.$Storage]:'';

		$command = "sudo python3 ${WORKING_DIR}/lib_storage.py --Action mount --StorageName $Storage --Role $Role --DeviceIdentifierPresetThis \\'$DeviceIdentifierPresetThis\\'";
		print($command);
		shell_exec ("python3 $WORKING_DIR/lib_log.py 'execute' '' \"${command}\" '1'");

		echo "<script>";
			echo "window.location = window.location.href;";
		echo "</script>";
	}

	elseif (isset($_POST['umount'])) {
		[$Role,$Storage]	= explode('_',$_POST['umount'],2);

		$command = "sudo python3 ${WORKING_DIR}/lib_storage.py --Action umount --StorageName $Storage --Role $Role";
		shell_exec ("python3 $WORKING_DIR/lib_log.py 'execute' '' '${command}' '1'");

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
			shell_exec ("python3 $WORKING_DIR/lib_log.py 'message' \"fsck ${PARAM1} ${PARAM2}\" \"1\"");
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
			shell_exec ("python3 $WORKING_DIR/lib_log.py 'message' \"format ${PARAM1} ${PARAM2}\" \"1\"");
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
				shell_exec ("python3 $WORKING_DIR/lib_log.py 'message' \"format ${PARAM1} ${PARAM2}\" \"1\"");
			}
	}

	?>
</body>

</html>
