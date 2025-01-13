<!doctype html>

<!--
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
#######################################################################
-->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-i18n-loader.php");

	if (isset($_POST['form_sent'])) {
		$move_files				= isset($_POST['move_files'])?"True":"False";
		$rename_files			= isset($_POST['rename_files'])?"True":"False";
		$generate_thumbnails	= isset($_POST['generate_thumbnails'])?"True":"False";
		$update_exif			= isset($_POST['update_exif'])?"True":"False";
		$power_off_force		= isset($_POST['power_off'])?"True":"False";

		$preset_source			= isset($_POST['preset_source'])?$_POST['preset_source']:"";
		$preset_target			= isset($_POST['preset_target'])?$_POST['preset_target']:"";
	} else {
		$move_files				= $config['conf_BACKUP_MOVE_FILES']?"True":"False";
		$rename_files			= $config['conf_BACKUP_RENAME_FILES']?"True":"False";
		$generate_thumbnails	= $config['conf_BACKUP_GENERATE_THUMBNAILS']?"True":"False";
		$update_exif			= $config['conf_BACKUP_UPDATE_EXIF']?"True":"False";
		$power_off_force		= $config['conf_POWER_OFF']?"True":"False";

		$preset_source			= '';
		$preset_target			= '';
	}

	include("sub-popup.php");

	include("sub-common.php");
	$NVMe_available	= get_nvme_available(WORKING_DIR: $WORKING_DIR, constants: $constants, ignore_fs: false);
	$CloudServices	= get_cloudservices($constants);

	$LocalAutoServices	= array('anyusb');

	$LocalServices		= array('usb', 'internal');
	if ($NVMe_available) {
		array_push($LocalServices, 'nvme');
	}

	$CameraServices		= array('camera');

	$CloudServices_marked	= array();
	foreach($CloudServices as $CloudService) {
		$CloudServices_marked[]	= 'cloud:' . $CloudService;
	}

	// rsync
	$rsync_configurated	= ($config['conf_RSYNC_SERVER']=='' or $config['conf_RSYNC_PORT']=='' or $config['conf_RSYNC_USER']=='' or $config['conf_RSYNC_PASSWORD']=='' or $config['conf_RSYNC_SERVER_MODULE']=='') == false;
	if ($rsync_configurated) {
		$CloudServices_marked	= array_merge(['cloud_rsync'], $CloudServices_marked);
	}

	$SourceServices	= array(
		'anyusb'	=> $LocalAutoServices,
		'usb'		=> $LocalServices,
		'camera'	=> $CameraServices,
		'cloud'		=> $CloudServices_marked
	);

	$TargetServices			= array(
		'usb'		=> $LocalServices,
		'cloud'		=> $CloudServices_marked
	);
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "sub-standards-header-loader.php"; ?>
	<script type="text/javascript" src="js/logmonitor.js"></script>
	<script type="text/javascript" src="js/partition_presets.js"></script>

	<script>
		function HideDisallowedButtons(ActiveSource) {
			let TargetServices = [<?php 	$Separator = '';
											foreach ($TargetServices as $Group) {
												foreach ($Group as $TargetService)
												{
													print($Separator . "'" . $TargetService . "'");
													$Separator = ', ';
												}
											};?>];

			for (i in TargetServices) {
				let TargetService = TargetServices[i];

// 				find disallowed combinations
				if (
					((TargetService === ActiveSource.value) && (TargetService !== 'usb')) ||
					((ActiveSource.value === 'anyusb') && (TargetService === 'cloud_rsync')) ||
					((ActiveSource.value === 'camera') && (TargetService === 'cloud_rsync'))
				) {
					document.getElementById("Target_" + TargetService).disabled = true;
				} else {
					document.getElementById("Target_" + TargetService).disabled = false;
				}
			}
		}
    </script>
</head>

<?php
	$OldSource = isset($_POST['SourceDevice']) ? $_POST['SourceDevice'] : 'anyusb';
?>
<body onload="refreshLogMonitor();HideDisallowedButtons(document.getElementById('Source_<?php echo $OldSource; ?>'))" <?php echo $background; ?>>
	<?php include "sub-standards-body-loader.php"; ?>
	<?php include "sub-menu.php"; ?>

	<!-- Suppress form re-submit prompt on refresh -->
	<script>
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}
	</script>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;">LITTLE BACKUP BOX</h1>

	<form class="text-center" style="margin-top: 1em;" method="POST">

		<input type="hidden" name="form_sent" value="1">

		<div class="card">
			<div class="row" style="width:100%; float: left; display: block;">
				<div class="column" style="display: block;">
					<?php
						print("<h3>" . l::main_source . "</h3>" . l::main_source_select);
						foreach($SourceServices as $ButtonClass => $Group) {
							print("<div class='radio-$ButtonClass'>");
								foreach($Group as $Storage) {
									$LabelNameExplode		= explode(':', $Storage, 2);
									$LabelName				= end($LabelNameExplode);

									if ($LabelName == 'anyusb') {
										$LabelName		= l::box_backup_mode_anyusb;
									}
									elseif ($LabelName == 'usb') {
										$LabelName		= l::box_backup_mode_usb;
									}
									elseif ($LabelName == 'internal') {
										$LabelName		= l::box_backup_mode_internal;
									}
									elseif ($LabelName == 'nvme') {
										$LabelName		= l::box_backup_mode_nvme;
									}
									elseif ($LabelName == 'camera') {
										$LabelName		= l::box_backup_mode_cameras;
									}
									elseif ($LabelName == 'cloud_rsync') {
										$LabelName		= l::box_backup_mode_cloud_rsync;
									}
									print("<input type='radio' name='SourceDevice' value='$Storage' id='Source_$Storage' onchange='HideDisallowedButtons(this)' " . ($Storage == $OldSource ? 'checked' : '') . ">");
									print("<label for='Source_$Storage'>$LabelName</label></br>");
								}
							print("</div>");
						}
					?>
				</div>
				<div class="column" style="display: block;">
					<?php
						print("<h3>" . l::main_target . "</h3>" . l::main_target_execute);
						foreach($TargetServices as $ButtonClass => $Group) {
							print("<div class='backup-buttons'>");
							foreach($Group as $Storage) {
								$LabelNameExplode		= explode(':', $Storage, 2);
								$LabelName				= end($LabelNameExplode);

								if ($LabelName == 'usb') {
									$LabelName		= l::tools_mount_usb;
								}
								elseif ($LabelName == 'internal') {
									$LabelName		= l::box_backup_mode_internal;
								}
								elseif ($LabelName == 'nvme') {
									$LabelName		= l::box_backup_mode_nvme;
								}
								elseif ($LabelName == 'cloud_rsync') {
									$LabelName		= l::box_backup_mode_cloud_rsync;
								}

								print("<button class='$ButtonClass' name='TargetDevice' value='$Storage' id='Target_$Storage'>$LabelName</button></br>");

							}
							print("</div>");
						}
					?>
				</div>
			</div>

			<div class='backupsection'>
				<button name="stopbackup" class="danger"><?php echo L::main_stopbackup_button; ?>
			</div>

				<details>

					<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::main_backup_modifications; ?></summary>

					<div class='backupsection'>
						<h4><?php echo L::main_backup_primary; ?></h4>
						<table style='border: 0;'>
							<tr>
								<td style='padding-right: 10pt; vertical-align: top;'>
									<input type="checkbox" id="move_files" name="move_files" <?php echo $move_files=="True"?"checked":""; ?>>
								</td>
								<td>
									<label for="move_files"><?php echo L::config_backup_move_files_label; ?></label>
								</td>
							</tr>

							<tr>
								<td style='padding-right: 10pt; vertical-align: top;'>
									<input type="checkbox" id="rename_files" name="rename_files" <?php echo $rename_files=="True"?"checked":""; ?>>
								</td>
								<td>
									<label for="rename_files"><?php echo L::main_backup_rename_checkbox_label; ?> <a title="<?php echo L::config_backup_rename_warning; ?>">&#x1F6C8;</a></label>
								</td>
							</tr>

							<tr>
								<td style='padding-right: 10pt;'>
									<input type="checkbox" id="generate_thumbnails" name="generate_thumbnails" <?php echo $generate_thumbnails=="True"?"checked":""; ?>>
								</td>
								<td>
									<label for="generate_thumbnails"><?php echo L::main_backup_generate_thumbnails_checkbox_label; ?></label>
								</td>
							</tr>

							<tr>
								<td style='padding-right: 10pt;'>
									<input type="checkbox" id="update_exif" name="update_exif" <?php echo $update_exif=="True"?"checked":""; ?>>
								</td>
								<td>
									<label for="update_exif"><?php echo L::main_backup_update_exif_checkbox_label; ?></label>
								</td>
							</tr>
						</table>

						<?php
							unset($Partitions);
							exec("sudo python3 ${WORKING_DIR}/lib_storage.py --Action get_available_partitions --skipMounted False", $Partitions);
						?>

						<table style='border: 0;'>

							<tr>
								<td>
									<label for="preset_source"><?php echo L::main_backup_preset_source_label; ?>:</label>
								</td>
								<td style='padding-right: 10pt;'>
									<select id="preset_source" name="preset_source" onchange="PresetPartitionChange()">
										<option value=''><?php echo L::main_backup_preset_partition_auto; ?></option>
										<?php
											foreach ($Partitions as $Partition) {
												list($Lum, $DeviceIdentifier)	= explode(': ',$Partition,2);
												$Lum	= str_replace('/dev/', '', $Lum);
												echo "<option value='".$DeviceIdentifier."'>".$Lum.($DeviceIdentifier!=''?" (".trim($DeviceIdentifier, '-').")":'')."</option>";
											}
										?>
									</select>
								</td>
							</tr>

							<tr>
								<td>
									<label for="preset_target"><?php echo L::main_backup_preset_target_label; ?>:</label>
								</td>
								<td style='padding-right: 10pt;'>
									<select id="preset_target" name="preset_target" onchange="PresetPartitionChange()">
										<option value=''><?php echo L::main_backup_preset_partition_auto; ?></option>
										<?php
											foreach ($Partitions as $Partition) {
												list($Lum, $DeviceIdentifier)	= explode(': ',$Partition,2);
												$Lum	= str_replace('/dev/', '', $Lum);
												echo "<option value='".$DeviceIdentifier."'>".$Lum.($DeviceIdentifier!=''?" (".trim($DeviceIdentifier, '-').")":'')."</option>";
											}
										?>
									</select>
								</td>
							</tr>

						</table>
					</div>

					<div class='backupsection'>
						<h4><?php echo L::main_backup_secondary; ?></h4>
						<table style='border: 0;'>
							<tr>
								<td style='padding-right: 10pt;'>
									<?php get_secondary_backup_selector('BACKUP_MODE_2', $CloudServices, $config, $NVMe_available, true); ?>
								</td>
								<td>
									<label for="preset_target"><?php echo L::main_backup_secondary_label; ?></label>
								</td>
							</tr>
						</table>
					</div>

					<div class='backupsection'>
						<h4><?php echo L::main_backup_general; ?></h4>
						<table style='border: 0;'>
							<tr>
								<td style='padding-right: 10pt;'>
									<input type="checkbox" id="power_off" name="power_off" <?php echo $power_off_force=="True"?"checked":""; ?>>
								</td>
								<td>
									<label for="power_off"><?php echo L::main_backup_power_off_checkbox_label; ?></label>
								</td>
							</tr>
						</table>
					</div>


				</details>
			</div>

		<div class="card" style="margin-top: 3em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::main_view_database; ?></summary>

				<div class='backupsection'>
					<h3><?php echo L::main_thumbnails_header; ?></h3>
					<button name="backup_function" value="thumbnails_usb" class="usb"><?php echo L::right_arrow . L::main_usb_button; ?></button>
					<button name="backup_function" value="thumbnails_internal" class="usb"><?php echo L::right_arrow . L::main_internal_button; ?></button>
					<?php if ($NVMe_available) {echo('<button name="backup_function" value="thumbnails_nvme" class="usb">'.L::right_arrow.L::main_nvme_button.'</button>');} ?>
				</div>

				<div class='backupsection'>
					<h3><?php echo L::main_database_header; ?></h3>
					<button name="backup_function" value="database_usb" class="usb"><?php echo L::right_arrow . L::main_usb_button; ?></button>
					<button name="backup_function" value="database_internal" class="usb"><?php echo L::right_arrow . L::main_internal_button; ?></button>
					<?php if ($NVMe_available) {echo('<button name="backup_function" value="database_nvme" class="usb">'.L::right_arrow.L::main_nvme_button.'</button>');} ?>
				</div>

				<div class='backupsection'>
					<h3><?php echo L::main_exif_header; ?></h3>
					<button name="backup_function" value="exif_usb" class="usb"><?php echo L::right_arrow . L::main_usb_button; ?></button>
					<button name="backup_function" value="exif_internal" class="usb"><?php echo L::right_arrow . L::main_internal_button; ?></button>
					<?php if ($NVMe_available) {echo('<button name="backup_function" value="exif_nvme" class="usb">'.L::right_arrow.L::main_nvme_button.'</button>');} ?>
				</div>

			</details>

			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::main_file_operations; ?></summary>

				<div class='backupsection'>
					<h3><?php echo L::main_backup_rename_checkbox_label; ?> <a title="<?php echo L::config_backup_rename_warning; ?>">&#x1F6C8;</a></h3>
					<button name="backup_function" value="rename_usb" class="usb"><?php echo L::right_arrow . L::main_usb_button; ?></button>
					<button name="backup_function" value="rename_internal" class="usb"><?php echo L::right_arrow . L::main_internal_button; ?></button>
					<?php if ($NVMe_available) {echo('<button name="backup_function" value="rename_nvme" class="usb">'.L::right_arrow.L::main_nvme_button.'</button>');} ?>
				</div>

			</details>
		</div>

	</form>

	<?php include "sub-logmonitor.php";
		logmonitor($sourcefile=$constants['const_LOGFILE'], $title=L::log_logmonitor, $allow_logfile_operations=true);
	?>

	<div class="card" style="margin-top: 3em;">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::help; ?></summary>
			<p><?php echo L::main_help_txt; ?></p>
		</details>
	</div>

	<?php include "sub-footer.php"; ?>
	<?php
		shell_exec("mkdir -p tmp");
		shell_exec("sudo chown www-data:www-data ./tmp -R");

		// 	Backup
		if (isset($_POST['TargetDevice'])) {

			$SecBackupArgs	= '';
			if (isset($_POST['BACKUP_MODE_2'])) {
				$BACKUP_MODE_2_array	= explode(' ', $_POST['BACKUP_MODE_2'], 2);
				if (count($BACKUP_MODE_2_array)==2) {
					if (($BACKUP_MODE_2_array[0] !== 'none') and ($BACKUP_MODE_2_array[1] !== 'none')) {
						$SecBackupArgs	= "--SecSourceName " . escapeshellarg($BACKUP_MODE_2_array[0]) . " --SecTargetName " . escapeshellarg($BACKUP_MODE_2_array[1]);
					}
				}
			}

			if ($_POST['SourceDevice'] !== 'usb') {
				$preset_source	= '';
			}

			shell_exec("sudo $WORKING_DIR/stop_backup.sh");
			shell_exec("sudo python3 $WORKING_DIR/backup.py --SourceName " . escapeshellarg($_POST['SourceDevice']) . " --TargetName " . escapeshellarg($_POST['TargetDevice']) . " --move-files '$move_files' --rename-files '$rename_files' --force-sync-database False --generate-thumbnails '$generate_thumbnails' --update-exif '$update_exif' --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force $SecBackupArgs> /dev/null 2>&1 &");
			popup(L::main_backup_backup . " " . $_POST['SourceDevice'] . " " . L::main_backup_to . " " . $_POST['TargetDevice'] . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
		}

		// 	functions
		if (isset($_POST['backup_function'])) {
			$FunctionValues	= explode('_', $_POST['backup_function'], 2);
			$Function	= $FunctionValues[0];
			$Target		= $FunctionValues[1];

			$sync_database	= 'False';
			if ($Function == 'thumbnails') {
				$generate_thumbnails	= 'True';
				$update_exif			= 'False';

				$ButtonLabel_Function	= L::main_function_thumbnails;
			}
			elseif ($Function == 'database') {
				$sync_database			= 'True';
				$generate_thumbnails	= 'False';
				$update_exif			= 'False';

				$ButtonLabel_Function	= L::main_function_sync;
			}
			elseif ($Function == 'exif') {
				$rename_files			= 'False';
				$generate_thumbnails	= 'False';
				$update_exif			= 'True';

				$ButtonLabel_Function	= L::main_function_exif;
			}
			elseif ($Function == 'rename') {
				$rename_files			= 'True';
				$generate_thumbnails	= 'False';
				$update_exif			= 'False';

				$ButtonLabel_Function	= L::main_function_exif;
			}

			if ($Target == 'internal') {
				$ButtonLabel_Target	= L::main_internal_button;
			}
			elseif ($Target == 'nvme') {
				$ButtonLabel_Target	= L::main_nvme_button;
			}
			else {
				$ButtonLabel_Target	= L::main_usb_button;
			}

			shell_exec("sudo $WORKING_DIR/stop_backup.sh");
			shell_exec("sudo python3 $WORKING_DIR/backup.py --SourceName $Function --TargetName $Target --rename-files '$rename_files' --force-sync-database $sync_database --generate-thumbnails $generate_thumbnails --update-exif $update_exif --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force> /dev/null 2>&1 &");
			popup($ButtonLabel_Function . " " . L::main_backup_on . " " . $ButtonLabel_Target . ".",$config["conf_POPUP_MESSAGES"]);
		}

		// stop backup
		if (isset($_POST['stopbackup'])) {
			popup(L::main_stopbackup_m,$config["conf_POPUP_MESSAGES"]);
			shell_exec("sudo $WORKING_DIR/stop_backup.sh");
		}

	?>
</body>

</html>
