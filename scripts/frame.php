<?php
/*
# Author: Stefan Saam, github@saams.de

#######################################################################
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#######################################################################*/

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

	<div style="position: absolute; left: 0; width: 100%; height: 90%; border: none; background: #FF0000;">
		<iframe id="pageframe" src="<?php echo $framed_pages[$frame_index]; ?>" style="width: 100%; height: 100%; border: none; background: #FFFFFF;"></iframe>
	<div>

	<?php include "sub-footer.php"; ?>
</body>

</html>
