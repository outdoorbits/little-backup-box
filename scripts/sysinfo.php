<?php include_once './header.php'; ?>

<main class='flex-1 overflow-x-hidden overflow-y-auto bg-gray-200 dark:bg-gray-900 transition duration-500 ease-out'>
    <div class='container mx-auto px-6 py-8'>

        <h1 class="text-gray-700 dark:text-gray-200 text-5xl font-medium transition duration-500 ease-out"><?= L::sysinfo_sysinfo; ?></h1>

        <div class="card mt-5">
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

	<div class="card mt-5">
		<h3><?= L::sysinfo_devices; ?></h3>
        <div class="overflow-scroll">
            <?php
            echo '<pre>';
            passthru('lsblk');
            echo '</pre>';
            ?>
        </div>
	</div>

	<div class="card mt-5">
		<h3><?= L::sysinfo_diskspace; ?></h3>
        <div class='overflow-scroll'>
			<?php
				echo '<pre>';
				passthru("df -H");
				echo '</pre>';
			?>
        </div>
	</div>

	<div class="card mt-5">
		<h3><?= L::sysinfo_camera; ?></h3>
        <div class='overflow-scroll'>
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
	</div>
    </div>
</main>

    <?php include_once './footer.php'; ?>
