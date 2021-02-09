<html lang="en">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/classless.css">
	<style>
		.uk-button {
			width: 14em;
		}
	</style>
</head>

<body>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	?>
	<h1 class="text-center" style="margin-bottom: 1em;">LITTLE BACKUP BOX</h1>
	<nav>
		<ul>
			<li><a href="sysinfo.php"><?php echo L::sysinfo; ?></a></li>
			<li><a href="config.php"><?php echo L::config; ?></a></li>
			<li class="float-right"><a href="upload.php"><?php echo L::upload; ?></a></li>
		</ul>
	</nav>
	<form class="text-center" method="POST">
		<button name="cardbackup"><?php echo L::cardbackup_b; ?></button>
		<button name="camerabackup"><?php echo L::camerabackup_b; ?></button>
		<button name="internalbackup"><?php echo L::internalbackup_b; ?></button>
		<button name="shutdown"><?php echo L::shutdown_b; ?></button>
	</form>
	<div class="card" style="margin-top: 3em;">
		<h2><?php echo L::help; ?></h2>
		<hr>
		<p><?php echo L::help_txt; ?></p>
	</div>
	<?php
	if (isset($_POST['cardbackup'])) {
		shell_exec('sudo pkill -f card-backup*');
		shell_exec('sudo umount /media/storage');
		shell_exec('sudo ./card-backup.sh > /dev/null 2>&1 & echo $!');
		echo "<script>";
		echo 'alert("' . L::cardbackup_m . '")';
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
	if (isset($_POST['shutdown'])) {
		echo "<script>";
		echo 'alert("' . L::shutdown_m . '")';
		echo "</script>";
		shell_exec('sudo poweroff > /dev/null 2>&1 & echo $!');
	}
	?>
</body>

</html>