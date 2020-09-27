<html lang="en">
	
<!-- Author: Dmitri Popov, dmpop@linux.com
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<head>
	<title>Little Backup Box</title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/uikit@3.5.6/dist/css/uikit.min.css" />
	<script src="https://cdn.jsdelivr.net/npm/uikit@3.5.6/dist/js/uikit.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/uikit@3.5.6/dist/js/uikit-icons.min.js"></script>
</head>

<body>
	<div class="uk-container uk-margin-small-top">
		<div class="uk-card uk-card-primary uk-card-body uk-text-center">
			<h1 class="uk-heading-line uk-text-center"><span>IP address</span></h1>
			<h1>
				<?php
				if (isset($_GET["ip"])) {
					echo $_GET['ip'];
					$f = fopen("ip", "w");
					fwrite($f, $_GET['ip']);
					fclose($f);
				} else {
					$ip = file_get_contents('ip', true);
					echo "<a href='http://".$ip.":8000'>".$ip."</a>";
				}
				?>
			</h1>
		</div>
	</div>
</body>

</html>