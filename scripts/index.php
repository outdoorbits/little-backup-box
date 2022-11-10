<!doctype html>

<!-- Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");

	include("get-cloudservices.php");
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "sub-standards-header-loader.php"; ?>
	<script src="js/refresh_iframe.js"></script>
</head>

<body onload="refreshIFrame()" <?php echo $background; ?>>
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
		<div class="card">
			<div class='backupsection'>
				<button name="backup_usb_usb" class="usb"><?php echo L::main_usb_button . L::right_arrow . L::main_usb_button; ?></button>
				<button name="backup_usb_internal" class="usb"><?php echo L::main_usb_button . L::right_arrow . L::main_internal_button; ?></button>
				<button name="backup_internal_usb" class="usb"><?php echo L::main_internal_button . L::right_arrow . L::main_usb_button; ?></button>
			</div>
			<div class='backupsection'>
				<button name="backup_camera_usb" class="camera"><?php echo L::main_camera_button . L::right_arrow . L::main_usb_button; ?></button>
				<button name="backup_camera_internal" class="camera"><?php echo L::main_camera_button . L::right_arrow . L::main_internal_button; ?></button>
			</div>
			<div class='backupsection'>
				<button name="backup_ios_usb" class="ios"><?php echo L::main_ios_button . L::right_arrow . L::main_usb_button; ?></button>
				<button name="backup_ios_internal" class="ios"><?php echo L::main_ios_button . L::right_arrow . L::main_internal_button; ?></button>
			</div>
			<?php
				if (! ($config["conf_RSYNC_SERVER"]=="" or $config["conf_RSYNC_PORT"]=="" or $config["conf_RSYNC_USER"]=="" or $config["conf_RSYNC_conf_PASSWORD"]=="" or $config["conf_RSYNC_SERVER_MODULE"]=="")) {
					echo "<div class='backupsection'>";
						echo "<button name=\"backup_usb_server\" class=\"cloud\">" . L::main_usb_button . L::right_arrow . L::main_rsync_button ."</button>";
						echo "<button name=\"backup_internal_server\" class=\"cloud\">" . L::main_internal_button . L::right_arrow . L::main_rsync_button ."</button>";
					echo "</div>";
				}
			?>

			<?php

				foreach($CloudServices as $CloudService) {
					echo "<div class='backupsection'>";
						echo "<button name=\"backup_usb_cloud_" . $CloudService . "\" class=\"cloud\">" . L::main_usb_button . L::right_arrow ." " . $CloudService . "</button>";
						echo "<button name=\"backup_internal_cloud_" . $CloudService . "\" class=\"cloud\">" . L::main_internal_button . L::right_arrow ." " . $CloudService . "</button>";
					echo "</div>";
				}
			?>

			<div class='backupsection'>
				<button name="backup_thumbnails_usb"><?php echo L::main_thumbnails_button . L::right_arrow . L::main_usb_button; ?></button>
				<button name="backup_thumbnails_internal"><?php echo L::main_thumbnails_button . L::right_arrow . L::main_internal_button; ?></button>
			</div>

			<div class='backupsection'>
				<button name="backup_database_usb"><?php echo L::main_database_button . L::right_arrow . L::main_usb_button; ?></button>
				<button name="backup_database_internal"><?php echo L::main_database_button . L::right_arrow . L::main_internal_button; ?></button>
			</div>

			<div class='backupsection'>
				<button name="stopbackup" class="danger"><?php echo L::main_stopbackup_button; ?>
			</div>

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

	if (isset($_POST['backup_usb_usb'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh usb usb > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_usb_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_usb_internal'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh usb internal > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_usb_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_internal_usb'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh internal usb > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_internal_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_camera_usb'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh camera usb > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_camera_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_camera_internal'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh camera internal > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_camera_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_ios_usb'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh ios usb > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_ios_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_ios_internal'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh ios internal > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_ios_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_usb_server'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh usb rsyncserver > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_usb_button . " " . L::main_backup_to . " " . L::main_rsync_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_internal_server'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh internal rsyncserver > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_internal_button . " " . L::main_backup_to . " " . L::main_rsync_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	foreach (['usb','internal'] as $Source) {
		foreach ($CloudServices as $CloudService) {
			if (isset($_POST['backup_' . $Source . '_cloud_' . $CloudService])) {
				exec('sudo pkill -f "backup*"');
				$SourceDevice=$Source === "usb"?"usb":"internal";
				exec("sudo ./backup.sh " . $SourceDevice . " cloud_" . $CloudService . " > /dev/null 2>&1 & echo $!");

				popup(L::main_backup_backup . " " . ($Source=="usb"?L::main_usb_button:L::main_internal_button) . " " . L::main_backup_to . " " . L::main_cloudservice . " " . $CloudService . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
			}
		}
	}

	if (isset($_POST['backup_thumbnails_usb'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh thumbnails usb > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_thumbnails_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_thumbnails_internal'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh thumbnails internal > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_thumbnails_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_database_usb'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh database usb > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_database_button . " " . L::main_backup_to . " " . L::main_usb_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_database_internal'])) {
		exec('sudo pkill -f "backup*"');
		exec("sudo ./backup.sh database internal > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_database_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}

	if (isset($_POST['stopbackup'])) {
		popup(L::main_stopbackup_m,$config["conf_POPUP_MESSAGES"]);

		exec('sudo pkill -f "backup*"');
		exec('sudo ./lib-lcd-helper.sh "' . L::main_backup_break1 . '" "' . L::main_backup_break2 . '" "+" "+" "+"');
	}
	?>
</body>

</html>
