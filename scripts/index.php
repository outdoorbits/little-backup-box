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
</head>

<body>
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
			<li class="float-right"><a href="upload.php"><?php echo L::upload; ?></a></li>
		</ul>
	</nav>
	<div class="card">
		<form class="text-center" style="margin-top: 1em;" method="POST">
			<button name="sourcebackup"><?php echo L::sourcebackup_b; ?></button>
			<button name="internalbackup"><?php echo L::internalbackup_b; ?></button>
			<button name="camerabackup"><?php echo L::camerabackup_b; ?></button>
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
		<h2 style="margin-top: 0em;"><?php echo L::help; ?></h2>
		<hr>
		<p><?php echo L::help_txt; ?></p>
	</div>
	<?php
	if (isset($_POST['sourcebackup'])) {
		shell_exec('sudo pkill -f source-backup*');
		shell_exec('sudo umount /media/storage');
		shell_exec('sudo ./source-backup.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::sourcebackup_m . '")';
		echo "</script>";
	}
	if (isset($_POST['camerabackup'])) {
		shell_exec('sudo pkill -f camera-backup*');
		shell_exec('sudo umount /media/storage');
		shell_exec('sudo ./camera-backup.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::camerabackup_m . '")';
		echo "</script>";
	}
	if (isset($_POST['internalbackup'])) {
		shell_exec('sudo pkill -f internal-backup*');
		shell_exec('sudo umount /media/storage');
		shell_exec('sudo ./internal-backup.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::internalbackup_m . '")';
		echo "</script>";
	}
	if (isset($_POST['iosbackup'])) {
		shell_exec('./ios-backup.sh > /dev/null 2>&1 & echo $!');
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
		shell_exec('sudo ./poweroff.sh force');
	}
	if (isset($_POST['custom1'])) {
		shell_exec('sudo pkill -f *backup*');
		shell_exec('sudo ./custom1.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom1_m . '")';
		echo "</script>";
	}
	if (isset($_POST['custom2'])) {
		shell_exec('sudo pkill -f *backup*');
		shell_exec('sudo ./custom2.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom2_m . '")';
		echo "</script>";
	}
	if (isset($_POST['custom3'])) {
		shell_exec('sudo pkill -f *backup*');
		shell_exec('sudo ./custom3.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::custom3_m . '")';
		echo "</script>";
	}
	?>
</body>

</html>
