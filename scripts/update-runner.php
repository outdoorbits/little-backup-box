<!-- Author: Stefan Saam, github@saams.de
License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	$theme = "dark";
	$WORKING_DIR=dirname(__FILE__);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);
?>

<html lang="en" data-theme="<?= $theme; ?>">
	<head>
		<script language="javascript">
			var int = self.setInterval("window.scrollBy(0,1000);", 200);
		</script>
	</head>
	<body>

		<?php
			ob_implicit_flush(true);
			ob_end_flush();

			$cmd = "cd ~pi; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | sudo -u pi bash";

			$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
			);

			# write lockfile
			$lockfile = fopen($constants["const_UPDATE_LOCKFILE"],"w");
			fwrite($lockfile, "update");
			fclose($lockfile);

			# start update
			$process = proc_open($cmd, $descriptorspec, $pipes, realpath('./'), array());

			echo '<pre>';
			if (is_resource($process)) {
				while ($s = fgets($pipes[1])) {
					print $s;
				}
			}
			echo '</pre>';

			unlink($constants["const_UPDATE_LOCKFILE"]);
		?>

	</body>
</html>

