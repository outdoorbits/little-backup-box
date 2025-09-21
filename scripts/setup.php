<!doctype html>

<!--
# Author: Stefan Saam, github@saams.de
# Original author: Dmitri Popov, dmpop@linux.com

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
#######################################################################
-->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file("$WORKING_DIR/config.cfg", false);
	$config_standard = parse_ini_file("$WORKING_DIR/config-standards.cfg", false);
	$constants = parse_ini_file("constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='" . $constants["const_MEDIA_DIR"] .'/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include("sub-popup.php");
	include("sub-virtual-keyboard.php");

	include("sub-common.php");
	$NVMe_available	= get_nvme_available($WORKING_DIR, $constants, false);
	$CloudServices	= get_cloudservices($constants);

	$TIME_ZONE_old	= $config["conf_TIME_ZONE"];

	$WIFI_COUNTRY	= trim(shell_exec("raspi-config nonint get_wifi_country"));

	$vpn_types		= array('OpenVPN','WireGuard');

	$SetupMessages	= '';

	// load i18n languages
	if (isset($_POST['conf_LANGUAGE'])) {
		$config['conf_LANGUAGE']	= $_POST['conf_LANGUAGE'];
	}
	include("sub-i18n-loader.php");

	// exit lbb
	if (isset($_POST['exit_lbb'])) {
		// pause idletime
		try {
			$IDLETIME_LOCKFILE = fopen($constants['const_IDLETIME_LOCKFILE'], 'w');
			fclose($IDLETIME_LOCKFILE);
		} catch (Exception $e) {echo ("Can't write ".$constants['const_IDLETIME_LOCKFILE']);}


		// stop backup
		if (isset($_POST['exit_lbb_stop_backup'])) {
			shell_exec("sudo $WORKING_DIR/stop_backup.sh");
		}

		// stop firefox
		shell_exec("sudo pkill -f firefox");
	}
	// write new config
	elseif (isset($_POST['save'])) {
		// write new config to config.cfg
		write_config();

		// testmail
		if (isset($_POST['send_testmail'])) {
			exec("sudo python3 $WORKING_DIR/lib_mail.py '" . L::config_mail_testmail_subject . "' '" . L::config_mail_testmail_content . "' /dev/null 2>&1 &");
			$SetupMessages .= '<div class="card" style="margin-top: 2em;">' . L::config_mail_testmail_sent . '</div>';
		}

		// rclone_gui
		if (isset($_POST['restart_rclone_gui'])) {
			exec("sudo python3 $WORKING_DIR/start-rclone-gui.py True > /dev/null /dev/null 2>&1 &");
			$SetupMessages .= '<div class="card" style="margin-top: 2em;">' . L::config_rclone_gui_restarted . '</div>';
		}

		// restart display using new config
		exec("sudo bash -c \"python3 $WORKING_DIR/lib_display.py 'set:kill'; sleep ".($config['conf_DISP_FRAME_TIME']>$config['conf_DISP_FRAME_TIME_IP']?$config['conf_DISP_FRAME_TIME']:$config['conf_DISP_FRAME_TIME_IP'])+0.2."; python3 $WORKING_DIR/lib_display.py '" . L::config_display_message_settings_saved_1 . "' '" . L::config_display_message_settings_saved_2 . "'  > /dev/null 2>&1 &\"");
	}

	if (isset($_GET['check_update'])) {
			$UpdateAvailable	= trim(shell_exec("sudo python3 $WORKING_DIR/lib_git.py --write-available --update-available"));
			if ($UpdateAvailable == 'True') {
				$SetupMessages	.= popup(L::config_update_available, POPUP_ALLOWED: true, ECHO_OUTPUT: false);
			} else {
				$SetupMessages	.= popup(L::config_update_not_available,  POPUP_ALLOWED: true, ECHO_OUTPUT: false);
			}
	}

	// Upload settings
	if (isset($_POST['upload_settings'])) {
		upload_settings();
	};

	// read (new) config
	$config = parse_ini_file("$WORKING_DIR/config.cfg", false);

	# set timezone
	if ($TIME_ZONE_old !== $config["conf_TIME_ZONE"]) {
		shell_exec("sudo raspi-config nonint do_change_timezone ".$config["conf_TIME_ZONE"]);
	}

	# write wifi country-code from config.cfg
	if (($WIFI_COUNTRY !== $config["conf_WIFI_COUNTRY"]) and ($config["conf_WIFI_COUNTRY"] !== "")) {
		shell_exec("sudo raspi-config nonint do_wifi_country ".$config["conf_WIFI_COUNTRY"]);
		$WIFI_COUNTRY	= trim(shell_exec("raspi-config nonint get_wifi_country"));
	}

	function get_wifi_country_selector($ID,$NAME) {
		global $WIFI_COUNTRY;

		$wifi_country_selector	= '<select id="'.$ID.'" name="'.$NAME.'">';

			exec("sed '/^#/d' /usr/share/zoneinfo/iso3166.tab",$COUNTRIES);
			foreach($COUNTRIES as $COUNTRY) {
				$COUNTRY_ARRAY	= explode("\t",$COUNTRY,2);
				$COUNTRYCODE	= trim($COUNTRY_ARRAY[0]);
				$COUNTRYNAME	= trim($COUNTRY_ARRAY[1]);
				$selected		= $WIFI_COUNTRY==$COUNTRYCODE ? " selected" : "";

				$wifi_country_selector	.= '<option value="'.$COUNTRYCODE.'"'.$selected.'>'.$COUNTRYCODE.' '.$COUNTRYNAME.'</option>';
			}
		$wifi_country_selector	.= '</select>';
		return ($wifi_country_selector);
	}

	function check_new_password($title, $pwd_1, $pwd_2) {
		global $SetupMessages;

		$pwd_valid = false;
			if ($pwd_1 !== $pwd_2) {
				$SetupMessages	.= popup($title . "\n" . L::config_alert_password_not_identical,  POPUP_ALLOWED: true, ECHO_OUTPUT: false);
			} elseif (strlen($pwd_1) < 5) {
				$SetupMessages	.= popup($title . "\n" . L::config_alert_password_too_short,  POPUP_ALLOWED: true, ECHO_OUTPUT: false);
			} elseif (
					strpos("_" . $pwd_1,"\\") or
					strpos("_" . $pwd_1,"'") or
					strpos("_" . $pwd_1,"\"") or
					strpos("_" . $pwd_1," ")
					) {
				$SetupMessages	.= popup($title . "\n" . L::config_alert_password_characters_not_allowed,  POPUP_ALLOWED: true, ECHO_OUTPUT: false);
			} else {
				$pwd_valid=true;
			}

		return($pwd_valid);
	}

	function write_config() {
		# write config.cfg

		global $WORKING_DIR, $WIFI_COUNTRY, $config, $constants, $vpn_types, $CloudServices, $SetupMessages;

		extract ($_POST, EXTR_SKIP);

	// 	prepare variables for saving

		list($conf_BACKUP_DEFAULT_SOURCE,$conf_BACKUP_DEFAULT_TARGET)	= explode(" ",$BACKUP_MODE,2);
		list($conf_BACKUP_DEFAULT_SOURCE2,$conf_BACKUP_DEFAULT_TARGET2)	= explode(" ",$BACKUP_MODE_2,2);

		$conf_BACKUP_CAMERA_FOLDER_MASK	= str_replace("\r\n", ';', $conf_BACKUP_CAMERA_FOLDER_MASK);
		$conf_BACKUP_CAMERA_FOLDER_MASK	= str_replace("\n", ';', $conf_BACKUP_CAMERA_FOLDER_MASK);

		$conf_BACKUP_CLOUDS_TARGET_BASEDIR		= '';
		$conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE	= '';
		foreach($CloudServices as $CloudService) {
			if (isset(${'conf_BACKUP_CLOUDS_TARGET_BASEDIR_'.$CloudService})) {
				$conf_BACKUP_CLOUDS_TARGET_BASEDIR	= $conf_BACKUP_CLOUDS_TARGET_BASEDIR ? $conf_BACKUP_CLOUDS_TARGET_BASEDIR . '|;|' : $conf_BACKUP_CLOUDS_TARGET_BASEDIR;
				${'conf_BACKUP_CLOUDS_TARGET_BASEDIR_'.$CloudService}	= trim(${'conf_BACKUP_CLOUDS_TARGET_BASEDIR_'.$CloudService});
				${'conf_BACKUP_CLOUDS_TARGET_BASEDIR_'.$CloudService}	= trim(${'conf_BACKUP_CLOUDS_TARGET_BASEDIR_'.$CloudService}, '/\\');
				$conf_BACKUP_CLOUDS_TARGET_BASEDIR	.= $CloudService.'|=|'.${'conf_BACKUP_CLOUDS_TARGET_BASEDIR_'.$CloudService};
			}

			$conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE	= $conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE ? $conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE . '|;|' : $conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE;
			${'conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE_'.$CloudService}	= (isset(${'conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE_'.$CloudService})) ? 'true':'false';
			$conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE	.= $CloudService.'|=|'.${'conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE_'.$CloudService};
		}

		$conf_BACKUP_SYNC_METHOD_CLOUDS	= '';
		foreach($CloudServices as $CloudService) {
			if (isset(${'conf_BACKUP_SYNC_METHOD_CLOUDS_'.$CloudService})) {
				$conf_BACKUP_SYNC_METHOD_CLOUDS	= $conf_BACKUP_SYNC_METHOD_CLOUDS ? $conf_BACKUP_SYNC_METHOD_CLOUDS . '|;|' : $conf_BACKUP_SYNC_METHOD_CLOUDS;
				${'conf_BACKUP_SYNC_METHOD_CLOUDS_'.$CloudService}	= trim(${'conf_BACKUP_SYNC_METHOD_CLOUDS_'.$CloudService});
				$conf_BACKUP_SYNC_METHOD_CLOUDS	.= $CloudService.'|=|'.${'conf_BACKUP_SYNC_METHOD_CLOUDS_'.$CloudService};
			}
		}

		$conf_SOFTWARE_DATE_INSTALLED				= $config["conf_SOFTWARE_DATE_INSTALLED"];
		$conf_SOFTWARE_DATE_AVAILABLE				= $config["conf_SOFTWARE_DATE_AVAILABLE"];

		$conf_BACKUP_DEFAULT_MOVE_FILES				= isset($conf_BACKUP_DEFAULT_MOVE_FILES)?'true':'false';
		$conf_BACKUP_DEFAULT_RENAME_FILES			= isset($conf_BACKUP_DEFAULT_RENAME_FILES)?'true':'false';
		$conf_BACKUP_DEFAULT2_MOVE_FILES			= isset($conf_BACKUP_DEFAULT2_MOVE_FILES)?'true':'false';
		$conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS	= isset($conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS)?'true':'false';
		$conf_BACKUP_DEFAULT_UPDATE_EXIF			= isset($conf_BACKUP_DEFAULT_UPDATE_EXIF)?'true':'false';
		$conf_BACKUP_MOVE_FILES						= isset($conf_BACKUP_MOVE_FILES)?'true':'false';
		$conf_POWER_OFF								= isset($conf_POWER_OFF)?'true':'false';
		$conf_MAIL_IP								= isset($conf_MAIL_IP)?'true':'false';
		$conf_MAIL_NOTIFICATIONS					= isset($conf_MAIL_NOTIFICATIONS)?'true':'false';
		$conf_MAIL_HTML								= isset($conf_MAIL_HTML)?'true':'false';
		$conf_DISP									= isset($conf_DISP)?'true':'false';
		$conf_DISP_COLOR_BGR						= isset($conf_DISP_COLOR_BGR)?'true':'false';
		$conf_DISP_COLOR_INVERSE					= isset($conf_DISP_COLOR_INVERSE)?'true':'false';
		$conf_DISP_BACKLIGHT_ENABLED				= isset($conf_DISP_BACKLIGHT_ENABLED)?'true':'false';
		$conf_DISP_IP_REPEAT						= isset($conf_DISP_IP_REPEAT)?'true':'false';
		$conf_DISP_SHOW_STATUSBAR					= isset($conf_DISP_SHOW_STATUSBAR)?'true':'false';
		$conf_MENU_ENABLED							= isset($conf_MENU_ENABLED)?'true':'false';
		$conf_VIRTUAL_KEYBOARD_ENABLED				= isset($conf_VIRTUAL_KEYBOARD_ENABLED)?'true':'false';
		$conf_LOG_SYNC								= isset($conf_LOG_SYNC)?'true':'false';
		$conf_POPUP_MESSAGES						= isset($conf_POPUP_MESSAGES)?'true':'false';
		$conf_BACKUP_RENAME_FILES					= isset($conf_BACKUP_RENAME_FILES)?'true':'false';
		$conf_BACKUP_CHECKSUM						= isset($conf_BACKUP_CHECKSUM)?'true':'false';
		$conf_BACKUP_GENERATE_THUMBNAILS			= isset($conf_BACKUP_GENERATE_THUMBNAILS)?'true':'false';
		$conf_BACKUP_UPDATE_EXIF					= isset($conf_BACKUP_UPDATE_EXIF)?'true':'false';
		$conf_VIEW_CONVERT_HEIC						= isset($conf_VIEW_CONVERT_HEIC)?'true':'false';
		$conf_VIEW_WRITE_RATING_EXIF				= isset($conf_VIEW_WRITE_RATING_EXIF)?'true':'false';
		$conf_DIPLAY_IMAGES_KEEP					= isset($conf_DIPLAY_IMAGES_KEEP)?'true':'false';

		if ($conf_MAIL_PASSWORD != '') {
			if (! check_new_password (L::config_alert_password_mail_header, $conf_MAIL_PASSWORD, $conf_MAIL_PASSWORD)) {
				$conf_MAIL_PASSWORD	= '';
			} else {
				$conf_MAIL_PASSWORD					= base64_encode($conf_MAIL_PASSWORD);
			}
		}

		if ($conf_RSYNC_PASSWORD != '') {
			if (! check_new_password (L::config_alert_password_rsync_header, $conf_RSYNC_PASSWORD, $conf_RSYNC_PASSWORD)) {
				$conf_RSYNC_PASSWORD	= '';
			} else {
				$conf_RSYNC_PASSWORD				= base64_encode($conf_RSYNC_PASSWORD);
			}
		}

		$conf_PASSWORD								= $conf_PASSWORD_OLD; // conf_PASSWORD_OLD is given as base64
		if (isset($conf_PASSWORD_REMOVE)) {
			$conf_PASSWORD='';
			exec("sudo python3 " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/lib_password.py"); # remove password
			$SetupMessages	.= popup(L::config_alert_password_change_after_reboot_remove, POPUP_ALLOWED: true, ECHO_OUTPUT: false);
		} elseif ($conf_PASSWORD_1 != '') {
			if (check_new_password (L::config_alert_password_global, $conf_PASSWORD_1, $conf_PASSWORD_2)) {
				exec("sudo python3 " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/lib_password.py '" . $conf_PASSWORD_1 . "'");
				$conf_PASSWORD=base64_encode($conf_PASSWORD_1);

				if ((strlen($conf_PASSWORD_1) < 8) or (strlen($conf_PASSWORD_1) > 63)) {
					$SetupMessages	.= popup(L::config_alert_password_wifi_size_error, POPUP_ALLOWED: true, ECHO_OUTPUT: false);
				}
				$SetupMessages	.= popup(L::config_alert_password_change_after_reboot_set, POPUP_ALLOWED: true, ECHO_OUTPUT: false);
			}
		}

		$CONFIGFILE = "$WORKING_DIR/config.cfg";
		$config_file_handle = fopen($CONFIGFILE, "w");

		$config_file_content = <<<CONFIGDATA
conf_SOFTWARE_DATE_INSTALLED='$conf_SOFTWARE_DATE_INSTALLED'
conf_SOFTWARE_DATE_AVAILABLE='$conf_SOFTWARE_DATE_AVAILABLE'
conf_LANGUAGE='$conf_LANGUAGE'
conf_TIME_ZONE='$conf_TIME_ZONE'
conf_BACKUP_DEFAULT_SOURCE='$conf_BACKUP_DEFAULT_SOURCE'
conf_BACKUP_DEFAULT_TARGET='$conf_BACKUP_DEFAULT_TARGET'
conf_BACKUP_DEFAULT_MOVE_FILES=$conf_BACKUP_DEFAULT_MOVE_FILES
conf_BACKUP_DEFAULT_RENAME_FILES=$conf_BACKUP_DEFAULT_RENAME_FILES
conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS=$conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS
conf_BACKUP_DEFAULT_UPDATE_EXIF=$conf_BACKUP_DEFAULT_UPDATE_EXIF
conf_BACKUP_DEFAULT_SOURCE2='$conf_BACKUP_DEFAULT_SOURCE2'
conf_BACKUP_DEFAULT_TARGET2='$conf_BACKUP_DEFAULT_TARGET2'
conf_BACKUP_DEFAULT2_MOVE_FILES=$conf_BACKUP_DEFAULT2_MOVE_FILES
conf_BACKUP_TARGET_SIZE_MIN=$conf_BACKUP_TARGET_SIZE_MIN
conf_BACKUP_CAMERA_FOLDER_MASK='$conf_BACKUP_CAMERA_FOLDER_MASK'
conf_BACKUP_CLOUDS_TARGET_BASEDIR='$conf_BACKUP_CLOUDS_TARGET_BASEDIR'
conf_BACKUP_SYNC_METHOD_CLOUDS='$conf_BACKUP_SYNC_METHOD_CLOUDS'
conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE='$conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE'
conf_BACKUP_MOVE_FILES=$conf_BACKUP_MOVE_FILES
conf_POWER_OFF=$conf_POWER_OFF
conf_DISP=$conf_DISP
conf_DISP_CONNECTION='$conf_DISP_CONNECTION'
conf_DISP_DRIVER='$conf_DISP_DRIVER'
conf_DISP_I2C_ADDRESS='$conf_DISP_I2C_ADDRESS'
conf_DISP_SPI_PORT='$conf_DISP_SPI_PORT'
conf_DISP_RESOLUTION_X=$conf_DISP_RESOLUTION_X
conf_DISP_RESOLUTION_Y=$conf_DISP_RESOLUTION_Y
conf_DISP_OFFSET_X=$conf_DISP_OFFSET_X
conf_DISP_OFFSET_Y=$conf_DISP_OFFSET_Y
conf_DISP_ROTATE='$conf_DISP_ROTATE'
conf_DISP_CONTRAST=$conf_DISP_CONTRAST
conf_DISP_COLOR_BGR=$conf_DISP_COLOR_BGR
conf_DISP_COLOR_INVERSE=$conf_DISP_COLOR_INVERSE
conf_DISP_BACKLIGHT_PIN=$conf_DISP_BACKLIGHT_PIN
conf_DISP_BACKLIGHT_ENABLED=$conf_DISP_BACKLIGHT_ENABLED
conf_DISP_COLOR_MODEL='$conf_DISP_COLOR_MODEL'
conf_DISP_COLOR_TEXT='$conf_DISP_COLOR_TEXT'
conf_DISP_COLOR_HIGH='$conf_DISP_COLOR_HIGH'
conf_DISP_COLOR_ALERT='$conf_DISP_COLOR_ALERT'
conf_DISP_COLOR_BACKGROUND='$conf_DISP_COLOR_BACKGROUND'
conf_DISP_FONT_SIZE=$conf_DISP_FONT_SIZE
conf_DISP_FRAME_TIME=$conf_DISP_FRAME_TIME
conf_DISP_FRAME_TIME_IP=$conf_DISP_FRAME_TIME_IP
conf_DISP_IP_REPEAT=$conf_DISP_IP_REPEAT
conf_DISP_SHOW_STATUSBAR=$conf_DISP_SHOW_STATUSBAR
conf_MENU_ENABLED=$conf_MENU_ENABLED
conf_MENU_BUTTON_COMBINATION='$conf_MENU_BUTTON_COMBINATION'
conf_MENU_BUTTON_ROTATE='$conf_MENU_BUTTON_ROTATE'
conf_MENU_BUTTON_BOUNCETIME=$conf_MENU_BUTTON_BOUNCETIME
conf_MENU_BUTTON_EDGE_DETECTION='$conf_MENU_BUTTON_EDGE_DETECTION'
conf_MENU_BUTTON_RESISTOR_PULL='$conf_MENU_BUTTON_RESISTOR_PULL'
conf_VIRTUAL_KEYBOARD_ENABLED=$conf_VIRTUAL_KEYBOARD_ENABLED
conf_FAN_PWM_TEMP_C=$conf_FAN_PWM_TEMP_C
conf_FAN_PWM_GPIO=$conf_FAN_PWM_GPIO
conf_THEME=$conf_THEME
conf_BACKGROUND_IMAGE=$conf_BACKGROUND_IMAGE
conf_POPUP_MESSAGES=$conf_POPUP_MESSAGES
conf_LOGLEVEL=$conf_LOGLEVEL
conf_LOG_SYNC=$conf_LOG_SYNC
conf_POWER_OFF_IDLE_TIME=$conf_POWER_OFF_IDLE_TIME
conf_BACKUP_RENAME_FILES=$conf_BACKUP_RENAME_FILES
conf_BACKUP_CHECKSUM=$conf_BACKUP_CHECKSUM
conf_BACKUP_GENERATE_THUMBNAILS=$conf_BACKUP_GENERATE_THUMBNAILS
conf_BACKUP_UPDATE_EXIF=$conf_BACKUP_UPDATE_EXIF
conf_VIEW_CONVERT_HEIC=$conf_VIEW_CONVERT_HEIC
conf_VIEW_WRITE_RATING_EXIF=$conf_VIEW_WRITE_RATING_EXIF
conf_MAIL_IP=$conf_MAIL_IP
conf_MAIL_NOTIFICATIONS=$conf_MAIL_NOTIFICATIONS
conf_MAIL_TIMEOUT_SEC=$conf_MAIL_TIMEOUT_SEC
conf_MAIL_HTML=$conf_MAIL_HTML
conf_SMTP_SERVER='$conf_SMTP_SERVER'
conf_SMTP_PORT='$conf_SMTP_PORT'
conf_MAIL_SECURITY='$conf_MAIL_SECURITY'
conf_MAIL_USER='$conf_MAIL_USER'
conf_MAIL_PASSWORD='$conf_MAIL_PASSWORD'
conf_MAIL_FROM='$conf_MAIL_FROM'
conf_MAIL_TO='$conf_MAIL_TO'
conf_RSYNC_SERVER='$conf_RSYNC_SERVER'
conf_RSYNC_PORT='$conf_RSYNC_PORT'
conf_RSYNC_USER='$conf_RSYNC_USER'
conf_RSYNC_PASSWORD='$conf_RSYNC_PASSWORD'
conf_RSYNC_SERVER_MODULE='$conf_RSYNC_SERVER_MODULE'
conf_WIFI_COUNTRY='$conf_WIFI_COUNTRY'
conf_VPN_TYPE_RSYNC='$conf_VPN_TYPE_RSYNC'
conf_VPN_TYPE_CLOUD='$conf_VPN_TYPE_CLOUD'
conf_VPN_TIMEOUT=$conf_VPN_TIMEOUT
conf_PASSWORD='$conf_PASSWORD'
conf_PASSWORD_ENCRYPTION='$conf_PASSWORD_ENCRYPTION'
conf_DIPLAY_IMAGES_KEEP=$conf_DIPLAY_IMAGES_KEEP
conf_TELEGRAM_TOKEN="$conf_TELEGRAM_TOKEN"
conf_TELEGRAM_CHAT_ID=$conf_TELEGRAM_CHAT_ID
conf_TELEGRAM_CHAT_IDENTIFIER="$conf_TELEGRAM_CHAT_IDENTIFIER"
conf_MASTODON_BASE_URL="$conf_MASTODON_BASE_URL"
conf_MASTODON_TOKEN="$conf_MASTODON_TOKEN"

CONFIGDATA;

		fwrite($config_file_handle, $config_file_content);
		fclose($config_file_handle);

		# remove vpn config file
		foreach($vpn_types as $vpn_type) {
			if (isset($_POST['vpn_remove_' . $vpn_type])) {
				exec ('sudo rm "' . $constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type] . '"');
			}
		}

		# save vpn config file
		if ($vpn_upload_type !== 'none') {
			if(file_exists($_FILES["vpn_conf_file"]["tmp_name"])) {
				exec ('sudo mkdir -p "' . $constants['const_VPN_DIR_' . $vpn_upload_type] . '"');
				exec ('sudo mv "' . $_FILES["vpn_conf_file"]["tmp_name"] . '" "' . $constants['const_VPN_DIR_' . $vpn_upload_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_upload_type] . '"');
				exec ('sudo chown root:root "' . $constants['const_VPN_DIR_' . $vpn_upload_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_upload_type] . '"');
				exec ('sudo chmod 700 "' . $constants['const_VPN_DIR_' . $vpn_upload_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_upload_type] . '"');
			}
		}

		# write hardware-settings
		exec("sudo " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/set_hardware.sh");

		if (strlen($conf_LANGUAGE) > 1) {
			include("./tinyfilemanager-config.php");
			$tinyfilemanager	= new tinyfilemanager\tinyfilemanager;
			$tinyfilemanager->reconfig(array("lang=" . substr($conf_LANGUAGE,0,2)));
			$tinyfilemanager	= null;
		}

		# response
		$SetupMessages .= '<div class="card" style="margin-top: 2em;">' . L::config_message_settings_saved . '</div>';
	}

	function upload_settings() {
		global $WORKING_DIR, $config, $constants, $vpn_types, $SetupMessages;

		if($_FILES["settings_file"]["name"]) {
			$filename = $_FILES["settings_file"]["name"];
			$source = $_FILES["settings_file"]["tmp_name"];
			$type = $_FILES["settings_file"]["type"];
			$name = explode(".", $filename);
			$accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');

			$FILETYPE_ZIP	= false;
			foreach($accepted_types as $mime_type) {
				if($mime_type == $type) {
					$FILETYPE_ZIP = true;
					break;
				}
			}
			$continue = ((strtolower($name[1]) == 'zip') and $FILETYPE_ZIP) ? true : false;
			if(!$continue) {
				exec("sudo python3 $WORKING_DIR/lib_display.py '" . L::config_display_message_settings_upload_error_1 . "' '" . L::config_display_message_settings_upload_error_2 . "' '" . L::config_display_message_settings_upload_error_3 . "' > /dev/null 2>&1 &");
				$SetupMessages	.= popup(L::config_alert_settings_upload_not_zip, POPUP_ALLOWED: true, ECHO_OUTPUT: false);
			} else {
				/* PHP current path */

				$targetdir = $constants["const_WEB_ROOT_LBB"].'/tmp/unzip';
				$targetzip = $targetdir . '/' . $filename;
				/* create directory if not exists' */
				@mkdir($targetdir, 0777);

				/* here it is really happening */

				if(move_uploaded_file($source, $targetzip)) {
					$zip = new ZipArchive();
					$file_opened = $zip->open($targetzip);  // open the zip file to extract
					if ($file_opened === true) {
						$zip->extractTo($targetdir); // place in the directory with same name
						$zip->close();

						unlink($targetzip);
						$Files_Copied="";

						if (file_exists($targetdir."/config.cfg")) {
							@unlink($constants["const_WEB_ROOT_LBB"].'/config.cfg');
							if (rename($targetdir."/config.cfg",$constants["const_WEB_ROOT_LBB"]."/config.cfg")) {
								$Files_Copied="\n* 'config.cfg'";
								exec('sudo dos2unix "' . $constants["const_WEB_ROOT_LBB"] . '/config.cfg"');
							}

						}

						if (file_exists($targetdir."/".$constants["const_RCLONE_CONFIG_FILE"])) {
							if (rename($targetdir."/".$constants["const_RCLONE_CONFIG_FILE"],$constants["const_MEDIA_DIR"] . '/' . $constants["const_RCLONE_CONFIG_FILE"])) {
								$Files_Copied=$Files_Copied."\n* '".$constants["const_RCLONE_CONFIG_FILE"]."'";
								exec('sudo dos2unix "' . $constants["const_MEDIA_DIR"] . '/' . $constants["const_RCLONE_CONFIG_FILE"] . '"');
							}
						}

						if (file_exists($targetdir."/".$constants["const_BUTTONS_PRIVATE_CONFIG_FILE"])) {
							if (rename($targetdir."/".$constants["const_BUTTONS_PRIVATE_CONFIG_FILE"],$constants["const_MEDIA_DIR"] . '/' . $constants["const_BUTTONS_PRIVATE_CONFIG_FILE"])) {
								$Files_Copied=$Files_Copied."\n* '".$constants["const_BUTTONS_PRIVATE_CONFIG_FILE"]."'";
								exec('sudo dos2unix "'. $constants["const_MEDIA_DIR"] . '/' . $constants["const_BUTTONS_PRIVATE_CONFIG_FILE"] . '"');
							}
						}

						foreach($vpn_types as $vpn_type) {
							if (file_exists($targetdir.'/'.$constants['const_VPN_FILENAME_' . $vpn_type])) {
								exec ('sudo rm "' . $constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type] . '"');
								exec ('sudo mv "' . $targetdir.'/'.$constants['const_VPN_FILENAME_' . $vpn_type] . '" "' . $constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type] . '"');
								$Files_Copied=$Files_Copied."\n* '" . $constants['const_VPN_FILENAME_' . $vpn_type] . "'";
								exec('sudo dos2unix "'. $constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type] . '"');

								## secure VPN config files
								exec ('sudo chmod 700 "' . $constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type] . '" -R');
							}
						}

						#Background images
						if (is_dir($targetdir.'/bg-images')){

							exec ("sudo mkdir -p '" . $constants["const_MEDIA_DIR"] . '/' . $constants['const_BACKGROUND_IMAGES_DIR'] . "'");
							exec ("sudo chown www-data:www-data '" . $constants["const_MEDIA_DIR"] . '/' . $constants['const_BACKGROUND_IMAGES_DIR'] ."' -R");

							$background_images	= scandir($targetdir.'/bg-images');
							foreach ($background_images as $BACKGROUND_IMAGE) {
								if (is_file($targetdir . '/bg-images/' . $BACKGROUND_IMAGE)) {
									$Files_Copied=$Files_Copied."\n* '" . $BACKGROUND_IMAGE . "'";
								}
							}
							exec ("sudo mv '" . $targetdir . "/bg-images/'* '" . $constants["const_MEDIA_DIR"] . '/' . $constants['const_BACKGROUND_IMAGES_DIR'] . "/'");
							exec ("sudo chown www-data:www-data '" . $constants["const_MEDIA_DIR"] . '/' . $constants['const_BACKGROUND_IMAGES_DIR'] ."/'*");
						}

						# rewrite config to actual version
						exec("sudo python3 $WORKING_DIR/lib_setup.py");

						# Feedback: Files in place
						exec("sudo python3 $WORKING_DIR/lib_display.py ':" . L::config_display_message_settings_uploaded_1 . "' ':" . L::config_display_message_settings_uploaded_2 . "' > /dev/null 2>&1 &");
						$SetupMessages	.= popup(L::config_alert_settings_upload_success. " ". $Files_Copied, POPUP_ALLOWED: true, ECHO_OUTPUT: false);

						# reload config
						$config = parse_ini_file("$WORKING_DIR/config.cfg", false);

						# set new password
						if (isset ($config["conf_PASSWORD"]) and check_new_password(L::config_alert_password_global,base64_decode($config["conf_PASSWORD"]),base64_decode($config["conf_PASSWORD"]))) {
							exec("sudo python3 " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/lib_password.py '" . base64_decode($config["conf_PASSWORD"]) . "'");
							$SetupMessages	.= popup(L::config_alert_password_change_after_reboot_set, POPUP_ALLOWED: true, ECHO_OUTPUT: false);
						} else {
							exec("sudo python3 " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/lib_password.py");
							$SetupMessages	.= popup(L::config_alert_password_change_after_reboot_remove, POPUP_ALLOWED: true, ECHO_OUTPUT: false);
						}
					}

				} else {
					exec("sudo python3 $WORKING_DIR/lib_display.py '" . L::config_display_message_settings_upload_error_1 . "' '" . L::config_display_message_settings_upload_error_2 . "' '" . L::config_display_message_settings_upload_error_3 . "' > /dev/null 2>&1 &");
					$SetupMessages	.= popup(L::config_alert_settings_upload_problem, POPUP_ALLOWED: true, ECHO_OUTPUT: false);
				}

			}
			exec("sudo rm -R ".$targetdir);
		}
	}
?>
<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php
		include "${WORKING_DIR}/sub-standards-header-loader.php";
		echo virtual_keyboard_css($config["conf_VIRTUAL_KEYBOARD_ENABLED"]);
	?>
	<script type="text/javascript" src="js/display.js"></script>
</head>

<body <?php echo $background; ?> onload="refreshDisplay();">
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>
	<?php
		include "${WORKING_DIR}/sub-display.php";
		display();
	?>

	<?php echo($SetupMessages); ?>

	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">

		<div class="card" style="margin-top: 2em;">
			<?php echo '<button style="margin-top: 2em;" type="submit" name="save">' . L::config_save_button . '</button>'; ?>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_lang_time_section; ?></summary>

				<div>
					<h3><?php echo L::config_lang_header; ?></h3>
						<label for="conf_LANGUAGE"><?php echo L::config_lang_label; ?></label><br />
							<select name="conf_LANGUAGE" id="conf_LANGUAGE">
							<?php
								echo "<option value='' " . ($config["conf_LANGUAGE"] == ""?" selected":"") . ">" . L::config_lang_browser_detect . "</option>";
								$languages=array();
								exec ("find 'lang'/*.json -type f ",$languages);
								foreach($languages as $language) {
									$language = basename($language, ".json");
									echo "<option value='" . $language . "' " . ($config["conf_LANGUAGE"] == $language?" selected":"") . ">" . $language . "</option>";
								}
							?>
						</select>
				</div>

				<div>
					<h3><?php echo L::config_time_zone_header; ?></h3>
						<label for="conf_TIME_ZONE"><?php echo L::config_time_zone_label; ?></label><br />
							<select name="conf_TIME_ZONE" id="conf_TIME_ZONE">
								<?php
									$time_zones=array();
									exec ("timedatectl list-timezones",$time_zones);
									foreach($time_zones as $time_zone) {
										echo "<option value='" . $time_zone . "' " . ($config["conf_TIME_ZONE"] == $time_zone?" selected":"") . ">" . $time_zone . "</option>";
									}
								?>
							</select>
					</div>
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_backup_section; ?></summary>

				<h3><?php echo L::config_backup_header; ?></h3>
					<label for="BACKUP_MODE"><?php echo L::config_backup_label; ?></label><br />

					<select name="BACKUP_MODE" id="BACKUP_MODE">
						<option value="none none" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="none none"?" selected":""; ?>><?php echo L::config_backup_none; ?></option>

<!-- to USB -->
						<optgroup label="&rarr; <?php echo L::main_usb_button; ?>">
							<option value="anyusb usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="anyusb usb"?" selected":""; ?>><?php echo L::main_anyusb_button . L::right_arrow . L::main_usb_button; ?></option>
							<option value="usb usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="usb usb"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . L::main_usb_button; ?></option>
							<?php
								if ($NVMe_available) {
									?>
										<option value="nvme usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="nvme usb"?" selected":""; ?>><?php echo L::main_nvme_button . L::right_arrow . L::main_usb_button; ?></option>
									<?php
								}
							?>
							<option value="internal usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="internal usb"?" selected":""; ?>><?php echo L::main_internal_button . L::right_arrow . L::main_usb_button; ?></option>
							<option value="camera usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="camera usb"?" selected":""; ?>><?php echo L::main_camera_button . L::right_arrow . L::main_usb_button; ?></option>
							<option value="ftp usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="ftp usb"?" selected":""; ?>><?php echo L::box_backup_mode_ftp . L::right_arrow . L::main_usb_button; ?></option>
						</optgroup>

<!-- to NVMe -->

						<?php
							if ($NVMe_available) {
								?>
									<optgroup label="&rarr; <?php echo L::main_nvme_button; ?>">
										<option value="anyusb nvme" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="anyusb nvme"?" selected":""; ?>><?php echo L::main_anyusb_button . L::right_arrow . L::main_nvme_button; ?></option>
										<option value="usb nvme" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="usb nvme"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . L::main_nvme_button; ?></option>
										<option value="internal nvme" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="internal nvme"?" selected":""; ?>><?php echo L::main_internal_button . L::right_arrow . L::main_nvme_button; ?></option>
										<option value="camera nvme" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="camera nvme"?" selected":""; ?>><?php echo L::main_camera_button . L::right_arrow . L::main_nvme_button; ?></option>
										<option value="ftp nvme" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="ftp nvme"?" selected":""; ?>><?php echo L::box_backup_mode_ftp . L::right_arrow . L::main_nvme_button; ?></option>
									</optgroup>
								<?php
							}
						?>

<!-- to internal -->
						<optgroup label="&rarr; <?php echo L::main_internal_button; ?>">
							<option value="anyusb internal" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="anyusb internal"?" selected":""; ?>><?php echo L::main_anyusb_button . L::right_arrow . L::main_internal_button; ?></option>
							<option value="usb internal" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="usb internal"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . L::main_internal_button; ?></option>
							<?php
								if ($NVMe_available) {
									?>
										<option value="nvme internal" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="nvme internal"?" selected":""; ?>><?php echo L::main_nvme_button . L::right_arrow . L::main_internal_button; ?></option>
									<?php
								}
							?>
							<option value="camera internal" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="camera internal"?" selected":""; ?>><?php echo L::main_camera_button . L::right_arrow . L::main_internal_button; ?></option>
							<option value="ftp internal" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="ftp internal"?" selected":""; ?>><?php echo L::box_backup_mode_ftp . L::right_arrow . L::main_internal_button; ?></option>
						</optgroup>

<!-- to rsync server -->
						<?php
							if (! ($config["conf_RSYNC_SERVER"]=="" or $config["conf_RSYNC_PORT"]=="" or $config["conf_RSYNC_USER"]=="" or $config["conf_RSYNC_PASSWORD"]=="" or $config["conf_RSYNC_SERVER_MODULE"]=="")) {
						?>
								<optgroup label="&rarr; <?php echo L::main_rsync_button; ?>">
									<option value="usb cloud_rsync" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="usb cloud_rsync"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . L::main_rsync_button; ?></option>
									<?php
										if ($NVMe_available) {
											?>
												<option value="nvme cloud_rsync" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="nvme cloud_rsync"?" selected":""; ?>><?php echo L::main_nvme_button . L::right_arrow . L::main_rsync_button; ?></option>
											<?php
										}
									?>
									<option value="internal cloud_rsync" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="internal cloud_rsync"?" selected":""; ?>><?php echo L::main_internal_button . L::right_arrow . L::main_rsync_button; ?></option>
								</optgroup>
						<?php
							}

// to cloud service
							foreach($CloudServices as $CloudService) {
								?>
									<optgroup label="&rarr; <?php echo $CloudService; ?>">
										<option value="anyusb cloud:<?php print $CloudService; ?>" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="anyusb cloud:${CloudService}"?" selected":""; ?>><?php echo L::main_anyusb_button . L::right_arrow . $CloudService; ?></option>
										<option value="usb cloud:<?php print $CloudService; ?>" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="usb cloud:${CloudService}"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . $CloudService; ?></option>
										<?php
											if ($NVMe_available) {
												?>
													<option value="nvme cloud:<?php print $CloudService; ?>" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="nvme cloud:${CloudService}"?" selected":""; ?>><?php echo L::main_nvme_button . L::right_arrow . $CloudService; ?></option>
												<?php
											}
										?>
										<option value="internal cloud:<?php print $CloudService; ?>" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="internal cloud:${CloudService}"?" selected":""; ?>><?php echo L::main_internal_button . L::right_arrow . $CloudService; ?></option>
										<option value="camera cloud:<?php print $CloudService; ?>" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="camera cloud:${CloudService}"?" selected":""; ?>><?php echo L::main_camera_button . L::right_arrow . $CloudService; ?></option>
										<option value="ftp cloud:<?php print $CloudService; ?>" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="ftp cloud:${CloudService}"?" selected":""; ?>><?php echo L::box_backup_mode_ftp . L::right_arrow . $CloudService; ?></option>
									</optgroup>
								<?php
							}
						?>
					</select>

				<h4><?php echo L::config_backup_default_settings_header; ?></h4>

					<h5><?php echo L::config_backup_move_files_header; ?></h5>
						<input type="checkbox" id="conf_BACKUP_DEFAULT_MOVE_FILES" name="conf_BACKUP_DEFAULT_MOVE_FILES" <?php echo $config['conf_BACKUP_DEFAULT_MOVE_FILES']=="1"?"checked":""; ?>>
						<label for="conf_BACKUP_DEFAULT_MOVE_FILES"><?php echo L::config_backup_move_files_label; ?></label><br />

					<h5><?php echo L::config_backup_rename_header; ?></h5>
						<input type="checkbox" id="conf_BACKUP_DEFAULT_RENAME_FILES" name="conf_BACKUP_DEFAULT_RENAME_FILES" <?php echo $config['conf_BACKUP_DEFAULT_RENAME_FILES']=="1"?"checked":""; ?>>
						<label for="conf_BACKUP_DEFAULT_RENAME_FILES"><?php echo L::config_backup_rename_label.'<br />'.L::config_backup_rename_warning; ?></label><br />

					<h5><?php echo L::config_backup_generate_thumbnails_header; ?></h5>
						<input type="checkbox" id="conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS" name="conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS" <?php echo $config['conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS']=="1"?"checked":""; ?>>
						<label for="conf_BACKUP_DEFAULT_GENERATE_THUMBNAILS"><?php echo L::config_backup_generate_thumbnails_label; ?></label><br />

					<h5><?php echo L::config_backup_update_exif_header; ?></h5>
						<input type="checkbox" id="conf_BACKUP_DEFAULT_UPDATE_EXIF" name="conf_BACKUP_DEFAULT_UPDATE_EXIF" <?php echo $config['conf_BACKUP_DEFAULT_UPDATE_EXIF']=="1"?"checked":""; ?>>
						<label for="conf_BACKUP_DEFAULT_UPDATE_EXIF"><?php echo L::config_backup_update_exif_label; ?></label><br />

				<h3><?php echo L::config_backup_header2; ?></h3>
					<label for="BACKUP_MODE_2"><?php echo L::config_backup_label2; ?></label><br />

					<?php
						get_secondary_backup_selector("BACKUP_MODE_2", $CloudServices, $config, $NVMe_available);
					?>

					<h4><?php echo L::config_backup_default2_settings_header; ?></h4>

						<h5><?php echo L::config_backup_move_files_header; ?></h5>
							<input type="checkbox" id="conf_BACKUP_DEFAULT2_MOVE_FILES" name="conf_BACKUP_DEFAULT2_MOVE_FILES" <?php echo $config['conf_BACKUP_DEFAULT2_MOVE_FILES']=="1"?"checked":""; ?>>
							<label for="conf_BACKUP_DEFAULT2_MOVE_FILES"><?php echo L::config_backup_move_files_label; ?></label><br />


				<h3><?php echo L::config_backup_target_requirements_header; ?></h3>
					<label for="conf_BACKUP_TARGET_SIZE_MIN"><?php echo L::config_backup_target_requirements_label; ?></label><br />
					<select name="conf_BACKUP_TARGET_SIZE_MIN" id="conf_BACKUP_TARGET_SIZE_MIN">
						<?php
							$target_size_options	= array(
								array(
									'label'	=> l::config_backup_target_requirements_size_ignore,
									'size'	=> 0
								),
								array(
									'label'	=> '100 MB',
									'size'	=> 100 * pow (1024,2)
								),
								array(
									'label'	=> '512 MB',
									'size'	=> 512 * pow (1024,2)
								),
								array(
									'label'	=> '1 GB',
									'size'	=> 1 * pow (1024,3)
								),
								array(
									'label'	=> '512 GB',
									'size'	=> 512 * pow (1024,3)
								),
								array(
									'label'	=> '1 TB',
									'size'	=> 1 * pow (1024,4)
								),
							);
							foreach($target_size_options as $target_size_option) {
								echo('<option value="' . $target_size_option['size'] . '" ' . ($config["conf_BACKUP_TARGET_SIZE_MIN"]==$target_size_option['size'] ?" selected":"") . '>' . $target_size_option['label'] . '</option>');
							}
						?>
					</select>

				<h3><?php echo L::config_backup_camera_folder_mask_header; ?></h3>
					<label for="conf_BACKUP_CAMERA_FOLDER_MASK"><?php echo L::config_backup_camera_folder_mask_label; ?></label><br />
					<textarea <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_BACKUP_CAMERA_FOLDER_MASK" name="conf_BACKUP_CAMERA_FOLDER_MASK" rows="8" style="width: 100%;"><?php echo str_replace(';', "\n", $config['conf_BACKUP_CAMERA_FOLDER_MASK']); ?></textarea>

				<h3><?php echo L::config_backup_general_settings_header; ?></h3>
					<input type="checkbox" id="conf_BACKUP_MOVE_FILES" name="conf_BACKUP_MOVE_FILES" <?php echo $config['conf_BACKUP_MOVE_FILES']=="1"?"checked":""; ?>>
					<label for="conf_BACKUP_MOVE_FILES"><?php echo L::config_backup_move_files_label; ?></label><br />
					<br />
					<input type="checkbox" id="conf_BACKUP_RENAME_FILES" name="conf_BACKUP_RENAME_FILES" <?php echo $config['conf_BACKUP_RENAME_FILES']=="1"?"checked":""; ?>>
					<label for="conf_BACKUP_RENAME_FILES"><?php echo L::config_backup_rename_header; ?><br /><?php echo L::config_backup_rename_label.'<br />'.L::config_backup_rename_warning; ?></label><br />
					<br />
					<input type="checkbox" id="conf_BACKUP_CHECKSUM" name="conf_BACKUP_CHECKSUM" <?php echo $config['conf_BACKUP_CHECKSUM']=="1"?"checked":""; ?>>
					<label for="conf_BACKUP_CHECKSUM"><?php echo L::config_backup_checksum_header; ?><br /><?php echo L::config_backup_checksum_label.'<br />'.L::config_backup_checksum_warning; ?></label><br />
					<br />
					<input type="checkbox" id="conf_POWER_OFF" name="conf_POWER_OFF" <?php echo $config['conf_POWER_OFF']=="1"?"checked":""; ?>>
					<label for="conf_POWER_OFF"><?php echo L::config_backup_power_off_label; ?></label><br />

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_energy_section; ?></summary>

				<h3><?php echo L::config_energy_power_off_idle_time_header; ?></h3>
					<label for="conf_POWER_OFF_IDLE_TIME"><?php echo L::config_energy_power_off_idle_time_label; ?></label><br />
					<select name="conf_POWER_OFF_IDLE_TIME" id="conf_POWER_OFF_IDLE_TIME">
						<?php
							$IDLE_TIME_OPTIONS=array(0,2,5,10,15,20,30);
							foreach($IDLE_TIME_OPTIONS as $IDLE_TIME_OPTION) {
								echo "<option value=\"" . $IDLE_TIME_OPTION ."\"". ($config["conf_POWER_OFF_IDLE_TIME"]==$IDLE_TIME_OPTION?" selected":"") . ">" . ($IDLE_TIME_OPTION=="0"?L::config_energy_power_off_idle_time_none:$IDLE_TIME_OPTION . " " . L::units_minutes_long) ."</option>";
							}
						?>

					</select>

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_view_section; ?></summary>

				<h3><?php echo L::config_view_theme_header; ?></h3>
					<label for="conf_THEME"><?php echo L::config_view_theme_label; ?></label><br />
						<select name="conf_THEME" id="conf_THEME">
						<option value="light" <?php echo $config["conf_THEME"] == "light"?" selected":""; ?>><?php echo L::config_view_theme_light; ?></option>
						<option value="dark" <?php echo $config["conf_THEME"] == "dark"?" selected":""; ?>><?php echo L::config_view_theme_dark; ?></option>
						<option value="sepia" <?php echo $config["conf_THEME"] == "sepia"?" selected":""; ?>><?php echo L::config_view_theme_sepia; ?></option>
					</select>

				<h3><?php echo L::config_view_bg_image_header; ?></h3>
					<label for="conf_BACKGROUND_IMAGE"><?php echo L::config_view_bg_image_label; ?> &quot;<?php echo $constants["const_MEDIA_DIR"] . '/' . $constants['const_BACKGROUND_IMAGES_DIR'] ;?>&quot;.</label><br />
						<select name="conf_BACKGROUND_IMAGE" id="conf_BACKGROUND_IMAGE">
							<option value="" <?php echo $config["conf_BACKGROUND_IMAGE"] ==""?" selected":""; ?>>none</option>
							<?php
								$bg_images=array();
								exec ("find '" . $constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . "' -type f -exec file --mime-type {} \+ | awk -F: '{if ($2 ~/image\//) print $1}'",$bg_images);
								foreach($bg_images as $bg_image) {
									$bg_image = str_replace($constants["const_MEDIA_DIR"] . '/' . $constants["const_BACKGROUND_IMAGES_DIR"] . '/','',$bg_image);
									echo "<option value='" . $bg_image . "' " . ($config["conf_BACKGROUND_IMAGE"] == $bg_image?" selected":"") . ">" . $bg_image . "</option>";
								}
							?>
						</select>

				<h3><?php echo L::config_view_popup_header; ?></h3>
					<input type="checkbox" id="conf_POPUP_MESSAGES" name="conf_POPUP_MESSAGES" <?php echo $config['conf_POPUP_MESSAGES']=="1"?"checked":""; ?>>
					<label for="conf_POPUP_MESSAGES"><?php echo L::config_view_popup_label; ?></label><br />

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_display_section; ?></summary>

				<h3><?php echo L::config_display_behavior_header; ?></h3>
					<div>
						<input type="checkbox" id="conf_DISP" name="conf_DISP" <?php echo $config['conf_DISP']=="1"?"checked":""; ?>>
						<label for="conf_DISP"><?php echo L::config_display_activate_label; ?></label><br />
					</div>

					<div>
						<label for="conf_DISP_FONT_SIZE"><?php echo L::config_display_font_size_label; ?></label><br />
							<select name="conf_DISP_FONT_SIZE" id="conf_DISP_FONT_SIZE">
								<?php
									$display_font_sizes_array=array(10,12,14,16);
									foreach($display_font_sizes_array as $display_font_size) {
										echo "<option value='" . $display_font_size . "' " . ($config["conf_DISP_FONT_SIZE"] == $display_font_size?" selected":"") . ">" . $display_font_size . "</option>";
									}
								?>
							</select>
					</div>

					<div>
						<label for="conf_DISP_FRAME_TIME"><?php echo L::config_display_frame_time_label; ?></label><br />
							<select name="conf_DISP_FRAME_TIME" id="conf_DISP_FRAME_TIME">
								<?php
									$display_frame_times_array=array('0.1', '0.25', '0.5', '1', '1.5', '2', '2.5', '3');
									foreach($display_frame_times_array as $display_frame_time) {
										echo "<option value='" . $display_frame_time . "' " . ($config["conf_DISP_FRAME_TIME"] == $display_frame_time?" selected":"") . ">" . $display_frame_time . "</option>";
									}
								?>
							</select>
					</div>

					<div>
						<label for="conf_DISP_CONTRAST"><?php echo L::config_display_contrast_label; ?></label><br />
						<input type="range" id="conf_DISP_CONTRAST" name="conf_DISP_CONTRAST" style="width: 50%;" min="1" max="255" step="1" value="<?php echo $config['conf_DISP_CONTRAST']; ?>">
					</div>

					<div>
						<input type="checkbox" id="conf_DISP_IP_REPEAT" name="conf_DISP_IP_REPEAT" <?php echo $config['conf_DISP_IP_REPEAT']=="1"?"checked":""; ?>>
						<label for="conf_DISP_IP_REPEAT"><?php echo L::config_display_ip_label; ?></label><br />
						<br />
						<input type="checkbox" id="conf_DISP_SHOW_STATUSBAR" name="conf_DISP_SHOW_STATUSBAR" <?php echo $config['conf_DISP_SHOW_STATUSBAR']=="1"?"checked":""; ?>>
						<label for="conf_DISP_SHOW_STATUSBAR"><?php echo L::config_display_statusbar_label; ?></label><br />
						<br />
						<label for="conf_DISP_FRAME_TIME_IP"><?php echo L::config_display_frame_time_ip_label; ?></label><br />
							<select name="conf_DISP_FRAME_TIME_IP" id="conf_DISP_FRAME_TIME_IP">
								<?php
									$display_frame_times_ip_array=array('1', '1.5', '2', '2.5', '3', '4', '5');
									foreach($display_frame_times_ip_array as $display_frame_time_ip) {
										echo "<option value='" . $display_frame_time_ip . "' " . ($config["conf_DISP_FRAME_TIME_IP"] == $display_frame_time_ip?" selected":"") . ">" . $display_frame_time_ip . "</option>";
									}
								?>
							</select>
					</div>

					<div>
						<h4><?php echo L::config_display_colors_header; ?></h4>

						<?php $display_colors_array=array('blue','green','red','white','yellow','orange','lightgrey','grey','black'); ?>

							<label for="conf_DISP_COLOR_TEXT"><?php echo L::config_display_color_text_label; ?></label><br />
								<select name="conf_DISP_COLOR_TEXT" id="conf_DISP_COLOR_TEXT">
									<?php
										foreach($display_colors_array as $display_color) {
											$display_color_entity="config_display_color_" . $display_color;
											echo "<option value='" . $display_color . "' " . ($config["conf_DISP_COLOR_TEXT"] == $display_color?" selected":"") . ">" . L::{"$display_color_entity"}() . "</option>";
										}
									?>
								</select>
					</div>

					<div>
						<label for="conf_DISP_COLOR_HIGH"><?php echo L::config_display_color_high_label; ?></label><br />
							<select name="conf_DISP_COLOR_HIGH" id="conf_DISP_COLOR_HIGH">
								<?php
									foreach($display_colors_array as $display_color) {
										$display_color_entity="config_display_color_" . $display_color;
										echo "<option value='" . $display_color . "' " . ($config["conf_DISP_COLOR_HIGH"] == $display_color?" selected":"") . ">" . L::{"$display_color_entity"}() . "</option>";
									}
								?>
							</select>
					</div>

					<div>
						<label for="conf_DISP_COLOR_ALERT"><?php echo L::config_display_color_alert_label; ?></label><br />
							<select name="conf_DISP_COLOR_ALERT" id="conf_DISP_COLOR_ALERT">
								<?php
									foreach($display_colors_array as $display_color) {
										$display_color_entity="config_display_color_" . $display_color;
										echo "<option value='" . $display_color . "' " . ($config["conf_DISP_COLOR_ALERT"] == $display_color?" selected":"") . ">" . L::{"$display_color_entity"}() . "</option>";
									}
								?>
							</select>
					</div>

					<div>
						<label for="conf_DISP_COLOR_BACKGROUND"><?php echo L::config_display_color_background_label; ?></label><br />
							<select name="conf_DISP_COLOR_BACKGROUND" id="conf_DISP_COLOR_BACKGROUND">
								<?php
									foreach($display_colors_array as $display_color) {
										$display_color_entity="config_display_color_" . $display_color;
										echo "<option value='" . $display_color . "' " . ($config["conf_DISP_COLOR_BACKGROUND"] == $display_color?" selected":"") . ">" . L::{"$display_color_entity"}() . "</option>";
									}
								?>
							</select>
					</div>

					<div>
						<h4><?php echo L::config_display_backlight_header; ?></h4>

							<div>
								<input type="checkbox" id="conf_DISP_BACKLIGHT_ENABLED" name="conf_DISP_BACKLIGHT_ENABLED" <?php echo $config['conf_DISP_BACKLIGHT_ENABLED']=="1"?"checked":""; ?>>
								<label for="conf_DISP_BACKLIGHT_ENABLED"><?php echo L::config_display_backlight_enabled_label; ?></label><br />
							<div>
					</div>

					<div>
						<h4><?php echo L::config_display_rotate_header; ?></h4>

						<label for="conf_DISP_ROTATE"><?php echo L::config_display_rotate_label; ?></label><br />
							<select name="conf_DISP_ROTATE" id="conf_DISP_ROTATE">
								<?php
									$display_rotate_array=array(
										'0' => '0°',
										'2' => '180°',
									);
									foreach($display_rotate_array as $display_rotate_code => $display_rotate_text) {
										echo "<option value='" . $display_rotate_code . "' " . ($config["conf_DISP_ROTATE"] == $display_rotate_code?" selected":"") . ">" . $display_rotate_text . "</option>";
									}
								?>
							</select>
					</div>

				<h3><?php echo L::config_hardware_section; ?></h3>

					<div>
						<label for="conf_DISP_DRIVER"><?php echo L::config_display_driver_label; ?></label><br />
							<select name="conf_DISP_DRIVER" id="conf_DISP_DRIVER">
								<?php
									$display_drivers_array=array(
										'none',
										'SSD1306',
										'SSD1309',
										'SSD1322',
										'SSD1331',
										'SH1106',
										'ST7735',
										'ST7735 WAVESHARE LCD display HAT'
									);
									foreach($display_drivers_array as $display_driver) {
										echo "<option value='" . $display_driver . "' " . ($config["conf_DISP_DRIVER"] == $display_driver?" selected":"") . ">" . $display_driver . "</option>";
									}
								?>
							</select>
					</div>

					<div>
						<h4><?php echo L::config_display_connection_header; ?></h4>
							<label for="conf_DISP_CONNECTION"><?php echo L::config_display_connection_label; ?></label><br />
								<select name="conf_DISP_CONNECTION" id="conf_DISP_CONNECTION">
									<?php
										$display_connections_array=array("I2C","SPI");
										foreach($display_connections_array as $display_connection) {
											echo "<option value='" . $display_connection . "' " . ($config["conf_DISP_CONNECTION"] == $display_connection?" selected":"") . ">" . $display_connection . "</option>";
										}
									?>
								</select>
					</div>

					<div>
						<h4><?php echo L::config_display_i2c_header; ?></h4>

							<label for="conf_DISP_I2C_ADDRESS"><?php echo L::config_display_i2c_address_label; ?></label><br />

							<?php
								$I2C_DETECT=shell_exec("sudo i2cdetect -y 1");

								$I2C_LIST=array("0x3c","0x3d");
								foreach($I2C_LIST as $I2C) {
									if (! in_array($config['conf_DISP_I2C_ADDRESS'], $I2C_LIST)) {
										$config['conf_DISP_I2C_ADDRESS']	= $I2C;
									}
							?>
									<input type="radio" id="conf_DISP_I2C_ADDRESS_<?php echo $I2C; ?>" name="conf_DISP_I2C_ADDRESS" value="<?php echo $I2C; ?>" <?php echo strcasecmp($config['conf_DISP_I2C_ADDRESS'],$I2C)==0?"checked":""; ?>>
									<label for="conf_DISP_I2C_ADDRESS_<?php echo $I2C; ?>"><?php echo $I2C; ?> <?php echo strpos($I2C_DETECT," " . $I2C)?" - " . L::config_display_device_available:""; ?></label><br />
							<?php
								}
							?>
					</div>

					<div>
						<h4><?php echo L::config_display_spi_header; ?></h4>
							<label for="conf_DISP_SPI_PORT"><?php echo L::config_display_spi_port_label; ?></label><br />

									<?php
										$spi_ports_array=array("0","1");
										foreach($spi_ports_array as $spi_port) {
							?>
									<input type="radio" id="conf_DISP_SPI_PORT_<?php echo $spi_port; ?>" name="conf_DISP_SPI_PORT" value="<?php echo $spi_port; ?>" <?php echo strcasecmp($config['conf_DISP_SPI_PORT'],$spi_port)==0?"checked":""; ?>>
									<label for="conf_DISP_SPI_PORT_<?php echo $spi_port; ?>"><?php echo $spi_port; ?></label><br />
							<?php
								}
							?>
					</div>

					<div>
						<h4><?php echo L::config_display_additional_settings_header; ?></h4>

						<div>
							<label for="conf_DISP_RESOLUTION_X"><?php echo L::config_display_resolution_x_label; ?></label><br />
								<select name="conf_DISP_RESOLUTION_X" id="conf_DISP_RESOLUTION_X">
									<?php
										$display_resolutions_array=array(96, 128, 160, 250);
										foreach($display_resolutions_array as $display_resolution) {
											echo "<option value='" . $display_resolution . "' " . ($config["conf_DISP_RESOLUTION_X"] == $display_resolution?" selected":"") . ">" . $display_resolution . "</option>";
										}
									?>
								</select>
						</div>

						<div>
							<label for="conf_DISP_RESOLUTION_Y"><?php echo L::config_display_resolution_y_label; ?></label><br />
								<select name="conf_DISP_RESOLUTION_Y" id="conf_DISP_RESOLUTION_Y">
									<?php
										$display_resolutions_array=array(32, 64, 80, 122, 128);
										foreach($display_resolutions_array as $display_resolution) {
											echo "<option value='" . $display_resolution . "' " . ($config["conf_DISP_RESOLUTION_Y"] == $display_resolution?" selected":"") . ">" . $display_resolution . "</option>";
										}
									?>
								</select>
						</div>

						<div>
							<label for="conf_DISP_OFFSET_X"><?php echo L::config_display_offset_x_label; ?></label><br />
								<select name="conf_DISP_OFFSET_X" id="conf_DISP_OFFSET_X">
									<?php
										for ($display_offset=-30; $display_offset<=30; $display_offset++) {
											echo "<option value='" . $display_offset . "' " . ($config["conf_DISP_OFFSET_X"] == $display_offset?" selected":"") . ">" . $display_offset . "</option>";
										}
									?>
								</select>
						</div>

						<div>
							<label for="conf_DISP_OFFSET_Y"><?php echo L::config_display_offset_y_label; ?></label><br />
								<select name="conf_DISP_OFFSET_Y" id="conf_DISP_OFFSET_Y">
									<?php
										for ($display_offset=-30; $display_offset<=30; $display_offset++) {
											echo "<option value='" . $display_offset . "' " . ($config["conf_DISP_OFFSET_Y"] == $display_offset?" selected":"") . ">" . $display_offset . "</option>";
										}
									?>
								</select>
						</div>

						<div>
							<label for="conf_DISP_COLOR_MODEL"><?php echo L::config_display_color_model_label; ?></label><br />
								<select name="conf_DISP_COLOR_MODEL" id="conf_DISP_COLOR_MODEL">
									<?php
										$display_color_models_array=array("1","RGB","RGBA");
										foreach($display_color_models_array as $display_color_model) {
											$display_color_model_entity="config_display_color_model_" . $display_color_model;
											echo "<option value='" . $display_color_model . "' " . ($config["conf_DISP_COLOR_MODEL"] == $display_color_model?" selected":"") . ">" . L::{"$display_color_model_entity"}() . "</option>";
										}
									?>
								</select>
						</div>

						<div>
							<input type="checkbox" id="conf_DISP_COLOR_BGR" name="conf_DISP_COLOR_BGR" <?php echo $config['conf_DISP_COLOR_BGR']=="1"?"checked":""; ?>>
							<label for="conf_DISP_COLOR_BGR"><?php echo L::config_display_color_bgr_label; ?></label><br />
						<div>

						<div>
							<input type="checkbox" id="conf_DISP_COLOR_INVERSE" name="conf_DISP_COLOR_INVERSE" <?php echo $config['conf_DISP_COLOR_INVERSE']=="1"?"checked":""; ?>>
							<label for="conf_DISP_COLOR_INVERSE"><?php echo L::config_display_color_inverse_label; ?></label><br />
						<div>

						<div>
							<label for="conf_DISP_BACKLIGHT_PIN"><?php echo L::config_display_backlight_pin_label; ?></label><br />
								<select name="conf_DISP_BACKLIGHT_PIN" id="conf_DISP_BACKLIGHT_PIN">
									<?php
										$display_backlight_pin_array=array(
											0	=>	'-',
											18	=> 'GPIO 18',
											24	=> 'GPIO 24'
										);
										foreach($display_backlight_pin_array as $display_backlight_pin => $display_backlight_pin_text) {
											echo "<option value='" . $display_backlight_pin . "' " . ($config["conf_DISP_BACKLIGHT_PIN"] == $display_backlight_pin?" selected":"") . ">" . $display_backlight_pin_text . "</option>";
										}
									?>
								</select>
						</div>
					</div>
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_menu_section; ?></summary>

				<h3><?php echo L::config_menu_enable_header; ?></h3>
					<div>
						<input type="checkbox" id="conf_MENU_ENABLED" name="conf_MENU_ENABLED" <?php echo $config['conf_MENU_ENABLED']=="1"?"checked":""; ?>>
						<label for="conf_MENU_ENABLED"><?php echo L::config_menu_enable_label; ?></label><br />
					</div>

				<h3><?php echo L::config_menu_rotate_header; ?></h3>
					<div>
						<label for="conf_MENU_BUTTON_ROTATE"><?php echo L::config_menu_button_rotate_label; ?></label><br />
							<select name="conf_MENU_BUTTON_ROTATE" id="conf_MENU_BUTTON_ROTATE">
								<?php
									$button_rotate_array=array(
										'2' => '0°',
										'0' => '180°',
									);
									foreach($button_rotate_array as $button_rotate_code => $button_rotate_text) {
										echo "<option value='" . $button_rotate_code . "' " . ($config["conf_MENU_BUTTON_ROTATE"] == $button_rotate_code?" selected":"") . ">" . $button_rotate_text . "</option>";
									}
								?>
							</select>
					</div>

				<h3><?php echo L::config_hardware_section; ?></h3>

					<div>
						<label for="conf_MENU_BUTTON_COMBINATION"><?php echo L::config_menu_button_combination_label.' ('.L::config_menu_section.')'; ?></label><br />

							<?php
								$button_combinations	= array();

								$button_config_files	= array(
									$WORKING_DIR.'/'.$constants['const_BUTTONS_CONFIG_FILE'],
									$constants['const_MEDIA_DIR'].'/'.$constants['const_BUTTONS_PRIVATE_CONFIG_FILE']
								);
								$BasicLines	= 0;
								foreach ($button_config_files as $button_config_file) {

									if ($f = fopen($button_config_file, "r")) {
										while(!feof($f)) {
											$Line = trim(fgets($f));
											if (! empty($Line)) {
												if (substr($Line,0,1) != '#') {
													$CombinationArray	= array(
														'up' 		=> '',
														'down'		=> '',
														'left' 		=> '',
														'right'		=> '',
														'comment'	=> ''
													);

													$explodeLine	= explode(':',$Line,2);

													if (count($explodeLine) > 1) {
														$CombinationArray['comment']	= $explodeLine[1];
													}

													$PIN_Defs		= explode(',',$explodeLine[0]);

													foreach($PIN_Defs as $PIN_Def) {
														$explodePinDef	= explode('=', $PIN_Def);
														if (count($explodePinDef) == 2) {
															if (in_array($explodePinDef[1],array('up','down','left','right','comment'))) {
																if (! empty($CombinationArray[$explodePinDef[1]])) {
																	$CombinationArray[$explodePinDef[1]]	.= ', ';
																}
																$CombinationArray[$explodePinDef[1]]	.= $explodePinDef[0];
															}
														}
													}

													array_push($button_combinations,$CombinationArray);
													if ($button_config_file == $button_config_files[0]) {
														$BasicLines	+= 1;
													}
												}
											}
										}
										fclose($f);
									}
								}

							?>
							<select name="conf_MENU_BUTTON_COMBINATION" id="conf_MENU_BUTTON_COMBINATION">
								<?php
									foreach($button_combinations as $variant => $button_combination) {
										if ($variant < $BasicLines) {
											$VariantOption	= $variant + 1;
										} else {
											$VariantOption	= 'c' . ($variant - $BasicLines + 1);
										}
										echo ("<option value='" . $VariantOption . "' " . ($config["conf_MENU_BUTTON_COMBINATION"] == $VariantOption?" selected":"") . ">" . $VariantOption . "</option>");
									}
								?>
							</select>

							<table style="width: 100%;">
								<tr style="border: thin solid;">
									<th style="width: 20%;">
										<?php echo L::config_menu_button_combination_variant; ?>
									</th>
									<th style="width: 20%;">
										<?php echo L::config_menu_button_up; ?>
									</th>
									<th>
										<?php echo L::config_menu_button_down; ?>
									</th>
									<th style="width: 20%;">
										<?php echo L::config_menu_button_left; ?>
									</th>
									<th style="width: 20%;">
										<?php echo L::config_menu_button_right; ?>
									</th>
									<th style="width: 20%;">
										<?php echo L::config_menu_button_comment; ?>
									</th>
								</tr>

								<?php
									foreach($button_combinations as $variant => $button_combination) {

										if ($variant < $BasicLines) {
											$openerTAG		= '';
											$closerTAG		= '';
											$VariantOption	= $variant + 1;
										} else {
											$openerTAG		= '<i>';
											$closerTAG		= '</i>';
											$VariantOption	= 'c' . ($variant - $BasicLines + 1);
										}
										echo("<tr style=\"border: thin solid; vertical-align: top;\"><td><b>".$openerTAG.$VariantOption.$closerTAG."</b></td>");

										foreach($button_combination as $column => $FIELD) {
											?>
												<td style="border: thin solid; vertical-align: top;">
													<?php echo ($openerTAG.$FIELD.$closerTAG); ?>
												</td>
											<?php
										}
										echo("</tr>");

									}
								?>
							</table>
							<a href="/files/index.php?p=&edit=buttons.private.cfg"><?php echo L::config_menu_button_edit_custom_configfile; ?></a>
					</div>

					<div>
						<label for="conf_MENU_BUTTON_BOUNCETIME"><?php echo L::config_menu_button_bouncetime_label; ?></label><br />
							<select name="conf_MENU_BUTTON_BOUNCETIME" id="conf_MENU_BUTTON_BOUNCETIME">
								<?php
									$button_bouncetimes_array=array(5, 10, 15, 20, 25, 50, 100, 200, 300, 400, 500);
									foreach($button_bouncetimes_array as $button_bouncetime) {
										echo "<option value='" . $button_bouncetime . "' " . ($config["conf_MENU_BUTTON_BOUNCETIME"] == $button_bouncetime?" selected":"") . ">" . $button_bouncetime . "</option>";
									}
								?>
							</select><br />

						<label for="conf_MENU_BUTTON_EDGE_DETECTION"><?php echo L::config_menu_button_edge_detection_label; ?></label><br />
							<select name="conf_MENU_BUTTON_EDGE_DETECTION" id="conf_MENU_BUTTON_EDGE_DETECTION">
								<?php
									$button_edge_detections_array=array('RISING','FALLING');
									foreach($button_edge_detections_array as $button_edge_detection) {
										echo "<option value='" . $button_edge_detection . "' " . ($config["conf_MENU_BUTTON_EDGE_DETECTION"] == $button_edge_detection?" selected":"") . ">" . $button_edge_detection . "</option>";
									}
								?>
							</select><br />

						<label for="conf_MENU_BUTTON_RESISTOR_PULL"><?php echo L::config_menu_button_resistor_pull_label; ?></label><br />
							<select name="conf_MENU_BUTTON_RESISTOR_PULL" id="conf_MENU_BUTTON_RESISTOR_PULL">
								<?php
									$button_resistor_pulls_array=array('DOWN','UP');
									foreach($button_resistor_pulls_array as $button_resistor_pull) {
										echo "<option value='" . $button_resistor_pull . "' " . ($config["conf_MENU_BUTTON_RESISTOR_PULL"] == $button_resistor_pull?" selected":"") . ">" . $button_resistor_pull . "</option>";
									}
								?>
							</select>
					</div>

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_screen_section; ?></summary>
				<p>
					<?php echo L::config_screen_description; ?>
				</p>
				<h3><?php echo L::config_screen_virtual_keyboard_enable_header; ?></h3>
					<div>
						<input type="checkbox" id="conf_VIRTUAL_KEYBOARD_ENABLED" name="conf_VIRTUAL_KEYBOARD_ENABLED" <?php echo $config['conf_VIRTUAL_KEYBOARD_ENABLED']=="1"?"checked":""; ?>>
						<label for="conf_VIRTUAL_KEYBOARD_ENABLED"><?php echo L::config_screen_virtual_keyboard_enable_label; ?></label><br />
					</div>

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_imageviewer_section; ?></summary>

				<h3><?php echo L::config_backup_generate_thumbnails_header; ?></h3>
					<input type="checkbox" id="conf_BACKUP_GENERATE_THUMBNAILS" name="conf_BACKUP_GENERATE_THUMBNAILS" <?php echo $config['conf_BACKUP_GENERATE_THUMBNAILS']=="1"?"checked":""; ?>>
					<label for="conf_BACKUP_GENERATE_THUMBNAILS"><?php echo L::config_backup_generate_thumbnails_label; ?></label><br />

				<h3><?php echo L::config_imageviewer_convert_heic_header; ?></h3>
					<input type="checkbox" id="conf_VIEW_CONVERT_HEIC" name="conf_VIEW_CONVERT_HEIC" <?php echo $config['conf_VIEW_CONVERT_HEIC']=="1"?"checked":""; ?>>
					<label for="conf_VIEW_CONVERT_HEIC"><?php echo L::config_imageviewer_convert_heic_label; ?></label><br />

				<h3><?php echo L::config_backup_update_exif_header; ?></h3>
					<input type="checkbox" id="conf_BACKUP_UPDATE_EXIF" name="conf_BACKUP_UPDATE_EXIF" <?php echo $config['conf_BACKUP_UPDATE_EXIF']=="1"?"checked":""; ?>>
					<label for="conf_BACKUP_UPDATE_EXIF"><?php echo L::config_backup_update_exif_label; ?></label><br />

				<h3><?php echo L::config_imageviewer_write_rating_exif_header; ?></h3>
					<input type="checkbox" id="conf_VIEW_WRITE_RATING_EXIF" name="conf_VIEW_WRITE_RATING_EXIF" <?php echo $config['conf_VIEW_WRITE_RATING_EXIF']=="1"?"checked":""; ?>>
					<label for="conf_VIEW_WRITE_RATING_EXIF"><?php echo L::config_imageviewer_write_rating_exif_label; ?></label><br />

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_mail_section; ?></summary>

				<h3><?php echo L::config_mail_notify_header; ?></h3>
					<input type="checkbox" id="conf_MAIL_NOTIFICATIONS" name="conf_MAIL_NOTIFICATIONS"<?php echo $config['conf_MAIL_NOTIFICATIONS']=="1"?"checked":""; ?>>
					<label for="conf_MAIL_NOTIFICATIONS"><?php echo L::config_mail_notify_backup_label; ?></label><br />

					<input type="checkbox" id="conf_MAIL_IP" name="conf_MAIL_IP"<?php echo $config['conf_MAIL_IP']=="1"?"checked":""; ?>>
					<label for="conf_MAIL_IP"><?php echo L::config_mail_notify_ip_label; ?></label><br />

				<h3><?php echo L::config_mail_html_header; ?></h3>
					<input type="checkbox" id="conf_MAIL_HTML" name="conf_MAIL_HTML"<?php echo $config['conf_MAIL_HTML']=="1"?"checked":""; ?>>
					<label for="conf_MAIL_HTML"><?php echo L::config_mail_html_label; ?></label><br />

				<h3><?php echo L::config_mail_smtp_header; ?></h3>
					<label for="conf_SMTP_SERVER"><?php echo L::config_mail_smtp_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_SMTP_SERVER" name="conf_SMTP_SERVER" size="6" value="<?php echo $config['conf_SMTP_SERVER']; ?>">

				<h3><?php echo L::config_mail_port_header; ?></h3>
					<label for="conf_SMTP_PORT"><?php echo L::config_mail_port_label . " " . $config_standard["conf_SMTP_PORT"]; ?>)</label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','numpad','bottom'); ?> id="conf_SMTP_PORT" name="conf_SMTP_PORT" size="20" value="<?php echo $config['conf_SMTP_PORT']; ?>">

				<h3><?php echo L::config_mail_security_header; ?></h3>
					<input type="radio" id="conf_MAIL_SECURITY_STARTTLS" name="conf_MAIL_SECURITY" value="STARTTLS" <?php echo $config['conf_MAIL_SECURITY']!=="SSL"?"checked":""; ?>>
					<label for="conf_MAIL_SECURITY_STARTTLS">STARTTLS</label><br />

					<input type="radio" id="conf_MAIL_SECURITY_SSL" name="conf_MAIL_SECURITY" value="SSL" <?php echo $config['conf_MAIL_SECURITY']=="SSL"?"checked":""; ?>>
					<label for="conf_MAIL_SECURITY_SSL">SSL</label><br />

				<h3><?php echo L::config_mail_user_header; ?></h3>
					<label for="conf_MAIL_USER"><?php echo L::config_mail_user_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_MAIL_USER" name="conf_MAIL_USER" size="20" value="<?php echo $config['conf_MAIL_USER']; ?>">

				<h3><?php echo L::config_mail_password_header; ?></h3>
					<label for="conf_MAIL_PASSWORD"><?php echo L::config_mail_password_label; ?></label><br />
					<input type="password" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_MAIL_PASSWORD" name="conf_MAIL_PASSWORD" size="20" value="<?php echo base64_decode($config['conf_MAIL_PASSWORD']); ?>">

				<h3><?php echo L::config_mail_sender_header; ?></h3>
					<label for="conf_MAIL_FROM"><?php echo L::config_mail_sender_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_MAIL_FROM" name="conf_MAIL_FROM" size="20" value="<?php echo $config['conf_MAIL_FROM']; ?>">

				<h3><?php echo L::config_mail_recipient_header; ?></h3>
					<label for="conf_MAIL_TO"><?php echo L::config_mail_recipient_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_MAIL_TO" name="conf_MAIL_TO" size="20" value="<?php echo $config['conf_MAIL_TO']; ?>">

				<h3><?php echo L::config_mail_timeout_header; ?></h3>
					<label for="conf_MAIL_TIMEOUT_SEC"><?php echo L::config_mail_timeout_label; ?></label><br />
					<select name="conf_MAIL_TIMEOUT_SEC" id="conf_MAIL_TIMEOUT_SEC">
						<?php
							$mail_timeouts	= array(5,10,20,30,40,50,60,90,120,300,600);
							foreach($mail_timeouts as $mail_timeout) {
								echo "<option value='" . $mail_timeout . "' " . ($config["conf_MAIL_TIMEOUT_SEC"] == $mail_timeout?" selected":"") . ">" . $mail_timeout . ' ' . L::seconds_short . "</option>";
							}
						?>
					</select>

				<h3><?php echo L::config_mail_testmail_header; ?></h3>
					<input type="checkbox" id="send_testmail" name="send_testmail">
					<label for="send_testmail"><?php echo L::config_mail_testmail_label; ?></label><br />
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_rsync_section; ?></summary>

				<h3><?php echo L::config_rsync_server_header; ?></h3>
					<label for="conf_RSYNC_SERVER"><?php echo L::config_rsync_server_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_RSYNC_SERVER" name="conf_RSYNC_SERVER" size="6" value="<?php echo $config['conf_RSYNC_SERVER']; ?>">

				<h3><?php echo L::config_rsync_port_header; ?></h3>
					<label for="conf_RSYNC_PORT"><?php echo L::config_rsync_port_label . " " . $config_standard['conf_RSYNC_PORT']; ?>)</label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','numpad','bottom'); ?> id="conf_RSYNC_PORT" name="conf_RSYNC_PORT" size="20" value="<?php echo $config['conf_RSYNC_PORT']; ?>">

				<h3><?php echo L::config_rsync_user_header; ?></h3>
					<label for="conf_RSYNC_USER"><?php echo L::config_rsync_user_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_RSYNC_USER" name="conf_RSYNC_USER" size="20" value="<?php echo $config['conf_RSYNC_USER']; ?>">

				<h3><?php echo L::config_rsync_password_header; ?></h3>
					<label for="conf_RSYNC_PASSWORD"><?php echo L::config_rsync_password_label; ?></label><br />
					<input type="password" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_RSYNC_PASSWORD" name="conf_RSYNC_PASSWORD" size="20" value="<?php echo base64_decode($config['conf_RSYNC_PASSWORD']); ?>">

				<h3><?php echo L::config_rsync_module_header; ?></h3>
					<label for="conf_RSYNC_SERVER_MODULE"><?php echo L::config_rsync_module_label1 .  $config_standard['conf_RSYNC_SERVER_MODULE'] . L::config_rsync_module_label2; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_RSYNC_SERVER_MODULE" name="conf_RSYNC_SERVER_MODULE" size="20" value="<?php echo $config['conf_RSYNC_SERVER_MODULE']; ?>">
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_cloud_section; ?></summary>

				<h3><?php echo L::config_backup_cloud_target_basedir_header; ?></h3>
					<?php
						echo '<p>' . L::config_backup_cloud_target_basedir_label . '</p>';

					$CloudBaseDirsRAW	= explode('|;|', $config['conf_BACKUP_CLOUDS_TARGET_BASEDIR']);
					$CloudBaseDirs	= array();
					foreach($CloudServices as $CloudService) {
						$CloudBaseDirs[$CloudService]	= '';
					}
					foreach($CloudBaseDirsRAW as $BaseDir) {
						list($CloudService, $CloudBaseDir) = explode('|=|', $BaseDir);
						$CloudBaseDirs[$CloudService]	= $CloudBaseDir;
					}

					foreach($CloudServices as $CloudService) { ?>
							<label for="conf_BACKUP_CLOUDS_TARGET_BASEDIR_"><?php echo $CloudService; ?>:</label><br />
							<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_BACKUP_CLOUDS_TARGET_BASEDIR_<?php echo ($CloudService);?>" name="conf_BACKUP_CLOUDS_TARGET_BASEDIR_<?php echo ($CloudService);?>" size="6" value="<?php echo $CloudBaseDirs[$CloudService]; ?>">
					<?php } ?>

					<h3><?php echo L::config_backup_cloud_sync_parameter_header; ?></h3>

						<h4><?php echo L::config_backup_cloud_sync_method_header; ?></h4>
							<p><?php echo L::config_backup_cloud_sync_method_label; ?></p>

						<h4><?php echo L::config_backup_cloud_files_stay_in_place_header; ?></h4>
							<p><?php echo L::config_backup_cloud_files_stay_in_place_label; ?> </p>

						<?php
							$CloudSyncMethodsRAW	= explode('|;|', $config['conf_BACKUP_SYNC_METHOD_CLOUDS']);
							$CloudSyncMethods	= array();
							foreach($CloudServices as $CloudService) {
								$CloudSyncMethods[$CloudService]	= '';
							}
							foreach($CloudSyncMethodsRAW as $SyncMethod) {
								list($CloudService, $CloudSyncMethod) = explode('|=|', $SyncMethod);
								$CloudSyncMethods[$CloudService]	= $CloudSyncMethod;
							}

							$CloudFilesStayInPlacesRAW	= explode('|;|', $config['conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE']);
							$CloudFilesStayInPlaces	= array();
							foreach($CloudServices as $CloudService) {
								$CloudFilesStayInPlaces[$CloudService]	= True;
							}
							foreach($CloudFilesStayInPlacesRAW as $CloudFilesStayInPlaceRAW) {
								list($CloudService, $CloudFilesStayInPlace) = explode('|=|', $CloudFilesStayInPlaceRAW);
								$CloudFilesStayInPlaces[$CloudService]	= $CloudFilesStayInPlace == 'true';
							}

						?>
						<table>
							<tr>
								<th style="padding-left: 30px; text-align: center"></th>
								<th style="padding-left: 30px; text-align: center" colspan=2><?php echo L::config_backup_cloud_sync_method_header; ?></th>
								<th style="padding-left: 30px; text-align: center"><?php echo L::config_backup_cloud_files_stay_in_place_header; ?></th>
							</tr>
							<tr>
								<th></th>
								<th style="padding-left: 30px; text-align: center">rclone</th>
								<th style="padding-left: 30px; text-align: center">rsync</th>
								<th style="padding-left: 30px; text-align: center"></th>
							</tr>
						<?php
						foreach($CloudServices as $CloudService) { ?>
							<tr>
								<th>
									<label for="conf_BACKUP_SYNC_METHOD_CLOUDS_"><?php echo $CloudService; ?>:</label>
								</th>

								<td style="padding-left: 30px; text-align: center">
									<input type="radio" id="conf_BACKUP_SYNC_METHOD_CLOUDS_<?php echo ($CloudService);?>" name="conf_BACKUP_SYNC_METHOD_CLOUDS_<?php echo ($CloudService);?>" value="rclone" <?php if ($CloudSyncMethods[$CloudService] != 'rsync') {echo 'checked';} ?>>
								</td>

								<td style="padding-left: 30px; text-align: center;">
										<input type="radio" id="conf_BACKUP_SYNC_METHOD_CLOUDS_<?php echo ($CloudService);?>" name="conf_BACKUP_SYNC_METHOD_CLOUDS_<?php echo ($CloudService);?>" value="rsync" <?php if ($CloudSyncMethods[$CloudService] == 'rsync') {echo 'checked';} ?>>
								</td>

								<td style="padding-left: 30px; text-align: center">
									<input type="checkbox" id="conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE_<?php echo ($CloudService);?>" name="conf_BACKUP_CLOUDS_TARGET_FILES_STAY_IN_PLACE_<?php echo ($CloudService);?>" <?php if ($CloudFilesStayInPlaces[$CloudService]) {echo 'checked';} ?>>
								</td>
							</tr>
						<?php } ?>
						</table>

				<h3><?php echo L::config_cloud_header; ?></h3>
					<p>
						<?php echo L::config_cloud_rclone_description ; ?><br />
						<?php
							if (empty($config['conf_PASSWORD'])) {
								echo L::config_username . ": 'lbb', " . L::config_password . ": 'lbb'";
							}
							else {
								echo L::config_username . ": 'lbb'; " . L::config_password_as_set_in . " '" . L::config_password_section . "'";
							}
						?>

					</p>
					<a href="/frame.php?page=rclone_gui" target="_blank"><?php echo L::config_cloud_rclone_gui; ?></a>

				<h3><?php echo L::config_cloud_restart_header; ?></h3>
					<input type="checkbox" id="restart_rclone_gui" name="restart_rclone_gui">
					<label for="restart_rclone_gui"><?php echo L::config_cloud_restart_label; ?></label><br />

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_social_media_section; ?></summary>

				<h3><?php echo L::config_telegram_header; ?></h3>
					<?php echo L::config_telegram_install_desc; ?><br />
					<br />
					<label for="conf_TELEGRAM_TOKEN"><?php echo L::config_telegram_token_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_TELEGRAM_TOKEN" name="conf_TELEGRAM_TOKEN" size="50" value="<?php echo $config['conf_TELEGRAM_TOKEN']; ?>"><br />

					<?php include("${WORKING_DIR}/sub-telegram-chat-id.php"); ?>

				<h3><?php echo L::config_mastodon_header; ?></h3>
					<?php echo L::config_mastodon_install_desc; ?><br />
					<br />
					<label for="conf_MASTODON_BASE_URL"><?php echo L::config_mastodon_base_url_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_MASTODON_BASE_URL" name="conf_MASTODON_BASE_URL" size="50" value="<?php echo $config['conf_MASTODON_BASE_URL']; ?>"><br />

					<label for="conf_MASTODON_TOKEN"><?php echo L::config_mastodon_token_label; ?></label><br />
					<input type="text" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_MASTODON_TOKEN" name="conf_MASTODON_TOKEN" size="50" value="<?php echo $config['conf_MASTODON_TOKEN']; ?>"><br />

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_vpn_section; ?></summary>

				<h3><?php echo L::config_vpn_type_header; ?></h3>

					<div><?php echo L::config_vpn_type_desc; ?></div>
					<br />
					<label for="conf_VPN_TYPE_RSYNC"><?php echo L::config_vpn_type_rsync_label; ?></label><br />
					<select name="conf_VPN_TYPE_RSYNC" id="conf_VPN_TYPE_RSYNC">
						<?php
							echo "<option value='none'  " . ($config["conf_VPN_TYPE_RSYNC"] == "none"?" selected":"") . ">" . L::config_vpn_type_none . "</option>";
							foreach($vpn_types as $vpn_type) {
								echo "<option value='" . $vpn_type . "' " . ($config["conf_VPN_TYPE_RSYNC"] == $vpn_type?" selected":"") . ">" . $vpn_type . "</option>";
							}
						?>
					</select><br />

					<label for="conf_VPN_TYPE_CLOUD"><?php echo L::config_vpn_type_cloud_label; ?></label><br />
					<select name="conf_VPN_TYPE_CLOUD" id="conf_VPN_TYPE_CLOUD">
						<?php
							echo "<option value='none'  " . ($config["conf_VPN_TYPE_CLOUD"] == "none"?" selected":"") . ">" . L::config_vpn_type_none . "</option>";
							foreach($vpn_types as $vpn_type) {
								echo "<option value='" . $vpn_type . "' " . ($config["conf_VPN_TYPE_CLOUD"] == $vpn_type?" selected":"") . ">" . $vpn_type . "</option>";
							}
						?>
					</select>

				<h3><?php echo L::config_vpn_timeout_header; ?></h3>

					<label for="conf_VPN_TIMEOUT"><?php echo L::config_vpn_timeout_label; ?></label><br />
					<select name="conf_VPN_TIMEOUT" id="conf_VPN_TIMEOUT">
						<?php
							$vpn_timeouts	= array(5,10,20,30,40,50,60,90,120,300,600);
							foreach($vpn_timeouts as $vpn_timeout) {
								echo "<option value='" . $vpn_timeout . "' " . ($config["conf_VPN_TIMEOUT"] == $vpn_timeout?" selected":"") . ">" . $vpn_timeout . ' ' . L::seconds_short . "</option>";
							}
						?>
					</select>

				<h3><?php echo L::config_vpn_upload_header; ?></h3>

					<label for="vpn_upload_type"><?php echo L::config_vpn_upload_type_label; ?></label>
					<select name="vpn_upload_type" id="vpn_upload_type">
						<?php
							echo "<option value='none' selected>" . L::config_vpn_upload_type_none . "</option>";
							foreach($vpn_types as $vpn_type) {
								echo "<option value='" . $vpn_type . "'>" . $vpn_type . "</option>";
							}
						?>
					</select>

					<label for="vpn_conf_file"><?php echo L::config_vpn_upload_file_label; ?></label>
					<input type="file" name="vpn_conf_file" id="vpn_conf_file">

					<?php
						$vpn_remove_section	= False;
						foreach($vpn_types as $vpn_type) {
							$vpn_file_exists[$vpn_type]	= exec('sudo -- bash -c "if [ -f \"' . $constants['const_VPN_DIR_' . $vpn_type] . '/' . $constants['const_VPN_FILENAME_' . $vpn_type] . '\" ]; then echo \"true\"; fi"')=="true";
							if ($vpn_file_exists[$vpn_type]) {$vpn_remove_section = True;}
						}
						if ($vpn_remove_section) {
								echo ('<h3>' . L::config_vpn_remove_header .'</h3>');

									foreach($vpn_types as $vpn_type) {
										if ($vpn_file_exists[$vpn_type]) {
											echo "<p>";
											echo ('<input type="checkbox" id="vpn_remove_' . $vpn_type . '" name="vpn_remove_' . $vpn_type . '">&nbsp;');
											echo ('<label for="vpn_remove_' . $vpn_type . '">' . $vpn_type . '</label><br />');
											echo "</p>";
										}
									}
						}
					?>

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_wifi_section; ?></summary>

				<h3><?php echo L::config_wifi_country_header; ?></h3>
					<label for="conf_WIFI_COUNTRY"><?php echo L::config_wifi_country_label; ?></label><br />
					<?php echo get_wifi_country_selector("conf_WIFI_COUNTRY","conf_WIFI_COUNTRY"); ?>
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_hardware_section; ?></summary>

				<h3><?php echo L::config_hardware_fan_header; ?></h3>
					<div>
					<label for="conf_FAN_PWM_TEMP_C"><?php echo L::config_hardware_fan_temp_label; ?></label><br />
						<select name="conf_FAN_PWM_TEMP_C" id="conf_FAN_PWM_TEMP_C">
							<?php
								$conf_FAN_PWM_TEMP_Cs=array(0,60,65,70,75,80);# allowed 60..120 °C
								foreach($conf_FAN_PWM_TEMP_Cs as $conf_FAN_PWM_TEMP_C) {
									echo "<option value=\"" . $conf_FAN_PWM_TEMP_C ."\"". ($config["conf_FAN_PWM_TEMP_C"]==$conf_FAN_PWM_TEMP_C?" selected":"") . ">" . ($conf_FAN_PWM_TEMP_C=="0"?L::config_hardware_fan_always_on:$conf_FAN_PWM_TEMP_C . " " . L::units_celsius) . "</option>";
								}
							?>
						</select>
					</div>

					<div>
						<label for="conf_FAN_PWM_GPIO"><?php echo L::config_hardware_fan_gpio_label; ?></label><br />
						<select name="conf_FAN_PWM_GPIO" id="conf_FAN_PWM_GPIO">
							<?php
								$conf_FAN_PWM_GPIOs=array('-','14','15','18','23','24'); # allowed 2..27
								foreach($conf_FAN_PWM_GPIOs as $conf_FAN_PWM_GPIO) {
									echo "<option value=\"" . $conf_FAN_PWM_GPIO ."\"". ($config["conf_FAN_PWM_GPIO"]==$conf_FAN_PWM_GPIO?" selected":"") . ">" . $conf_FAN_PWM_GPIO . "</option>";
								}
							?>
						</select>
					</div>
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_password_section; ?></summary>

				<h3><?php echo L::config_password_header; ?></h3>
					<input type="hidden" id="conf_PASSWORD_OLD" name="conf_PASSWORD_OLD" value="<?php echo $config['conf_PASSWORD']; ?>">
					<input type="hidden" id="conf_PASSWORD_ENCRYPTION" name="conf_PASSWORD_ENCRYPTION" value="<?php echo $config['conf_PASSWORD_ENCRYPTION']; ?>">
					<label for="conf_PASSWORD_1"><p><?php echo L::config_password_global_lbb_label . '</p><p style="text-decoration: underline;">' . L::config_password_global_wifi_label . '</p><p><b>' . L::config_alert_password_characters_not_allowed . '</b>'; ?></label></p>
					<input type="password" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_PASSWORD_1" name="conf_PASSWORD_1" size="20" value="">
					<label for="conf_PASSWORD_2"><?php echo L::config_password_repeat_label; ?></label><br />
					<input type="password" <?php echo virtual_keyboard_options($config["conf_VIRTUAL_KEYBOARD_ENABLED"],'','all','bottom','true'); ?> id="conf_PASSWORD_2" name="conf_PASSWORD_2" size="20" value="">

					<?php
						if ($config['conf_PASSWORD'] != "") {
							echo "<h3>" . L::config_password_remove_header . "</h3>";
							echo "<p>";
							echo "<input type=\"checkbox\" id=\"conf_PASSWORD_REMOVE\" name=\"conf_PASSWORD_REMOVE\">&nbsp;";
							echo "<label for=\"conf_PASSWORD_REMOVE\">" . L::config_password_remove_label ."</label><br />";
							echo "</p>";
						}
					?>
			</details>
		</div>

<!-- 		debug -->
		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_debug_section; ?></summary>

				<h3><?php echo L::config_debug_loglevel_header; ?></h3>
					<p><?php echo L::config_debug_loglevel_text . " " . $constants["const_LOGFILE"]; ?>)</p>
					<label for="conf_LOGLEVEL"><?php echo L::config_debug_loglevel_label; ?></label><br />
					<select name="conf_LOGLEVEL" id="conf_LOGLEVEL">
						<option value="1" <?php echo $config["conf_LOGLEVEL"]=="1"?" selected":""; ?>>1, <?php echo L::config_debug_loglevel_minimum; ?></option>
						<option value="2" <?php echo $config["conf_LOGLEVEL"]=="2"?" selected":""; ?>>2, <?php echo L::config_debug_loglevel_medium; ?></option>
						<option value="3" <?php echo $config["conf_LOGLEVEL"]=="3"?" selected":""; ?>>3, <?php echo L::config_debug_loglevel_maximum; ?></option>
					</select>

				<h3><?php echo L::config_debug_log_sync_protokoll_header; ?></h3>
					<input type="checkbox" id="conf_LOG_SYNC" name="conf_LOG_SYNC"<?php echo $config['conf_LOG_SYNC']=="1"?"checked":""; ?>>
					<label for="conf_LOG_SYNC"><?php echo L::config_debug_log_sync_protokoll_label; ?></label><br />

				<h3><?php echo L::config_debug_display_images_keep_header; ?></h3>
					<input type="checkbox" id="conf_DIPLAY_IMAGES_KEEP" name="conf_DIPLAY_IMAGES_KEEP"<?php echo $config['conf_DIPLAY_IMAGES_KEEP']=="1"?"checked":""; ?>>
					<label for="conf_DIPLAY_IMAGES_KEEP"><?php echo L::config_debug_display_images_keep_label; ?> (<?php echo $constants['const_DISPLAY_IMAGE_KEEP_PATH']; ?>)</label><br />

			</details>
		</div>

<!-- 		save -->
		<div class="card" style="margin-top: 2em; position: sticky; bottom: 0; z-index: 10; background-color: var(--cbg)">
            <?php echo '<button style="margin-top: 2em" type="submit" name="save">' . L::config_save_button . '</button>'; ?>
		</div>

	</form>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_save_settings_section; ?></summary>

				<h3><?php echo L::config_save_settings_download_header; ?></h3>
					<p><?php echo L::config_save_settings_download_text; ?></p>
					<a href="download-settings.php"><?php echo L::config_save_settings_download_link_text; ?></a>

				<h3><?php echo L::config_save_settings_upload_header; ?></h3>
					<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST" enctype="multipart/form-data">
						<label for="settings_file"><?php echo L::config_save_settings_upload_label; ?></label>
						<input type="file" name="settings_file" id="settings_file">
						<button style="margin-top: 2em;" type="submit" name="upload_settings"><?php echo L::config_save_settings_upload_button; ?></button>
					</form>

			</details>
		</div>

		<?php if (shell_exec('which comitup-cli') != '') { ?>
			<div class="card" style="margin-top: 2em;">
				<details>
					<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_comitup_section; ?></summary>
					<?php echo L::config_comitup_text; ?><br />
					<br />
					<a href='/cmd.php?CMD=comitup_reset'><?php echo L::config_comitup_linktext ?></a>
				</details>
			</div>
		<?php } ?>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_update_section; ?></summary>
				<?php echo L::config_update_text; ?>
<!-- 				<ul> -->
<!-- 					<li> -->
						<?php echo ((isset($constants['const_SOFTWARE_BRANCH']) and $constants['const_SOFTWARE_BRANCH'] == 'main') ? '<b>' : ''); ?>
							<button onclick="window.location.href='/cmd.php?CMD=update';"><?php echo L::config_update_linktext ?></button>
						<?php echo ((isset($constants['const_SOFTWARE_BRANCH']) and $constants['const_SOFTWARE_BRANCH'] == 'main') ? '</b>' : ''); ?>
<!-- 					</li> -->
<!-- 					<li> -->
<!-- 						<?php echo ((isset($constants['const_SOFTWARE_BRANCH']) and $constants['const_SOFTWARE_BRANCH'] == 'development') ? '<b>' : ''); ?> -->
<!-- 								<button onclick="window.location.href='/cmd.php?CMD=update_development';"><?php echo L::config_update_development_linktext ?></button> -->
<!-- 						<?php echo ((isset($constants['const_SOFTWARE_BRANCH']) and $constants['const_SOFTWARE_BRANCH'] == 'development') ? '</b>' : ''); ?> -->
<!-- 					</li> -->
<!-- 				</ul> -->

				<p>
					<a href="/setup.php?check_update=true"><?php echo L::config_update_check_label; ?></a>
				</p>

<!--				<h3><?php echo L::cmd_update_mods_header; ?></h3>
					<p><?php echo L::cmd_update_mods_description; ?></p>
					<h4><?php echo L::cmd_update_libraw_header; ?></h4>
						<label><?php echo L::cmd_update_libraw_warning; ?></label>
						<button onclick="window.location.href='/cmd.php?CMD=update_libraw';"><?php echo L::cmd_update_libraw_header ?></button>-->

			</details>
		</div>

		<?php
			if (file_exists('/usr/bin/startx')) {
				?>
				<div class="card" style="margin-top: 2em;">
					<details>
						<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_exit_section; ?></summary>
						<p><?php echo L::config_exit_desc; ?></p>
						<p><?php echo L::config_exit_idletime; ?></p>

						<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
							<input type="checkbox" name="exit_lbb_stop_backup">
							<label for="exit_lbb_stop_backup"><?php echo L::config_exit_stop_backup; ?></label><br />
							<button type="submit" name="exit_lbb" value="1"><?php echo L::config_exit_submit; ?></button>
						</form>

					</details>
				</div>
				<?php
			}
		?>

		<?php include "sub-footer.php"; ?>
		<?php echo virtual_keyboard_js($config["conf_VIRTUAL_KEYBOARD_ENABLED"],$config["conf_LANGUAGE"],$config["conf_THEME"]); ?>
</body>

</html>
