<?php $LogFile="${WORKING_DIR}/tmp/little-backup-box.log"; ?>

<div class="card" style="margin-top: 3em;">
	<h2 style="margin-top: 0em;"><?php echo L::log_logmonitor; ?></h2>
	<hr>
	<iframe id="logmonitor" src="/tmp/little-backup-box.log" width="100%" height="200" style="background: #FFFFFF;"></iframe>
	<div class="text-center" style="margin-top: 0.5em;">
		<button name="refresh" onclick="window.location.reload();"><?php echo L::log_refresh_button; ?></button>
		<a role="button" href="/tmp/little-backup-box.log" download="little-backup-box.log"><button>Download</button></a>

		<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
			<button style="margin-top: 2em;" type="delete" name="delete" class="danger"><?php echo (L::log_delete_button); ?></button>
		</form>
	</div>
</div>
<?php
	if (isset($_POST['delete'])) {
		unlink("$LogFile");
		$LogFileHandle = fopen("$LogFile", 'w') or die('Error opening file: '+"$LogFile");
		fwrite($LogFileHandle,"");
		fclose("$LogFileHandle");
	};
?>
