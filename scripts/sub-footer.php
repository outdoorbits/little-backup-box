<!--
$WORKING_DIR and $constants mustbe defined in main script.
-->

<form class="text-center" style="margin-top: 1em;" method="POST">
	<div class="card" style="margin-top: 3em;">
		<button name="reboot" class="danger"><?php echo L::main_reboot_button; ?></button>
		<button name="shutdown" class="danger"><?php echo L::main_shutdown_button; ?></button>
	</div>
</form>

<?php
	if (isset($constants['const_SOFTWARE_VERSION']) and $constants['const_SOFTWARE_VERSION'] != 'main') {
		echo ("<div style='float: right;'>" . $constants['const_SOFTWARE_VERSION'] . "</div>");
	}

	if (isset($_POST['reboot']) or isset($_GET['reboot'])) {
		popup(L::main_reboot_m,$config["conf_POPUP_MESSAGES"]);

		exec("sudo python3 $WORKING_DIR/lib_poweroff.py reboot");
	}
	if (isset($_POST['shutdown']) or isset($_GET['shutdown'])) {
		popup(L::main_shutdown_m,$config["conf_POPUP_MESSAGES"]);
		exec("sudo python3 $WORKING_DIR/lib_poweroff.py poweroff");
	}
?>

