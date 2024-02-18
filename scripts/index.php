<!doctype html>

<!-- Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	if (isset($_POST['form_sent'])) {
		$generate_thumbnails	= isset($_POST['generate_thumbnails'])?"True":"False";
		$update_exif			= isset($_POST['update_exif'])?"True":"False";
		$power_off_force		= isset($_POST['power_off'])?"True":"False";

		$preset_source			= isset($_POST['preset_source'])?$_POST['preset_source']:"";
		$preset_target			= isset($_POST['preset_target'])?$_POST['preset_target']:"";
	} else {
		$generate_thumbnails	= $config['conf_BACKUP_GENERATE_THUMBNAILS']?"True":"False";
		$update_exif			= $config['conf_BACKUP_UPDATE_EXIF']?"True":"False";
		$power_off_force		= $config['conf_POWER_OFF']?"True":"False";

		$preset_source			= '';
		$preset_target			= '';
	}

	include("sub-popup.php");

	$LocalServices	= array('usb','internal');

	$CameraServices	= array('camera');

	include("get-cloudservices.php");
	$CloudServices_marked	= array();
	foreach($CloudServices as $CloudService) {
		$CloudServices_marked[]	= 'cloud:' . $CloudService;
	}

	$SourceServices	= array(
		'usb'		=> $LocalServices,
		'camera'	=> $CameraServices,
		'cloud'		=> $CloudServices_marked
	);

	$rsync_unconfigurated	= ($config['conf_RSYNC_SERVER']=='' or $config['conf_RSYNC_PORT']=='' or $config['conf_RSYNC_USER']=='' or $config['conf_RSYNC_PASSWORD']=='' or $config['conf_RSYNC_SERVER_MODULE']=='');
	if ($rsync_unconfigurated == false) {
		$CloudServices_marked	=array_merge(['cloud_rsync'],$CloudServices_marked);
	}

	$TargetServices	= array_merge($LocalServices, $CloudServices_marked);
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "sub-standards-header-loader.php"; ?>
	<script type="text/javascript" src="js/logmonitor.js"></script>
	<script type="text/javascript" src="js/partition_presets.js"></script>

	<script>
		function HideDisallowedButtons(ActiveSource) {
			let TargetServices = [<?php $Separator = ''; foreach ($TargetServices as $TargetService) {print($Separator . "'" . $TargetService . "'"); $Separator = ', ';}; ?>];

			for (i in TargetServices) {
				let TargetService = TargetServices[i];

// 				find disallowed combinations
				if (
					((TargetService === ActiveSource.value) && (TargetService !== 'usb')) ||
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

<body onload="refreshLogMonitor()" <?php echo $background; ?>>
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

									if ($LabelName == 'usb') {
										$LabelName		= l::box_backup_mode_usbs;
									}
									elseif ($LabelName == 'camera') {
										$LabelName		= l::box_backup_mode_cameras;
									}
									elseif ($LabelName == 'cloud_rsync') {
										$LabelName		= l::box_backup_mode_cloud_rsync;
									}
									$OldSource = isset($_POST['SourceDevice']) ? $_POST['SourceDevice'] : 'usb';
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
							print("<div class='backup-buttons'>");
							foreach($TargetServices as $Storage) {
								$LabelNameExplode		= explode(':', $Storage, 2);
								$LabelName				= end($LabelNameExplode);
								if (@substr_compare($Storage, 'cloud:', 0, strlen('cloud:'))==0) { /* use "@" to suppress error messages*/
									$ButtonClass	= 'cloud';
								}
								elseif ($LabelName == 'cloud_rsync') {
									$ButtonClass	= 'cloud';
									$LabelName		= l::box_backup_mode_cloud_rsync;
								}
								elseif ($LabelName == 'usb') {
									$ButtonClass	= 'usb';
									$LabelName		= l::tools_mount_usb;
								}
								elseif ($LabelName == 'camera') {
									$ButtonClass	= 'camera';
									$LabelName		= l::box_backup_mode_camera;
								}

								print("<button class='$ButtonClass' name='TargetDevice' value='$Storage' id='Target_$Storage'>$LabelName</button></br>");

							}
							print("</div>");


					?>
				</div>
			</div>

			<div class='backupsection'>
				<button name="stopbackup" class="danger"><?php echo L::main_stopbackup_button; ?>
			</div>
		</div>

		<div class="card" style="margin-top: 3em;">
			<details>

				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::main_backup_modifications; ?></summary>

				<div class='backupsection'>
					<table style='border: 0;'>
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

						<tr>
							<td style='padding-right: 10pt;'>
								<input type="checkbox" id="power_off" name="power_off" <?php echo $power_off_force=="True"?"checked":""; ?>>
							</td>
							<td>
								<label for="power_off"><?php echo L::main_backup_power_off_checkbox_label; ?></label>
							</td>
						</tr>
					</table>

					<?php
						$Partitions	= shell_exec("sudo python3 ${WORKING_DIR}/lib_storage.py get_available_partitions");
						$PartitionsArray	= explode(',', $Partitions);
					?>
					<table style='border: 0;'>
						<tr>
							<td style='padding-right: 10pt;'>
								<select id="preset_target" name="preset_target" onchange="PresetPartitionChange()">
									<option value=''><?php echo L::main_backup_preset_partition_auto; ?></option>
									<?php
										foreach ($PartitionsArray as $Partition) {
											$Partition	= trim($Partition, "[]' \n");
											list($Lum, $DeviceIdentifier)	= explode(':',$Partition,2);
											$Lum	= trim($Lum,'/dev');
											echo "<option value='".$DeviceIdentifier."'>".$Lum.($DeviceIdentifier!=''?" (".trim($DeviceIdentifier, '-').")":'')."</option>";
										}
									?>
								</select>
							</td>
							<td>
								<label for="preset_target"><?php echo L::main_backup_preset_target_label; ?></label>
							</td>
						</tr>

						<tr>
							<td style='padding-right: 10pt;'>
								<select id="preset_source" name="preset_source" onchange="PresetPartitionChange()">
									<option value=''><?php echo L::main_backup_preset_partition_auto; ?></option>
									<?php
										foreach ($PartitionsArray as $Partition) {
											$Partition	= trim($Partition, "[]' \n");
											list($Lum, $DeviceIdentifier)	= explode(':',$Partition,2);
											$Lum	= trim($Lum,'/dev');
											echo "<option value='".$DeviceIdentifier."'>".$Lum.($DeviceIdentifier!=''?" (".trim($DeviceIdentifier, '-').")":'')."</option>";
										}
									?>
								</select>
							</td>
							<td>
								<label for="preset_source"><?php echo L::main_backup_preset_source_label; ?></label>
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
					<button name="backup_function_thumbnails_usb" class="usb"><?php echo L::right_arrow . L::main_usb_button; ?></button>
					<button name="backup_function_thumbnails_internal" class="usb"><?php echo L::right_arrow . L::main_internal_button; ?></button>
				</div>

				<div class='backupsection'>
					<h3><?php echo L::main_database_header; ?></h3>
					<button name="backup_function_database_usb" class="usb"><?php echo L::right_arrow . L::main_usb_button; ?></button>
					<button name="backup_function_database_internal" class="usb"><?php echo L::right_arrow . L::main_internal_button; ?></button>
				</div>

				<div class='backupsection'>
					<h3><?php echo L::main_exif_header; ?></h3>
					<button name="backup_function_exif_usb" class="usb"><?php echo L::right_arrow . L::main_usb_button; ?></button>
					<button name="backup_function_exif_internal" class="usb"><?php echo L::right_arrow . L::main_internal_button; ?></button>
				</div>

			</details>
		</div>

	</form>

	<?php include "sub-logmonitor.php"; ?>

	<div class="card" style="margin-top: 3em;">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::help; ?></summary>
			<p><?php echo L::main_help_txt; ?></p>
		</details>
	</div>

	<?php include "sub-footer.php"; ?>
	<?php
		exec("mkdir -p tmp");
		exec("sudo chown www-data:www-data ./tmp -R");

		// 	Backup
		if (isset($_POST['TargetDevice'])) {
			exec("sudo $WORKING_DIR/stop_backup.sh");
			exec("sudo python3 $WORKING_DIR/backup.py --SourceName " . escapeshellarg($_POST['SourceDevice']) . " --TargetName " . escapeshellarg($_POST['TargetDevice']) . " --sync-database False --generate-thumbnails '$generate_thumbnails' --update-exif '$update_exif' --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force> /dev/null 2>&1 &");

			popup(L::main_backup_backup . " " . $_POST['SourceDevice'] . " " . L::main_backup_to . " " . $_POST['TargetDevice'] . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
		}

		// 	Functions
		if (isset($_POST['backup_function_thumbnails_usb'])) {
			exec("sudo $WORKING_DIR/stop_backup.sh");
			exec("sudo python3 $WORKING_DIR/backup.py --SourceName thumbnails --TargetName usb --sync-database False --generate-thumbnails True --update-exif False --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force> /dev/null 2>&1 &");
			popup(L::main_backup_backup . " " . L::main_thumbnails_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
		}
		if (isset($_POST['backup_function_thumbnails_internal'])) {
			exec("sudo $WORKING_DIR/stop_backup.sh");
			exec("sudo python3 $WORKING_DIR/backup.py --SourceName thumbnails --TargetName internal --sync-database False --generate-thumbnails True --update-exif False --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force> /dev/null 2>&1 &");
			popup(L::main_backup_backup . " " . L::main_thumbnails_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
		}

		if (isset($_POST['backup_function_database_usb'])) {
			exec("sudo $WORKING_DIR/stop_backup.sh");
			exec("sudo python3 $WORKING_DIR/backup.py --SourceName database --TargetName usb --sync-database True --generate-thumbnails False --update-exif False --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force> /dev/null 2>&1 &");
			popup(L::main_backup_backup . " " . L::main_database_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
		}
		if (isset($_POST['backup_function_database_internal'])) {
			exec("sudo $WORKING_DIR/stop_backup.sh");
			exec("sudo python3 $WORKING_DIR/backup.py --SourceName database --TargetName internal --sync-database True --generate-thumbnails False --update-exif False --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force> /dev/null 2>&1 &");
			popup(L::main_backup_backup . " " . L::main_database_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
		}

		if (isset($_POST['backup_function_exif_usb'])) {
			exec("sudo $WORKING_DIR/stop_backup.sh");
			exec("sudo python3 $WORKING_DIR/backup.py --SourceName exif --TargetName usb --sync-database False --generate-thumbnails False --update-exif True --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force> /dev/null 2>&1 &");
			popup(L::main_backup_backup . " " . L::main_exif_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
		}
		if (isset($_POST['backup_function_exif_internal'])) {
			exec("sudo $WORKING_DIR/stop_backup.sh");
			exec("sudo python3 $WORKING_DIR/backup.py --SourceName exif --TargetName internal --sync-database False --generate-thumbnails False --update-exif True --device-identifier-preset-source " . escapeshellarg($preset_source) . " --device-identifier-preset-target " . escapeshellarg($preset_target) . " --power-off $power_off_force> /dev/null 2>&1 &");
			popup(L::main_backup_backup . " " . L::main_exif_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
		}

		if (isset($_POST['stopbackup'])) {
			popup(L::main_stopbackup_m,$config["conf_POPUP_MESSAGES"]);
			exec("sudo $WORKING_DIR/stop_backup.sh");
		}

	?>
</body>

</html>
