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

// Usage:
// 1. in the beginning of the main script load the library:
// 		include("sub-virtual-keyboard.php");
//
// 2. in the html head integrate the css:
// 		<?php virtual_keyboard_css();
//
//$WORKING_DIR and $constants mustbe defined in main script.-->
?>

<div class="footer">
	<form class="text-center" style="margin-top: 1em;" method="POST">
			<div class="card" style="margin-top: 3em;">
				<button name="reboot" class="danger"><?php echo L::main_reboot_button; ?></button>
				<button name="shutdown" class="danger"><?php echo L::main_shutdown_button; ?></button>
			</div>
	</form>

</div>

<?php
	if (isset($_SERVER['HTTPS'])) {
		$PROTOCOL	= "https";
	} else {
		$PROTOCOL	= "http";
	}

	$qr_links	= shell_exec("sudo python3 $WORKING_DIR/lib_network.py --Mode qr_links --Protocol $PROTOCOL");

	$info_box	= '';
	if ($qr_links != '') {
		$info_box	= $qr_links;
	}

	if (isset($constants['const_SOFTWARE_BRANCH']) and $constants['const_SOFTWARE_BRANCH'] != 'main') {
		$info_box	= $info_box . "<div style='float: right;'>" . $constants['const_SOFTWARE_BRANCH'] . "</div>";
	}

	if ($info_box != '') {
		echo('<div class="card" style="margin-top: 3em;">' . $info_box . '</div>');
	}
?>

<?php
	if (isset($_POST['reboot']) or isset($_GET['reboot'])) {
		popup(L::main_reboot_m,$config["conf_POPUP_MESSAGES"]);

		exec("sudo python3 $WORKING_DIR/lib_poweroff.py reboot");
	}
	if (isset($_POST['shutdown']) or isset($_GET['shutdown'])) {
		popup(L::main_shutdown_m,$config["conf_POPUP_MESSAGES"]);
		exec("sudo python3 $WORKING_DIR/lib_poweroff.py poweroff");
	}
?>
