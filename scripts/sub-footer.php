<!--
$WORKING_DIR and $constants mustbe defined in main script.
-->

<div class="footer">
	<form class="text-center" style="margin-top: 1em;" method="POST">
			<div class="card" style="margin-top: 3em;">
				<button name="reboot" class="danger"><?php echo L::main_reboot_button; ?></button>
				<button name="shutdown" class="danger"><?php echo L::main_shutdown_button; ?></button>
			</div>
	</form>

</div>

<?php
	$qr_links	= shell_exec("sudo python3 $WORKING_DIR/lib_network.py qr_links");

	$info_box	= '';
	if ($qr_links != '') {
		$info_box	= $qr_links;
	}

	if (isset($constants['const_SOFTWARE_VERSION']) and $constants['const_SOFTWARE_VERSION'] != 'main') {
		$info_box	= $info_box . "<div style='float: right;'>" . $constants['const_SOFTWARE_VERSION'] . "</div>";
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
