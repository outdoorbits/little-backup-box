<!doctype html>

<!-- Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file("$WORKING_DIR/config.cfg", false);
	$config_standard = parse_ini_file("$WORKING_DIR/config-standards.cfg", false);
	$constants = parse_ini_file("constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] .'/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");

	include("get-cloudservices.php");

	$TIME_ZONE_old	= $config["conf_TIME_ZONE"];

	$WIFI_COUNTRY	= trim(shell_exec("raspi-config nonint get_wifi_country"));

	$vpn_types		= array('OpenVPN','WireGuard');
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
</head>

<body <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<iframe id="logmonitor" src="/files" width="100%" height="100%" style="background: #FFFFFF;"></iframe>

</body>

</html>
