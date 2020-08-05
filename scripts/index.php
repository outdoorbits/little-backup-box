<html lang="en">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<link rel="shortcut icon" href="favicon.png" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="css/uikit.min.css" />
	<script src="js/uikit.min.js"></script>
	<script src="js/uikit-icons.min.js"></script>
</head>

<body>
	<?php
	// include i18n class and initialize it
	require_once 'i18n.class.php';
	$i18n = new i18n('lang/{LANGUAGE}.ini', 'cache/', 'en');
	$i18n->init();
	?>
	<div class="uk-container uk-margin-top">
		<div class="uk-card uk-card-primary uk-card-body uk-width-1-2@m">
			<h1 class="uk-card-title">Little Backup Box</h1>
			<a class="uk-button uk-button-default uk-margin-top" href="sysinfo.php"><?php echo L::sysinfo; ?></a>
			<a class="uk-button uk-button-default uk-margin-top" href="raw-viewer/"><?php echo L::viewer; ?></a>
			<a class="uk-button uk-button-default uk-margin-top" href="config.php"><?php echo L::config; ?></a>
		</div>
		<div class="uk-card uk-card-default uk-card-body uk-width-1-2@m">
			<form method="post">
				<button class="uk-button uk-button-primary uk-margin-top" name="cardbackup"><?php echo L::cardbackup_b; ?></button>
				<button class="uk-button uk-button-primary uk-margin-top" name="camerabackup"><?php echo L::camerabackup_b; ?></button>
				<button class="uk-button uk-button-primary uk-margin-top" name="internalbackup"><?php echo L::internalbackup_b; ?></button>
			</form>
		</div>
		<div class="uk-card uk-card-default uk-card-body uk-width-1-2@m">
			<form method="post">
				<button class="uk-button uk-button-secondary uk-margin-top" name="cancelshutdown"><?php echo L::cancel_shutdown_b; ?></button>
				<button class="uk-button uk-button-danger uk-margin-top" name="shutdown"><?php echo L::shutdown_b; ?></button>
			</form>
		</div>
		<?php
		if (isset($_POST['cardbackup'])) {
			shell_exec('sudo pkill -f card-backup*');
			shell_exec('sudo umount /media/storage');
			shell_exec('sudo shutdown -c > /dev/null 2>&1 & echo $!');
			shell_exec('sudo ./card-backup.sh > /dev/null 2>&1 & echo $!');
			echo "<script>";
			echo "UIkit.notification({message: '<span uk-icon=\'icon: check\'></span>" . L::cardbackup_m . "'});";
			echo "</script";
		}
		if (isset($_POST['camerabackup'])) {
			shell_exec('sudo pkill -f camera-backup*');
			shell_exec('sudo umount /media/storage');
			shell_exec('sudo shutdown -c > /dev/null 2>&1 & echo $!');
			shell_exec('sudo ./camera-backup.sh > /dev/null 2>&1 & echo $!');
			echo "<script>";
			echo "UIkit.notification({message: '<span uk-icon=\'icon: check\'></span>" . L::camerabackup_m . "'});";
			echo "</script";
		}
		if (isset($_POST['internalbackup'])) {
			shell_exec('sudo pkill -f internal-backup*');
			shell_exec('sudo umount /media/storage');
			shell_exec('sudo shutdown -c > /dev/null 2>&1 & echo $!');
			shell_exec('sudo ./internal-backup.sh > /dev/null 2>&1 & echo $!');
			echo "<script>";
			echo "UIkit.notification({message: '<span uk-icon=\'icon: check\'></span>" . L::internalbackup_m . "'});";
			echo "</script";
		}
		if (isset($_POST['shutdown'])) {
			shell_exec('sudo shutdown -h now > /dev/null 2>&1 & echo $!');
			echo "<script>";
			echo "UIkit.notification({message: '<span uk-icon=\'icon: check\'></span>" . L::shutdown_m . "'});";
			echo "</script";
		}
		if (isset($_POST['cancelshutdown'])) {
			shell_exec('sudo shutdown -c > /dev/null 2>&1 & echo $!');
			echo "<script>";
			echo "UIkit.notification({message: '<span uk-icon=\'icon: check\'></span>" . L::cancel_shutdown_m . "'});";
			echo "</script";
		}
		?>
		<p>
			<details>
				<summary><?php echo L::help; ?></summary>
				<?php echo L::help_txt; ?>
			</details>
		</p>
	</div>
</body>

</html>