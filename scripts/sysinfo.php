<!doctype html>

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
	<script src="js/refresh_iframe.js"></script>
</head>

<body onload="refreshIFrame()" <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<!-- Suppress form re-submit prompt on refresh -->
	<script>
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}
	</script>

	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::sysinfo_sysinfo; ?></h1>

	<div class="card">
		<?php
		$temp = shell_exec('cat /sys/class/thermal/thermal_zone*/temp');
		$temp = round($temp / 1000, 1);

		$cpuusage = 100 - shell_exec("vmstat | tail -1 | awk '{print $15}'");

		$mem_ram_frac = shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
		$mem_ram_all = shell_exec("free | grep Mem | awk '{print $2 / 1024}'");
		$mem_ram = round($mem_ram_frac, 1) . " % * " . round($mem_ram_all) . " MB";

		$mem_swap_frac = shell_exec("free | grep Swap | awk '{print $3/$2 * 100.0}'");
		$mem_swap_all = shell_exec("free | grep Swap | awk '{print $2 / 1024}'");
		$mem_swap = round($mem_swap_frac, 1) . " % * " . round($mem_swap_all) . " MB";

		$abnormal_conditions = shell_exec("${WORKING_DIR}/system_conditions.sh 'abnormal_conditions'");

			if (isset($temp) && is_numeric($temp)) {
				echo "<p>" . L::sysinfo_temp . ": <strong>" . $temp . "°C</strong></p>";
			}

			if (isset($cpuusage) && is_numeric($cpuusage)) {
				echo "<p>" . L::sysinfo_cpuload . ": <strong>" . $cpuusage . "%</strong></p>";
			}

			echo "<p>" . L::sysinfo_memory_ram . ": <strong>" . $mem_ram . "</strong></p>";

			echo "<p>" . L::sysinfo_memory_swap . ": <strong>" . $mem_swap . "</strong></p>";

			echo "<p>" . L::sysinfo_conditions . ": <strong>" . $abnormal_conditions . "</strong></p>";

		?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_devices; ?></h3>
			<?php
			echo '<pre>';
			passthru("lsblk");
			echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_diskspace; ?></h3>
			<?php
				echo '<pre>';
				passthru("df -H");
				echo '</pre>';
			?>
	</div>

	<div class="card">
		<h3><?php echo L::sysinfo_camera; ?></h3>
			<?php
				echo '<pre>';
					exec("sudo gphoto2 --summary | grep 'Model' | cut -d: -f2 | tr -d '[:space:]'",$DEVICES);
					if (count($DEVICES)) {
						echo "<ul>";
							foreach ($DEVICES as $DEVICE) {
								echo "<li>$DEVICE</li>";
							}
						echo "<ul>";
					}
					else
					{
						echo "-";
					}
				echo '</pre>';
			?>
	</div>

	<div class="text-center"><button onClick="history.go(0)" role="button"><?php echo (L::log_refresh_button); ?></button></div>

	<?php include "sub-logmonitor.php"; ?>
		
</body>

</html>
