<!doctype html>

<!-- Author: Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file("$WORKING_DIR/config.cfg", false);
	$config_standard = parse_ini_file("$WORKING_DIR/config-standards.cfg", false);
	$constants = parse_ini_file("constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] .'/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	$HTTP_HOST	= $_SERVER['SERVER_PORT']==80?$_SERVER['HTTP_HOST'] . ":80":$_SERVER['HTTP_HOST'];

	if (isset($_SERVER['HTTPS'])) {
		$PROTOCOL	= "https://";
	} else {
		$PROTOCOL	= "http://";
	}

	$framed_pages	= array(
		'files'			=> '/files',
		'rclone_gui'	=> $PROTOCOL.$HTTP_HOST.($PROTOCOL == "https://"?":8443":":81")
	);

	$frame_index	= '';
	if (isset($_GET['page'])) {
		$frame_index	= $_GET['page'];
	}

	if (! isset($framed_pages[$frame_index])) {
		header("Location: " . $PROTOCOL . $HTTP_HOST);
		exit();
	}
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
</head>

<body <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<iframe id="logmonitor" src="<?php echo $framed_pages[$frame_index]; ?>" style="position: absolute; left: 0; width: 100%; height: 90%; border: none; background: #FFFFFF;"></iframe>

</body>

</html>
