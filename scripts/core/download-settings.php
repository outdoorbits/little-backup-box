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
$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

//Archive name
$ZIP_FILE_NAME="lbb-settings.zip";
$ZIP_FILE_PATH=$constants["const_WEB_ROOT_LBB"] . "/tmp/";

//Files to zip into archive
$FILES	= array();

$FILES[]	= array(
	'file'	=> $constants["const_WEB_ROOT_LBB"]."/config.cfg",
	'dir'	=> '',
);

$FILES[]	= array(
	'file'	=> $constants["const_MEDIA_DIR"] . '/' . $constants["const_RCLONE_CONFIG_FILE"],
	'dir'	=> '',
);

$FILES[]	= array(
	'file'	=> $constants["const_MEDIA_DIR"] . '/' . $constants["const_BUTTONS_PRIVATE_CONFIG_FILE"],
	'dir'	=> '',
);

#VPN
$vpn_types	= array('OpenVPN','WireGuard');
foreach ($vpn_types as $vpn_type) {
	$VPN_CONFIG_FILE=$constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type];

	#make it visible, if exists
	exec ('sudo chmod 755 "' . $constants['const_VPN_DIR_' . $vpn_type] . '"');
	exec ('sudo chmod 755 "' . $VPN_CONFIG_FILE . '"');

	if (file_exists($VPN_CONFIG_FILE)) {
		$FILES[]	= array(
			'file'	=> $VPN_CONFIG_FILE,
			'dir'	=> '',
		);
	}
}

#Background images
$background_images	= scandir($constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"]);
foreach ($background_images as $BACKGROUND_IMAGE) {
	$BACKGROUND_IMAGE = $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . '/' . $BACKGROUND_IMAGE;
	if (is_file($BACKGROUND_IMAGE)) {
		$FILES[]	= array(
			'file'	=> $BACKGROUND_IMAGE,
			'dir'	=> 'bg-images',
		);
	}
}

# Create ZIP-Archive
$zip = new ZipArchive();
//create the file and throw the error if unsuccessful
if ($zip->open($ZIP_FILE_PATH.$ZIP_FILE_NAME, ZIPARCHIVE::CREATE )!==TRUE) {
	exit("cannot open <$ZIP_FILE_NAME>\n");
}
//add each files of $FILE array to archive
foreach($FILES as $FILE)
{
	if (file_exists($FILE['file'])) {
		$zip->addFile($FILE['file'],$FILE['dir'] . '/' . basename($FILE['file']));
	}
}
$zip->close();

// http headers for zip downloads
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"".$ZIP_FILE_NAME."\"");
header("Content-Transfer-Encoding: binary");
header("Content-Length: ".filesize($ZIP_FILE_PATH.$ZIP_FILE_NAME));

ob_end_flush();
@readfile($ZIP_FILE_PATH.$ZIP_FILE_NAME);

@unlink($ZIP_FILE_PATH.$ZIP_FILE_NAME);

## secure VPN config files
foreach ($vpn_types as $vpn_type) {
	$VPN_CONFIG_FILE=$constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type];

	if (file_exists($VPN_CONFIG_FILE)) {
		exec ('sudo chmod 700 "' . $VPN_CONFIG_FILE . '"');
	}
}

?>
