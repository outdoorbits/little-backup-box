<!doctype html>

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");

	function get_device_selector($name, $list_partitions=true) {
		if ($list_partitions) {
			exec("ls /dev/sd* | xargs -n 1 basename", $devices);
		} else {
			exec("ls /dev/sd* | xargs -n 1 basename | grep -v '[0123456789]'", $devices);
		}

		$selector	= '<select name="' . $name . '">\n';
		$selector .= "<option value='-'>-</option>\n";
		foreach ($devices as $n => $device) {
			$selector .= "<option value='$device'>$device</option>\n";
		}
		$selector .= "</select>";
		return($selector);
	}
	?>

<html lang="en" data-theme="<?php echo $theme; ?>">
<!-- Author: Stefan Saam github@saams.de, Dmitri Popov, dmpop@linux.com
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
						<?php
						print(get_device_selector("partition"));
						echo ("<button name='fsck_check'>" . l::tools_fsck_check_b . "</button>");
						echo ("<button name='fsck_autorepair' class='danger'>" . l::tools_fsck_autorepair_b . "</button>");
						?>
			</form>
	</div>

	<?php include "sub-logmonitor.php"; ?>

	<div class="card" style="margin-top: 3em;">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::cmd_format_header; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
					<label for="PARAM1"><?php echo l::tools_select_partition; ?>:</label>
					<br>
					<?php
					print(get_device_selector("PARAM1"));
					?>
					<br>
					<label for="PARAM2"><?php echo l::tools_select_format_fstype; ?>:</label>
					<br>
					<select name="PARAM2">
						<option value="-">-</option>
						<option value="FAT32">FAT32 (Windows&#174;)</option>
						<option value="exFAT">exFAT (Windows&#174;)</option>
						<option value="NTFS (compression enabled)">NTFS (compression enabled) (Windows&#174;)</option>
						<option value="NTFS (no compression)">NTFS (compression disabled) (Windows&#174;)</option>
						<option value="Ext4">Ext4 (Linux)</option>
						<option value="Ext3">Ext3 (Linux)</option>
						<option value="HFS Plus">HFS Plus (Mac)</option>
						<option value="HFS">HFS (Mac)</option>
					</select>
					<br>
					<?php
					echo ("<button name='format' class='danger'>" . l::tools_format_b . "</button>");
					?>
			</form>
	</div>

	<div class="card" style="margin-top: 3em;">
		<h3 class="text-center" style="margin-top: 0em;"><?php echo l::cmd_f3_header; ?></h3>
		<hr>
			<form class="text-center" style="margin-top: 1em;" method="POST">
					<label for="PARAM1"><?php echo l::tools_select_partition; ?>:</label>
					<br>
					<?php
					print(get_device_selector("PARAM1",false));
					?>
					<br>
					<label for="PARAM2"><?php echo l::tools_f3_select_action; ?>:</label>
					<br>
					<select name="PARAM2">
						<option value="-">-</option>
						<option value="f3probe_non_destructive"><?php echo l::tools_f3_probe_non_destructive; ?></option>
						<option value="f3probe_destructive"><?php echo l::tools_f3_probe_destructive; ?></option>
					</select>
					<br>
					<?php
					echo ("<button name='f3' class='danger'>" . l::tools_f3_b . "</button>");
					?>
			</form>
	</div>

	<div class="card" style="margin-top: 3em;">
		<details>
			<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo l::tools_help; ?></summary>
			<p><?php echo l::tools_help_text; ?></p>
		</details>
	</div>

	<?php include "sub-footer.php"; ?>

	<?php
	if (isset($_POST['mount_usb_1'])) {
			$command = "./mount-storage.sh mount usb_1";
			shell_exec ("./lib-log-helper.sh \"log_exec\" \"\" \"${command}\" \"1\"");
			echo "<script>";
				echo "window.location = window.location.href;";
			echo "</script>";
	}
	if (isset($_POST['umount_usb_1'])) {
			$command = "./mount-storage.sh umount usb_1";
			shell_exec ("./lib-log-helper.sh \"log_exec\" \"\" \"${command}\" \"1\"");
			echo "<script>";
				echo "window.location = window.location.href;";
			echo "</script>";
	}
	if (isset($_POST['mount_usb_2'])) {
			$command = "./mount-storage.sh mount usb_2";
			shell_exec ("./lib-log-helper.sh \"log_exec\" \"\" \"${command}\" \"1\"");
			echo "<script>";
				echo "window.location = window.location.href;";
			echo "</script>";
	}
	if (isset($_POST['umount_usb_2'])) {
			$command = "./mount-storage.sh umount usb_2";
			shell_exec ("./lib-log-helper.sh \"log_exec\" \"\" \"${command}\" \"1\"");
			echo "<script>";
				echo "window.location = window.location.href;";
			echo "</script>";
	}
	if (isset($_POST['fsck_check'])) {

			$device = $_POST['partition'];

			popup($device . l::tools_fsck_check_m,$config["conf_POPUP_MESSAGES"]);

			$command = "sudo ${WORKING_DIR}/tools-fsck.sh '${device}' 'check'";
			exec ("./lib-log-helper.sh \"log_exec\" \"\" \"${command}\" \"1\" \"false\"");
	}
	if (isset($_POST['fsck_autorepair'])) {

			$device = $_POST['partition'];

			popup($device . l::tools_fsck_autorepair_m,$config["conf_POPUP_MESSAGES"]);

			$command = "sudo ${WORKING_DIR}/tools-fsck.sh '${device}' 'repair'";
			exec ("./lib-log-helper.sh \"log_exec\" \"\" \"${command}\" \"1\" \"false\"");
	}

	if (isset($_POST['format'])) {

			$PARAM1 = $_POST['PARAM1'];
			$PARAM2 = $_POST['PARAM2'];

			if (($PARAM1 !== "-") and ($PARAM2 !== "-")) {
				?>
				<script>
						document.location.href="/cmd.php?CMD=format&PARAM1=<?php echo $PARAM1; ?>&PARAM2=<?php echo $PARAM2; ?>";
				</script>
				<?php
				exec ("./lib-log-helper.sh \"log_message\" \"format ${PARAM1} ${PARAM2}\" \"1\"");
			}
	}

	if (isset($_POST['f3'])) {

			$PARAM1 = $_POST['PARAM1'];
			$PARAM2 = $_POST['PARAM2'];

			if (($PARAM1 !== "-") and ($PARAM2 !== "-")) {
				?>
				<script>
						document.location.href="/cmd.php?CMD=f3&PARAM1=<?php echo $PARAM1; ?>&PARAM2=<?php echo $PARAM2; ?>";
				</script>
				<?php
				exec ("./lib-log-helper.sh \"log_message\" \"format ${PARAM1} ${PARAM2}\" \"1\"");
			}
	}

	?>
</body>

</html>
