<!doctype html>

<?php
/*
# Author: Stefan Saam, github@saams.de

#######################################################################
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#######################################################################*/

	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-i18n-loader.php");
?>
<html lang="en" data-theme="<?php echo $theme; ?>">
	<!-- Author: Stefan Saam github@saams.de
			License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

	<head>
		<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
		<script type="text/javascript" src="js/logmonitor.js"></script>
		<script type="text/javascript" src="js/display.js"></script>
	</head>

	<body onload="refreshLogMonitor();refreshDisplay();" <?php echo $background; ?>>

		<div class="card">
			<h3 class='text-center' style='margin-top: 0em;'>
				<?php
					if (isset($_POST['reboot']) or isset($_GET['reboot'])) {
						echo(L::main_reboot_m);
					} elseif (isset($_POST['shutdown']) or isset($_GET['shutdown'])) {
						echo(L::main_shutdown_m);
					}
				?>
			</h3>
		</div>

	</body>

</html>

<?php
	if (ob_get_level()) {
	ob_end_flush();
}

flush();

if (function_exists('fastcgi_finish_request')) {
	fastcgi_finish_request();
}

if (isset($_POST['reboot']) or isset($_GET['reboot'])) {
	exec("sudo python3 $WORKING_DIR/lib_poweroff.py reboot");
} elseif (isset($_POST['shutdown']) or isset($_GET['shutdown'])) {
	exec("sudo python3 $WORKING_DIR/lib_poweroff.py poweroff");
}
?>

