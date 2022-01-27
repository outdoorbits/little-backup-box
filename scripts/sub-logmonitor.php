<iframe id="logmonitor" src="/tmp/little-backup-box.log" width="100%" height="200"></iframe>
<div class="text-center mt-2">
    <button class="btn" name="refresh" onclick="window.location.reload();"><?= L::log_refresh_button; ?></button>
    <a class="btn" href="/tmp/little-backup-box.log" download="little-backup-box.log">Download</a>

    <form action="<?= $_SERVER['PHP_SELF'] ?>" method="POST">
        <button type="delete" name="delete"
                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded mt-10"><?= (L::log_delete_button); ?></button>
    </form>
</div>
<?php
if (isset($_POST['delete'])) {
    unlink($constants["const_LOGFILE"]);
    $LogFileHandle = fopen($constants["const_LOGFILE"], 'w') or die('Error opening file: ' . $constants["const_LOGFILE"]);
    fwrite($LogFileHandle, "");
		fclose("$LogFileHandle");
	}
?>
