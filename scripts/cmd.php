<!doctype html>

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
$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

$theme = $config["conf_THEME"];
$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] . "/" . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

include("sub-i18n-loader.php");

include("sub-virtual-keyboard.php");
include("sub-logmonitor.php");

$run_command	= false;

# expected parameters:
# CMD: "update", "format", "f3"
# optional parameters:
# PARAM1, PARAM2

function doFlush() {
		if (!headers_sent()) {
			// Disable gzip in PHP.
			ini_set('zlib.output_compression', 0);

			// Force disable compression in a header.
			// Required for flush in some cases (Apache + mod_proxy, nginx, php-fpm).
			header('Content-Encoding: none');
		}

		// Fill-up buffer (should be enough in most cases).
		echo str_pad('', ini_get('output_buffering')),"\n";

		// Flush all buffers.
		do {
			$flushed = @ob_end_flush();
		} while ($flushed);

		@ob_flush();
		flush();
	}

ob_implicit_flush(true);
ob_start();
doFlush();
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php
		include "${WORKING_DIR}/sub-standards-header-loader.php";
		echo virtual_keyboard_css($config["conf_VIRTUAL_KEYBOARD_ENABLED"]);
	?>
	<script src="js/logmonitor.js"></script>
</head>

<body <?php echo $background; ?> onload="refreshLogMonitor()">
<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>

<?php

if (count($_GET) > 0) {
	$INPUT	= $_GET;
} else {
	$INPUT	= $_POST;
}
// allowed parameters
$PWD						= isset($INPUT['PWD']) ? $INPUT['PWD'] : '';
$MAIL_ASKED					= isset($INPUT['MAIL_ASKED']) ? $INPUT['MAIL_ASKED'] : '';

$MAIL_RESULT	= isset($INPUT['MAIL_RESULT']);

$CMD			= isset($INPUT['CMD']) ? $INPUT['CMD'] : '';
$PARAM1			= isset($INPUT['PARAM1']) ? $INPUT['PARAM1'] : '';
$PARAM2			= isset($INPUT['PARAM2']) ? $INPUT['PARAM2'] : '';

switch($CMD) {
	case 'update':
		$CMD_HEADER			= L::cmd_update_header;
		$INFO_TEXT			= L::cmd_update_warning;
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= True;
		$ALLOW_MAIL_RESULT	= False;
		break;

	case 'update_development':
		$CMD_HEADER			= L::cmd_update_development_header;
		$INFO_TEXT			= L::cmd_update_development_warning;
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= True;
		$ALLOW_MAIL_RESULT	= False;
		break;

	case 'fsck':
		$CMD_HEADER			= L::cmd_fsck_header;
		$INFO_TEXT			= L::cmd_fsck_warning;
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= ($PARAM2 == 'repair');
		$ALLOW_MAIL_RESULT	= True;
		break;

	case 'format':
		$CMD_HEADER			= L::cmd_format_header;
		$INFO_TEXT			= L::cmd_format_warning;
		$CMD_DESCRIPTION	= L::cmd_format_description.": <ul class='danger'><li>" . L::cmd_format_header . ": " . $PARAM1 . " &rarr; " . $PARAM2 . "</li></ul>";
		$PASSWORD_REQ		= True;
		$ALLOW_MAIL_RESULT	= True;
		break;

	case 'f3':
		$CMD_HEADER			= L::cmd_no_cmd;
		$INFO_TEXT			= "";
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= False;
		$ALLOW_MAIL_RESULT	= False;

		switch($PARAM2) {
			case 'f3probe_non_destructive':
				$CMD_HEADER			= L::cmd_f3_header;
				$INFO_TEXT			= L::cmd_f3_warning_non_destructive;
				$CMD_DESCRIPTION	= L::cmd_f3_description.": <ul class='danger'><li>" . L::cmd_f3_header . ": " . $PARAM1 . " &rarr; " . L::cmd_f3_description_non_destructive . "</li></ul>";
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;

			case 'f3probe_destructive':
				$CMD_HEADER			= L::cmd_f3_header;
				$INFO_TEXT			= L::cmd_f3_warning_destructive;
				$CMD_DESCRIPTION	= L::cmd_f3_description.": <ul class='danger'><li>" . L::cmd_f3_header . ": " . $PARAM1 . " &rarr; " . L::cmd_f3_description_destructive . "</li></ul>";
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;
		}
		break;

			case 'comitup_reset':
				$CMD_HEADER			= L::config_comitup_section;
				$INFO_TEXT			= '';
				$CMD_DESCRIPTION	= L::config_comitup_text;
				$PASSWORD_REQ		= True;
				$ALLOW_MAIL_RESULT	= True;
				break;
	default:
		$CMD_HEADER			= L::cmd_no_cmd;
		$INFO_TEXT			= "";
		$CMD_DESCRIPTION	= "";
		$PASSWORD_REQ		= False;
		$ALLOW_MAIL_RESULT	= False;
}

$PASSWORD_ASK	= false;
if ($PASSWORD_REQ) {
	if ($PWD !== '') {
		$PASSWORD_ASK	= ($PWD !== base64_decode($config['conf_PASSWORD']));
	} else {
		$PASSWORD_ASK	= true;
	}
}

$MAIL_ASK = false;
if ($ALLOW_MAIL_RESULT and ($MAIL_ASKED == '')) {
	$MAIL_ASK	= (
		(strlen($config['conf_SMTP_SERVER']) > 0) and
		(strlen($config['conf_SMTP_PORT']) > 0) and
		(strlen($config['conf_MAIL_SECURITY']) > 0) and
		(strlen($config['conf_MAIL_USER']) > 0) and
		(strlen($config['conf_MAIL_PASSWORD']) > 0) and
		(strlen($config['conf_MAIL_FROM']) > 0) and
		(strlen($config['conf_MAIL_TO']) > 0)
	);
}

if (isset($CMD_HEADER)) {
?>

	<h1><?php echo $CMD_HEADER; ?></h1>
	<p>
		<b><u><?php echo $INFO_TEXT; ?></u></b><br>

		<?php echo $CMD_DESCRIPTION; ?><br>
	</p>

	<?php
		if ($PASSWORD_ASK or $MAIL_ASK) {
// 			password check necessary
			if ($config['conf_PASSWORD'] != "") {
	?>
			<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
				<input type="hidden" name="CMD" value="<?php echo $CMD; ?>">
				<input type="hidden" name="PARAM1" value="<?php echo $PARAM1; ?>">
				<input type="hidden" name="PARAM2" value="<?php echo $PARAM2; ?>">

				<div class="card" style="margin-top: 2em;">

					<?php
						if ($MAIL_ASK) {
							echo '<input type="hidden" name="MAIL_ASKED" value="1">';
							echo '<input type="checkbox" name="MAIL_RESULT" id="MAIL_RESULT" checked>&nbsp;';
							echo '<label for="MAIL_RESULT">' . L::cmd_mail_result . ':</label><br>';
						}
					?>

					<?php
						if ($PASSWORD_ASK) {
							echo '<label for="PWD">' . L::cmd_input_password . ':</label>';
							echo '<input type="password" ' . virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true') . ' size="20" name="PWD" id="PWD"><br>';
						}
					?>

					<button style="margin-top: 2em;" type="submit" name="upload_settings"><?php echo L::cmd_execute; ?></button>
				</div>
			</form>
	<?php
			} else {
	?>
				<div class="card" style="margin-top: 2em;">
					<?php echo l::cmd_password_input_info . "<br><a href ='/setup.php'>" . l::cmd_password_set_link . '</a>'; ?><br>
				</div>
	<?php
			}
			?>
				<p>
					<a href="/"><?php echo L::cmd_link_text_home; ?></a>
				</p>
			<?php
		} elseif (isset($CMD)) {
//  			run command later
			$run_command	= true;
			logmonitor(sourcefile: $constants['const_CMD_LOGFILE'], title: '', allow_logfile_operations: true);
			?>
		<p>
			<a href="/"><?php echo L::cmd_link_text_home_running; ?></a>
		</p>
	<?php
		}
}
		echo virtual_keyboard_js($config["conf_VIRTUAL_KEYBOARD_ENABLED"],$config["conf_LANGUAGE"],$config["conf_THEME"]);
	?>
</body>
</html>

<?php
doFlush();
if ($run_command) {exec_command($CMD, $PARAM1, $PARAM2, $MAIL_RESULT);}

function exec_command($CMD, $PARAM1, $PARAM2, $MAIL_RESULT) {
	# call this function at the end of the code: if $MAIL_RESULT==true the script will wait until the process is finished

	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include "${WORKING_DIR}/sub-security.php";

// 			allowed parameters

			if ($CMD == '') {
				echo 'NOT AUTHORISED</body</html>';
				exit('NOT AUTHORISED');
			}

	if ($CMD !== '') {

		switch($CMD) {
			case 'update':
				$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_update_start1 . "' ':" . L::box_cmd_update_start2 . "'";
				$COMMAND_LINE	.= ";cd ~pi; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/main/install-little-backup-box.sh | sudo -u pi bash";
				break;

			case 'update_development':
				$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_update_start1 . "' ':" . L::box_cmd_update_start2 . "'";
				$COMMAND_LINE	.= ";cd ~pi; curl -sSL https://raw.githubusercontent.com/outdoorbits/little-backup-box/development/install-little-backup-box.sh | sudo -u pi bash -s -- development";
				break;

			case 'fsck':
				$DEVICE_FSTYPE	= exec("sudo lsblk -p -P -o PATH,MOUNTPOINT,UUID,FSTYPE | grep /dev/".clean_argument($PARAM1));
				$DEVICE_FSTYPE	= explode('FSTYPE=',$DEVICE_FSTYPE)[1];
				$DEVICE_FSTYPE	= explode('"',$DEVICE_FSTYPE)[1];

				if ($PARAM2 == 'repair') {
					if ($DEVICE_FSTYPE	== 'exfat') {
						$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE -p '/dev/".clean_argument($PARAM1)."'";
					}
					else {
						$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE -f -p '/dev/".clean_argument($PARAM1)."'";
					}
				}
				else {
// 					check only
					$MAIN_COMMAND	= "fsck.$DEVICE_FSTYPE '/dev/".clean_argument($PARAM1)."'";
				}

				$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_fsck_start1 . "' ':" . L::box_cmd_fsck_start2 . "' ':" . clean_argument($PARAM2,array(' ')) . "'";
				$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
				$COMMAND_LINE	.= ";echo ''";
				$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
				$COMMAND_LINE	.= ";echo ''";
				$COMMAND_LINE	.= ";echo 'FINISHED.'";
				$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_fsck_stop1 . "' ':" . L::box_cmd_fsck_stop2 . "' ':" . clean_argument($PARAM2,array(' ')) . "'";
				break;

			case 'format':
				if (($PARAM1 !== "-") and ($PARAM1 !== " ") and ($PARAM2 !== "-") and ($PARAM2 !== " ")) {
					if ($PARAM2 == "FAT32") {
						$MAIN_COMMAND	= "mkfs.vfat -v -I -F32 '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "exFAT") {
						$MAIN_COMMAND	= "mkfs.exfat '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "NTFS (compression enabled)") {
						$MAIN_COMMAND	= "mkfs.ntfs --enable-compression --force --verbose '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "NTFS (no compression)") {
						$MAIN_COMMAND	= "mkfs.ntfs --force --verbose '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "Ext4") {
						$MAIN_COMMAND	= "mkfs.ext4 -v -F '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "Ext3") {
						$MAIN_COMMAND	= "mkfs.ext3 -v -F '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "HFS Plus") {
						$MAIN_COMMAND	= "mkfs.hfsplus '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck.hfsplus '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					elseif ($PARAM2 == "HFS") {
						$MAIN_COMMAND	= "mkfs.hfs '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_start1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fdisk -l '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";lsblk -f '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo fsck.hfs '/dev/".clean_argument($PARAM1)."'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_format_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": ".clean_argument($PARAM2,array(' '))."' ':" . L::box_cmd_format_stop2 . "'";
					}

					else {
						$COMMAND_LINE	= '';
					}

				} else {
					$COMMAND_LINE	= '';
				}
				break;

			case 'f3':
				switch($PARAM2) {
					case 'f3probe_non_destructive':
						$MAIN_COMMAND	= "f3probe --time-ops '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_start1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_non_destructive . "' ':" . L::box_cmd_f3_probe_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_non_destructive . "' ':" . L::box_cmd_f3_probe_stop2 . "'";
						break;

					case 'f3probe_destructive':
						$MAIN_COMMAND	= "f3probe --destructive --time-ops '/dev/".clean_argument($PARAM1)."'";

						$COMMAND_LINE	= "sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_start1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_destructive . "' ':" . L::box_cmd_f3_probe_start2 . "'";
						$COMMAND_LINE	.= ";echo 'sudo $MAIN_COMMAND'";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
						$COMMAND_LINE	.= ";echo ''";
						$COMMAND_LINE	.= ";echo 'FINISHED.'";
						$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::box_cmd_f3_probe_stop1 . "' ':".clean_argument($PARAM1,array(' ')).": " . L::box_cmd_f3_probe_destructive . "' ':" . L::box_cmd_f3_probe_stop2 . "'";
						break;

					default:
						$COMMAND_LINE	= "";
				}
				break;

			case 'comitup_reset':
				$MAIN_COMMAND	= "$WORKING_DIR/comitup-reset.sh";

				$COMMAND_LINE	= "cat $MAIN_COMMAND | grep '^[^# ]'";
				$COMMAND_LINE	.= ";echo ''";
				$COMMAND_LINE	.= ";sudo $MAIN_COMMAND";
				$COMMAND_LINE	.= ";echo ''";
				$COMMAND_LINE	.= ";echo 'FINISHED.'";
				$COMMAND_LINE	.= ";sudo python3 $WORKING_DIR/lib_display.py ':" . L::config_comitup_section . "' ':" . L::cmd_reset . "'";
				break;

			default:
				$COMMAND_LINE	= "";
		}

		# write lockfile
		$lockfile = fopen($constants["const_CMD_RUNNER_LOCKFILE"],"w");
			fwrite($lockfile, $COMMAND_LINE);
			fclose($lockfile);

		# start command in background
		$PID	= intval(shell_exec(sprintf('sh -c "%s" > %s 2>&1 & echo $!', $COMMAND_LINE, $constants['const_CMD_LOGFILE'])));

		# mail result
		if ($MAIL_RESULT) {
			# no more output to the web ui
			flush();

			# wait until process is finished
			if ($PID > 0) {
				while (posix_getpgid($PID)) {sleep(0.5);}
			}

			# read result from logfile
			$RESULT	= file_get_contents($constants['const_CMD_LOGFILE']);

			shell_exec('sudo python3 ' . $WORKING_DIR . '/lib_mail.py "' . $CMD . ' ' . clean_argument($PARAM1,array(' ')) . ' ' . clean_argument($PARAM2,array(' ')) . '" "' . $RESULT . '"');
		}

		unlink($constants["const_CMD_RUNNER_LOCKFILE"]);
	}

}
?>
