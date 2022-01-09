<!doctype html>

<?php
$WORKING_DIR=dirname(__FILE__);
$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

$theme = $config["conf_THEME"];
$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

include($WORKING_DIR . "/sub-popup.php");
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

	<?php include "${WORKING_DIR}/sub-menu.php"; ?>
	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo l::tools_tools; ?></h1>
	<div class="card">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::tools_mount_header; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
				<?php
					$button = trim(shell_exec("./mount-storage.sh check usb_1"))==""?"<button name='mount_usb_1'>" . l::tools_mount_b . " USB 1</button>":"<button name='umount_usb_1'>" . l::tools_umount_b . " USB 1</button>";
					echo ($button);
					$button = trim(shell_exec("./mount-storage.sh check usb_2"))==""?"<button name='mount_usb_2'>" . l::tools_mount_b . " USB 2</button>":"<button name='umount_usb_2'>" . l::tools_umount_b . " USB 2</button>";
					echo ($button);

				?>
			</form>
	</div>

	<div class="card" style="margin-top: 3em;">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::tools_repair; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
					<label for="partition"><?php echo l::tools_select_partition ?></label>
					<select name="partition">
						<?php
						unset ($devices);
						exec("ls /dev/sd* | xargs -n 1 basename", $devices);
						foreach ($devices as $n => $device) {
								echo "<option value='$device'>$device</option>";
						}
						echo "</select>";
						echo ("<button name='fsck_check'>" . l::tools_fsck_check_b . "</button>");
						echo ("<button name='fsck_autorepair' class='danger'>" . l::tools_fsck_autorepair_b . "</button>");
						?>
			</form>
	</div>

	<?php include "sub-logmonitor.php"; ?>
	<?php include "lib-log-helper.php"; ?>

	<div class="card" style="margin-top: 3em;">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo l::tools_help; ?></summary>
			<p><?php echo l::tools_help_text; ?></p>
		</details>
	</div>

	<?php
	if (isset($_POST['mount_usb_1'])) {
			$command = "./mount-storage.sh mount usb_1";
			$MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");
			exec ("./lib-log-helper.sh \"log_message\" \"${MSG}\" \"\" \"1\"");
			echo "<script>";
				echo "window.location = window.location.href;";
			echo "</script>";
	}
	if (isset($_POST['umount_usb_1'])) {
			$command = "./mount-storage.sh umount usb_1";
			$MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");
			exec ("./lib-log-helper.sh \"log_message\" \"${MSG}\" \"\" \"1\"");
			echo "<script>";
				echo "window.location = window.location.href;";
			echo "</script>";
	}
	if (isset($_POST['mount_usb_2'])) {
			$command = "./mount-storage.sh mount usb_2";
			$MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");
			exec ("./lib-log-helper.sh \"log_message\" \"${MSG}\" \"\" \"1\"");
			echo "<script>";
				echo "window.location = window.location.href;";
			echo "</script>";
	}
	if (isset($_POST['umount_usb_2'])) {
			$command = "./mount-storage.sh umount usb_2";
			$MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");
			exec ("./lib-log-helper.sh \"log_message\" \"${MSG}\" \"\" \"1\"");
			echo "<script>";
				echo "window.location = window.location.href;";
			echo "</script>";
	}
	if (isset($_POST['fsck_check'])) {

			$device = $_POST['partition'];

			popup($device . l::tools_fsck_check_m,$config["conf_POPUP_MESSAGES"]);

			$command = "sudo umount /dev/${device}";
			$MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");
			exec ("./lib-log-helper.sh \"log_message\" \"${MSG}\" \"\" \"1\"");

			$command = "sudo fsck /dev/${device}";
			$MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");
			exec ("./lib-log-helper.sh \"log_message\" \"${MSG}\" \"\" \"1\"");
	}
	if (isset($_POST['fsck_autorepair'])) {

			$device = $_POST['partition'];

			popup($device . l::tools_fsck_autorepair_m,$config["conf_POPUP_MESSAGES"]);

			$command = "sudo umount /dev/${device}";
			$MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");
			exec ("./lib-log-helper.sh \"log_message\" \"${MSG}\" \"\" \"1\"");

			$command = "sudo fsck -a /dev/${device}";
			$MSG=date("H:i:s") . "\n> ${command}:\\n" . shell_exec("${command}");
			exec ("./lib-log-helper.sh \"log_message\" \"${MSG}\" \"\" \"1\"");
	}

	?>
</body>

</html>
