<!doctype html>

<!-- Author: Dmitri Popov, dmpop@linux.com; Stefan Saam, github@saams.de
         License: GPLv3 https://www.gnu.org/licenses/gpl-3.0.txt -->

<?php
	$WORKING_DIR=dirname(__FILE__);
	$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
	$config_standard = parse_ini_file("$WORKING_DIR/config-standards.cfg", false);
	$constants = parse_ini_file($WORKING_DIR . "/constants.sh", false);

	$theme = $config["conf_THEME"];
	$background = $config["conf_BACKGROUND_IMAGE"] == ""?"":"background='/img/backgrounds/" . $config["conf_BACKGROUND_IMAGE"] . "'";

	include($WORKING_DIR . "/sub-popup.php");

	include($WORKING_DIR . "/get-cloudservices.php");

	$WIFI_COUNTRY	= trim(shell_exec("raspi-config nonint get_wifi_country"));
?>

<html lang="<?php echo $config["conf_LANGUAGE"]; ?>" data-theme="<?php echo $theme; ?>">

<head>
	<?php include "${WORKING_DIR}/sub-standards-header-loader.php"; ?>
</head>

<body <?php echo $background; ?>>
	<?php include "${WORKING_DIR}/sub-standards-body-loader.php"; ?>
	<?php include "${WORKING_DIR}/sub-menu.php"; ?>

	<?php
		// write new config
		if (isset($_POST['save'])) {
			write_config();
		};

		// Upload settings
		if (isset($_POST['upload_settings'])) {
			upload_settings();
		};

		// read (new) config
		$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);

		# write wifi country-code from config.cfg
		if (($WIFI_COUNTRY !== $config["conf_WIFI_COUNTRY"]) and ($config["conf_WIFI_COUNTRY"] !== "")) {
			shell_exec("sudo raspi-config nonint do_wifi_country ".$config["conf_WIFI_COUNTRY"]);
			$WIFI_COUNTRY	= trim(shell_exec("raspi-config nonint get_wifi_country"));
		}

	?>

	<h1 class="text-center" style="margin-bottom: 1em; letter-spacing: 3px;"><?php echo L::config_config; ?></h1>
	<?php

function get_wifi_country_selector($ID,$NAME) {
	global $WIFI_COUNTRY;

	$wifi_contry_selector	= '<select id="'.$ID.'" name="'.$NAME.'">';

		exec("sed '/^#/d' /usr/share/zoneinfo/iso3166.tab",$COUNTRIES);
		foreach($COUNTRIES as $COUNTRY) {
			$COUNTRY_ARRAY	= explode("\t",$COUNTRY,2);
			$COUNTRYCODE	= trim($COUNTRY_ARRAY[0]);
			$COUNTRYNAME	= trim($COUNTRY_ARRAY[1]);
			$selected		= $WIFI_COUNTRY==$COUNTRYCODE ? " selected" : "";

			$wifi_contry_selector	.= '<option value="'.$COUNTRYCODE.'"'.$selected.'>'.$COUNTRYCODE.' '.$COUNTRYNAME.'</option>';
		}
	$wifi_contry_selector	.= '</select>';
	return ($wifi_contry_selector);
}

function check_new_password($title, $pwd_1, $pwd_2) {
	$pwd_valid = false;
		if ($pwd_1 !== $pwd_2) {
			popup($title . "\n" . L::config_alert_password_not_identical, true);
		} elseif (strlen($pwd_1) < 5) {
			popup($title . "\n" . L::config_alert_password_too_short, true);
		} elseif (strpos("_" . $pwd_1,"\\") or strpos("_" . $pwd_1,"\"")) {
			popup($title . "\n" . L::config_alert_password_characters_not_allowed, true);
		} else {
			$pwd_valid=true;
		}

	return($pwd_valid);
}

function write_config()
{
	# write config.cfg
	extract ($_POST);
	global $WIFI_COUNTRY;

	list($conf_BACKUP_DEFAULT_SOURCE,$conf_BACKUP_DEFAULT_TARGET)=explode(" ",$BACKUP_MODE,2);
	list($conf_BACKUP_DEFAULT_SOURCE2,$conf_BACKUP_DEFAULT_TARGET2)=explode(" ",$BACKUP_MODE_2,2);
	$conf_BACKUP_GENERATE_THUMBNAILS	= isset($conf_BACKUP_GENERATE_THUMBNAILS)?"true":"false";
	$conf_POWER_OFF						= isset($conf_POWER_OFF)?"true":"false";
	$conf_NOTIFY						= isset($conf_NOTIFY)?"true":"false";
	$conf_MAIL_HTML						= isset($conf_MAIL_HTML)?"true":"false";
	$conf_DISP							= isset($conf_DISP)?"true":"false";
	$conf_DISP_BLACK_ON_POWER_OFF		= isset($conf_DISP_BLACK_ON_POWER_OFF)?"true":"false";
	$conf_conf_DISP_IP_REPEAT			= isset($conf_conf_DISP_IP_REPEAT)?"true":"false";
	$conf_LOG_SYNC						= isset($conf_LOG_SYNC)?"true":"false";
	$conf_POPUP_MESSAGES				= isset($conf_POPUP_MESSAGES)?"true":"false";

	$conf_PASSWORD_LINE="conf_PASSWORD=\"$conf_PASSWORD_OLD\"";

	if ($conf_MAIL_conf_PASSWORD != "") {
		if (! check_new_password (L::config_alert_password_mail_header, $conf_MAIL_conf_PASSWORD, $conf_MAIL_conf_PASSWORD)) {
			$conf_MAIL_conf_PASSWORD	= "";
		}
	}

	if ($conf_RSYNC_conf_PASSWORD != "") {
		if (! check_new_password (L::config_alert_password_rsync_header, $conf_RSYNC_conf_PASSWORD, $conf_RSYNC_conf_PASSWORD)) {
			$conf_RSYNC_conf_PASSWORD	= "";
		}
	}

	if (isset($conf_PASSWORD_REMOVE)) {
		$conf_PASSWORD_LINE="conf_PASSWORD=\"\"";
		exec("sudo " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/password.sh remove");
		popup($title . "\n" . L::config_alert_password_change_after_reboot,true);
	} elseif ($conf_PASSWORD_1 != "") {
		if (check_new_password (L::config_alert_password_global, $conf_PASSWORD_1, $conf_PASSWORD_2)) {
			$conf_PASSWORD_LINE="conf_PASSWORD=\"$conf_PASSWORD_1\"";
			exec("sudo " . $_SERVER['CONTEXT_DOCUMENT_ROOT'] . "/password.sh set \"" . $conf_PASSWORD_1 . "\"");
			popup($title . "\n" . L::config_alert_password_change_after_reboot,true);
		}
	}

	$CONFIGFILE = "config.cfg";
	$config_file_handle = fopen($CONFIGFILE, "w");

	$config_file_content = <<<CONFIGDATA
conf_LANGUAGE="$conf_LANGUAGE"
conf_BACKUP_DEFAULT_SOURCE="$conf_BACKUP_DEFAULT_SOURCE"
conf_BACKUP_DEFAULT_TARGET="$conf_BACKUP_DEFAULT_TARGET"
conf_BACKUP_DEFAULT_SOURCE2="$conf_BACKUP_DEFAULT_SOURCE2"
conf_BACKUP_DEFAULT_TARGET2="$conf_BACKUP_DEFAULT_TARGET2"
conf_BACKUP_CAMERA_FOLDER_MASK="$conf_BACKUP_CAMERA_FOLDER_MASK"
conf_BACKUP_TARGET_BASEDIR_CLOUD="$conf_BACKUP_TARGET_BASEDIR_CLOUD"
conf_BACKUP_GENERATE_THUMBNAILS=$conf_BACKUP_GENERATE_THUMBNAILS
conf_POWER_OFF=$conf_POWER_OFF
conf_NOTIFY=$conf_NOTIFY
conf_MAIL_HTML=$conf_MAIL_HTML
conf_DISP=$conf_DISP
conf_DISP_BLACK_ON_POWER_OFF=$conf_DISP_BLACK_ON_POWER_OFF
conf_conf_DISP_IP_REPEAT=$conf_conf_DISP_IP_REPEAT
conf_THEME=$conf_THEME
conf_BACKGROUND_IMAGE=$conf_BACKGROUND_IMAGE
conf_POPUP_MESSAGES=$conf_POPUP_MESSAGES
conf_LOGLEVEL=$conf_LOGLEVEL
conf_LOG_SYNC=$conf_LOG_SYNC
conf_POWER_OFF_IDLE_TIME=$conf_POWER_OFF_IDLE_TIME
conf_SMTP_SERVER="$conf_SMTP_SERVER"
conf_SMTP_PORT="$conf_SMTP_PORT"
conf_MAIL_USER="$conf_MAIL_USER"
conf_MAIL_conf_PASSWORD="$conf_MAIL_conf_PASSWORD"
conf_MAIL_TO="$conf_MAIL_TO"
conf_RSYNC_SERVER="$conf_RSYNC_SERVER"
conf_RSYNC_PORT="$conf_RSYNC_PORT"
conf_RSYNC_USER="$conf_RSYNC_USER"
conf_RSYNC_conf_PASSWORD="$conf_RSYNC_conf_PASSWORD"
conf_RSYNC_SERVER_MODULE="$conf_RSYNC_SERVER_MODULE"
conf_WIFI_COUNTRY="$conf_WIFI_COUNTRY"
$conf_PASSWORD_LINE
CONFIGDATA;

	fwrite($config_file_handle, $config_file_content);
	fclose($config_file_handle);
	exec ("dos2unix './" . $CONFIGFILE . "'");
	echo '<div class="card" style="margin-top: 2em;">' . L::config_message_settings_saved . '</div>';
}

function upload_settings() {
	global $WORKING_DIR, $config, $constants;

	if($_FILES["settings_file"]["name"]) {
		$filename = $_FILES["settings_file"]["name"];
		$source = $_FILES["settings_file"]["tmp_name"];
		$type = $_FILES["settings_file"]["type"];
		$name = explode(".", $filename);
		$accepted_types = array('application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-compressed');
		foreach($accepted_types as $mime_type) {
			if($mime_type == $type) {
				$okay = true;
				break;
			}
		}
		$continue = strtolower($name[1]) == 'zip' ? true : false;
		if(!$continue) {
			popup(L::config_alert_settings_upload_not_zip,$config["conf_POPUP_MESSAGES"]);
		}
		/* PHP current path */
		$filenoext = basename ($filename, '.zip');
		$filenoext = basename ($filenoext, '.ZIP');

		$targetdir = $constants["const_WEB_ROOT_LBB"].'/tmp/unzip/';
		$targetzip = $targetdir . $filename;
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

				if (file_exists($targetdir."config.cfg")) {
					@unlink($constants["const_WEB_ROOT_LBB"]/config.cfg);
					if (rename($targetdir."config.cfg",$constants["const_WEB_ROOT_LBB"]."/config.cfg")) {$Files_Copied="\n* 'config.cfg'";}

				}
				if (file_exists($targetdir."rclone.conf")) {
					@unlink($constants["const_WEB_ROOT_LBB"]/config.cfg);
					if (rename($targetdir."rclone.conf",$constants["const_RCLONE_CONFIG_FILE"])) {$Files_Copied=$Files_Copied."\n* 'rclone.cfg'";}
				}
				popup(L::config_alert_settings_upload_success. " ". $Files_Copied,true);

				$config = parse_ini_file($WORKING_DIR . "/config.cfg", false);
				if (isset ($config["conf_PASSWORD"]) and check_new_password(L::config_alert_password_global,$config["conf_PASSWORD"],$config["conf_PASSWORD"])) {
					exec("sudo " . $WORKING_DIR . "/password.sh set \"" . $config["conf_PASSWORD"] . "\"");
				} else {
					exec("sudo " . $WORKING_DIR . "/password.sh remove");
				}
				popup(L::config_alert_password_change_after_reboot,true);
			}

		} else {
			popup(L::config_alert_settings_upload_problem,$config["conf_POPUP_MESSAGES"]);
		}

		exec("sudo rm -R ".$targetdir);
	}
}
?>


	<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">

		<div class="card" style="margin-top: 2em;">
			<?php echo '<button style="margin-top: 2em;" type="submit" name="save">' . L::config_save_button . '</button>'; ?>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_lang_section; ?></summary>

				<h3><?php echo L::config_lang_header; ?></h3>
					<label for="conf_LANGUAGE"><?php echo L::config_lang_label; ?></label><br>
						<select name="conf_LANGUAGE" id="conf_LANGUAGE">
						<?php
							echo "<option value='' " . ($config["conf_LANGUAGE"] == ""?" selected":"") . ">" . L::config_lang_browser_detect . "</option>";
							$languages=array();
							exec ("find '" . $WORKING_DIR . "/lang'/*.json -type f ",$languages);
							foreach($languages as $language) {
								$language = basename($language, ".json");
								echo "<option value='" . $language . "' " . ($config["conf_LANGUAGE"] == $language?" selected":"") . ">" . $language . "</option>";
							}
						?>
					</select>
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_backup_section; ?></summary>

				<h3><?php echo L::config_backup_header; ?></h3>
					<label for="BACKUP_MODE"><?php echo L::config_backup_label; ?></label><br>

					<select name="BACKUP_MODE" id="BACKUP_MODE">
						<option value="none none" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="none none"?" selected":""; ?>><?php echo L::config_backup_none; ?></option>
						<option value="usb usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="usb usb"?" selected":""; ?>><?php echo L::config_backup_usb_usb; ?></option>
						<option value="usb internal" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="usb internal"?" selected":""; ?>><?php echo L::config_backup_usb_internal; ?></option>
						<option value="camera usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="camera usb"?" selected":""; ?>><?php echo L::config_backup_camera_usb; ?></option>
						<option value="camera internal" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="camera internal"?" selected":""; ?>><?php echo L::config_backup_camera_internal; ?></option>
						<option value="ios usb" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="ios usb"?" selected":""; ?>><?php echo L::config_backup_ios_usb; ?></option>
						<option value="ios internal" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE"] . " " . $config["conf_BACKUP_DEFAULT_TARGET"]=="ios internal"?" selected":""; ?>><?php echo L::config_backup_ios_internal; ?></option>
					</select>

				<h3><?php echo L::config_backup_header2; ?></h3>
					<label for="BACKUP_MODE_2"><?php echo L::config_backup_label2; ?></label><br>

					<select name="BACKUP_MODE_2" id="BACKUP_MODE_2">
						<option value="none none" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE2"] . " " . $config["conf_BACKUP_DEFAULT_TARGET2"]=="none none"?" selected":""; ?>><?php echo L::config_backup_none; ?></option>
							<?php
								if (! ($config["conf_RSYNC_SERVER"]=="" or $config["conf_RSYNC_PORT"]=="" or $config["conf_RSYNC_USER"]=="" or $config["conf_RSYNC_conf_PASSWORD"]=="" or $config["conf_RSYNC_SERVER_MODULE"]=="")) {
							?>
									<option value="internal rsyncserver" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE2"] . " " . $config["conf_BACKUP_DEFAULT_TARGET2"]=="internal rsyncserver"?" selected":""; ?>><?php echo L::main_internal_button . L::right_arrow . L::main_rsync_button; ?></option>
									<option value="usb rsyncserver" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE2"] . " " . $config["conf_BACKUP_DEFAULT_TARGET2"]=="usb rsyncserver"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . L::main_rsync_button; ?></option>
							<?php
								}

								foreach($CloudServices as $CloudService) {
							?>
									<option value="internal cloud_<?php print $CloudService; ?>" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE2"] . " " . $config["conf_BACKUP_DEFAULT_TARGET2"]=="internal cloud_${CloudService}"?" selected":""; ?>><?php echo L::main_internal_button . L::right_arrow . $CloudService; ?></option>
									<option value="usb cloud_<?php print $CloudService; ?>" <?php echo $config["conf_BACKUP_DEFAULT_SOURCE2"] . " " . $config["conf_BACKUP_DEFAULT_TARGET2"]=="usb cloud_${CloudService}"?" selected":""; ?>><?php echo L::main_usb_button . L::right_arrow . $CloudService; ?></option>
							<?php
								}
							?>
					</select>

				<h3><?php echo L::config_backup_camera_folder_mask_header; ?></h3>
					<label for="conf_BACKUP_CAMERA_FOLDER_MASK"><?php echo L::config_backup_camera_folder_mask_label; ?></label><br>
					<input type="text" id="conf_BACKUP_CAMERA_FOLDER_MASK" name="conf_BACKUP_CAMERA_FOLDER_MASK" size="6" value="<?php echo $config['conf_BACKUP_CAMERA_FOLDER_MASK']; ?>">

				<h3><?php echo L::config_backup_target_basedir_cloud_header; ?></h3>
					<label for="conf_BACKUP_TARGET_BASEDIR_CLOUD"><?php echo L::config_backup_target_basedir_cloud_label; ?></label><br>
					<input type="text" id="conf_BACKUP_TARGET_BASEDIR_CLOUD" name="conf_BACKUP_TARGET_BASEDIR_CLOUD" size="6" value="<?php echo $config['conf_BACKUP_TARGET_BASEDIR_CLOUD']; ?>">

				<h3><?php echo L::config_backup_generate_thumbnails_header; ?></h3>
					<label for="conf_BACKUP_GENERATE_THUMBNAILS"><?php echo L::config_backup_generate_thumbnails_label; ?></label><br>
					<input type="checkbox" id="conf_BACKUP_GENERATE_THUMBNAILS" name="conf_BACKUP_GENERATE_THUMBNAILS" <?php echo $config['conf_BACKUP_GENERATE_THUMBNAILS']=="1"?"checked":""; ?>>

				<h3><?php echo L::config_backup_power_off_header; ?></h3>
					<label for="conf_POWER_OFF"><?php echo L::config_backup_power_off_label; ?></label><br>
					<input type="checkbox" id="conf_POWER_OFF" name="conf_POWER_OFF" <?php echo $config['conf_POWER_OFF']=="1"?"checked":""; ?>>
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_behavior_section; ?></summary>

				<h3><?php echo L::config_behavior_notify_header; ?></h3>
					<label for="conf_NOTIFY"><?php echo L::config_behavior_notify_label; ?></label><br>
					<input type="checkbox" id="conf_NOTIFY" name="conf_NOTIFY"<?php echo $config['conf_NOTIFY']=="1"?"checked":""; ?>>

				<h3><?php echo L::config_behavior_mail_html_header; ?></h3>
					<label for="conf_MAIL_HTML"><?php echo L::config_behavior_mail_html_label; ?></label><br>
					<input type="checkbox" id="conf_MAIL_HTML" name="conf_MAIL_HTML"<?php echo $config['conf_MAIL_HTML']=="1"?"checked":""; ?>>

				<h3><?php echo L::config_behavior_loglevel_header; ?></h3>
					<p><?php echo L::config_behavior_loglevel_text . " " . $constants["const_LOGFILE"]; ?>)</p>
					<label for="conf_LOGLEVEL"><?php echo L::config_behavior_loglevel_label; ?></label><br>
					<select name="conf_LOGLEVEL" id="conf_LOGLEVEL">
						<option value="1" <?php echo $config["conf_LOGLEVEL"]=="1"?" selected":""; ?>>1, <?php echo L::config_behavior_loglevel_minimum; ?></option>
						<option value="2" <?php echo $config["conf_LOGLEVEL"]=="2"?" selected":""; ?>>2, <?php echo L::config_behavior_loglevel_medium; ?></option>
						<option value="3" <?php echo $config["conf_LOGLEVEL"]=="3"?" selected":""; ?>>3, <?php echo L::config_behavior_loglevel_maximum; ?></option>
					</select>

				<h3><?php echo L::config_behavior_log_sync_protokoll_header; ?></h3>
					<label for="conf_LOG_SYNC"><?php echo L::config_behavior_log_sync_protokoll_label; ?></label><br>
					<input type="checkbox" id="conf_LOG_SYNC" name="conf_LOG_SYNC"<?php echo $config['conf_LOG_SYNC']=="1"?"checked":""; ?>>

				<h3><?php echo L::config_behavior_power_off_idle_time_header; ?></h3>
					<label for="conf_POWER_OFF_IDLE_TIME"><?php echo L::config_behavior_power_off_idle_time_label; ?></label><br>
					<select name="conf_POWER_OFF_IDLE_TIME" id="conf_POWER_OFF_IDLE_TIME">
						<?php
							$IDLE_TIME_OPTIONS=array(0,2,5,10,15,20,30);
							foreach($IDLE_TIME_OPTIONS as $IDLE_TIME_OPTION) {
								echo "<option value=\"" . $IDLE_TIME_OPTION ."\"". ($config["conf_POWER_OFF_IDLE_TIME"]==$IDLE_TIME_OPTION?" selected":"") . ">" . ($IDLE_TIME_OPTION=="0"?L::config_behavior_power_off_idle_time_none:$IDLE_TIME_OPTION . " " . L::minutes_long) ."</option>";
							}
						?>

					</select>

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_view_section; ?></summary>

				<h3><?php echo L::config_view_theme_header; ?></h3>
					<label for="conf_THEME"><?php echo L::config_view_theme_label; ?></label><br>
						<select name="conf_THEME" id="conf_THEME">
						<option value="light" <?php echo $config["conf_THEME"] == "light"?" selected":""; ?>><?php echo L::config_view_theme_light; ?></option>
						<option value="dark" <?php echo $config["conf_THEME"] == "dark"?" selected":""; ?>><?php echo L::config_view_theme_dark; ?></option>
						<option value="sepia" <?php echo $config["conf_THEME"] == "sepia"?" selected":""; ?>><?php echo L::config_view_theme_sepia; ?></option>
					</select>

				<h3><?php echo L::config_view_bg_image_header; ?></h3>
					<label for="conf_BACKGROUND_IMAGE"><?php echo L::config_view_bg_image_label; ?> &quot;<?php echo $WORKING_DIR . "/img/backgrounds" ;?>&quot;.</label><br>
						<select name="conf_BACKGROUND_IMAGE" id="conf_BACKGROUND_IMAGE">
						<option value="" <?php echo $config["conf_BACKGROUND_IMAGE"] ==""?" selected":""; ?>>none</option>
						<?php
							$bg_images=array();
							exec ("find '" . $WORKING_DIR . "/img/backgrounds' -type f -exec file --mime-type {} \+ | awk -F: '{if ($2 ~/image\//) print $1}'",$bg_images);
							foreach($bg_images as $bg_image) {
								$bg_image = basename($bg_image);
								echo "<option value='" . $bg_image . "' " . ($config["conf_BACKGROUND_IMAGE"] == $bg_image?" selected":"") . ">" . $bg_image . "</option>";
							}
						?>
					</select>

				<h3><?php echo L::config_view_popup_header; ?></h3>
					<label for="conf_POPUP_MESSAGES"><?php echo L::config_view_popup_label; ?></label><br>
					<input type="checkbox" id="conf_POPUP_MESSAGES" name="conf_POPUP_MESSAGES" <?php echo $config['conf_POPUP_MESSAGES']=="1"?"checked":""; ?>>

				<h3><?php echo L::config_behavior_display_header; ?></h3>
					<label for="conf_DISP"><?php echo L::config_behavior_display_label; ?></label><br>
					<input type="checkbox" id="conf_DISP" name="conf_DISP" <?php echo $config['conf_DISP']=="1"?"checked":""; ?>>

				<h3><?php echo L::config_behavior_disp_ip_header; ?></h3>
					<label for="conf_conf_DISP_IP_REPEAT"><?php echo L::config_behavior_disp_ip_label; ?></label><br>
					<input type="checkbox" id="conf_conf_DISP_IP_REPEAT" name="conf_conf_DISP_IP_REPEAT" <?php echo $config['conf_conf_DISP_IP_REPEAT']=="1"?"checked":""; ?>>

				<h3><?php echo L::config_disp_black_on_power_off_header; ?></h3>
					<label for="conf_DISP_BLACK_ON_POWER_OFF"><?php echo L::config_disp_black_on_power_off_label; ?></label><br>
					<input type="checkbox" id="conf_DISP_BLACK_ON_POWER_OFF" name="conf_DISP_BLACK_ON_POWER_OFF" <?php echo $config['conf_DISP_BLACK_ON_POWER_OFF']=="1"?"checked":""; ?>>

			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_mail_section; ?></summary>

				<h3><?php echo L::config_mail_smtp_header; ?></h3>
					<label for="conf_SMTP_SERVER"><?php echo L::config_mail_smtp_label; ?></label><br>
					<input type="text" id="conf_SMTP_SERVER" name="conf_SMTP_SERVER" size="6" value="<?php echo $config['conf_SMTP_SERVER']; ?>">

				<h3><?php echo L::config_mail_port_header; ?></h3>
					<label for="conf_SMTP_PORT"><?php echo L::config_mail_port_label . " " . $config_standard["conf_SMTP_PORT"]; ?>)</label><br>
					<input type="text" id="conf_SMTP_PORT" name="conf_SMTP_PORT" size="20" value="<?php echo $config['conf_SMTP_PORT']; ?>">

				<h3><?php echo L::config_mail_user_header; ?></h3>
					<label for="conf_MAIL_USER"><?php echo L::config_mail_user_label; ?></label><br>
					<input type="text" id="conf_MAIL_USER" name="conf_MAIL_USER" size="20" value="<?php echo $config['conf_MAIL_USER']; ?>">

				<h3><?php echo L::config_mail_password_header; ?></h3>
					<label for="conf_MAIL_conf_PASSWORD"><?php echo L::config_mail_password_label; ?></label><br>
					<input type="password" id="conf_MAIL_conf_PASSWORD" name="conf_MAIL_conf_PASSWORD" size="20" value="<?php echo $config['conf_MAIL_conf_PASSWORD']; ?>">

				<h3><?php echo L::config_mail_recipient_header; ?></h3>
					<label for="conf_MAIL_TO"><?php echo L::config_mail_recipient_label; ?></label><br>
					<input type="text" id="conf_MAIL_TO" name="conf_MAIL_TO" size="20" value="<?php echo $config['conf_MAIL_TO']; ?>">
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_rsync_section; ?></summary>

				<h3><?php echo L::config_rsync_server_header; ?></h3>
					<label for="conf_RSYNC_SERVER"><?php echo L::config_rsync_server_label; ?></label><br>
					<input type="text" id="conf_RSYNC_SERVER" name="conf_RSYNC_SERVER" size="6" value="<?php echo $config['conf_RSYNC_SERVER']; ?>">

				<h3><?php echo L::config_rsync_port_header; ?></h3>
					<label for="conf_RSYNC_PORT"><?php echo L::config_rsync_port_label . " " . $config_standard["conf_RSYNC_PORT"]; ?>)</label><br>
					<input type="text" id="conf_RSYNC_PORT" name="conf_RSYNC_PORT" size="20" value="<?php echo $config['conf_RSYNC_PORT']; ?>">

				<h3><?php echo L::config_rsync_user_header; ?></h3>
					<label for="conf_RSYNC_USER"><?php echo L::config_rsync_user_label; ?></label><br>
					<input type="text" id="conf_RSYNC_USER" name="conf_RSYNC_USER" size="20" value="<?php echo $config['conf_RSYNC_USER']; ?>">

				<h3><?php echo L::config_rsync_password_header; ?></h3>
					<label for="conf_RSYNC_conf_PASSWORD"><?php echo L::config_rsync_password_label; ?></label><br>
					<input type="password" id="conf_RSYNC_conf_PASSWORD" name="conf_RSYNC_conf_PASSWORD" size="20" value="<?php echo $config['conf_RSYNC_conf_PASSWORD']; ?>">

				<h3><?php echo L::config_rsync_module_header; ?></h3>
					<label for="conf_RSYNC_SERVER_MODULE"><?php echo L::config_rsync_module_label1 .  $config_standard['conf_RSYNC_SERVER_MODULE'] . L::config_rsync_module_label2; ?></label><br>
					<input type="text" id="conf_RSYNC_SERVER_MODULE" name="conf_RSYNC_SERVER_MODULE" size="20" value="<?php echo $config['conf_RSYNC_SERVER_MODULE']; ?>">
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_wifi_section; ?></summary>

				<h3><?php echo L::config_wifi_country_header; ?></h3>
					<label for="conf_WIFI_COUNTRY"><?php echo L::config_wifi_country_label; ?></label><br>
					<?php echo get_wifi_country_selector("conf_WIFI_COUNTRY","conf_WIFI_COUNTRY"); ?>
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_password_section; ?></summary>

				<h3><?php echo L::config_password_header; ?></h3>
					<input type="hidden" id="conf_PASSWORD_OLD" name="conf_PASSWORD_OLD" value="<?php echo $config['conf_PASSWORD']; ?>">
					<label for="conf_PASSWORD_1"><?php echo L::config_password_label1; ?></label><br>
					<input type="password" id="conf_PASSWORD_1" name="conf_PASSWORD_1" size="20" value="">
					<label for="conf_PASSWORD_2"><?php echo L::config_password_label2; ?></label><br>
					<input type="password" id="conf_PASSWORD_2" name="conf_PASSWORD_2" size="20" value="">

					<?php
						if ($config['conf_PASSWORD'] != "") {
							echo "<h3>" . L::config_password_remove_header . "</h3>";
							echo "<label for=\"conf_PASSWORD_REMOVE\">" . L::config_password_remove_label ."</label><br>";
							echo "<input type=\"checkbox\" id=\"conf_PASSWORD_REMOVE\" name=\"conf_PASSWORD_REMOVE\">";
						}
					?>
			</details>
		</div>

		<div class="card" style="margin-top: 2em;">
			<?php echo '<button style="margin-top: 2em;" type="submit" name="save">' . L::config_save_button . '</button>'; ?>
		</div>
	</form>


		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_cloud_section; ?></summary>

				<h3><?php echo L::config_cloud_header; ?></h3>
					<p>
						Depending on your cloud-service, your configuration can expire. Maybe you have to repeat this step always before cloud-access.<br>
						<?php
							if (empty($config['conf_PASSWORD'])) {
								echo L::config_username . ": 'lbb', " . L::config_password . ": 'lbb'";
							}
							else {
								echo L::config_username . ": 'lbb', " . L::config_password_as_set_in . " '" . L::config_password_section . "'";
							}
						?>

					</p>
					<?php
						if (strpos($_SERVER['SERVER_PORT'], "443") !== false) {
							$rclone_link="https://" . $_SERVER['SERVER_ADDR'] . ":8443";
						} else {
							$rclone_link="http://" . $_SERVER['SERVER_ADDR'] . ":81";
						}
					?>
					<a href="<?php echo $rclone_link; ?>" target="_blank"><?php echo L::config_cloud_rclone_gui; ?></a>

			</details>
		</div>

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

		<div class="card" style="margin-top: 2em;">
			<details>
				<summary style="letter-spacing: 1px; text-transform: uppercase;"><?php echo L::config_update_section; ?></summary>
				<?php echo L::config_update_text; ?>
				<a href='/cmd.php?CMD=update'><?php echo L::config_update_linktext ?></a>
			</details>
		</div>


</body>

</html>
