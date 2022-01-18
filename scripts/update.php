<!-- Author: Stefan Saam, github@saams.de
License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->
<!doctype html>

<?php
$WORKING_DIR=dirname(__FILE__);
$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

$theme = $config["conf_THEME"];
$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";
?>

<html lang="en" data-theme="<?= $theme; ?>">
	<head>
		<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
	</head>
	<body <?= $background; ?>>
		<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

		<h1><?= L::update_header; ?></h1>
		<p>
			<b><u><?= L::update_warning; ?></u></b><br>
		</p>

		<iframe id="updatemonitor" src="/update-runner.php" width="100%" height="500" style="background: #FFFFFF;"></iframe>

		<p>
			<a href="/"><?= L::update_return_link; ?></a>
		</p>
	</body>
</html>

