<?php
$theme = "dark";
$WORKING_DIR=dirname(__FILE__);
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
	<style>
		button {
			width: 15em;
		}
	</style>
	<!-- Refresh the log monitor iframe every 2 seconds -->
	<script>
		function refreshIFrame() {
			var x = document.getElementById("logmonitor");
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
			<?php include "${WORKING_DIR}/sub-menu.php"; ?>
		</ul>
	</nav>
	<div class="card">
		<form class="text-center" style="margin-top: 1em;" method="POST">
			<button name="backup_storage_external"><?php echo L::backup_storage_external_b; ?></button>
			<button name="backup_storage_internal"><?php echo L::backup_storage_internal_b; ?></button>
			<hr style="margin-top: 1em; margin-bottom: 1em;">
			<button name="backup_camera_external"><?php echo L::backup_camera_external_b; ?></button>
			<button name="backup_camera_internal"><?php echo L::backup_camera_internal_b; ?></button>
			<hr style="margin-top: 1em; margin-bottom: 1em;">
			<button name="backup_ios_external"><?php echo L::backup_ios_external_b; ?></button>
			<button name="backup_ios_internal"><?php echo L::backup_ios_internal_b; ?></button>
		</form>
		<hr style="margin-top: 1em; margin-bottom: 1em;">
		<form class="text-center" method="POST">
			<button name="backup_storage_server"><?php echo L::backup_storage_server_b; ?></button>
			<button name="backup_internal_server"><?php echo L::backup_internal_server_b; ?></button>
		</form><hr style="margin-top: 1em; margin-bottom: 1em;">
		<form class="text-center" method="POST">
			<button name="custom1"><?php echo L::custom1_b; ?></button>
			<button name="custom2"><?php echo L::custom2_b; ?></button>
			<button name="custom3"><?php echo L::custom3_b; ?></button>
		</form>
		<hr style="margin-top: 1em; margin-bottom: 1em;">
		<form class="text-center" method="POST">
			<button name="reboot"><?php echo L::reboot_b; ?></button>
			<button name="shutdown"><?php echo L::shutdown_b; ?></button>
		</form>
	</div>
	<div class="card" style="margin-top: 3em;">
		<h2 style="margin-top: 0em;">
			<?php echo L::logmonitor; ?>
		</h2>
		<hr>
		<iframe id="logmonitor" src="/tmp/little-backup-box.log" width="100%" height="200" style="background: #FFFFFF;"></iframe>
		<div class="text-center" style="margin-top: 0.5em;"><button name="refresh" onclick="window.location.reload();"><?php echo L::refresh_b; ?></button></div>
	</div>
	<div class="card" style="margin-top: 3em;">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::help; ?></summary>
			<p><?php echo L::help_txt; ?></p>
		</details>
	</div>
	<?php
	exec("mkdir -p tmp");

	if (isset($_POST['backup_storage_external'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./backup.sh storage external > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_storage_external_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_storage_internal'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./backup.sh storage internal > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_storage_internal_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_camera_external'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./backup.sh camera external > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_camera_external_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_camera_internal'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./backup.sh camera internal > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_camera_internal_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_ios_external'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./backup.sh ios external > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_ios_external_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_ios_internal'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./backup.sh ios internal > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_ios_internal_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_storage_server'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./backup.sh storage server > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_storage_server_m . '")';
		echo "</script>";
	}
	if (isset($_POST['backup_internal_server'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./backup.sh internal server > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::backup_storage_server_m . '")';
		echo "</script>";
	}
	if (isset($_POST['reboot'])) {
		echo "<script>";
		echo 'alert("' . L::reboot_m . '")';
		echo "</script>";
		shell_exec('sudo ./poweroff.sh reboot force');
	}
	if (isset($_POST['shutdown'])) {
		echo "<script>";
		echo 'alert("' . L::shutdown_m . '")';
		echo "</script>";
		shell_exec('sudo ./poweroff.sh poweroff force');
	}
	if (isset($_POST['custom1'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./custom1.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom1_m . '")';
		echo "</script>";
	}
	if (isset($_POST['custom2'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo ./custom2.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom2_m . '")';
		echo "</script>";
	}
	if (isset($_POST['custom3'])) {
		shell_exec('sudo pkill -f backup*');
		shell_exec('sudo .//custom3.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom3_m . '")';
		echo "</script>";
	}
	?>
</body>

</html>
