<form class="text-center" style="margin-top: 1em;" method="POST">
	<div class="card" style="margin-top: 3em;">
		<button name="reboot" class="danger"><?php echo L::main_reboot_button; ?></button>
		<button name="shutdown" class="danger"><?php echo L::main_shutdown_button; ?></button>
	</div>
</form>

<?php
	if (isset($_POST['reboot'])) {
		popup(L::main_reboot_m,$config["conf_POPUP_MESSAGES"]);

		exec("sudo ./poweroff.sh reboot force");
	}
	if (isset($_POST['shutdown'])) {
		popup(L::main_shutdown_m,$config["conf_POPUP_MESSAGES"]);

		exec("sudo ./poweroff.sh poweroff force");
	}
?>

