<html lang="en">
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<meta charset="utf-8">
	<title>Little Backup Box IP Address</title>
	<link rel="stylesheet" href="https://unpkg.com/terminal.css@0.7.1/dist/terminal.min.css" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style>
		#content {
			margin: 0px auto;
			text-align: center;
		}

		img {
			display: block;
			margin-left: auto;
			margin-right: auto;
			margin-top: 1%;
			margin-bottom: 1%;
		}
	</style>
</head>

<body>
	<div id="content">
		<div style="margin-top: 1.9em; font-size: 2em;">
			<?php
			if (isset($_GET["ip"])) {
				echo $_GET['ip'];
				$f = fopen("ip", "w");
				fwrite($f, $_GET['ip']);
				fclose($f);
			} else {
				echo file_get_contents('ip', true);
			}
			?>
		</div>
	</div>
</body>

</html>