<?php

$WORKING_DIR=dirname(__FILE__);
$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

//Archive name
$ZIP_FILE_NAME="lbb-settings.zip";
$ZIP_FILE_PATH=$constants["const_WEB_ROOT_LBB"] . "/tmp/";

//Files to zip into archive
$FILES		= array(
	$constants["const_WEB_ROOT_LBB"]."/config.cfg",
	$constants["const_RCLONE_CONFIG_FILE"]
);

#VPN
$vpn_types	= array('OpenVPN','WireGuard');
foreach ($vpn_types as $vpn_type) {
	$VPN_CONFIG_FILE=$constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type];

	#make it visible, if exists
	exec ('sudo chmod 755 "' . $constants['const_VPN_DIR_' . $vpn_type] . '"');
	exec ('sudo chmod 755 "' . $VPN_CONFIG_FILE . '"');

	if (file_exists($VPN_CONFIG_FILE)) {
		$FILES[]	= $VPN_CONFIG_FILE;
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
	if (file_exists($FILE)) {
		$zip->addFile($FILE,basename($FILE));
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
