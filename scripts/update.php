<!-- Author: Stefan Saam, github@saams.de
License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
$WORKING_DIR=dirname(__FILE__);
$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

$theme = $config["conf_THEME"];
$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";
?>

<html lang="en" data-theme="<?php echo $theme; ?>">
	<head>
		<meta charset="utf-8">
		<title>Little Backup Box</title>
		<meta charset="utf-8">
		<link rel="shortcut icon" href="favicon.png" />
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="css/classless.css">
	</head>
	<body <?php echo $background; ?>>
		<?php
			// include i18n class and initialize it
			require_once 'i18n.class.php';
			$i18n = new i18n('lang/{LANGUAGE}.json', 'cache/', 'en');
			if ($config["conf_LANGUAGE"] !== "") {$i18n->setForcedLang($config["conf_LANGUAGE"]);}
			$i18n->init();
		?>
		<h1><?php echo L::update_header; ?></h1>
		<p>
			<b><u><?php echo L::update_warning; ?></u></b><br>
		</p>

		<iframe id="updatemonitor" src="/update-runner.php" width="100%" height="70%" style="background: #FFFFFF;"></iframe>
		<p>
			<a href="/"><?php echo L::update_return_link; ?></a>
		</p>
	</body>
</html>

