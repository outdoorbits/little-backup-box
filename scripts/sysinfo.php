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

	include("sub-i18n-loader.php");

	include("sub-popup.php");
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
	<script src="js/logmonitor.js"></script>
	<script src="js/refresh_site.js"></script>
	<script src="js/copy2clipboard.js"></script>
</head>

<body onload="refreshLogMonitor(); refresh_site()" <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<!-- Suppress form re-submit prompt on refresh -->
	<script>
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}
	</script>

	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<div class="card">
		<h3><?php echo L::sysinfo_system; ?></h3>
		<?php
			$model					= shell_exec("sudo python3 ${WORKING_DIR}/lib_system.py get_pi_model");

			$temp					= shell_exec('cat /sys/class/thermal/thermal_zone*/temp');
			$temp					= round((float) $temp / 1000, 1);

			$cpuusage				= 100 - (float) shell_exec("vmstat | tail -1 | awk '{print $15}'");

			$mem_ram_frac			= shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
			$mem_ram_all			= shell_exec("free | grep Mem | awk '{print $2 / 1024}'");
			$mem_ram				= round((float) $mem_ram_frac, 1) . " % * " . round((float) $mem_ram_all) . " MB";

			$mem_swap_frac			= shell_exec("free | grep Swap | awk '{print $3/$2 * 100.0}'");
			$mem_swap_all			= shell_exec("free | grep Swap | awk '{print $2 / 1024}'");
			$mem_swap				= round($mem_swap_frac, 1) . " % * " . round($mem_swap_all) . " MB";

			$abnormal_conditions	= shell_exec("sudo python3 ${WORKING_DIR}/lib_system.py get_abnormal_system_conditions");

			echo '<table>';

			echo "<tr><td width='30%'>" . L::sysinfo_model . ": </td><td><strong>" . $model . "</strong></td></tr>";

			if (isset($temp) && is_numeric($temp)) {
				echo "<tr><td>" . L::sysinfo_temp . ": </td><td><strong>" . $temp . "°C</strong></td></tr>";
			}

			if (isset($cpuusage) && is_numeric($cpuusage)) {
				echo "<tr><td>" . L::sysinfo_cpuload . ": </td><td><strong>" . $cpuusage . "%</strong></td></tr>";
			}

			echo "<tr><td>" . L::sysinfo_memory_ram . ": </td><td><strong>" . $mem_ram . "</strong></td></tr>";

			echo "<tr><td>" . L::sysinfo_memory_swap . ": </td><td><strong>" . $mem_swap . "</strong></td></tr>";

			echo "<tr><td>" . L::sysinfo_conditions . ": </td><td><strong>" . $abnormal_conditions . "</strong></td></tr>";

			echo '</table>';
		?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_diskspace; ?></h3>
			<?php
				echo '<pre>';
				passthru("sudo lsblk --output NAME,SIZE,FSAVAIL,FSUSED,FSUSE%,MOUNTPOINT");
				echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_devices; ?></h3>
			<?php
			echo '<pre>';
			passthru("sudo lsblk --output NAME,FSTYPE,UUID,MODEL");
			echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_device_states; ?></h3>
			<?php
			echo ('<pre><table style="width: 100%;">');
			unset($Partitions);
			exec("sudo python3 ${WORKING_DIR}/lib_storage.py --Action get_available_partitions --skipMounted False --ignore-fs True", $Partitions);

			foreach ($Partitions as $Partition) {
				$output	= false;

				if (str_contains($Partition, ':')) {
					list($Lum, $DeviceIdentifier)	= explode(': ', $Partition, 2);
				} else {
					$Lum				= $Partition;
					$DeviceIdentifier	= '';
				}

				echo ("<tr><th style='vertical-align:top;'>$Lum</th><td style='padding-left: 10px;'>");

					unset($States);
					exec("sudo smartctl -a $Lum", $States);

					$StatusMessage	= '';
					foreach($States as $Line) {
						if (strpos($Line, ': ')) {
							$output	= true;

							list($Value, $measured)	= explode(':', $Line, 2);
								$Value		= str_pad(trim($Value) . ':', 35, ' ');
								$measured	= trim($measured);

							echo ($Value . $measured . '<br>');
						}
					}

					if (! $output) {
						echo ('-');
					}

				echo ("</td></tr>");
			}
			echo '</table></pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_cameras; ?></h3>
			<?php
				echo '<pre>';


					exec("sudo gphoto2 --auto-detect",$DEVICES);
					if (count($DEVICES)>2) {

						echo "<ol>";

							$FirstColumnLength	= strpos($DEVICES[0],'Port');

							$LineNumber	= 0;
							foreach ($DEVICES as $DEVICE) {
								$LineNumber	+= 1;

									if ($LineNumber > 2) {
										$MODEL	= substr($DEVICE,0,$FirstColumnLength);
										$MODEL	= trim($MODEL);

										$PORT	= substr($DEVICE,$FirstColumnLength);
										$PORT	= trim($PORT);

										echo "<li>";


											echo "$MODEL $PORT";

											echo '<h4>' . L::sysinfo_camera_model.'</h4>';

											unset($SUMMARY);
											exec("sudo gphoto2 --camera '$MODEL' --port '$PORT' --summary | grep 'Model' | cut -d: -f2", $SUMMARY);

											if (count($SUMMARY)) {
												echo "<ul>";

													$MODEL	= mb_ereg_replace("([^a-zA-Z0-9-])", '_', trim($SUMMARY[0]));

													echo "<li>$MODEL</li>";

												echo "</ul>";
											}
											else
											{
												echo "-";
											}

											echo '<h4>' . L::sysinfo_camera_serial.'</h4>';
											unset($SUMMARY);
											exec("sudo gphoto2 --camera '$MODEL' --port '$PORT' --summary | grep 'Serial Number' | cut -d: -f2 | tr -d ' '",$SUMMARY);
											if (count($SUMMARY)) {
												echo "<ul>";

													$SERIAL	= mb_ereg_replace("([^a-zA-Z0-9-_\.])", '_', $SUMMARY[0]);
													$SERIAL	= ltrim($SERIAL, "0");

													echo "<li>$SERIAL</li>";

												echo "</ul>";
											}
											else
											{
												echo "-";
											}

											echo '<h4>' . L::sysinfo_camera_storages.'</h4>';
											unset($STORAGES);
											exec("sudo gphoto2 --camera '$MODEL' --port '$PORT' --storage-info | grep 'basedir' | cut -d= -f2 | tr -d ' '",$STORAGES);
											if (count($STORAGES)) {
												echo "<ul>";
													foreach ($STORAGES as $STORAGE) {

														echo "<li>$STORAGE<br>" . L::config_backup_camera_device_folder_pattern . ':';
														echo "<ul>";
															echo "<li>" . L::config_backup_camera_model_folders_header . ':<br><b>' . $MODEL . ':!' . $STORAGE . "</b> <button style=\"padding: 0; border: none; background: none;\" onclick=\"copy2clipboard('" . $MODEL . ':!' . $STORAGE . "')\"><img style=\"height: 1.5em;\" src=\"/img/copy2clipboard.gif\"></button></li>";
															echo "<li>" . L::config_backup_camera_specific_device_folders_header . ':<br><b>' . $MODEL . '_' . $SERIAL . ':!' . $STORAGE . "</b> <button style=\"padding: 0; border: none; background: none;\" onclick=\"copy2clipboard('" . $MODEL . '_' . $SERIAL . ':!' . $STORAGE . "')\"><img style=\"height: 1.5em;\" src=\"/img/copy2clipboard.gif\"></button></li>";
														echo "</ul>";
														echo "</li>";
													}
												echo "</ul>";
											}
											else
											{
												echo "-";
											}

										echo "</li>";


									}



							}

						echo "</ol>";
					}
					else
					{
						echo "-";
					}

				echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_wifi_header; ?></h3>
			<?php
				echo '<pre>';
				exec("iw dev | awk '$1==\"Interface\"{print $2}'", $WiFi_array);
				asort($WiFi_array);
				foreach($WiFi_array as $WiFi) {
					passthru("iwconfig $WiFi");
				}
				echo '</pre>';
			?>
	</div>

	<div class="text-center"><button onClick="history.go(0)" role="button"><?php echo (L::sysinfo_refresh_button); ?></button></div>

	<?php include "sub-logmonitor.php";
		logmonitor($sourcefile=$constants['const_LOGFILE'], $title=L::log_logmonitor, $allow_logfile_operations=true);
	?>

	<?php include "sub-footer.php"; ?>

</body>

</html>
