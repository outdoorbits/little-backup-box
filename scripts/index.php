<!doctype html>

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include($WORKING_DIR . "/sub-popup.php");

	$CloudServices=array();
	exec("sudo rclone config show --config \"" . $constants["const_RCLONE_CONFIG_FILE"] . "\" | grep '^\[.*\]$' | sed 's/^\[//' | sed 's/\]$//'",$CloudServices);
?>

<html lang="<?= $config["conf_LANGUAGE"]; ?>" data-theme="<?= $theme; ?>">
<!-- Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
	<script src="js/refresh_iframe.js"></script>
</head>

<body onload="refreshIFrame()" <?= $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<!-- Suppress form re-submit prompt on refresh -->
	<script>
		if (window.history.replaceState) {
			window.history.replaceState(null, null, window.location.href);
		}
	</script>

	<h1 class="text-center mb-4 tracking-wide">LITTLE BACKUP BOX</h1>

	<form class="text-center mt-4" method="POST">
		<div class="card">
			<div class='backupsection'>
				<button name="backup_storage_external" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><?= L::main_source_button . L::right_arrow . L::main_external_button; ?></button>
				<button name="backup_storage_internal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><?= L::main_source_button . L::right_arrow . L::main_internal_button; ?></button>
				<button name="backup_internal_external" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><?= L::main_internal_button . L::right_arrow . L::main_external_button; ?></button>
			</div>
			<div class='backupsection'>
				<button name="backup_camera_external" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><?= L::main_camera_button . L::right_arrow . L::main_external_button; ?></button>
				<button name="backup_camera_internal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><?= L::main_camera_button . L::right_arrow . L::main_internal_button; ?></button>
			</div>
			<div class='backupsection'>
				<button name="backup_ios_external" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><?= L::main_ios_button . L::right_arrow . L::main_external_button; ?></button>
				<button name="backup_ios_internal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><?= L::main_ios_button . L::right_arrow . L::main_internal_button; ?></button>
			</div>
			<?php
				if (! ($config["conf_RSYNC_SERVER"]=="" or $config["conf_RSYNC_PORT"]=="" or $config["conf_RSYNC_USER"]=="" or $config["conf_RSYNC_conf_PASSWORD"]=="" or $config["conf_RSYNC_SERVER_MODULE"]=="")) {
					echo "<div class='backupsection'>";
						echo "<button name=\"backup_external_server\" class=\"cloud\">" . L::main_external_button . L::right_arrow . L::main_rsync_button ."</button>";
						echo "<button name=\"backup_internal_server\" class=\"cloud\">" . L::main_internal_button . L::right_arrow . L::main_rsync_button ."</button>";
					echo "</div>";
				}
			?>

			<?php

				foreach($CloudServices as $CloudService) {
					echo "<div class='backupsection'>";
						echo "<button name=\"backup_external_cloud_" . $CloudService . "\" class=\"cloud\">" . L::main_external_button . L::right_arrow ." " . $CloudService . "</button>";
						echo "<button name=\"backup_internal_cloud_" . $CloudService . "\" class=\"cloud\">" . L::main_internal_button . L::right_arrow ." " . $CloudService . "</button>";
					echo "</div>";
				}
			?>

			<div class='backupsection'>
				<button name="stopbackup" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"><?= L::main_stopbackup_button; ?>
			</div>

		</div>


        <div class='max-w-md rounded overflow-hidden shadow-lg p-4 mx-auto'>
            <button name='reboot' class='bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded'><?= L::main_reboot_button; ?></button>
            <button name="shutdown" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded"><?= L::main_shutdown_button; ?></button>
        </div>
	</form>



	<?php include "sub-logmonitor.php"; ?>

	<div class="max-w-sm rounded overflow-hidden shadow-lg p-4 mx-auto mt-10">
		<details>
			<summary class="uppercase"><?= L::help; ?></summary>
			<p><?= L::main_help_txt; ?></p>
		</details>
	</div>
	<?php
	exec("mkdir -p tmp");
	exec("sudo chown www-data:www-data ./tmp -R");

	if (isset($_POST['backup_storage_external'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh storage external > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_source_button . " " . L::main_backup_to . " " . L::main_external_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_storage_internal'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh storage internal > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_source_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_internal_external'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh internal external > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_internal_button . " " . L::main_backup_to . " " . L::main_external_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_camera_external'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh camera external > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_camera_button . " " . L::main_backup_to . " " . L::main_external_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_camera_internal'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh camera internal > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_camera_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_ios_external'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh ios external > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_ios_button . " " . L::main_backup_to . " " . L::main_external_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_ios_internal'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh ios internal > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_ios_button . " " . L::main_backup_to . " " . L::main_internal_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_external_server'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh storage rsyncserver > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_external_button . " " . L::main_backup_to . " " . L::main_rsync_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	if (isset($_POST['backup_internal_server'])) {
		exec("sudo pkill -f backup*");
		exec("sudo ./backup.sh internal rsyncserver > /dev/null 2>&1 & echo $!");
		popup(L::main_backup_backup . " " . L::main_internal_button . " " . L::main_backup_to . " " . L::main_rsync_button . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
	}
	foreach (['external','internal'] as $Source) {
		foreach ($CloudServices as $CloudService) {
			if (isset($_POST['backup_' . $Source . '_cloud_' . $CloudService])) {
				exec("sudo pkill -f backup*");
				$SourceDevice=$Source === "external"?"storage":"internal";
				exec("sudo ./backup.sh " . $SourceDevice . " cloud_" . $CloudService . " > /dev/null 2>&1 & echo $!");

				popup(L::main_backup_backup . " " . ($Source=="external"?L::main_external_button:L::main_internal_button) . " " . L::main_backup_to . " " . L::main_cloudservice . " " . $CloudService . " ". L::main_backup_initiated. ".",$config["conf_POPUP_MESSAGES"]);
			}
		}
	}
	if (isset($_POST['stopbackup'])) {
		popup(L::main_stopbackup_m,$config["conf_POPUP_MESSAGES"]);

		exec("sudo pkill -f backup*");
	}
	if (isset($_POST['reboot'])) {
		popup(L::main_reboot_m,$config["conf_POPUP_MESSAGES"]);

		exec("sudo ./poweroff.sh reboot force");
	}
	if (isset($_POST['shutdown'])) {
		popup(L::main_shutdown_m,$config["conf_POPUP_MESSAGES"]);

		exec("sudo ./poweroff.sh poweroff force");
	}
	?>
</body>

</html>
