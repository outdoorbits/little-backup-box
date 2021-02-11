<html lang="en">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/classless.css">
</head>

<body>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	?>
	<nav>
		<ul>
			<li><a href="index.php"><?php echo L::main; ?></a></li>
			<li><a href="config.php"><?php echo L::config; ?></a></li>
			<li class="float-right"><a href="upload.php"><?php echo L::upload; ?></a></li>
		</ul>
	</nav>
	<div class="card text-center" style="margin-top: 3em;">
		<h1><?php echo L::status; ?></h1>
		<hr>
		<p><?php
			passthru("./status-webui.sh");
			?></p>
		<button onClick="history.go(0)" role="button"><?php echo L::refresh_b; ?></button>
	</div>
	<div class="card">
		<h1><?php echo L::sysinfo; ?></h1>
		<hr>
		<?php
		$temp = shell_exec('cat /sys/class/thermal/thermal_zone*/temp');
		$temp = round($temp / 1000, 1);
		$cpuusage = 100 - shell_exec("vmstat | tail -1 | awk '{print $15}'");
		$mem = shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
		$mem = round($mem, 1);
		if (isset($temp) && is_numeric($temp)) {
			echo "<p>" . L::temp . ": <strong>" . $temp . "°C</strong></p>";
		}
		if (isset($cpuusage) && is_numeric($cpuusage)) {
			echo "<p>" . L::cpuload . ": <strong>" . $cpuusage . "%</strong></p>";
		}
		if (isset($mem) && is_numeric($mem)) {
			echo L::memory . ": <strong>" . $mem . "%</strong>";
		}
		?>
		<h3><?php echo L::devices; ?></h3>
		<?php
		echo '<pre>';
		passthru("lsblk");
		echo '</pre>';
		?>
		<h3><?php echo L::diskspace; ?></h3>
		<?php
		echo '<pre>';
		passthru("df -H");
		echo '</pre>';
		?>
		<h3><?php echo L::log; ?></h3>
		<hr>
		<?php
		if (file_exists("/root/little-backup-box.log")) {
			echo '<pre>';
			passthru("sudo cat /root/little-backup-box.log");
			echo '</pre>';
		} else {
			echo "<p>" . L::log_txt . "</p>";
		}
		?>
		<button onClick="history.go(0)" role="button"><?php echo L::refresh_b; ?></button>
	</div>
</body>

</html>