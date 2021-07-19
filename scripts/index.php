<?php
$theme = "dark";
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/classless.css">
	<script>
		function refreshIFrame() {
			var x = document.getElementById("logscreen");
			x.contentWindow.location.reload();
			var t = setTimeout(refreshIFrame, 2000);
		}
	</script>
</head>

<body onload="refreshIFrame()">
	<!-- Suppress form re-submit prompt on refresh -->
	<script>
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}
	</script>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	?>
	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;">LITTLE BACKUP BOX</h1>
	<nav>
		<ul>
			<li><a href="sysinfo.php"><?php echo L::sysinfo; ?></a></li>
			<li><a href="config.php"><?php echo L::config; ?></a></li>
			<li><a href="repair.php"><?php echo L::repair; ?></a></li>
			<li class="float-right"><a href="upload.php"><?php echo L::upload; ?></a></li>
		</ul>
	</nav>
	<div class="card">
		<form class="text-center" style="margin-top: 1em;" method="POST">
			<button name="backup_storage_external"><?php echo L::backup_storage_external_b; ?></button>
			<button name="backup_storage_internal"><?php echo L::backup_storage_internal_b; ?></button>
			<button name="backup_camera_external"><?php echo L::backup_camera_external_b; ?></button>
			<button name="backup_camera_internal"><?php echo L::backup_camera_internal_b; ?></button>
			<button name="iosbackup"><?php echo L::iosbackup_b; ?></button>

		</form>
		<hr>
		<form class="text-center" style="margin-top: 1em;" method="POST">
			<button name="reboot"><?php echo L::reboot_b; ?></button>
			<button name="shutdown"><?php echo L::shutdown_b; ?></button>
		</form>
		<hr style="margin-bottom: 1em;">
		<form class="text-center" method="POST">
			<button name="custom1"><?php echo L::custom1_b; ?></button>
			<button name="custom2"><?php echo L::custom2_b; ?></button>
			<button name="custom3"><?php echo L::custom3_b; ?></button>
		</form>
	</div>
	<div class="card" style="margin-top: 3em;">
		<h2 style="margin-top: 0em;"><?php echo L::logscreen; ?></h2>
		<hr>
		<iframe id="logscreen" src="tmp/little-backup-box.log" width="100%" height="200" style="background: #FFFFFF;"></iframe>
	</div>
	<div class="card" style="margin-top: 3em;">
		<h2 style="margin-top: 0em;"><?php echo L::help; ?></h2>
		<hr>
		<p><?php echo L::help_txt; ?></p>
	</div>
	<?php
	exec("mkdir -p /home/pi/little-backup-box/scripts/tmp");
	exec("echo '' > /home/pi/little-backup-box/scripts/tmp/little-backup-box.log}");

	if (isset($_POST['backup_storage_external'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo /home/pi/little-backup-box/scripts/backup.sh storage external > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_storage_external_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_storage_internal'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo /home/pi/little-backup-box/scripts/backup.sh storage internal > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_storage_internal_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_camera_external'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo /home/pi/little-backup-box/scripts/backup.sh camera external > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_camera_external_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_camera_internal'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo /home/pi/little-backup-box/scripts/backup.sh camera internal > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_camera_internal_m . '")';
		echo "</script>";
	}
	if (isset($_POST['iosbackup'])) {
		shell_exec('/home/pi/little-backup-box/scripts/ios-backup.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::iosbackup_m . '")';
		echo "</script>";
	}
	if (isset($_POST['reboot'])) {
		echo "<script>";
		echo 'alert("' . L::reboot_m . '")';
		echo "</script>";
		shell_exec('sudo reboot');
	}
	if (isset($_POST['shutdown'])) {
		echo "<script>";
		echo 'alert("' . L::shutdown_m . '")';
		echo "</script>";
		shell_exec('sudo /home/pi/little-backup-box/scripts/poweroff.sh force');
	}
	if (isset($_POST['custom1'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo /home/pi/little-backup-box/scripts/custom1.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom1_m . '")';
		echo "</script>";
	}
	if (isset($_POST['custom2'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo /home/pi/little-backup-box/scripts/custom2.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom2_m . '")';
		echo "</script>";
	}
	if (isset($_POST['custom3'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo /home/pi/little-backup-box/scripts/custom3.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom3_m . '")';
		echo "</script>";
	}
	?>
</body>

</html>